<?php
// utils/upload_image.php
require_once dirname(__DIR__, 2) . '/config.php';

use Cloudinary\Api\Upload\UploadApi;
use GuzzleHttp\RequestOptions;

function uploadImageWithFallback(array $file, string $subDir = 'products', string $cloudFolder = 'line-shop/products'): ?string
{
    // 0) เช็คว่าไฟล์โอเคไหม
    if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmpPath  = $file['tmp_name'];
    $origName = $file['name'] ?? 'unknown';

    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) {
        return null;
    }

    $logDir = BASE_PATH . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . '/upload_image.log';

    $log = function (string $msg) use ($logFile) {
        @error_log("[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", 3, $logFile);
    };

    $imagePath = null;

    // ===== 1) พยายามอัปโหลดไป Cloudinary ก่อนเสมอ =====
    if (USE_CLOUDINARY && file_exists(UTILS_PATH . '/cloudinary_config.php')) {
        require_once UTILS_PATH . '/cloudinary_config.php';

        if (class_exists('\\Cloudinary\\Api\\Upload\\UploadApi')) {
            try {
                $log("CLOUDINARY: trying upload {$origName} to folder={$cloudFolder}");
                $result = (new UploadApi())->upload(
                    $tmpPath,
                    [
                        'folder'       => $cloudFolder,
                        RequestOptions::VERIFY => false,
                    ]
                );

                if (!empty($result['secure_url'])) {
                    $imagePath = $result['secure_url'];
                    $log("CLOUDINARY: success -> {$imagePath}");
                } else {
                    $log("CLOUDINARY: no secure_url from response=".json_encode($result));
                }
            } catch (\Throwable $e) {
                $log("CLOUDINARY: exception -> ".$e->getMessage());
            }
        } else {
            $log("CLOUDINARY: UploadApi class not found");
        }
    } else {
        $log("CLOUDINARY: disabled or cloudinary_config.php not found");
    }

    // ===== 2) ถ้า Cloudinary ไม่ได้ ให้เก็บเป็น local =====
    if ($imagePath === null) {
        $targetDir = rtrim(UPLOAD_BASE_DIR, '/\\') . '/' . trim($subDir, '/\\');
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
            $log("LOCAL: created dir {$targetDir}");
        }

        $newName   = uniqid('img_', true) . '.' . $ext;
        $targetFs  = $targetDir . '/' . $newName;

        if (!move_uploaded_file($tmpPath, $targetFs)) {
            $log("LOCAL: move_uploaded_file FAILED for {$origName}");
            return null;
        }

        // path ที่จะเก็บลง DB เช่น storage/uploads/products/img_xxx.jpg
        $imagePath = rtrim(UPLOAD_BASE_URL_PATH, '/\\') . '/' . trim($subDir, '/\\') . '/' . $newName;
        $log("LOCAL: saved {$origName} -> {$imagePath}");
    }

    return $imagePath;
}