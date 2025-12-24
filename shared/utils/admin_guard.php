<?php
// utils/admin_guard.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config.php';

function require_admin(): void
{
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
              (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

    if (empty($_SESSION['admin_id'])) {
        if ($isAjax) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'unauthorized']);
        } else {
            header("Location: " . BACKEND_URL . "/Users/ad_login.php");
        }
        exit;
    }
}
