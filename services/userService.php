<?php
// services/UserService.php

function getUserById(mysqli $conn, int $user_id): ?array
{
    $sql = "
        SELECT id, line_uid, display_name, picture_url,
               first_name, last_name, phone, citizen_id, created_at
        FROM users
        WHERE id = ?
        LIMIT 1
    ";

    $res = db_query($conn, $sql, [$user_id], "i");
    if (!$res || $res->num_rows === 0) return null;

    return $res->fetch_assoc();
}

function getUserByLineUid(mysqli $conn, string $line_uid): ?array
{
    $sql = "
        SELECT id, line_uid, display_name, picture_url,
               first_name, last_name, phone, citizen_id, created_at
        FROM users
        WHERE line_uid = ?
        LIMIT 1
    ";

    $res = db_query($conn, $sql, [$line_uid], "s");
    if (!$res || $res->num_rows === 0) return null;

    return $res->fetch_assoc();
}

function createUser(mysqli $conn, array $data): int
{
    $line_uid     = trim($data['line_uid'] ?? '');
    $display_name = trim($data['display_name'] ?? '');
    $picture_url  = trim($data['picture_url'] ?? '');
    $first_name   = trim($data['first_name'] ?? '');
    $last_name    = trim($data['last_name'] ?? '');
    $phone        = trim($data['phone'] ?? '');
    $citizen_id   = trim($data['citizen_id'] ?? '');

    $sql = "
        INSERT INTO users
            (line_uid, display_name, picture_url, first_name, last_name, phone, citizen_id)
        VALUES
            (?, ?, ?, ?, ?, ?, ?)
    ";

    $ok = db_exec($conn, $sql, [
        $line_uid, $display_name, $picture_url,
        $first_name, $last_name, $phone, $citizen_id
    ], "sssssss");

    if (!$ok) throw new Exception("createUser failed");

    return (int)$conn->insert_id;
}

function updateUser(mysqli $conn, int $user_id, array $data): bool
{
    $fields = [];
    $params = [];
    $types  = "";

    $allow = ['display_name','picture_url','first_name','last_name','phone','citizen_id'];

    foreach ($allow as $f) {
        if (array_key_exists($f, $data)) {
            $fields[] = "$f = ?";
            $params[] = trim($data[$f]);
            $types   .= "s";
        }
    }

    if (empty($fields)) return false;

    $params[] = $user_id;
    $types   .= "i";

    $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";

    return (bool)db_exec($conn, $sql, $params, $types);
}
