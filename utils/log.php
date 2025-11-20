<?php

function log_db_action($conn, $userId, $action, $tableName, $recordId, $queryText, $status = 'success') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO db_logs (user_id, action, table_name, record_id, query_text, status, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ississss",
        $userId,
        $action,
        $tableName,
        $recordId,
        $queryText,
        $status,
        $ip,
        $ua
    );

    $stmt->execute();
    $stmt->close();
}
