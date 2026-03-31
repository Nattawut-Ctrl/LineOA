<?php

/**
 * paymentIntentService.php
 * จัดการ payment_intents (ออเดอร์ที่ยังไม่จ่าย)
 */

require_once __DIR__ . '/../utils/db_with_log.php';

/**
 * สร้าง payment intent ใหม่
 * คืน intent_id
 */
function createPaymentIntent(mysqli $conn, array $data): int
{
    $sql = "
        INSERT INTO payment_intents (
            user_id,
            mode,
            product_id,
            variant_id,
            items_json,
            amount,
            address_id,
            address_json,
            status,
            expires_at,
            created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, 'active',
            DATE_ADD(NOW(), INTERVAL 30 MINUTE),
            NOW()
        )
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'isiisdis',
        $data['user_id'],
        $data['mode'],
        $data['product_id'],
        $data['variant_id'],
        $data['items_json'],
        $data['amount'],
        $data['address_id'],
        $data['address_json']
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to create payment intent');
    }

    return $stmt->insert_id;
}

/**
 * ดึง intent ตาม id
 */
function getIntentById(mysqli $conn, int $intent_id): ?array
{
    $sql = "SELECT * FROM payment_intents WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $intent_id);
    $stmt->execute();

    $res = $stmt->get_result();
    return $res->fetch_assoc() ?: null;
}

/**
 * ดึง intent ที่ active ล่าสุดของ user (กันสร้างซ้ำ)
 */
function getActiveIntentForUser(mysqli $conn, int $user_id): ?array
{
    $sql = "
        SELECT *
        FROM payment_intents
        WHERE user_id = ?
          AND status = 'active'
          AND expires_at > NOW()
        ORDER BY id DESC
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();

    $res = $stmt->get_result();
    return $res->fetch_assoc() ?: null;
}

/**
 * เช็ค intent หมดเวลาหรือไม่
 */
function isIntentExpired(array $intent): bool
{
    if ($intent['status'] !== 'active') {
        return true;
    }

    return strtotime($intent['expires_at']) <= time();
}

/**
 * เวลาที่เหลือ (นาที)
 */
function getIntentTimeRemaining(array $intent): int
{
    $remain = strtotime($intent['expires_at']) - time();
    return max(0, (int)ceil($remain / 60));
}

/**
 * expire intent (ยังไม่คืน stock — ให้คุณเรียกฟังก์ชันคืน stock เอง)
 */
function expireIntent(mysqli $conn, int $intent_id): bool
{
    $sql = "
        UPDATE payment_intents
        SET status = 'expired',
            updated_at = NOW()
        WHERE id = ?
          AND status = 'active'
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $intent_id);
    return $stmt->execute();
}

/**
 * mark intent ว่า convert เป็น payment แล้ว
 */
function markIntentConverted(mysqli $conn, int $intent_id, int $payment_id): bool
{
    $sql = "
        UPDATE payment_intents
        SET status = 'converted',
            converted_payment_id = ?,
            updated_at = NOW()
        WHERE id = ?
          AND status = 'active'
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $payment_id, $intent_id);
    return $stmt->execute();
}
function extractItemsFromIntent(array $intent): array
{
    $items = [];
    $decoded = json_decode($intent['items_json'] ?? '[]', true);

    if (!is_array($decoded)) return [];

    foreach ($decoded as $it) {
        $pid = (int)($it['product_id'] ?? 0);
        if ($pid <= 0) continue;

        $variantId = isset($it['variant_id']) ? (int)$it['variant_id'] : 0;
        $variantId = $variantId > 0 ? $variantId : null;

        $qty = (int)($it['quantity'] ?? 1);
        if ($qty <= 0) $qty = 1;

        $items[] = [
            'product_id' => $pid,
            'variant_id' => $variantId,
            'quantity'   => $qty,
        ];
    }

    return $items;
}

/**
 * reserveStockForIntent
 * - จอง stock โดยเพิ่ม reserved_stock (ยังไม่ตัด stock จริง)
 * - กันจองซ้ำด้วย reserved_at ใน payment_intents
 * - เปิด transaction ภายในเพื่อความ atomic
 */
