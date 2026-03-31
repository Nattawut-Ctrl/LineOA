<?php
session_start();
require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';
require_once UTILS_PATH . '/csrf.php';
require_once SERVICES_PATH . '/slipService.php';

require_admin();
$conn = connectDBWithLog();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

csrf_verify($_POST['csrf_token'] ?? null);

$id     = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if ($id <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
    http_response_code(400);
    exit('Invalid input');
}

$conn->begin_transaction();

try {
    if ($status === 'approved') {
        $ok = approvePaymentAndApplyStock($conn, $id);
    } else { // 'rejected'
        $ok = rejectPaymentAndReleaseStock($conn, $id);
    }

    if (!$ok) {
        throw new Exception('Cannot change payment status or update stock');
    }

    $conn->commit();
    // ส่งการแจ้งเตือนผ่าน LINE Notify
    require_once __DIR__ . '/line_notify.php';

    // ดึงข้อมูล payment + user
    $sql = "SELECT p.id, p.amount, u.line_uid
        FROM payments p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    $stmt->close();

    if (!empty($payment['line_uid'])) {

        // ดึงรายการสินค้าแบบ join จริง
        $itemSql = "SELECT 
                    pr.name AS product_name,
                    pv.variant_name,
                    pi.quantity,
                    pi.unit_price
                FROM payment_items pi
                JOIN products pr ON pi.product_id = pr.id
                LEFT JOIN product_variants pv ON pi.variant_id = pv.id
                WHERE pi.payment_id = ?";

        $itemStmt = $conn->prepare($itemSql);
        $itemStmt->bind_param("i", $id);
        $itemStmt->execute();
        $itemResult = $itemStmt->get_result();

        $items = [];
        while ($row = $itemResult->fetch_assoc()) {
            $items[] = $row;
        }
        $itemStmt->close();

        sendLineNotification(
            $payment['line_uid'],
            $payment['id'],
            $payment['amount'],
            $status,
            $items
        );
    }
    header('Location: ' . rtrim(BACKEND_URL, '/') . '/pages/payments/list.php?ok=1');
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    error_log($e->getMessage());
    header('Location: ' . rtrim(BACKEND_URL, '/') . '/pages/payments/list.php?error=1');
    exit;
}
