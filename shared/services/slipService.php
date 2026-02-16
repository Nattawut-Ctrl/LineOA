<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once UTILS_PATH . '/stock_helper.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once BACKEND_PATH . '/services/adminEmailService.php';

function createPayment(mysqli $conn, array $data)
{
    $transfer_date = $data['transfer_date'] ?? null;
    $transfer_time = $data['transfer_time'] ?? null;
    $address_id = $data['address_id'] ?? null;
    $address_json = $data['address_json'] ?? null;

    $sql = "INSERT INTO payments (
                user_id,
                address_id,
                product_id,
                variant_id,
                items_json,
                address_json,
                amount,
                slip_path,
                mode,
                transfer_date,
                transfer_time,
                status,
                created_at
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?, 'pending', NOW())";

    $params = [
        $data['user_id'],
        $address_id,
        $data['product_id'],
        $data['variant_id'],
        $data['items_json'],
        $address_json,
        $data['amount'],
        $data['slip_path'],
        $data['mode'],
        $transfer_date,
        $transfer_time,
    ];

    $types = "iiiissdssss";

    $result = db_exec($conn, $sql, $params, $types);

    if (!$result['ok']) {
        error_log("DB error in createPayment: " . ($result['error'] ?? 'unknown error'));
        return 0;
    }

    $paymentId = (int)($result['insert_id'] ?? 0);

    return $paymentId;
}

function getAllPayments(mysqli $conn): array
{
    $sql = "SELECT 
                p.*, 
                u.first_name,
                u.last_name,
                u.display_name
            FROM payments p
            LEFT JOIN users u ON u.id = p.user_id
            WHERE p.slip_path IS NOT NULL AND p.slip_path != ''
            ORDER BY p.created_at DESC";

    $res = db_query($conn, $sql);
    if (!$res) return [];

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function getPaymentById(mysqli $conn, int $payment_id): ?array
{
    $sql = "
        SELECT 
        p.*, 
        u.first_name, 
        u.last_name, 
        u.display_name,
        CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS user_name
        FROM payments p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
        LIMIT 1
    ";

    $res = db_query($conn, $sql, [$payment_id], "i");
    if (!$res || $res->num_rows == 0) return null;

    return $res->fetch_assoc();
}

function extractItemsFromPayment(array $payment): array
{
    $items = [];

    if (!empty($payment['items_json'])) {
        $decoded = json_decode($payment['items_json'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $it) {
                $pid = (int)($it['product_id'] ?? 0);
                if ($pid <= 0) {
                    continue;
                }

                $variantId = isset($it['variant_id']) ? (int)$it['variant_id'] : 0;
                $variantId = $variantId > 0 ? $variantId : null;

                $qty = (int)($it['quantity'] ?? 1);
                if ($qty <= 0) {
                    $qty = 1;
                }

                $unitPrice = 0.0;
                if (isset($it['unit_price'])) {
                    $unitPrice = (float)$it['unit_price'];
                } elseif (isset($it['price'])) {
                    $p = (float)$it['price'];
                    $mode = (string)($payment['mode'] ?? '');
                    if ($mode === 'single' && $qty > 0) {
                        $unitPrice = $p / $qty;
                    } else {
                        $unitPrice = $p;
                    }
                }

                $items[] = [
                    'product_id' => $pid,
                    'variant_id' => $variantId,
                    'quantity'   => $qty,
                    'unit_price' => $unitPrice,
                ];
            }
        }
    } else {
        $pid = (int)($payment['product_id'] ?? 0);
        if ($pid > 0) {
            $variantId = isset($payment['variant_id']) ? (int)$payment['variant_id'] : 0;
            $variantId = $variantId > 0 ? $variantId : null;

            $items[] = [
                'product_id' => $pid,
                'variant_id' => $variantId,
                'quantity'   => 1,
                'unit_price' => 0.0,
            ];
        }
    }

    return $items;
}

function fiscalYearFromDate(string $dateYmd): int
{
    $ts = strtotime($dateYmd);
    if ($ts === false) {
        $y = (int)date('Y');
        $m = (int)date('n');
        return ($m >= 10) ? ($y + 1) : $y;
    }
    $y = (int)date('Y', $ts);
    $m = (int)date('n', $ts);
    return ($m >= 10) ? ($y + 1) : $y;
}

function getUnitPriceFromDb(mysqli $conn, int $productId, ?int $variantId): float
{
    if ($variantId !== null) {
        $res = db_query(
            $conn,
            "SELECT price FROM product_variants WHERE id = ? AND product_id = ? LIMIT 1",
            [$variantId, $productId],
            "ii"
        );
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            return (float)($row['price'] ?? 0);
        }
        return 0.0;
    }

    $res = db_query(
        $conn,
        "SELECT price FROM products WHERE id = ? LIMIT 1",
        [$productId],
        "i"
    );
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        return (float)($row['price'] ?? 0);
    }
    return 0.0;
}

