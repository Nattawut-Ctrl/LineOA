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
function createPayment(mysqli $conn, array $data): int
{
    $sql = "
        INSERT INTO payments
            (user_id, product_id, variant_id, items_json, amount, slip_path, status, mode)
        VALUES
            (?, ?, ?, ?, ?, ?, 'pending', ?)
    ";

    $ok = db_exec($conn, $sql, [
        $data['user_id'],
        $data['product_id'],
        $data['variant_id'],
        $data['items_json'],
        $data['amount'],
        $data['slip_path'],
        $data['mode']
    ], "iiissss");

    if (!$ok) {
        throw new Exception("Failed to create payment");
    }

    return (int)$conn->insert_id;
}


/**
 * ดึงรายการ Payment ทั้งหมด (สำหรับ Admin ใช้ดู)
 */
function getAllPayments(mysqli $conn): array
{
    $sql = "
        SELECT p.*, u.first_name, u.last_name, u.display_name
        FROM payments p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.id DESC
    ";
    $res = db_query($conn, $sql);
    $rows = [];

    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    return $rows;
}


/**
 * ดึง payment ตาม id (ใช้เปิดรายละเอียดใน Admin)
 */
function getPaymentById(mysqli $conn, int $payment_id): ?array
{
    $sql = "
        SELECT p.*, u.first_name, u.last_name, u.display_name
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
