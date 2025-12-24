<?php
// utils/db_with_log.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config.php';

/**
 * connect DB
 */
function connectDBWithLog(): mysqli
{
    return connectDB();
}

/**
 * เดา action / table_name จาก SQL
 */
function detectActionAndTable(string $sql): array
{
    $trim  = ltrim($sql);
    $first = strtolower(strtok($trim, " \n\t"));

    $action = $first ?: 'other';
    $table  = null;

    switch ($action) {
        case 'select':
            preg_match('/\bfrom\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $m);
            $table = $m[1] ?? null;
            break;
        case 'insert':
            preg_match('/\binsert\s+into\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $m);
            $table = $m[1] ?? null;
            break;
        case 'update':
            preg_match('/\bupdate\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $m);
            $table = $m[1] ?? null;
            break;
        case 'delete':
            preg_match('/\bfrom\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $m);
            $table = $m[1] ?? null;
            break;
    }

    return [$action, $table];
}

/**
 * ดึงข้อมูล row ก่อน/หลัง ตาม table + id
 */
function fetchSnapshot(mysqli $conn, string $table, int $id): ?array
{
    $allowed = ['products', 'product_variants']; // whitelist
    if (!in_array($table, $allowed, true)) {
        return null;
    }

    $sql = "SELECT * FROM `{$table}` WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc() ?: null;
    $stmt->close();
    return $row;
}

/**
 * เดา record_id สำหรับใช้ snapshot จาก SQL + params
 * สมมติรูปแบบ WHERE id = ? (id อยู่ param ตัวสุดท้าย)
 */
function guessRecordIdForSnapshot(string $sql, array $params): ?int
{
    if (empty($params)) {
        return null;
    }

    // สนใจเฉพาะเคส WHERE id = ?
    if (!preg_match('/where\s+`?id`?\s*=\s*\?/i', $sql)) {
        return null;
    }

    // เดาว่า id อยู่ตัวสุดท้ายของ params
    $idx = count($params) - 1;
    $val = $params[$idx] ?? null;

    return is_numeric($val) ? (int)$val : null;
}

/**
 * writeLog() *เวอร์ชันใหม่* รองรับ old/new snapshot
 */
function writeLogWithSnapshot(
    mysqli $conn,
    string $sql,
    array  $params = [],
    string $types  = '',
    string $status = 'success',
    ?string $error = null,
    ?int $recordId = null,
    ?array $oldData = null,
    ?array $newData = null
): void {

    $userId    = $_SESSION['user_id'] ?? null;
    $ip        = $_SERVER['REMOTE_ADDR']     ?? null;
    $ua        = $_SERVER['HTTP_USER_AGENT'] ?? null;

    [$action, $table] = detectActionAndTable($sql);

    // query_text
    $qt = $sql;
    if (!empty($params)) {
        $qt .= ' | params=' . json_encode($params, JSON_UNESCAPED_UNICODE);
    }
    if ($error) {
        $qt .= ' | error=' . $error;
    }

    // json encode
    $oldJson = $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null;
    $newJson = $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null;

    $stmt = $conn->prepare("
        INSERT INTO logs
        (user_id, action, table_name, record_id, query_text, old_data, new_data, status, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $uid = $userId !== null ? (int)$userId : null;
    $rid = $recordId !== null ? (int)$recordId : null;

    $stmt->bind_param(
        "ississssss",
        $uid,
        $action,
        $table,
        $rid,
        $qt,
        $oldJson,
        $newJson,
        $status,
        $ip,
        $ua
    );

    $stmt->execute();
    $stmt->close();
}

function writeLog(
    mysqli $conn,
    string $sql,
    array  $params = [],
    string $types = '',
    string $status = 'success',
    ?string $errorMsg = null,
    ?int $recordId = null
): void {
    // ไม่สนใจ old/new เพิ่มเติมในกรณีเรียกตรง ๆ แบบเดิม
    writeLogWithSnapshot(
        $conn,
        $sql,
        $params,
        $types,
        $status,
        $errorMsg,
        $recordId,
        null,
        null
    );
}

/**
 * db_query() = SELECT/INSERT/UPDATE/DELETE + log snapshot
 */
function db_query(mysqli $conn, string $sql, array $params = [], string $types = "")
{
    $recordId = null;
    $oldData  = null;
    $newData  = null;
    $errorMsg = null;

    [$action, $table] = detectActionAndTable($sql);

    // ถ้าเป็น UPDATE / DELETE ให้ลองเดา id จาก WHERE id = ?
    if (in_array($action, ['update', 'delete'], true)) {
        $recordId = guessRecordIdForSnapshot($sql, $params);
        if ($recordId && $table) {
            $oldData = fetchSnapshot($conn, $table, $recordId);
        }
    }

    try {
        if (!empty($params) && $types !== "") {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            // INSERT → ใช้ insert_id
            if ($action === 'insert') {
                $recordId = $stmt->insert_id ?: $conn->insert_id;
            }

            // หลัง UPDATE/INSERT ถ้ามี recordId → ดึง snapshot ใหม่
            if (in_array($action, ['insert', 'update'], true) && $recordId && $table) {
                $newData = fetchSnapshot($conn, $table, $recordId);
            }

            writeLogWithSnapshot($conn, $sql, $params, $types, 'success', null, $recordId, $oldData, $newData);

            $stmt->close();
            return $result;
        }

        // ไม่มี params
        $result = $conn->query($sql);
        if ($conn->errno) {
            throw new Exception($conn->error);
        }

        if ($action === 'insert') {
            $recordId = $conn->insert_id;
            if ($recordId && $table) {
                $newData = fetchSnapshot($conn, $table, $recordId);
            }
        }

        writeLogWithSnapshot($conn, $sql, [], '', 'success', null, $recordId, $oldData, $newData);

        return $result;
    } catch (Throwable $e) {
        $errorMsg = $e->getMessage();
        writeLogWithSnapshot($conn, $sql, $params, $types, 'error', $errorMsg, $recordId, $oldData, $newData);
        throw $e;
    }
}

/**
 * db_exec() แบบไม่คืน result แต่มี snapshot เช่นกัน
 */
function db_exec(mysqli $conn, string $sql, array $params = [], string $types = '')
{
    $recordId = null;
    $oldData  = null;
    $newData  = null;
    $errorMsg = null;

    [$action, $table] = detectActionAndTable($sql);

    // สำหรับ UPDATE / DELETE เดา id ก่อน execute
    if (in_array($action, ['update', 'delete'], true)) {
        $recordId = guessRecordIdForSnapshot($sql, $params);
        if ($recordId && $table) {
            $oldData = fetchSnapshot($conn, $table, $recordId);
        }
    }

    try {
        if (!empty($params)) {
            if ($types === '') {
                $types = str_repeat('s', count($params));
            }

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param($types, ...$params);
            $stmt->execute();

            $affected = $stmt->affected_rows;

            if ($action === 'insert') {
                $recordId = $stmt->insert_id ?: $conn->insert_id;
            }

            $stmt->close();
        } else {
            $ok = $conn->query($sql);
            if (!$ok) {
                throw new Exception($conn->error);
            }
            $affected = $conn->affected_rows;

            if ($action === 'insert') {
                $recordId = $conn->insert_id;
            }
        }

        // หลัง INSERT/UPDATE ดึง snapshot ใหม่ ถ้ารู้ recordId
        if (in_array($action, ['insert', 'update'], true) && $recordId && $table) {
            $newData = fetchSnapshot($conn, $table, $recordId);
        }

        writeLogWithSnapshot($conn, $sql, $params, $types, 'success', null, $recordId, $oldData, $newData);

        return [
            'ok'        => true,
            'error'     => null,
            'affected'  => $affected,
            'insert_id' => $recordId,
        ];
    } catch (Throwable $e) {
        $errorMsg = $e->getMessage();

        writeLogWithSnapshot($conn, $sql, $params, $types, 'error', $errorMsg, $recordId, $oldData, $newData);

        return [
            'ok'        => false,
            'error'     => $errorMsg,
            'affected'  => 0,
            'insert_id' => $recordId,
        ];
    }
}