function ensurePaymentItems(mysqli $conn, array $payment): array
{
    $paymentId = (int)($payment['id'] ?? 0);
    if ($paymentId <= 0) {
        error_log("Invalid payment_id in ensurePaymentItems");
        return [];
    }

    $resExisting = db_query(
        $conn,
        "SELECT id, payment_id, product_id, variant_id, quantity, unit_price FROM payment_items WHERE payment_id = ? ORDER BY id ASC",
        [$paymentId],
        "i"
    );
    if ($resExisting && $resExisting->num_rows > 0) {
        error_log("Payment items already exist for payment_id=$paymentId");
        $rows = [];
        while ($r = $resExisting->fetch_assoc()) {
            $rows[] = [
                'id' => (int)$r['id'],
                'payment_id' => (int)$r['payment_id'],
                'product_id' => (int)$r['product_id'],
                'variant_id' => $r['variant_id'] !== null ? (int)$r['variant_id'] : null,
                'quantity' => (int)$r['quantity'],
                'unit_price' => (float)$r['unit_price'],
            ];
        }
        return $rows;
    }

    $items = extractItemsFromPayment($payment);
    if (empty($items)) {
        error_log("No items extracted from payment_id=$paymentId");
        return [];
    }

    error_log("Creating " . count($items) . " payment items for payment_id=$paymentId");

    foreach ($items as $it) {
        $pid = (int)$it['product_id'];
        $vid = $it['variant_id'] ?? null;
        $qty = (int)$it['quantity'];
        if ($qty <= 0) $qty = 1;

        $unitPrice = (float)($it['unit_price'] ?? 0);
        if ($unitPrice <= 0) {
            $unitPrice = getUnitPriceFromDb($conn, $pid, $vid);
        }

        if ($vid !== null) {
            db_exec(
                $conn,
                "INSERT INTO payment_items (payment_id, product_id, variant_id, quantity, unit_price) VALUES (?,?,?,?,?)",
                [$paymentId, $pid, $vid, $qty, $unitPrice],
                "iiiid"
            );
        } else {
            db_exec(
                $conn,
                "INSERT INTO payment_items (payment_id, product_id, variant_id, quantity, unit_price) VALUES (?,?,NULL,?,?)",
                [$paymentId, $pid, $qty, $unitPrice],
                "iiid"
            );
        }
    }

    $res = db_query(
        $conn,
        "SELECT id, payment_id, product_id, variant_id, quantity, unit_price FROM payment_items WHERE payment_id = ? ORDER BY id ASC",
        [$paymentId],
        "i"
    );
    $rows = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'id' => (int)$r['id'],
                'payment_id' => (int)$r['payment_id'],
                'product_id' => (int)$r['product_id'],
                'variant_id' => $r['variant_id'] !== null ? (int)$r['variant_id'] : null,
                'quantity' => (int)$r['quantity'],
                'unit_price' => (float)$r['unit_price'],
            ];
        }
    }
    error_log("Returning " . count($rows) . " payment items for payment_id=$paymentId");
    return $rows;
}

