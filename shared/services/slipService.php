<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once UTILS_PATH . '/stock_helper.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once BACKEND_PATH . '/services/adminEmailService.php';

function createPayment(mysqli $conn, array $data)
{
    // กันไว้ถ้าไม่ได้ส่งมาก็ให้เป็น null
    $transfer_date = $data['transfer_date'] ?? null;
    $transfer_time = $data['transfer_time'] ?? null;

    $sql = "INSERT INTO payments (
                user_id,
                product_id,
                variant_id,
                items_json,
                amount,
                slip_path,
                mode,
                transfer_date,
                transfer_time,
                status,
                created_at
            ) VALUES (?,?,?,?,?,?,?,?,?, 'pending', NOW())";

    $params = [
        $data['user_id'],
        $data['product_id'],
        $data['variant_id'],
        $data['items_json'],
        $data['amount'],
        $data['slip_path'],
        $data['mode'],
        $transfer_date,
        $transfer_time,
    ];

    // i=user_id, i=product_id, i=variant_id, s=items_json, d=amount, s=slip_path, s=mode, s=transfer_date, s=transfer_time
    $types = "iiisdssss";

    $result = db_exec($conn, $sql, $params, $types);

    $paymentId = (int)($result['insert_id'] ?? 0);

    if ($paymentId > 0) {
        notifyAdminNewSlipOnce($conn, $paymentId);
    }

    return $paymentId;
}

/**
 * ดึงรายการ Payment ทั้งหมด (สำหรับ Admin ใช้ดู)
 */
function getAllPayments(mysqli $conn): array
{
    $sql = "SELECT 
                p.*, 
                u.first_name,
                u.last_name,
                u.display_name
            FROM payments p
            LEFT JOIN users u ON u.id = p.user_id
            ORDER BY p.created_at DESC";

    $res = db_query($conn, $sql);
    if (!$res) return [];

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * ดึง payment ตาม id (ใช้เปิดรายละเอียดใน Admin)
 */
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
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
        LIMIT 1
    ";

    $res = db_query($conn, $sql, [$payment_id], "i");
    if (!$res || $res->num_rows == 0) return null;

    return $res->fetch_assoc();
}

/**
 * แปลง items_json ของ payment → array พร้อมใช้ตัดสต็อก
 * ผลลัพธ์ประมาณ:
 * [
 *   ['product_id' => 11, 'variant_id' => null, 'quantity' => 2],
 *   ...
 * ]
 */
function extractItemsFromPayment(array $payment): array
{
    $items = [];

    // เคสหลัก: มี items_json (ทั้งโหมด cart และ single)
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

                $items[] = [
                    'product_id' => $pid,
                    'variant_id' => $variantId,
                    'quantity'   => $qty,
                ];
            }
        }
    } else {
        // เผื่อกันไว้ถ้าเคยเก็บ product_id / variant_id ตรง ๆ
        $pid = (int)($payment['product_id'] ?? 0);
        if ($pid > 0) {
            $variantId = isset($payment['variant_id']) ? (int)$payment['variant_id'] : 0;
            $variantId = $variantId > 0 ? $variantId : null;

            $items[] = [
                'product_id' => $pid,
                'variant_id' => $variantId,
                'quantity'   => 1,
            ];
        }
    }

    return $items;
}

/**
 * อัปเดตสถานะ: approved / rejected
 */
function updatePaymentStatus(mysqli $conn, int $payment_id, string $status): bool
{
    $sql = "UPDATE payments SET status = ? WHERE id = ?";

    return (bool)db_exec($conn, $sql, [$status, $payment_id], "si");
}

/**
 * กัน stock แบบ soft สำหรับ payment ที่เพิ่งสร้าง (status = pending)
 * หมายเหตุ: ฟังก์ชันนี้จะ "ไม่" เปิด transaction เอง
 *      ให้ตัวที่เรียกเป็นคน begin / commit / rollback
 */
function reserveStockForPayment(mysqli $conn, int $paymentId): bool
{
    $payment = getPaymentById($conn, $paymentId);
    if (!$payment) {
        return false;
    }

    $status = $payment['status'] ?? 'pending';
    if ($status !== 'pending') {
        // ถ้าไม่ใช่ pending แล้ว แสดงว่าเคยจัดการไปแล้ว ไม่ต้องทำซ้ำ
        return true;
    }

    $items = extractItemsFromPayment($payment);
    if (empty($items)) {
        return false;
    }

    // 1) เช็คก่อนว่าทุกรายการสต็อกยังพอไหม
    foreach ($items as $item) {
        $available = getAvailableStock(
            $conn,
            $item['product_id'],
            $item['variant_id']
        );

        if ($available < $item['quantity']) {
            // ถ้าชิ้นไหนไม่พอ → กันไม่ได้ทั้งบิล
            return false;
        }
    }

    // 2) ถ้าพอทุกชิ้น → อัปเดต reserved_stock
    foreach ($items as $item) {
        $qty = $item['quantity'];

        if ($item['variant_id'] !== null) {
            $sql = "
                UPDATE product_variants
                SET reserved_stock = reserved_stock + ?
                WHERE id = ? AND product_id = ?
            ";
            db_exec(
                $conn,
                $sql,
                [$qty, $item['variant_id'], $item['product_id']],
                "iii"
            );
        } else {
            $sql = "
                UPDATE products
                SET reserved_stock = reserved_stock + ?
                WHERE id = ?
            ";
            db_exec(
                $conn,
                $sql,
                [$qty, $item['product_id']],
                "ii"
            );
        }
    }

    return true;
}

/**
 * อนุมัติสลิป:
 *  - หัก stock จริง (stock - qty)
 *  - ลด reserved_stock ตาม qty
 *  - อัปเดตสถานะเป็น approved
 * ควรเรียกภายใน transaction
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
    if (empty($items)) {
        return false;
    }

    foreach ($items as $item) {
        $qty = $item['quantity'];

        if ($item['variant_id'] !== null) {
            $sql = "
                UPDATE product_variants
                SET stock = stock - ?, 
                    reserved_stock = GREATEST(reserved_stock - ?, 0)
                WHERE id = ? AND product_id = ?
            ";
            db_exec(
                $conn,
                $sql,
                [$qty, $qty, $item['variant_id'], $item['product_id']],
                "iiii"
            );
        } else {
            $sql = "
                UPDATE products
                SET stock = stock - ?, 
                    reserved_stock = GREATEST(reserved_stock - ?, 0)
                WHERE id = ?
            ";
            db_exec(
                $conn,
                $sql,
                [$qty, $qty, $item['product_id']],
                "iii"
            );
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
    if (!$payment) {
        return false;
    }

    if (($payment['status'] ?? 'pending') !== 'pending') {
        return false;
    }

    $items = extractItemsFromPayment($payment);
    if (empty($items)) {
        return false;
    }

    foreach ($items as $item) {
        $qty = $item['quantity'];

        if ($item['variant_id'] !== null) {
            $sql = "
                UPDATE product_variants
                SET reserved_stock = GREATEST(reserved_stock - ?, 0)
                WHERE id = ? AND product_id = ?
            ";
            db_exec(
                $conn,
                $sql,
                [$qty, $item['variant_id'], $item['product_id']],
                "iii"
            );
        } else {
            $sql = "
                UPDATE products
                SET reserved_stock = GREATEST(reserved_stock - ?, 0)
                WHERE id = ?
            ";
            db_exec(
                $conn,
                $sql,
                [$qty, $item['product_id']],
                "ii"
            );
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