function reserveStockForIntent(mysqli $conn, int $intentId): bool
{
    $intent = getIntentById($conn, $intentId);
    if (!$intent) return false;

    if (($intent['status'] ?? '') !== 'active') return true;

    $expiresAt = strtotime($intent['expires_at'] ?? '');
    if ($expiresAt !== false && time() >= $expiresAt) return true;

    if (!empty($intent['reserved_at'])) return true;

    $items = extractItemsFromIntent($intent);
    if (empty($items)) return false;

    // ล็อก intent กันจองซ้ำ
    $lock = db_exec(
        $conn,
        "UPDATE payment_intents
         SET reserved_at = NOW(), updated_at = NOW()
         WHERE id = ? AND status = 'active' AND reserved_at IS NULL",
        [$intentId],
        "i"
    );
    if (!($lock['ok'] ?? false)) return false;

    // ถ้า affected=0 แปลว่าโดนจองไปแล้ว → ถือว่าสำเร็จ
    if ((int)($lock['affected'] ?? 0) !== 1) return true;

    // จอง stock
    foreach ($items as $item) {
        $pid = (int)$item['product_id'];
        $vid = $item['variant_id'];
        $qty = (int)$item['quantity'];

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

        if (!($r['ok'] ?? false) || (int)($r['affected'] ?? 0) !== 1) {
            // ❗ โยน exception เพื่อให้ payment.php rollback ได้
            throw new Exception("reserve failed (out of stock?)");
        }
    }

    return true;
}

/**
 * expireAndReleaseStockForIntent
 * - ถ้าหมดเวลา: คืน reserved_stock (เฉพาะเคยจองแล้ว) + เปลี่ยน intent เป็น expired
 * - เปิด transaction ภายใน
 */
function expireAndReleaseStockForIntent(mysqli $conn, int $intentId): bool
{
    $intent = getIntentById($conn, $intentId);
    if (!$intent) return false;

    if (($intent['status'] ?? '') !== 'active') return true;

    $expiresAt = strtotime($intent['expires_at'] ?? '');
    if ($expiresAt === false) return false;

    if (time() < $expiresAt) return true;

    $items = extractItemsFromIntent($intent);

    // คืนสต็อกเฉพาะเคยจองแล้ว
    if (!empty($intent['reserved_at'])) {
        foreach ($items as $item) {
            $pid = (int)$item['product_id'];
            $vid = $item['variant_id'];
            $qty = (int)$item['quantity'];

            if ($qty <= 0) continue;

            if ($vid !== null) {
                db_exec(
                    $conn,
                    "UPDATE product_variants
                     SET reserved_stock = GREATEST(reserved_stock - ?, 0)
                     WHERE id = ? AND product_id = ?",
                    [$qty, $vid, $pid],
                    "iii"
                );
            } else {
                db_exec(
                    $conn,
                    "UPDATE products
                     SET reserved_stock = GREATEST(reserved_stock - ?, 0)
                     WHERE id = ?",
                    [$qty, $pid],
                    "ii"
                );
            }
        }
    }

    $u = db_exec(
        $conn,
        "UPDATE payment_intents
         SET status='expired', updated_at=NOW()
         WHERE id=? AND status='active'",
        [$intentId],
        "i"
    );

    if (!($u['ok'] ?? false) || (int)($u['affected'] ?? 0) !== 1) {
        throw new Exception("update intent expired failed");
    }

    return true;
}

/**
 * cancelIntentImmediately - ยกเลิก intent ทันทีและคืนสต็อก
 * ใช้เมื่อผู้ใช้กดปุ่มยกเลิก (ไม่รอหมดเวลา)
 */
function cancelIntentImmediately(mysqli $conn, int $intentId, int $user_id): bool
{
    $intent = getIntentById($conn, $intentId);
    if (!$intent) return false;

    // ตรวจสอบเจ้าของและสถานะ
    if ((int)$intent['user_id'] !== $user_id || ($intent['status'] ?? '') !== 'active') {
        return false;
    }

    $items = extractItemsFromIntent($intent);

    // คืนสต็อกเฉพาะเคยจองแล้ว
    if (!empty($intent['reserved_at'])) {
        foreach ($items as $item) {
            $pid = (int)$item['product_id'];
            $vid = $item['variant_id'];
            $qty = (int)$item['quantity'];

            if ($qty <= 0) continue;

            if ($vid !== null) {
                db_exec(
                    $conn,
                    "UPDATE product_variants
                     SET reserved_stock = GREATEST(reserved_stock - ?, 0)
                     WHERE id = ? AND product_id = ?",
                    [$qty, $vid, $pid],
                    "iii"
                );
            } else {
                db_exec(
                    $conn,
                    "UPDATE products
                     SET reserved_stock = GREATEST(reserved_stock - ?, 0)
                     WHERE id = ?",
                    [$qty, $pid],
                    "ii"
                );
            }
        }
    }

    // เปลี่ยนสถานะเป็น cancelled
    $u = db_exec(
        $conn,
        "UPDATE payment_intents SET status='cancelled', updated_at=NOW() WHERE id=? AND status='active'",
        [$intentId],
        "i"
    );

    return ($u['ok'] ?? false) && (int)($u['affected'] ?? 0) === 1;
}

function cleanupExpiredIntentsForUser(mysqli $conn, int $user_id): void
{
    $sql = "
        SELECT id
        FROM payment_intents
        WHERE user_id = ?
          AND status = 'active'
          AND expires_at < NOW()
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        expireAndReleaseStockForIntent($conn, (int)$row['id']);
    }
}