function allocateLotsFIFO(mysqli $conn, array $paymentItem): bool
{
    $paymentItemId = (int)($paymentItem['id'] ?? 0);
    $pid = (int)($paymentItem['product_id'] ?? 0);
    $vid = $paymentItem['variant_id'] ?? null;
    $need = (int)($paymentItem['quantity'] ?? 0);
    $unitSell = (float)($paymentItem['unit_price'] ?? 0);

    if ($paymentItemId <= 0 || $pid <= 0 || $need <= 0) return false;

    $resLots = db_query(
        $conn,
        "SELECT id, cost_price, qty_available
        FROM stock_lots
        WHERE product_id = ?
            AND variant_id " . ($vid !== null ? "= ?" : "IS NULL") . "
            AND qty_available > 0
            AND status <> 'blocked'
        ORDER BY created_at ASC, id ASC
        FOR UPDATE",
        ($vid !== null ? [$pid, $vid] : [$pid]),
        ($vid !== null ? "ii" : "i")
    );

    if (!$resLots || $resLots->num_rows === 0) {
        error_log("No stock lots found for product_id=$pid, variant_id=$vid");
        return true;
    }

    $remaining = $need;
    while ($remaining > 0 && ($lot = $resLots->fetch_assoc())) {
        $lotId = (int)$lot['id'];
        $avail = (int)$lot['qty_available'];
        $unitCost = (float)($lot['cost_price'] ?? 0);

        if ($avail <= 0) continue;

        $take = ($avail >= $remaining) ? $remaining : $avail;

        $okIns = db_exec(
            $conn,
            "INSERT INTO lot_allocations (payment_item_id, lot_id, qty, unit_sell_price, unit_cost_price) VALUES (?,?,?,?,?)",
            [$paymentItemId, $lotId, $take, $unitSell, $unitCost],
            "iiidd"
        );
        if (!($okIns['ok'] ?? false)) {
            error_log("Failed to insert lot allocation for payment_item_id=$paymentItemId");
            return false;
        }

        $okUp = db_exec(
            $conn,
            "UPDATE stock_lots
            SET qty_available = qty_available - ?
            WHERE id = ? AND qty_available >= ?",
            [$take, $lotId, $take],
            "iii"
        );

        if (!($okUp['ok'] ?? false) || (int)$okUp['affected'] !== 1) {
            error_log("Failed to update stock_lots for lot_id=$lotId");
            return false;
        }

        db_exec(
            $conn,
            "UPDATE stock_lots SET status = CASE WHEN qty_available <= 0 THEN 'exhausted' ELSE status END WHERE id = ?",
            [$lotId],
            "i"
        );

        $remaining -= $take;
    }

    return true; 
}

/**
 * อัปเดตสถานะ: approved / rejected
 */
function updatePaymentStatus(mysqli $conn, int $payment_id, string $status): bool
{
    $sql = "UPDATE payments SET status = ? WHERE id = ?";

    $result = db_exec($conn, $sql, [$status, $payment_id], "si");
    return ($result['ok'] ?? false) && ($result['affected'] ?? 0) > 0;
}

/**
 * หมายเหตุ: ฟังก์ชันนี้จะ "ไม่" เปิด transaction เอง
 *      ให้ตัวที่เรียกเป็นคน begin / commit / rollback
 */
function reserveStockForPayment(mysqli $conn, int $paymentId): bool
{
    $payment = getPaymentById($conn, $paymentId);
    if (!$payment) return false;

    if (($payment['status'] ?? 'pending') !== 'pending') return true;

    $items = extractItemsFromPayment($payment);
    if (empty($items)) return false;

    foreach ($items as $item) {
        $pid = (int)$item['product_id'];
        $vid = $item['variant_id'] ?? null;
        $qty = (int)$item['quantity'];

        if ($qty <= 0) return false;

        if ($vid !== null) {
            $r = db_exec(
                $conn,
                "UPDATE product_variants
                 SET reserved_stock = reserved_stock + ?
                 WHERE id = ? AND product_id = ?
                   AND (stock - reserved_stock) >= ?",
                [$qty, $vid, $pid, $qty],
                "iiii"
            );
        } else {
            $r = db_exec(
                $conn,
                "UPDATE products
                 SET reserved_stock = reserved_stock + ?
                 WHERE id = ?
                   AND (stock - reserved_stock) >= ?",
                [$qty, $pid, $qty],
                "iii"
            );
        }

        if (!($r['ok'] ?? false) || (int)$r['affected'] !== 1) {
            return false;
        }
    }

    return true;
}

