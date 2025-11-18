<?php
// utils/db_with_log.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

/**
 * ใช้แทน connectDB() เดิม
 */
function connectDBWithLog(): mysqli
{
    return connectDB();
}

/**
 * เดาตัว action / table_name จาก SQL
 */
function detectActionAndTable(string $sql): array
{
    $trim  = ltrim($sql);
    $first = strtolower(strtok($trim, " \n\t"));   // select / insert / update / delete / ...

    $action = $first ?: 'other';
    $table  = null;

    switch ($action) {
        case 'select':
            if (preg_match('/\bfrom\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $m)) {
                $table = $m[1];
            }
            break;
        case 'insert':
            if (preg_match('/\binsert\s+into\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $m)) {
                $table = $m[1];
            }
            break;
        case 'update':
            if (preg_match('/\bupdate\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $m)) {
                $table = $m[1];
            }
            break;
        case 'delete':
            if (preg_match('/\bfrom\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $m)) {
                $table = $m[1];
            }
            break;
        default:
            // อื่น ๆ เช่น ALTER, CREATE
            break;
    }

    return [$action, $table];
}

/**
 * เขียน log ลงตาราง logs
 */
function writeLog(
    mysqli $conn,
    string $sql,
    array  $params = [],
    string $types = '',
    string $status = 'success',
    ?string $errorMsg = null,
    ?int $recordId = null
): void {
    // user ปัจจุบัน (อาจ NULL ได้)
    $userId    = $_SESSION['user_id'] ?? null;
    $ip        = $_SERVER['REMOTE_ADDR']      ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT']  ?? null;

    // เดา action / table_name
    [$action, $tableName] = detectActionAndTable($sql);

    // query_text: เก็บทั้ง SQL + params
    $queryText = $sql;
    if (!empty($params)) {
        $queryText .= ' | params=' . json_encode($params, JSON_UNESCAPED_UNICODE);
    }
    if ($errorMsg) {
        $queryText .= ' | error=' . $errorMsg;
    }

    $stmt = $conn->prepare("
        INSERT INTO logs
        (user_id, action, table_name, record_id, query_text, status, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // user_id / record_id ให้เป็น int หรือ NULL
    $uid = $userId !== null ? (int)$userId : null;
    $rid = $recordId !== null ? (int)$recordId : null;

    $stmt->bind_param(
        "ississss",
        $uid,
        $action,
        $tableName,
        $rid,
        $queryText,
        $status,
        $ip,
        $userAgent
    );

    $stmt->execute();
    $stmt->close();
}

/**
 * ตัวช่วย query ทุกอย่าง + log อัตโนมัติ
 *
 * - ใช้กับ SELECT / INSERT / UPDATE / DELETE ได้หมด
 * - ถ้าใช้ params → ส่ง $params + $types (เหมือน bind_param)
 * - ถ้าไม่ใช้ params → ส่งแค่ $sql ก็พอ
 *
 * return:
 *   - SELECT → mysqli_result
 *   - INSERT/UPDATE/DELETE → mysqli_result|null (แล้วแต่ use case)
 */
function db_query(mysqli $conn, string $sql, array $params = [], string $types = "")
{
    $recordId = null;   // ค่า default

    try {
        // มี parameter
        if (!empty($params) && $types !== "") {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param($types, ...$params);
            $stmt->execute();

            // ถ้าเป็น SELECT จะมี result set
            $result = $stmt->get_result();

            // เดา action
            [$action, $_] = detectActionAndTable($sql);

            // ถ้าเป็น INSERT → เก็บ insert_id
            if ($action === 'insert') {
                $recordId = $stmt->insert_id ?: $conn->insert_id;
            }
            // ถ้าเป็น UPDATE/DELETE → เดาว่า param ตัวแรกคือ id (อยากแม่นกว่านี้ค่อยปรับกรณีต่อกรณีเอา)
            elseif (in_array($action, ['update', 'delete'], true) && isset($params[0])) {
                $recordId = is_numeric($params[0]) ? (int)$params[0] : null;
            }

            writeLog($conn, $sql, $params, $types, 'success', null, $recordId);

            $stmt->close();
            return $result;
        }

        // ไม่มี parameter
        $result = $conn->query($sql);
        if ($conn->errno) {
            throw new Exception($conn->error, $conn->errno);
        }

        [$action, $_] = detectActionAndTable($sql);

        if ($action === 'insert') {
            $recordId = $conn->insert_id;
        }

        writeLog($conn, $sql, [], '', 'success', null, $recordId);

        return $result;
    } catch (Throwable $e) {
        // log error ด้วย
        writeLog($conn, $sql, $params, $types, 'error', $e->getMessage(), $recordId);
        // แล้วค่อยโยน error ต่อ (จะได้เห็นบนหน้า dev)
        throw $e;
    }
}

function db_exec(mysqli $conn, string $sql, array $params = [], string $types = '')
{
    $start = microtime(true);
    $error = '';
    $affected = 0;

    if (empty($params)) {
        // ไม่มี params → ยิงตรง (ใช้เฉพาะกรณีไม่มี input จาก user)
        $ok = $conn->query($sql);
        $error = $conn->error;
        $affected = $conn->affected_rows;
    } else {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error = $conn->error;
            $ok = false;
        } else {
            if ($types === '') {
                $types = str_repeat('s', count($params));
            }
            $stmt->bind_param($types, ...$params);
            $ok = $stmt->execute();
            $error = $stmt->error;
            $affected = $stmt->affected_rows;
            $stmt->close();
        }
    }

    $duration = microtime(true) - $start;

    // log ลงตาราง query_logs เหมือน db_query
    db_query($conn, $sql, $params, $error, $duration);

    return [
        'ok'        => $ok,
        'error'     => $error,
        'affected'  => $affected,
        'duration'  => $duration,
    ];
}
