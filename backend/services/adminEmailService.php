<?php
// services/adminEmailService.php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../vendor/autoload.php';
// require_once __DIR__ . '/../../mail_config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once SERVICES_PATH . '/slipService.php';

/**
 * สร้าง PHPMailer พร้อม SMTP config กลาง
 */
function createMailer(): PHPMailer
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;

    $mail->CharSet = 'UTF-8';
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

    return $mail;
}

/**
 * ส่ง Email แจ้ง admin เมื่อมีสลิปใหม่ (ส่งครั้งเดียว)
 */
function notifyAdminNewSlipOnce(mysqli $conn, int $paymentId): bool
{
    // 1) กันซ้ำ (atomic)
    $lock = db_exec(
        $conn,
        "UPDATE payments SET admin_notified = 1 WHERE id = ? AND admin_notified = 0",
        [$paymentId],
        "i"
    );

    if (($lock['affected_rows'] ?? 0) <= 0) {
        return false; // เคยส่งแล้ว
    }

    // 2) โหลดข้อมูล payment
    $payment = getPaymentById($conn, $paymentId);
    if (!$payment) return false;

    $mail = createMailer();
    $mail->addAddress(ADMIN_NOTIFY_EMAIL);

    // 3) เตรียมข้อมูล
    $amount = number_format((float)$payment['amount'], 2);
    $orderCode = 'ORD-' . str_pad((string)$paymentId, 5, '0', STR_PAD_LEFT);

    $url = rtrim(BACKEND_URL, '/') .
           '/pages/payments/view.php?id=' . urlencode((string)$paymentId);

    $customer =
        trim(($payment['first_name'] ?? '') . ' ' . ($payment['last_name'] ?? ''))
        ?: 'ไม่ระบุชื่อ';

    // 4) เนื้อหาอีเมล
    $mail->isHTML(true);
    $mail->Subject = "มีสลิปใหม่รอตรวจสอบ: {$orderCode}";

    $mail->Body = "
    <div style='font-family:Arial;line-height:1.6'>
      <h2>มีสลิปใหม่รอตรวจสอบ</h2>
      <p><b>ออเดอร์:</b> {$orderCode}</p>
      <p><b>ลูกค้า:</b> {$customer}</p>
      <p><b>ยอดโอน:</b> {$amount} บาท</p>

      <a href='{$url}'
         style='display:inline-block;padding:10px 16px;
                background:#dc3545;color:#fff;
                text-decoration:none;border-radius:6px'>
        ไปตรวจสลิป
      </a>

      <p style='font-size:12px;color:#666;margin-top:10px'>
        หากปุ่มกดไม่ได้ ใช้ลิงก์นี้:<br>{$url}
      </p>
    </div>
    ";

    // 5) ส่ง
    try {
        $mail->send();
        return true;
    } catch (Exception $e) {
        // rollback ถ้าส่งไม่ผ่าน
        db_exec(
            $conn,
            "UPDATE payments SET admin_notified = 0 WHERE id = ?",
            [$paymentId],
            "i"
        );
        error_log('Email error: ' . $e->getMessage());
        return false;
    }
}