/**
 * อนุมัติสลิป:
 *  - หัก stock จริง (stock - qty)
 *  - ลด reserved_stock ตาม qty
 *  - อัปเดตสถานะเป็น approved
 */
function approvePaymentAndApplyStock(mysqli $conn, int $paymentId): bool
{
    $payment = getPaymentById($conn, $paymentId);
    if (!$payment) {
        return false;
    }

    if (($payment['status'] ?? 'pending') !== 'pending') {
        // กันการกดซ้ำ / เปลี่ยนจาก approved -> rejected ย้อนหลัง
        return false;
    }

    $items = extractItemsFromPayment($payment);
    if (empty($items)) return false;

    // 1) สร้าง payment_items (ถ้ายังไม่มี)
    $paymentItems = ensurePaymentItems($conn, $payment);
    if (empty($paymentItems)) {
        error_log("No payment items for payment_id: $paymentId");
        return false;
    }

    // 2) FIFO allocate lot และบันทึก lot_allocations
    foreach ($paymentItems as $pi) {
        if (!allocateLotsFIFO($conn, $pi)) {
            error_log("allocateLotsFIFO failed for payment_item_id: " . $pi['id']);
            return false;
        }
    }

    foreach ($items as $item) {
        $qty = $item['quantity'];

        if ($item['variant_id'] !== null) {
            $sql = "
                UPDATE product_variants
                SET stock = stock - ?, 
                    reserved_stock = reserved_stock - ?
                WHERE id = ? AND product_id = ? 
                    AND stock >= ?
                    AND reserved_stock >= ?
            ";

            $r = db_exec(
                $conn,
                $sql,
                [$qty, $qty, $item['variant_id'], $item['product_id'], $qty, $qty],
                "iiiiii"
            );

            if (!($r['ok'] ?? false) || (int)$r['affected'] !== 1) {
                return false;
            }
        } else {
            // writeLogWithSnapshot($conn, 'stock', 'approve_payment_variant_missing', [
            //     'payment_id' => $paymentId ?? null,
            //     'product_id' => $item['product_id'] ?? null,
            //     'qty'        => $qty ?? null,
            //     'item'       => $item,
            // ]);

            return false;

            // $sql = "
            //     UPDATE products
            //     SET stock = stock - ?, 
            //         reserved_stock = GREATEST(reserved_stock - ?, 0)
            //     WHERE id = ?
            // ";
            // db_exec(
            //     $conn,
            //     $sql,
            //     [$qty, $qty, $item['product_id']],
            //     "iii"
            // );
        }
    }

    // เปลี่ยนสถานะ payment
    return updatePaymentStatus($conn, $paymentId, 'approved');
}

/**
 * ปฏิเสธสลิป:
 *  - คืนของที่กันไว้ (ลด reserved_stock อย่างเดียว)
 *  - ไม่แตะ stock จริง
 *  - อัปเดตสถานะเป็น rejected
 */
function rejectPaymentAndReleaseStock(mysqli $conn, int $paymentId): bool
{
    $payment = getPaymentById($conn, $paymentId);
    if (!$payment) return false;

    if (($payment['status'] ?? 'pending') !== 'pending') return false;

    $items = extractItemsFromPayment($payment);
    if (empty($items)) return false;

    foreach ($items as $item) {
        $qty = (int)$item['quantity'];

        if ($item['variant_id'] !== null) {
            $ok = db_exec(
                $conn,
                "UPDATE product_variants
                 SET reserved_stock = GREATEST(reserved_stock - ?, 0)
                 WHERE id = ? AND product_id = ?",
                [$qty, $item['variant_id'], $item['product_id']],
                "iii"
            );
            if (!($ok['ok'] ?? false)) return false;
        } else {
            $ok = db_exec(
                $conn,
                "UPDATE products
                 SET reserved_stock = GREATEST(reserved_stock - ?, 0)
                 WHERE id = ?",
                [$qty, $item['product_id']],
                "ii"
            );
            if (!($ok['ok'] ?? false)) return false;
        }
    }

    return updatePaymentStatus($conn, $paymentId, 'rejected');
}

