<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();
$conn = connectDBWithLog();

header('Content-Type: application/json; charset=utf-8');

$name        = trim((string)($_POST['name'] ?? ''));
$category    = trim((string)($_POST['category'] ?? '')); // products.category เป็น varchar
$unit        = trim((string)($_POST['unit'] ?? ''));
$price       = isset($_POST['price']) ? (float)$_POST['price'] : 0.00;
$description = trim((string)($_POST['description'] ?? ''));

if ($name === '') {
  echo json_encode(['ok' => false, 'error' => 'กรุณากรอกชื่อสินค้า'], JSON_UNESCAPED_UNICODE);
  exit;
}
if ($price < 0) $price = 0;

mysqli_begin_transaction($conn);

try {
  // 1) สร้าง Product
  // ตาราง products ของคุณมี: sku (nullable), name (not null), price (not null), image (nullable), description (nullable),
  // category (nullable), stock (default 0), reserved_stock (not null default 0), unit (nullable), status (enum), created_at
  $insP = db_exec(
    $conn,
    "INSERT INTO products (sku, name, price, image, description, category, stock, reserved_stock, unit, status, created_at)
     VALUES (NULL, ?, ?, NULL, ?, ?, 0, 0, ?, 'active', NOW())",
    [$name, $price, ($description !== '' ? $description : null), ($category !== '' ? $category : null), ($unit !== '' ? $unit : null)],
    "sdsss"
  );

  if (!($insP['ok'] ?? false) || (int)($insP['insert_id'] ?? 0) <= 0) {
    throw new Exception($insP['error'] ?? 'insert products failed');
  }
  $pid = (int)$insP['insert_id'];

  // 2) สร้าง Default Variant 1 ตัว (SKU ตัวขายจริง)
  $sku = 'SKU-' . $pid . '-DEF';

  $insV = db_exec(
    $conn,
    "INSERT INTO product_variants (product_id, sku, variant_name, image, price, stock, reserved_stock)
     VALUES (?, ?, 'มาตรฐาน', NULL, ?, 0, 0)",
    [$pid, $sku, $price],
    "isd"
  );

  if (!($insV['ok'] ?? false) || (int)($insV['insert_id'] ?? 0) <= 0) {
    throw new Exception($insV['error'] ?? 'insert product_variants failed');
  }
  $vid = (int)$insV['insert_id'];

  mysqli_commit($conn);

  echo json_encode([
    'ok' => true,
    'product' => [
      'id' => $pid,
      'name' => $name,
      'category' => $category,
      'unit' => $unit,
      'price' => $price
    ],
    'variant' => [
      'id' => $vid,
      'variant_name' => 'มาตรฐาน',
      'sku' => $sku
    ]
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  mysqli_rollback($conn);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
}
