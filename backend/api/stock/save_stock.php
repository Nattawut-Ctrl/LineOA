<?php
session_start();
require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';
require_once UTILS_PATH . '/product_image_helper.php';

require_admin();
$conn = connectDBWithLog();

// 1) รับค่าจากฟอร์ม
$product_id        = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

$addStockArr = [];
if (isset($_POST['add_stock']) && is_array($_POST['add_stock'])) {
    $addStockArr = $_POST['add_stock'];
} elseif (isset($_POST['variant_stock']) && is_array($_POST['variant_stock'])) {
    $addStockArr = $_POST['variant_stock'];
}

$productAddStock = 0;
if (isset($_POST['product_add_stock'])) {
    $productAddStock = (int)$_POST['product_add_stock'];
} elseif (isset($_POST['product_stock'])) {
    $productAddStock = (int)$_POST['product_stock'];
}

$deleteReq         = isset($_POST['variant_image_delete']) && is_array($_POST['variant_image_delete']) ? $_POST['variant_image_delete'] : [];
$files             = $_FILES['variant_image'] ?? null;

$REDIRECT_ADD_STOCK = rtrim(BACKEND_URL, '/') . '/pages/stock/addStock.php';

function goAddStock(string $qs): void
{
    global $REDIRECT_ADD_STOCK;
    header('Location: ' . $REDIRECT_ADD_STOCK . (str_contains($qs, '?') ? $qs : ('?' . ltrim($qs, '?'))));
    exit;
}

// validate เบื้องต้น
if ($product_id <= 0) {
    goAddStock('error=invalid_input');
}

// ---------------------------------------------------------------------
// Shopee-like policy: ห้ามเพิ่มสต็อกผ่าน endpoint นี้ (ต้องรับเข้าผ่านใบรับของ)
// Endpoint นี้คงไว้เพื่อ "จัดการรูปภาพ variant" เท่านั้น
// ---------------------------------------------------------------------

// ตรวจว่ามีการพยายามเพิ่มสต็อกหรือไม่
$hasVariantStock = false;
if (!empty($addStockArr)) {
    foreach ($addStockArr as $vid => $val) {
        if ((int)$val > 0) {
            $hasVariantStock = true;
            break;
        }
    }
}
$hasProductStock = ($productAddStock > 0);

// งานรูปภาพ (รองรับทั้งลบรูป และอัปโหลดรูปใหม่)
$hasImageDelete = (!empty($deleteReq));
$hasImageUpload = false;
if (!empty($files) && isset($files['name'])) {
    if (is_array($files['name'])) {
        foreach ($files['name'] as $n) {
            if (!empty($n)) {
                $hasImageUpload = true;
                break;
            }
        }
    } else {
        $hasImageUpload = (!empty($files['name']));
    }
}
$hasImageOp = ($hasImageDelete || $hasImageUpload);

// ❌ บล็อกการเพิ่มสต็อก (ต้องไปทำผ่าน Goods Receipts เท่านั้น)
if ($hasVariantStock || $hasProductStock) {
    goAddStock('error=use_receipt_flow');
}

// ถ้าไม่มีงานรูปภาพเลย ถือว่า invalid
if (!$hasImageOp) {
    goAddStock('error=invalid_input');
}

// เช็กว่าสินค้ามีอยู่จริงไหม
$resProduct = db_query(
    $conn,
    "SELECT id FROM products WHERE id = ?",
    [$product_id],
    "i"
);

if (!$resProduct || $resProduct->num_rows === 0) {
    goAddStock('error=product_not_found');
}

// แปลง deleteReq เป็น set
$deleteSet = [];
foreach ($deleteReq as $vid) {
    $deleteSet[(int)$vid] = true;
}

// -----------------------------
// จัดการรูป (อัปโหลดใหม่ / ลบ) เท่านั้น
// -----------------------------

// ดึง variant ทั้งหมดของสินค้านี้
$resVariants = db_query(
    $conn,
    "SELECT id, image 
     FROM product_variants 
     WHERE product_id = ?",
    [$product_id],
    "i"
);

$variantRows = [];
if ($resVariants) {
    while ($r = $resVariants->fetch_assoc()) {
        $variantRows[(int)$r['id']] = $r;
    }
}

foreach ($variantRows as $vid => $row) {
    $oldImage = $row['image'];

    // 1) จัดการรูป (อัปโหลดใหม่ / ลบ)
    $needDelete   = isset($deleteSet[$vid]);
    $newImagePath = null;

    // ถ้ามีไฟล์อัปโหลดสำหรับ variant นี้
    if ($files && isset($files['name'][$vid]) && $files['error'][$vid] !== UPLOAD_ERR_NO_FILE) {
        $fileArr = [
            'name'     => $files['name'][$vid],
            'type'     => $files['type'][$vid],
            'tmp_name' => $files['tmp_name'][$vid],
            'error'    => $files['error'][$vid],
            'size'     => $files['size'][$vid],
        ];

        $uploaded = uploadImageFile($fileArr, 'variants');
        if ($uploaded) {
            $newImagePath = $uploaded;
        }
    }

    $finalImage = $oldImage;

    if ($needDelete) {
        $finalImage = null;
    }
    if ($newImagePath !== null) {
        $finalImage = $newImagePath;
    }

    if ($finalImage !== $oldImage) {
        db_exec(
            $conn,
            "UPDATE product_variants
             SET image = ?
             WHERE id = ? AND product_id = ?",
            [$finalImage, $vid, $product_id],
            "sii"
        );

        // ลบไฟล์เก่าออกจากเครื่อง (ถ้าไม่ใช่ URL)
        if ($oldImage && ($needDelete || $newImagePath) && $oldImage !== $finalImage) {
            deleteImageFileIfLocal($oldImage);
        }
    }
}

goAddStock('success=' . urlencode('variant_image_updated'));
