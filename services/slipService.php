<?php
// services/SlipService.php

/**
 * บันทึก payment ใหม่ (ทั้ง single และ cart)
 * $data = [
 *   'user_id'      => int,
 *   'product_id'   => int|null,
 *   'variant_id'   => int|null,
 *   'items_json'   => string(json array),
 *   'amount'       => float,
 *   'slip_path'    => string,
 *   'mode'         => 'single'|'cart'
 * ]
 */
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
    return $result['insert_id'] ?? 0;
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
 * อัปเดตสถานะ: approved / rejected
 */
function updatePaymentStatus(mysqli $conn, int $payment_id, string $status): bool
{
    $sql = "UPDATE payments SET status = ? WHERE id = ?";

    return (bool)db_exec($conn, $sql, [$status, $payment_id], "si");
}
