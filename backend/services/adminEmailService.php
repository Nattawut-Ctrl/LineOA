<?php
// services/adminEmailService.php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../mail_config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once SERVICES_PATH . '/slipService.php';

// Cloudinary logo URL
if (!defined('EMAIL_LOGO_URL')) {
    define('EMAIL_LOGO_URL', 'https://res.cloudinary.com/dfs4n2p9b/image/upload/v1769402959/logo_chivwy.png');
}

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
    $mail->SMTPDebug  = 2; // ให้ log ข้อมูล SMTP สำหรับ debug

    // ปิด SSL verification สำหรับ development (อย่าใช้ใน production!)
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        )
    );

    $mail->CharSet = 'UTF-8';
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

    return $mail;
}

/**
 * ส่ง Email แจ้ง admin เมื่อมีสลิปใหม่ (ส่งครั้งเดียว)
 */
function notifyAdminNewSlipOnce(mysqli $conn, int $paymentId): bool
{
    try {
        error_log("=== notifyAdminNewSlipOnce START for payment_id: {$paymentId} ===");

        // 1) กันซ้ำ (atomic)
        $lock = db_exec(
            $conn,
            "UPDATE payments SET admin_notified = 1 WHERE id = ? AND admin_notified = 0",
            [$paymentId],
            "i"
        );

        error_log("Lock result: " . json_encode($lock));

        if (($lock['affected'] ?? 0) <= 0) {
            error_log("Already notified or payment not found");
            return false; // เคยส่งแล้ว
        }

        // 2) โหลดข้อมูล payment
        $payment = getPaymentById($conn, $paymentId);
        if (!$payment) {
            throw new Exception("Payment not found: {$paymentId}");
        }

        error_log("Payment data loaded: " . json_encode([$payment['id'], $payment['amount'], $payment['first_name']]));

        $mail = createMailer();
        $adminEmail = defined('ADMIN_NOTIFY_EMAIL') ? constant('ADMIN_NOTIFY_EMAIL') : 'nattawutte65@nu.ac.th';

        error_log("Admin email: {$adminEmail}");

        $mail->addAddress($adminEmail);

        // 3) เตรียมข้อมูล
        $amount = number_format((float)$payment['amount'], 2);
        $orderCode = 'ORD-' . str_pad((string)$paymentId, 5, '0', STR_PAD_LEFT);

        $url = rtrim(BACKEND_URL, '/') .
            '/pages/payments/view.php?id=' . urlencode((string)$paymentId);

        $customer =
            trim(($payment['first_name'] ?? '') . ' ' . ($payment['last_name'] ?? ''))
            ?: 'ไม่ระบุชื่อ';

        // 4) เตรียมข้อมูลสำหรับอีเมล
        $mail->isHTML(true);
        $mail->Subject = "มีสลิปใหม่รอตรวจสอบ: {$orderCode}";

        // ตรวจสอบและใช้ Cloudinary logo
        $logoUrl = defined('EMAIL_LOGO_URL') ? EMAIL_LOGO_URL : '';
        $logoHtml = !empty($logoUrl)
            ? "<img src='" . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . "' alt='Logo' width='80' height='80' style='border-radius:8px; background:#ffffff; padding:4px; margin-right:14px; object-fit:contain;'>"
            : "";


        $mail->Body = "
        <div style='background:#f5f3ff; padding:20px; font-family:Kanit, sans-serif;'>
            <div style='max-width:600px; margin:auto; background:white; border-radius:12px; overflow:hidden; box-shadow:0 6px 18px rgba(0,0,0,0.12);'>
                <!-- Header -->
                <div style='background:linear-gradient(90deg,#ad28d9,#b73aed); padding:18px; display:flex; align-items:center; color:#ffffff;'>
                    " . $logoHtml . "
                    <div>
                        <h2 style='margin-bottom:10px; font-size:22px; font-weight:600;'>ศูนย์พัฒนลักษณ์ มหาวิทยาลัยนเรศวร</h2>
                        <div style='font-size:14px; opacity:0.9;'>มีรายการคำสั่งซื้อใหม่ กรุณาตรวจสอบสลิปการชำระเงิน</div>
                    </div>
                </div>

                <!-- Body -->
                <div style='padding:20px; color:#333; line-height:1.7;'>
                    <p>เรียนเจ้าหน้าที่,</p>
                    <p>มีคำสั่งซื้อใหม่เข้ามาในระบบ และลูกค้าได้ทำการแนบสลิปการชำระเงินแล้ว กรุณาตรวจสอบรายละเอียดด้านล่าง</p>

                    <!-- Order info -->
                    <div style='background:#fafafa; border-left:4px solid #ad28d9; padding:12px; margin:14px 0;'>
                        <p style='margin:0;'><b>เลขที่คำสั่งซื้อ :</b> " . htmlspecialchars($orderCode, ENT_QUOTES, 'UTF-8') . "</p>
                        <p style='margin:0;'><b>ชื่อลูกค้า :</b> " . htmlspecialchars($customer, ENT_QUOTES, 'UTF-8') . "</p>
                    </div>

                    <!-- Payment summary -->
                    <div style='background:#fafafa; border-left:4px solid #b73aed; padding:12px; margin:14px 0;'>
                        <p style='margin:0;'><b>ยอดชำระเงิน :</b> ฿" . htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') . "</p>
                        <p style='margin:0;'><b>สถานะ :</b> รอตรวจสอบสลิป</p>
                    </div>

                    <p style='margin-top:20px;'>กรุณาเข้าสู่ระบบเพื่อทำการตรวจสอบสลิป และดำเนินการอนุมัติหรือปฏิเสธการชำระเงิน</p>

                    <!-- CTA -->
                    <div style='text-align:center; margin:30px 0;'>
                        <a href='" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "' style='background:#ad28d9; color:white; padding:12px 26px; border-radius:8px; text-decoration:none; font-weight:600; display:inline-block;'>เปิดคำสั่งซื้อในระบบ</a>
                    </div>

                    <hr style='border:none; border-top:1px solid #ddd; margin:26px 0;'>

                    <p style='font-size:14px; color:#777; text-align:center; margin:0;'>อีเมลฉบับนี้เป็นการแจ้งเตือนอัตโนมัติจากระบบร้านค้า<br>กรุณาอย่าตอบกลับอีเมลฉบับนี้</p>
                </div>
            </div>
        </div>
        ";

        // 5) ส่ง
        $mail->send();
        error_log("Email sent successfully for payment {$paymentId} to {$adminEmail}");
        error_log("=== notifyAdminNewSlipOnce SUCCESS ===");
        return true;
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        error_log("=== notifyAdminNewSlipOnce FAILED ===");
        error_log("Exception: {$errorMsg}");

        // rollback ถ้าส่งไม่ผ่าน
        db_exec(
            $conn,
            "UPDATE payments SET admin_notified = 0 WHERE id = ?",
            [$paymentId],
            "i"
        );
        return false;
    }
}
