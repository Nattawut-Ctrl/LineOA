<?php
session_start();
ob_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once SERVICES_PATH . '/paymentIntentService.php';

header('Content-Type: application/json; charset=utf-8');

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
  ob_end_clean();
  http_response_code(401);
  echo json_encode(['ok'=>false,'message'=>'Unauthorized'], JSON_UNESCAPED_UNICODE);
  exit;
}

$conn = connectDBWithLog();
$intent_id = (int)($_POST['intent_id'] ?? 0);
if ($intent_id <= 0) {
  ob_end_clean();
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'intent_id ไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
  exit;
}

$intent = getIntentById($conn, $intent_id);
if (!$intent || (int)$intent['user_id'] !== $user_id) {
  ob_end_clean();
  http_response_code(404);
  echo json_encode(['ok'=>false,'message'=>'ไม่พบรายการชำระเงิน'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $conn->begin_transaction();

  $ok = cancelIntentImmediately($conn, $intent_id, $user_id);
  if (!$ok) {
    throw new Exception('ยกเลิกไม่สำเร็จ - ตรวจสอบสถานะของรายการ');
  }

  $conn->commit();
  ob_end_clean();
  echo json_encode(['ok'=>true, 'message'=>'ยกเลิกสำเร็จแล้ว'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  $conn->rollback();
  ob_end_clean();
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
