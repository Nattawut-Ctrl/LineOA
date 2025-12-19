<?php
function getUserAddresses(mysqli $conn, int $userId): array
{
    $sql = "SELECT *
            FROM user_addresses
            WHERE user_id = ?
              AND deleted_at IS NULL
            ORDER BY is_default DESC, id DESC";

    $res = db_query($conn, $sql, [$userId], "i");

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function getUserAddressById(mysqli $conn, int $userId, int $addressId): ?array
{
    $sql = "SELECT *
            FROM user_addresses
            WHERE id = ?
              AND user_id = ?
              AND deleted_at IS NULL
            LIMIT 1";

    $res = db_query($conn, $sql, [$addressId, $userId], "ii");
    $row = $res->fetch_assoc();
    return $row ?: null;
}

function setDefaultAddress(mysqli $conn, int $userId, int $addressId): bool
{
    $conn->begin_transaction();

    try {
        // 1) ยกเลิกค่าเริ่มต้นทั้งหมดของ user
        db_exec(
            $conn,
            "UPDATE user_addresses
             SET is_default = 0
             WHERE user_id = ?
               AND deleted_at IS NULL",
            [$userId],
            "i"
        );

        // 2) ตั้งอันที่เลือกเป็นค่าเริ่มต้น
        $r = db_exec(
            $conn,
            "UPDATE user_addresses
             SET is_default = 1
             WHERE id = ?
               AND user_id = ?
               AND deleted_at IS NULL",
            [$addressId, $userId],
            "ii"
        );

        $conn->commit();
        return $r['ok'] && $r['affected'] > 0;
    } catch (Throwable $e) {
        $conn->rollback();
        return false;
    }
}

function createAddress(mysqli $conn, int $userId, array $data): int
{
    $conn->begin_transaction();

    try {
        $isDefault   = !empty($data['is_default']) ? 1 : 0;

        $fullName    = trim($data['full_name'] ?? '');
        $phone       = trim($data['phone'] ?? '');
        $addressLine = trim($data['address_line'] ?? '');
        $subdistrict = trim($data['subdistrict'] ?? '');
        $district    = trim($data['district'] ?? '');
        $province    = trim($data['province'] ?? '');
        $postalCode  = trim($data['postal_code'] ?? '');
        $note        = trim($data['note'] ?? '');
        $label       = trim($data['label'] ?? '');

        // ถ้าติ๊ก default → เคลียร์ของเดิมก่อน
        if ($isDefault === 1) {
            db_exec(
                $conn,
                "UPDATE user_addresses
                 SET is_default = 0
                 WHERE user_id = ?
                   AND deleted_at IS NULL",
                [$userId],
                "i"
            );
        }

        $r = db_exec($conn, "
            INSERT INTO user_addresses
            (user_id, full_name, phone, address_line, subdistrict, district, province, postal_code, note, label, is_default)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $userId,
            $fullName,
            $phone,
            $addressLine,
            $subdistrict,
            $district,
            $province,
            $postalCode,
            $note,
            $label,
            $isDefault
        ], "isssssssssi");

        if (!$r['ok'] || empty($r['insert_id'])) {
            throw new Exception($r['error'] ?? 'Insert failed');
        }

        $conn->commit();
        return (int)$r['insert_id'];
    } catch (Throwable $e) {
        $conn->rollback();
        return 0;
    }
}

function updateAddress(mysqli $conn, int $userId, int $addressId, array $data): bool
{
    $conn->begin_transaction();

    try {
        $isDefault   = !empty($data['is_default']) ? 1 : 0;

        $fullName    = trim($data['full_name'] ?? '');
        $phone       = trim($data['phone'] ?? '');
        $addressLine = trim($data['address_line'] ?? '');
        $subdistrict = trim($data['subdistrict'] ?? '');
        $district    = trim($data['district'] ?? '');
        $province    = trim($data['province'] ?? '');
        $postalCode  = trim($data['postal_code'] ?? '');
        $note        = trim($data['note'] ?? '');
        $label       = trim($data['label'] ?? '');

        // ถ้าติ๊ก default → เคลียร์ของเดิมก่อน
        if ($isDefault === 1) {
            db_exec(
                $conn,
                "UPDATE user_addresses
                 SET is_default = 0
                 WHERE user_id = ?
                   AND deleted_at IS NULL",
                [$userId],
                "i"
            );
        }

        $r = db_exec($conn, "
            UPDATE user_addresses
            SET full_name = ?, phone = ?, address_line = ?, subdistrict = ?, district = ?, province = ?, postal_code = ?, note = ?, label = ?, is_default = ?
            WHERE id = ?
              AND user_id = ?
              AND deleted_at IS NULL
        ", [
            $fullName,
            $phone,
            $addressLine,
            $subdistrict,
            $district,
            $province,
            $postalCode,
            $note,
            $label,
            $isDefault,
            $addressId,
            $userId
        ], "sssssssssiii");

        $conn->commit();
        return (bool)$r['ok'];
    } catch (Throwable $e) {
        $conn->rollback();
        return false;
    }
}

function softDeleteAddress(mysqli $conn, int $userId, int $addressId): bool
{
    $r = db_exec($conn, "
        UPDATE user_addresses
        SET deleted_at = NOW(), is_default = 0
        WHERE id = ?
          AND user_id = ?
          AND deleted_at IS NULL
    ", [$addressId, $userId], "ii");

    return $r['ok'] && $r['affected'] > 0;
}
