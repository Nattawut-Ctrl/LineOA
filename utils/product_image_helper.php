<?php
// utils/product_image_helper.php

require_once __DIR__ . '/../config.php';

/**
 * แปลง path ใน DB → URL เต็ม (เหมือน buildImageUrl เดิม)
 * ถ้ามีอยู่แล้วที่อื่น จะใช้ของเดิมก็ได้
 */
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
    return rtrim(BASE_URL, '/') . '/assets/no-image.png';
}

/**
 * ดึง gallery ทั้งหมดสำหรับหน้า product-detail หรือ admin
 */
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

/**
 * ลบไฟล์รูปในเครื่อง (ไม่ยุ่งกับ Cloudinary / URL ที่เป็น http)
 * $dbPath = path ที่เก็บใน DB เช่น "uploads/products/xxx.jpg"
 */
function deleteImageFileIfLocal(?string $dbPath): void
{
    if (empty($dbPath)) return;

    // ถ้าเป็น URL (เช่น Cloudinary) ไม่ต้องลบ
    if (preg_match('#^https?://#', $dbPath)) {
        return;
    }

    // ปรับ ROOT ให้ตรงกับโปรเจกต์จริง
    $root = dirname(__DIR__); // โฟลเดอร์โปรเจกต์หลัก
    $full = $root . '/' . ltrim($dbPath, '/');

    if (is_file($full)) {
        @unlink($full);
    }
}

/**
 * อัปโหลดรูป (ใช้ได้ทั้ง product และ variant)
 * คืนค่าเป็น path สำหรับเก็บใน DB เช่น "uploads/variants/xxx.jpg"
 */
function uploadImageFile(array $file, string $subDir = 'uploads/variants'): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return null;
    }

    $root     = dirname(__DIR__); // โฟลเดอร์โปรเจกต์หลัก
    $targetDir = $root . '/' . trim($subDir, '/');

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = uniqid('img_', true) . '.' . $ext;
    $fullPath = $targetDir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        return null;
    }

    // path ที่จะเก็บใน DB
    $dbPath = trim($subDir, '/') . '/' . $fileName;
    return $dbPath;
}

/**
 * ใช้ใน "รายการสินค้า" :
 * ถ้า product.image ว่าง → ไปดึงรูปจาก variant ตัวไหนก็ได้ที่มีรูป
 * คืนค่า array [product_id => image_path]
 */
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

    // ถ้าไม่ใช่ Cloudinary ก็ไม่ยุ่ง
    if (!preg_match('#res\.cloudinary\.com#', $url)) {
        return $url;
    }

    // แทรก transform เข้าไปหลังคำว่า /upload/
    return str_replace('/upload/', '/upload/' . $transform . '/', $url);
}
