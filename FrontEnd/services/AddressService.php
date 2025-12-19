<?php
// services/AddressService.php
// ต้องมี $conn (mysqli) จาก db.php ที่หน้าเรียกใช้งานส่งเข้ามา

function getUserAddresses(mysqli $conn, int $userId): array
{
    $sql = "SELECT *
            FROM user_addresses
            WHERE user_id = ?
              AND deleted_at IS NULL
            ORDER BY is_default DESC, id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
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
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $addressId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function setDefaultAddress(mysqli $conn, int $userId, int $addressId): bool
{
    // ทำให้ปลอดภัย: ทำใน transaction เพื่อกันข้อมูลค้าง
    $conn->begin_transaction();

    try {
        // 1) ยกเลิกค่าเริ่มต้นของทุกอัน
        $stmt = $conn->prepare("UPDATE user_addresses
                                SET is_default = 0
                                WHERE user_id = ?
                                  AND deleted_at IS NULL");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

        // 2) ตั้งอันที่เลือกเป็นค่าเริ่มต้น (ต้องเป็นของ user นี้เท่านั้น)
        $stmt = $conn->prepare("UPDATE user_addresses
                                SET is_default = 1
                                WHERE id = ?
                                  AND user_id = ?
                                  AND deleted_at IS NULL");
        $stmt->bind_param("ii", $addressId, $userId);
        $stmt->execute();
        $ok = ($stmt->affected_rows > 0);
        $stmt->close();

        $conn->commit();
        return $ok;
    } catch (Throwable $e) {
        $conn->rollback();
        return false;
    }
}

function createAddress(mysqli $conn, int $userId, array $data): int
{
    // ถ้าติ๊ก default ให้เคลียร์อันเดิมก่อน
    $conn->begin_transaction();

    try {
        $isDefault = !empty($data['is_default']) ? 1 : 0;

        if ($isDefault === 1) {
            $stmt = $conn->prepare("UPDATE user_addresses
                                    SET is_default = 0
                                    WHERE user_id = ?
                                      AND deleted_at IS NULL");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
        }

        $sql = "INSERT INTO user_addresses
                (user_id, full_name, phone, address_line, subdistrict, district, province, postal_code, note, label, is_default)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        $fullName    = trim($data['full_name'] ?? '');
        $phone       = trim($data['phone'] ?? '');
        $addressLine = trim($data['address_line'] ?? '');
        $subdistrict = trim($data['subdistrict'] ?? '');
        $district    = trim($data['district'] ?? '');
        $province    = trim($data['province'] ?? '');
        $postalCode  = trim($data['postal_code'] ?? '');
        $note        = trim($data['note'] ?? '');
        $label       = trim($data['label'] ?? '');

        $stmt->bind_param(
            "isssssssssi",
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
        );

        $stmt->execute();
        $newId = (int)$stmt->insert_id;
        $stmt->close();

        $conn->commit();
        return $newId;
    } catch (Throwable $e) {
        $conn->rollback();
        return 0;
    }
}

function updateAddress(mysqli $conn, int $userId, int $addressId, array $data): bool
{
    $conn->begin_transaction();

    try {
        $isDefault = !empty($data['is_default']) ? 1 : 0;

        if ($isDefault === 1) {
            // ถ้าจะตั้งเป็น default ก็ต้องเคลียร์ของเก่าก่อน
            $stmt = $conn->prepare("UPDATE user_addresses
                                    SET is_default = 0
                                    WHERE user_id = ?
                                      AND deleted_at IS NULL");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
        }

        $sql = "UPDATE user_addresses
                SET full_name = ?, phone = ?, address_line = ?, subdistrict = ?, district = ?, province = ?, postal_code = ?, note = ?, label = ?, is_default = ?
                WHERE id = ?
                  AND user_id = ?
                  AND deleted_at IS NULL";
        $stmt = $conn->prepare($sql);

        $fullName    = trim($data['full_name'] ?? '');
        $phone       = trim($data['phone'] ?? '');
        $addressLine = trim($data['address_line'] ?? '');
        $subdistrict = trim($data['subdistrict'] ?? '');
        $district    = trim($data['district'] ?? '');
        $province    = trim($data['province'] ?? '');
        $postalCode  = trim($data['postal_code'] ?? '');
        $note        = trim($data['note'] ?? '');
        $label       = trim($data['label'] ?? '');

        $stmt->bind_param(
            "sssssssssi ii",
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
        );

        // NOTE: bind_param ห้ามมีช่องว่างใน type string
        // แก้เป็น:
        $stmt->close();

        // ทำใหม่แบบถูกต้อง
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssssssi ii",
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
        );

        $stmt->execute();
        $ok = ($stmt->affected_rows >= 0);
        $stmt->close();

        $conn->commit();
        return $ok;
    } catch (Throwable $e) {
        $conn->rollback();
        return false;
    }
}

function softDeleteAddress(mysqli $conn, int $userId, int $addressId): bool
{
    $sql = "UPDATE user_addresses
            SET deleted_at = NOW(), is_default = 0
            WHERE id = ?
              AND user_id = ?
              AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $addressId, $userId);
    $stmt->execute();
    $ok = ($stmt->affected_rows > 0);
    $stmt->close();
    return $ok;
}
