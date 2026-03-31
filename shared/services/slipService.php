<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once UTILS_PATH . '/stock_helper.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once BACKEND_PATH . '/services/adminEmailService.php';

/**
 * สร้าง payment record (ต้องสร้าง orders + order_items ก่อน)
 * 
 * @param mysqli $conn
 * @param array $data จะต้องมี: order_id, amount, slip_path, transfer_date, transfer_time
 * @return int payment_id (0 if failed)
 */
function createPayment(mysqli $conn, array $data)
{
    $order_id = (int)($data['order_id'] ?? 0);
    $amount = (float)($data['amount'] ?? 0);
    $slip_path = (string)($data['slip_path'] ?? '');
    $transfer_date = $data['transfer_date'] ?? null;
    $transfer_time = $data['transfer_time'] ?? null;

    if ($order_id <= 0 || $amount <= 0 || empty($slip_path)) {
        error_log("createPayment: invalid parameters");
        return 0;
    }

    $sql = "INSERT INTO payments (
                order_id,
                amount,
                slip_path,
                transfer_date,
                transfer_time,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";

    $params = [
        $order_id,
        $amount,
        $slip_path,
        $transfer_date,
        $transfer_time,
    ];

    $types = "idssss";

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
                pay.*,
                pay.id AS payment_id,
                pay.status AS payment_status,

                o.id AS order_id,
                o.grand_total,
                o.shipping_fee,
                o.subtotal,
                o.order_status,
                o.approved_payment_id,
                o.paid_at,
                (o.approved_payment_id IS NOT NULL) AS order_is_paid,

                u.first_name,
                u.last_name,
                u.display_name
            FROM payments pay
            INNER JOIN orders o ON o.id = pay.order_id
            LEFT JOIN users u ON u.id = o.user_id
            WHERE pay.slip_path IS NOT NULL AND pay.slip_path != ''
            ORDER BY pay.created_at DESC";

    $res = db_query($conn, $sql);
    if (!$res) return [];

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function getPaymentById(mysqli $conn, int $paymentId): ?array
{
    $sql = "
        SELECT
            pay.*,
            pay.id AS payment_id,
            pay.status AS payment_status,

            o.id AS order_id,
            o.user_id,
            o.address_id,
            o.subtotal,
            o.shipping_fee,
            o.grand_total,
            o.order_status,
            o.approved_payment_id,
            o.paid_at,
            (o.approved_payment_id IS NOT NULL) AS order_is_paid,
            o.address_json,
            o.tracking_no,
            o.carrier,

            u.first_name,
            u.last_name,
            u.display_name
        FROM payments pay
        INNER JOIN orders o ON o.id = pay.order_id
        LEFT JOIN users u ON u.id = o.user_id
        WHERE pay.id = ?
        LIMIT 1
    ";

    $res = db_query($conn, $sql, [$paymentId], 'i');
    if (!$res || $res->num_rows === 0) return null;
    return $res->fetch_assoc();
}

/**
 * ดึง items จาก payment โดยใช้ order_id
 */
function extractItemsFromPayment(mysqli $conn, array $payment): array
{
    $orderId = (int)($payment['order_id'] ?? 0);
    if ($orderId <= 0) {
        error_log("extractItemsFromPayment: order_id not found in payment");
        return [];
    }

    // 1) อ่านจาก order_items (source of truth หลังแยกตาราง)
    $items = [];
    $itemRes = db_query(
        $conn,
        "SELECT product_id, variant_id, quantity, unit_price FROM order_items WHERE order_id = ? ORDER BY id ASC",
        [$orderId],
        "i"
    );

    if ($itemRes) {
        while ($row = $itemRes->fetch_assoc()) {
            $items[] = [
                'product_id' => (int)$row['product_id'],
                'variant_id' => $row['variant_id'] !== null ? (int)$row['variant_id'] : null,
                'quantity'   => (int)$row['quantity'],
                'unit_price' => (float)$row['unit_price'],
            ];
        }
    }

    // 2) ถ้ายังว่าง: พยายาม backfill order_items จาก payment_intents.items_json (กรณีข้อมูลเก่าหรือ convert flow)
    if (empty($items)) {
        $items = ensurePaymentItems($conn, $payment); // จะ insert ลง order_items ให้ (ถ้าหา intent เจอ)
        if (!empty($items)) {
            // ensurePaymentItems คืนค่าเป็น rows ของ order_items อยู่แล้ว แต่เราต้อง normalize ให้อยู่รูปเดียวกัน
            $normalized = [];
            foreach ($items as $r) {
                $normalized[] = [
                    'product_id' => (int)($r['product_id'] ?? 0),
                    'variant_id' => isset($r['variant_id']) ? ($r['variant_id'] !== null ? (int)$r['variant_id'] : null) : null,
                    'quantity'   => (int)($r['quantity'] ?? 1),
                    'unit_price' => (float)($r['unit_price'] ?? 0),
                ];
            }
            $items = $normalized;
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

/**
 * ตรวจสอบ + สร้าง order_items สำหรับ order นี้ (ถ้ายังไม่มี)
 * @param mysqli $conn
 * @param array $payment row from payments (ต้องมี order_id และ items_json หรือดึงจาก orders)
 * @return array list of order_items
 */
function ensurePaymentItems(mysqli $conn, array $payment): array
{
    $orderId = (int)($payment['order_id'] ?? 0);
    if ($orderId <= 0) {
        error_log("Invalid order_id in ensurePaymentItems");
        return [];
    }

    // ถ้ามี order_items อยู่แล้ว → คืนเลย
    $resExisting = db_query(
        $conn,
        "SELECT id, order_id, product_id, variant_id, quantity, unit_price FROM order_items WHERE order_id = ? ORDER BY id ASC",
        [$orderId],
        "i"
    );
    if ($resExisting && $resExisting->num_rows > 0) {
        $rows = [];
        while ($r = $resExisting->fetch_assoc()) {
            $rows[] = [
                'id'         => (int)$r['id'],
                'order_id'   => (int)$r['order_id'],
                'product_id' => (int)$r['product_id'],
                'variant_id' => $r['variant_id'] !== null ? (int)$r['variant_id'] : null,
                'quantity'   => (int)$r['quantity'],
                'unit_price' => (float)$r['unit_price'],
            ];
        }
        return $rows;
    }

    // หลังแยกตาราง: items_json ไม่ได้อยู่ใน orders แล้ว
    // แหล่งสำรองที่ยังมี items_json คือ payment_intents (โดยผูกผ่าน converted_payment_id = payment_id)
    $paymentId = (int)($payment['payment_id'] ?? $payment['id'] ?? 0);
    if ($paymentId <= 0) {
        error_log("ensurePaymentItems: payment_id not found");
        return [];
    }

    $intentRes = db_query(
        $conn,
        "SELECT items_json FROM payment_intents WHERE converted_payment_id = ? LIMIT 1",
        [$paymentId],
        "i"
    );

    if (!$intentRes || $intentRes->num_rows === 0) {
        error_log("ensurePaymentItems: no payment_intent found for payment_id=$paymentId (cannot backfill order_items)");
        return [];
    }

    $intentRow = $intentRes->fetch_assoc();
    $items = json_decode($intentRow['items_json'] ?? '[]', true) ?: [];
    if (empty($items)) {
        error_log("ensurePaymentItems: items_json empty for payment_id=$paymentId");
        return [];
    }

    error_log("ensurePaymentItems: backfilling " . count($items) . " order_items for order_id=$orderId from payment_intents (payment_id=$paymentId)");

    foreach ($items as $it) {
        $pid = (int)($it['product_id'] ?? 0);
        if ($pid <= 0) continue;

        $vid = isset($it['variant_id']) && (int)$it['variant_id'] > 0 ? (int)$it['variant_id'] : null;

        $qty = (int)($it['quantity'] ?? 1);
        if ($qty <= 0) $qty = 1;

        $unitPrice = (float)($it['unit_price'] ?? 0);
        if ($unitPrice <= 0 && isset($it['price'])) {
            // บาง flow เก่าอาจส่ง 'price' มาแทน unit_price
            $unitPrice = (float)$it['price'];
            // ถ้า price เป็น total ของรายการและมี qty > 1 (ไม่แน่ชัด) คุณอาจปรับหาร qty ได้ที่นี่
        }
        if ($unitPrice <= 0) {
            $unitPrice = getUnitPriceFromDb($conn, $pid, $vid);
        }

        if ($vid !== null) {
            db_exec(
                $conn,
                "INSERT INTO order_items (order_id, product_id, variant_id, quantity, unit_price) VALUES (?,?,?,?,?)",
                [$orderId, $pid, $vid, $qty, $unitPrice],
                "iiiid"
            );
        } else {
            db_exec(
                $conn,
                "INSERT INTO order_items (order_id, product_id, variant_id, quantity, unit_price) VALUES (?,?,NULL,?,?)",
                [$orderId, $pid, $qty, $unitPrice],
                "iiid"
            );
        }
    }

    // คืนค่าที่เพิ่งสร้าง
    $resNow = db_query(
        $conn,
        "SELECT id, order_id, product_id, variant_id, quantity, unit_price FROM order_items WHERE order_id = ? ORDER BY id ASC",
        [$orderId],
        "i"
    );
    $rows = [];
    if ($resNow) {
        while ($r = $resNow->fetch_assoc()) {
            $rows[] = [
                'id'         => (int)$r['id'],
                'order_id'   => (int)$r['order_id'],
                'product_id' => (int)$r['product_id'],
                'variant_id' => $r['variant_id'] !== null ? (int)$r['variant_id'] : null,
                'quantity'   => (int)$r['quantity'],
                'unit_price' => (float)$r['unit_price'],
            ];
        }
    }
    return $rows;
}


function allocateLotsFIFO(mysqli $conn, array $orderItem): bool
{
    $orderItemId = (int)($orderItem['id'] ?? 0);
    $pid = (int)($orderItem['product_id'] ?? 0);
    $vid = $orderItem['variant_id'] ?? null;
    $need = (int)($orderItem['quantity'] ?? 0);
    $unitSell = (float)($orderItem['unit_price'] ?? 0);

    if ($orderItemId <= 0 || $pid <= 0 || $need <= 0) return false;

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
            "INSERT INTO lot_allocations (order_item_id, lot_id, qty, unit_sell_price, unit_cost_price) VALUES (?,?,?,?,?)",
            [$orderItemId, $lotId, $take, $unitSell, $unitCost],
            "iiidd"
        );
        if (!($okIns['ok'] ?? false)) {
            error_log("Failed to insert lot allocation for order_item_id=$orderItemId");
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
 * อนุมัติสลิป:
 *  - หัก stock จริง (stock - qty)
 *  - ลด reserved_stock ตาม qty
 *  - อัปเดตสถานะเป็น approved
 */
function approvePaymentAndApplyStock(mysqli $conn, int $paymentId): bool
{
    $tag = "[approve pid=$paymentId]";

    // 1) load payment
    $payment = getPaymentById($conn, $paymentId);
    if (!$payment) {
        error_log("$tag step=load_payment_failed");
        return false;
    }

    $st = $payment['status'] ?? 'pending';
    if ($st !== 'pending') {
        error_log("$tag step=status_not_pending status=$st");
        return false;
    }

    // 2) reload payment after reserve
    $payment = getPaymentById($conn, $paymentId);
    if (!$payment) {
        error_log("$tag step=reload_payment_failed");
        return false;
    }

    $st2 = $payment['status'] ?? 'pending';
    if ($st2 !== 'pending') {
        error_log("$tag step=status_changed_after_reserve status=$st2");
        return false;
    }

    $orderId = (int)($payment['order_id'] ?? 0);

    // 3) items
    $items = extractItemsFromPayment($conn, $payment);
    if (empty($items)) {
        error_log("$tag step=no_items order_id=$orderId");
        return false;
    }

    // 4) ensure order_items (your function name is ensurePaymentItems but actually returns order_items)
    $orderItems = ensurePaymentItems($conn, $payment);
    if (empty($orderItems)) {
        error_log("$tag step=ensure_items_failed order_id=$orderId");
        return false;
    }

    // 5) allocate lots FIFO
    foreach ($orderItems as $oi) {
        $oiId = (int)($oi['id'] ?? 0);
        if (!allocateLotsFIFO($conn, $oi)) {
            error_log("$tag step=allocate_lot_failed order_item_id=$oiId product_id=" . ($oi['product_id'] ?? 'null') . " variant_id=" . (($oi['variant_id'] ?? null) ?? 'null'));
            return false;
        }
    }

    // 6) apply stock & release reserved
    foreach ($items as $it) {
        $qty = (int)($it['quantity'] ?? 0);
        $pid = (int)($it['product_id'] ?? 0);
        $vid = $it['variant_id'] ?? null;
        $vid = ($vid !== null ? (int)$vid : null);

        if ($qty <= 0 || $pid <= 0) {
            error_log("$tag step=invalid_item pid=$pid vid=" . ($vid ?? 'null') . " qty=$qty");
            return false;
        }

        if ($vid !== null) {
            $sql = "
                UPDATE product_variants
                SET stock = stock - ?,
                    reserved_stock = reserved_stock - ?
                WHERE id = ? AND product_id = ?
                  AND stock >= ?
                  AND reserved_stock >= ?
            ";

            $r = db_exec($conn, $sql, [$qty, $qty, $vid, $pid, $qty, $qty], "iiiiii");

            if (!($r['ok'] ?? false) || (int)($r['affected'] ?? 0) !== 1) {
                error_log("$tag step=apply_variant_failed product_id=$pid variant_id=$vid qty=$qty affected=" . (int)($r['affected'] ?? 0) . " err=" . ($r['error'] ?? ''));
                return false;
            }
        } else {
            $sql = "
                UPDATE products
                SET stock = stock - ?,
                    reserved_stock = reserved_stock - ?
                WHERE id = ?
                  AND stock >= ?
                  AND reserved_stock >= ?
            ";

            $r = db_exec($conn, $sql, [$qty, $qty, $pid, $qty, $qty], "iiiii");

            if (!($r['ok'] ?? false) || (int)($r['affected'] ?? 0) !== 1) {
                error_log("$tag step=apply_product_failed product_id=$pid qty=$qty affected=" . (int)($r['affected'] ?? 0) . " err=" . ($r['error'] ?? ''));
                return false;
            }
        }
    }

    // 7) clear reserved_at (optional but recommended)
    $clr = db_exec($conn, "UPDATE payments SET reserved_at = NULL WHERE id = ?", [$paymentId], "i");
    if (!($clr['ok'] ?? false)) {
        // ไม่ถึงกับต้อง fail แต่ log ไว้
        error_log("$tag step=clear_reserved_at_failed err=" . ($clr['error'] ?? ''));
    }

    // 8) update status
    $okStatus = updatePaymentStatus($conn, $paymentId, 'approved');
    if (!$okStatus) {
        error_log("$tag step=update_status_failed");
        return false;
    }

    error_log("$tag step=success order_id=$orderId");
    return true;
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

    $items = extractItemsFromPayment($conn, $payment);
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
            pay.id AS payment_id,
            pay.order_id,
            pay.amount,
            pay.slip_path,
            pay.transfer_date,
            pay.transfer_time,
            pay.status AS payment_status,
            pay.created_at AS payment_created_at,

            o.grand_total,
            o.order_status,
            o.approved_payment_id,
            o.paid_at,
            (o.approved_payment_id IS NOT NULL) AS order_is_paid,

            u.id AS user_id,
            u.first_name,
            u.last_name,
            u.display_name
        FROM payments pay
        INNER JOIN orders o ON o.id = pay.order_id
        LEFT JOIN users u ON u.id = o.user_id
        WHERE pay.status = 'pending'
          AND pay.slip_path IS NOT NULL AND pay.slip_path <> ''
        ORDER BY pay.created_at DESC
        LIMIT ?
    ";

    $res = db_query($conn, $sql, [$limit], 'i');
    if (!$res) return [];

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
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
    $items = extractItemsFromPayment($conn, $payment);
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