/**
 * นับจำนวนสลิปที่ยังไม่ถูกอนุมัติ/ปฏิเสธ (status = 'pending')
 */
function getPendingSlipCount(mysqli $conn): int
{
    $sql = "SELECT COUNT(*) AS cnt 
            FROM payments 
            WHERE status = 'pending'";   // ถ้าชื่อ table หรือ status ต่างไป ปรับตรงนี้

    $res = db_query($conn, $sql);
    if (!$res) {
        return 0;
    }

    $row = $res->fetch_assoc();
    return (int)($row['cnt'] ?? 0);
}

function getPendingSlipNotifications(mysqli $conn, int $limit = 10): array
{
    $sql = "
        SELECT 
        p.id AS payment_id,
        p.amount,
        p.created_at,
        p.status,
        u.first_name,
        u.last_name
    FROM payments p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.status = 'pending'
    ORDER BY p.created_at DESC
    LIMIT ?
    ";

    $rows = [];
    $res = db_query($conn, $sql, [$limit], "i");
    if ($res && $res->num_rows > 0) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    return $rows;
}
/**
 * ตรวจสอบและคืนสต็อกสำหรับ payment ที่หมดเวลา (30 นาที)
 */
function expireAndReleaseStockForPayment(mysqli $conn, int $paymentId): bool
{
    $payment = getPaymentById($conn, $paymentId);
    if (!$payment) return false;

    if (($payment['status'] ?? 'pending') !== 'pending') {
        // ถ้าไม่ใช่ pending แล้ว ไม่ต้องทำอะไร
        return true;
    }

    $createdAt = strtotime($payment['created_at'] ?? '');
    if ($createdAt === false) return false;

    $expiresAt = $createdAt + (30 * 60); // 30 นาที
    $now = time();

    // ถ้ายังไม่หมดเวลา ไม่ต้องทำอะไร
    if ($now < $expiresAt) {
        return true;
    }

    // หมดเวลาแล้ว ให้คืนสต็อก
    $items = extractItemsFromPayment($payment);
    if (empty($items)) return false;

    foreach ($items as $item) {
        $qty = (int)$item['quantity'];

        if ($item['variant_id'] !== null) {
            db_exec(
                $conn,
                "UPDATE product_variants
                 SET reserved_stock = GREATEST(reserved_stock - ?, 0)
                 WHERE id = ? AND product_id = ?",
                [$qty, $item['variant_id'], $item['product_id']],
                "iii"
            );
        } else {
            db_exec(
                $conn,
                "UPDATE products
                 SET reserved_stock = GREATEST(reserved_stock - ?, 0)
                 WHERE id = ?",
                [$qty, $item['product_id']],
                "ii"
            );
        }
    }

    // เปลี่ยนสถานะเป็น expired
    return updatePaymentStatus($conn, $paymentId, 'expired');
}

/**
 * ตรวจสอบว่า payment หมดเวลาหรือไม่ (30 นาที)
 */
function isPaymentExpired(array $payment): bool
{
    if (($payment['status'] ?? 'pending') !== 'pending') {
        return false;
    }

    $createdAt = strtotime($payment['created_at'] ?? '');
    if ($createdAt === false) return false;

    $expiresAt = $createdAt + (30 * 60); // 30 นาที
    $now = time();

    return $now >= $expiresAt;
}

/**
 * คำนวณเวลาที่เหลือในการชำระเงิน (นาที)
 */
function getPaymentTimeRemaining(array $payment): int
{
    $createdAt = strtotime($payment['created_at'] ?? '');
    if ($createdAt === false) return 0;

    $expiresAt = $createdAt + (30 * 60); // 30 นาที
    $now = time();
    $remaining = $expiresAt - $now;

    return max(0, (int)ceil($remaining / 60)); // คืนค่าเป็นนาที
}

/**
 * ตรวจสอบว่า payment ยังสามารถจ่ายเงินได้หรือไม่
 * - สถานะต้องเป็น pending
 * - ยังไม่หมดเวลา (30 นาที)
 */
function canPaymentStillBePaid(array $payment): bool
{
    if (($payment['status'] ?? 'pending') !== 'pending') {
        return false;
    }

    return !isPaymentExpired($payment);
}
