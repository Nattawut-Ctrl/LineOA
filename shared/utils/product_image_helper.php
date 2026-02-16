<?php
// utils/product_image_helper.php
require_once dirname(__DIR__, 2) . '/config.php';

function buildImageUrlFromPath(?string $path): string
{
    if (empty($path)) {
        return '';
    }

    // ถ้าเป็น URL เต็มอยู่แล้ว
    if (preg_match('#^https?://#', $path)) {
        return $path;
    }

    // กัน ../
    while (strpos($path, '../') === 0) {
        $path = substr($path, 3);
    }

    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function getProductMainImageUrl(mysqli $conn, int $productId): string
{
    // 1) products.image
    $res = db_query(
        $conn,
        "SELECT image FROM products WHERE id = ?",
        [$productId],
        "i"
    );
    if ($res && ($row = $res->fetch_assoc()) && !empty($row['image'])) {
        return buildImageUrlFromPath($row['image']);
    }

    // 2) gallery
    $res2 = db_query(
        $conn,
        "SELECT image_path 
         FROM product_images 
         WHERE product_id = ?
         ORDER BY sort_order ASC, id ASC
         LIMIT 1",
        [$productId],
        "i"
    );
    if ($res2 && ($row2 = $res2->fetch_assoc()) && !empty($row2['image_path'])) {
        return buildImageUrlFromPath($row2['image_path']);
    }

    // 3) รูปจาก variant
    $res3 = db_query(
        $conn,
        "SELECT image 
         FROM product_variants 
         WHERE product_id = ? AND image IS NOT NULL AND image <> ''
         ORDER BY id ASC
         LIMIT 1",
        [$productId],
        "i"
    );
    if ($res3 && ($row3 = $res3->fetch_assoc()) && !empty($row3['image'])) {
        return buildImageUrlFromPath($row3['image']);
    }

    // 4) default
    return rtrim(SHARED_ASSETS_URL, '/') . '/img/no-image.png';
}

function getProductGallery(mysqli $conn, int $productId): array
{
    $items = [];

    $res = db_query(
        $conn,
        "SELECT id, image_path, cloudinary_public_id, sort_order
         FROM product_images
         WHERE product_id = ?
         ORDER BY sort_order ASC, id ASC",
        [$productId],
        "i"
    );

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $row['url'] = buildImageUrlFromPath($row['image_path']);
            $items[]    = $row;
        }
    }

    return $items;
}

function deleteImageFileIfLocal(?string $dbPath): void
{
    if (empty($dbPath)) return;

    if (preg_match('#^https?://#', $dbPath)) {
        return;
    }

    $p = ltrim($dbPath, '/');

    // ถ้าไฟล์อยู่ใต้ storage/uploads ให้ map ไป UPLOAD_BASE_DIR
    $baseUrlPath = trim(UPLOAD_BASE_URL_PATH, '/');
    if ($baseUrlPath !== '' && strpos($p, $baseUrlPath . '/') === 0) {
        $relative = substr($p, strlen($baseUrlPath) + 1); // ตัด "storage/uploads/"
        $full = rtrim(UPLOAD_BASE_DIR, '/\\') . '/' . $relative;
    } else {
        // fallback: ไฟล์ relative ต่อ root โปรเจกต์
        $full = rtrim(BASE_PATH, '/\\') . '/' . $p;
    }

    if (is_file($full)) {
        @unlink($full);
    }
}

function uploadImageFile(array $file, string $subDir = 'variants'): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return null;
    }

    // BASE/storage/uploads/<subDir>
    $targetDir = rtrim(UPLOAD_BASE_DIR, '/\\') . '/' . trim($subDir, '/\\');
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
    }

    $fileName = uniqid('img_', true) . '.' . $ext;
    $fullPath = $targetDir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        return null;
    }

    // storage/uploads/<subDir>/<fileName>
    return rtrim(UPLOAD_BASE_URL_PATH, '/\\') . '/' . trim($subDir, '/\\') . '/' . $fileName;
}

function loadVariantFallbackImages(mysqli $conn, array $productIds): array
{
    if (empty($productIds)) return [];

    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $types        = str_repeat('i', count($productIds));

    $sql = "
        SELECT pv.product_id, pv.image
        FROM product_variants pv
        WHERE pv.product_id IN ($placeholders)
          AND pv.image IS NOT NULL
          AND pv.image <> ''
        GROUP BY pv.product_id
    ";

    $res = db_query($conn, $sql, $productIds, $types);
    $map = [];

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $map[(int)$row['product_id']] = $row['image'];
        }
    }

    return $map;
}

function optimizeCloudinaryUrl(string $url, string $transform = 'f_auto,q_auto,w_800')
{
    if (!$url) return '';

    if (!preg_match('#res\.cloudinary\.com#', $url)) {
        return $url;
    }

    return str_replace('/upload/', '/upload/' . $transform . '/', $url);
}
