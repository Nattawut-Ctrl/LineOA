<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once SERVICES_PATH . '/slipService.php';
require_once UTILS_PATH . '/admin_guard.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

$conn = connectDBWithLog();

try {
    $count = getPendingSlipCount($conn);
    $rows   = getPendingSlipNotifications($conn, 10);

    $items = [];
    foreach ($rows as $r) {
        $slipId  = (int)$r['payment_id'];
        $first   = $r['first_name'] ?? '';
        $last    = $r['last_name'] ?? '';
        $cust    = trim($first . ' ' . $last) ?: 'ลูกค้าไม่ระบุชื่อ';
        $amount  = (float)($r['amount'] ?? 0);
        $created = $r['created_at'] ?? null;

        $orderCode = 'ORD-' . str_pad((string)$slipId, 5, '0', STR_PAD_LEFT);

        $timeText = '';
        if ($created) {
            $dt = new DateTime($created);
            $timeText = $dt->format('d/m H:i');
        }

        $items[] = [
            'id'           => $slipId,
            'order_code'   => $orderCode,
            'customer'     => $cust,
            'amount'       => $amount,
            'amount_text'  => number_format($amount, 2),
            'time_text'    => $timeText,
        ];
    }

    echo json_encode([
        'ok'    => true,
        'count' => (int)$count,
        'items' => $items,
    ]);
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage(),
    ]);
}
