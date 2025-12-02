<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';
require_once UTILS_PATH . '/product_image_helper.php';

require_admin();
$conn = connectDBWithLog();

// 1) รับค่าจากฟอร์ม
$product_id        = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$addStockArr       = isset($_POST['add_stock']) && is_array($_POST['add_stock']) ? $_POST['add_stock'] : [];
$productAddStock   = isset($_POST['product_add_stock']) ? (int)$_POST['product_add_stock'] : 0;
$deleteReq         = isset($_POST['variant_image_delete']) && is_array($_POST['variant_image_delete']) ? $_POST['variant_image_delete'] : [];
$files             = $_FILES['variant_image'] ?? null;

// validate เบื้องต้น
if ($product_id <= 0) {
    header("Location: addStock.php?error=invalid_input");
    exit;
}

// ต้องมีอย่างน้อย 1 อย่าง: เพิ่มสต็อก variant หรือเพิ่มสต็อกสินค้าหลัก
$hasVariantStock = false;
if (!empty($addStockArr)) {
    foreach ($addStockArr as $vid => $val) {
        if ((int)$val > 0) {
            $hasVariantStock = true;
            break;
        }
    }
}

if (!$hasVariantStock && $productAddStock <= 0) {
    header("Location: addStock.php?error=invalid_input");
    exit;
}

// เช็กว่าสินค้ามีอยู่จริงไหม
$resProduct = db_query(
    $conn,
    "SELECT id FROM products WHERE id = ?",
    [$product_id],
    "i"
);

if (!$resProduct || $resProduct->num_rows === 0) {
    header("Location: addStock.php?error=product_not_found");
    exit;
}

// แปลง deleteReq เป็น set
$deleteSet = [];
foreach ($deleteReq as $vid) {
    $deleteSet[(int)$vid] = true;
}

// -----------------------------
// กรณีมีการเพิ่มสต็อกใน variant
// -----------------------------
if ($hasVariantStock) {

    // ดึง variant ทั้งหมดของสินค้านี้
    $resVariants = db_query(
        $conn,
        "SELECT id, stock, image 
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

        // 1) เพิ่มสต็อก
        $inc = isset($addStockArr[$vid]) ? (int)$addStockArr[$vid] : 0;
        if ($inc > 0) {
            db_exec(
                $conn,
                "UPDATE product_variants
                 SET stock = stock + ?
                 WHERE id = ? AND product_id = ?",
                [$inc, $vid, $product_id],
                "iii"
            );
        }

        // 2) จัดการรูป (อัปโหลดใหม่ / ลบ)
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

            $uploaded = uploadImageFile($fileArr, 'uploads/variants');
            if ($uploaded) {
                $newImagePath = $uploaded;
            }
        }

        $finalImage = $oldImage;

        if ($needDelete) {
            $finalImage = null;     // ลบรูปเดิม
        }

        if ($newImagePath !== null) {
            $finalImage = $newImagePath;   // ใช้รูปใหม่
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

            // ลบไฟล์เก่าออกจากเครื่อง (ถ้าไม่ใช่ URL) เมื่อมีการเปลี่ยน/ลบ
            if ($oldImage && ($needDelete || $newImagePath) && $oldImage !== $finalImage) {
                deleteImageFileIfLocal($oldImage);
            }
        }
    }

    // คำนวณ stock รวมใหม่ของ product = SUM(stock) ของทุก variant
    $resSum = db_query(
        $conn,
        "SELECT COALESCE(SUM(stock), 0) AS total_stock
         FROM product_variants
         WHERE product_id = ?",
        [$product_id],
        "i"
    );

    $totalStock = 0;
    if ($resSum) {
        $rowSum     = $resSum->fetch_assoc();
        $totalStock = (int)$rowSum['total_stock'];
    }

    db_exec(
        $conn,
        "UPDATE products SET stock = ? WHERE id = ?",
        [$totalStock, $product_id],
        "ii"
    );

    $successKey = 'variant_stock_added';

// -----------------------------
// กรณีไม่มี variant → เพิ่มสต็อกสินค้าโดยตรง
// -----------------------------
} else {

    $resultProduct = db_exec(
        $conn,
        "UPDATE products
         SET stock = stock + ?
         WHERE id = ?",
        [$productAddStock, $product_id],
        "ii"
    );

    if (!$resultProduct['ok'] || $resultProduct['affected'] <= 0) {
        header("Location: addStock.php?error=product_stock_update_failed");
        exit;
    }

    $successKey = 'product_stock_added';
}

// เสร็จ → redirect กลับไปหน้า addStock
header("Location: addStock.php?success=" . $successKey);
exit;
