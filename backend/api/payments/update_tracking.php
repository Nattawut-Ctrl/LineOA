<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/csrf.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();

// CSRF (ของคุณเป็นแบบ void และ die() เมื่อ mismatch)
csrf_verify($_POST['csrf_token'] ?? null);

$payment_id = (int)($_POST['payment_id'] ?? 0);

$tracking_raw = ($_POST['tracking_no'] ?? '');
$tracking = trim((string)$tracking_raw);
$carrier = trim($_POST['carrier'] ?? '');

// Validate
if ($payment_id <= 0) {
    $_SESSION['error'] = 'Invalid payment ID';
    header('Location: ' . rtrim(BACKEND_URL, '/') . '/pages/payments/list.php');
    exit;
}

// จำกัดความยาวตาม DB varchar(100)
if (mb_strlen($tracking) > 100) {
    $_SESSION['error'] = 'เลขติดตามพัสดุต้องไม่เกิน 100 ตัวอักษร';
    header('Location: ' . rtrim(BACKEND_URL, '/') . '/pages/payments/view.php?id=' . $payment_id);
    exit;
}

// ถ้าเป็นค่าว่าง ให้ถือว่าเคลียร์ค่าเป็น NULL
$trackingOrNull = ($tracking === '') ? null : $tracking;

try {
    $conn = connectDBWithLog();

    // อ่านสถานะ + ค่า tracking เดิม เพื่อให้ feedback แม่นขึ้น
    $stmtSel = $conn->prepare("SELECT status, tracking_no FROM payments WHERE id = ? LIMIT 1");
    if (!$stmtSel) {
        throw new Exception('Prepare failed (SELECT): ' . $conn->error);
    }
    $stmtSel->bind_param('i', $payment_id);
    $stmtSel->execute();
    $res = $stmtSel->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmtSel->close();

    if (!$row) {
        $_SESSION['error'] = 'ไม่พบรายการชำระเงินนี้';
        header('Location: ' . rtrim(BACKEND_URL, '/') . '/pages/payments/list.php');
        exit;
    }

    if (($row['status'] ?? '') !== 'approved') {
        $_SESSION['warning'] = 'ยังไม่สามารถใส่เลขพัสดุได้ เนื่องจากสถานะยังไม่ใช่ approved';
        header('Location: ' . rtrim(BACKEND_URL, '/') . '/pages/payments/view.php?id=' . $payment_id);
        exit;
    }

    $oldTracking = $row['tracking_no'] ?? null;

    // ถ้าค่าเท่าเดิม ให้ถือว่าโอเค (กันกรณี affected_rows = 0)
    if ($oldTracking === $trackingOrNull) {
        $_SESSION['success'] = 'บันทึกแล้ว (ไม่มีการเปลี่ยนแปลงข้อมูล)';
        header('Location: ' . rtrim(BACKEND_URL, '/') . '/pages/payments/view.php?id=' . $payment_id);
        exit;
    }

    // UPDATE ที่ถูกต้อง
    $stmtUp = $conn->prepare("
        UPDATE payments
        SET tracking_no = ?, carrier = ?
        WHERE id = ? AND status = 'approved'
        LIMIT 1
    ");

    if (!$stmtUp) {
        throw new Exception('Prepare failed (UPDATE): ' . $conn->error);
    }

    // bind_param ไม่รองรับ NULL โดยตรงในบางกรณี ให้ใช้ตัวแปรและชนิด s,i ได้
    // ถ้า $trackingOrNull เป็น null จะถูกส่งเป็น NULL ได้ใน mysqli (ปกติใช้ได้)
    $stmtUp->bind_param('ssi', $trackingOrNull, $carrier, $payment_id);

    if (!$stmtUp->execute()) {
        throw new Exception('Execute failed: ' . $stmtUp->error);
    }

    if ($stmtUp->affected_rows > 0) {

        require_once BACKEND_PATH . '/actions/payments/line_notify.php';

        $stmtUser = $conn->prepare("
        SELECT u.line_uid
        FROM payments p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
        LIMIT 1
    ");
        $stmtUser->bind_param('i', $payment_id);
        $stmtUser->execute();
        $resUser = $stmtUser->get_result();
        $user = $resUser->fetch_assoc();
        $stmtUser->close();

        if (!empty($user['line_uid']) && !empty($trackingOrNull)) {

            sendShippingNotification(
                $user['line_uid'],
                $payment_id,
                $trackingOrNull,
                $carrier
            );

            // อัปเดต buyer_notified = 1
            $stmtNotify = $conn->prepare("
            UPDATE payments
            SET buyer_notified = 1
            WHERE id = ?
        ");
            $stmtNotify->bind_param('i', $payment_id);
            $stmtNotify->execute();
            $stmtNotify->close();
        }

        $_SESSION['success'] = 'เลขติดตามพัสดุอัปเดตสำเร็จ และแจ้งลูกค้าแล้ว';
    } else {
        $_SESSION['warning'] = 'ไม่สามารถอัปเดตได้ (อาจมีการเปลี่ยนสถานะของรายการ)';
    }

    $stmtUp->close();
    $conn->close();
} catch (Exception $e) {
    $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
}

header('Location: ' . rtrim(BACKEND_URL, '/') . '/pages/payments/view.php?id=' . $payment_id);
exit;
