<?php

function getAllProductsWithVariants(mysqli $conn): array
{
    $products = [];

    // --- ดึงสินค้า พร้อม available_stock ---
    $sqlProducts = "
        SELECT 
            id,
            name,
            price,
            image,
            description,
            category,
            stock,
            reserved_stock,
            (stock - reserved_stock) AS available_stock
        FROM products
        ORDER BY id DESC
    ";
    $resProd = db_query($conn, $sqlProducts);

    if ($resProd && $resProd->num_rows > 0) {
        while ($row = $resProd->fetch_assoc()) {
            $pid = (int)$row['id'];
            $row['available_stock'] = max(0, (int)$row['available_stock']);
            $products[$pid] = $row;
            $products[$pid]['variants'] = [];
        }
    }

    if (empty($products)) {
        return [];
    }

    // --- ดึง variants พร้อม available_stock ---
    $sqlVariants = "
        SELECT 
            id,
            product_id,
            variant_name,
            price,
            stock,
            reserved_stock,
            image,
            (stock - reserved_stock) AS available_stock
        FROM product_variants
        WHERE product_id IN (" . implode(',', array_keys($products)) . ")
        ORDER BY id ASC
    ";
    $resVar = db_query($conn, $sqlVariants);

    if ($resVar && $resVar->num_rows > 0) {
        while ($v = $resVar->fetch_assoc()) {
            $pid = (int)$v['product_id'];

            if (!isset($products[$pid])) continue;

            $v['available_stock'] = max(0, (int)$v['available_stock']);
            $products[$pid]['variants'][] = $v;
        }
    }

    // --- ถ้า product ไม่มีรูปหลัก → ใช้รูป variant ---
    foreach ($products as $pid => &$prod) {
        if (empty($prod['image']) && !empty($prod['variants'])) {
            foreach ($prod['variants'] as $v) {
                if (!empty($v['image'])) {
                    $prod['image'] = $v['image'];
                    break;
                }
            }
        }
    }
    unset($prod);

    // --- คำนวณ stock / price จาก available_stock ของ variants ---
    foreach ($products as $pid => &$prod) {

        if (!empty($prod['variants'])) {
            $totalAvailable = 0;
            $minPrice = null;

            foreach ($prod['variants'] as $v) {
                $vAvail = (int)($v['available_stock'] ?? 0);
                $vPrice = (float)($v['price'] ?? 0);

                $totalAvailable += $vAvail;

                if ($vPrice > 0 && ($minPrice === null || $vPrice < $minPrice)) {
                    $minPrice = $vPrice;
                }
            }

            // อัปเดต stock เป็น available ทั้งหมด (หลังหัก reserved แล้ว)
            $prod['available_stock'] = $totalAvailable;

            // ถ้าต้องการให้หน้า card ดูภาพรวม ใช้ราคาต่ำสุด
            if ($minPrice !== null) {
                $prod['price'] = $minPrice;
            }
        } else {
            // ไม่มี variants → ใช้ available_stock ของ product หลัก
            $prod['available_stock'] = (int)$prod['available_stock'];
        }
    }

    unset($prod);
    return $products;
}




// -----------------------------------------
// ดึงสินค้าตัวเดียว + variants
// -----------------------------------------
function getProductByIdWithVariants(mysqli $conn, int $product_id): ?array
{
    $sqlP = "
        SELECT 
            id,
            name,
            price,
            image,
            description,
            category,
            stock,
            reserved_stock,
            (stock - reserved_stock) AS available_stock
        FROM products
        WHERE id = ?
        LIMIT 1
    ";
    $resP = db_query($conn, $sqlP, [$product_id], "i");

    if (!$resP || $resP->num_rows === 0) {
        return null;
    }

    $product = $resP->fetch_assoc();
    $product['available_stock'] = max(0, (int)$product['available_stock']);
    $product['variants'] = [];

    $sqlV = "
        SELECT 
            id,
            product_id,
            variant_name,
            price,
            stock,
            reserved_stock,
            image,
            (stock - reserved_stock) AS available_stock
        FROM product_variants
        WHERE product_id = ?
        ORDER BY id ASC
    ";
    $resV = db_query($conn, $sqlV, [$product_id], "i");

    if ($resV && $resV->num_rows > 0) {
        while ($v = $resV->fetch_assoc()) {
            $v['available_stock'] = max(0, (int)$v['available_stock']);
            $product['variants'][] = $v;
        }
    }

    // fallback รูป
    if (empty($product['image']) && !empty($product['variants'])) {
        foreach ($product['variants'] as $v) {
            if (!empty($v['image'])) {
                $product['image'] = $v['image'];
                break;
            }
        }
    }

    // คำนวณ stock รวมถ้าสินค้ามี variants
    if (!empty($product['variants'])) {
        $totalAvailable = 0;
        $minPrice = null;

        foreach ($product['variants'] as $v) {
            $totalAvailable += (int)($v['available_stock']);
            $vPrice = (float)$v['price'];

            if ($minPrice === null || $vPrice < $minPrice) {
                $minPrice = $vPrice;
            }
        }

        $product['available_stock'] = $totalAvailable;
        if ($minPrice !== null) {
            $product['price'] = $minPrice;
        }
    }

    return $product;
}




// -----------------------------------------
// หมวดหมู่
// -----------------------------------------
function getAllCategories(mysqli $conn): array
{
    $categories = [];

    $sql = "SELECT DISTINCT category 
            FROM products 
            WHERE category IS NOT NULL AND category <> ''";

    $res = db_query($conn, $sql);

    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $categories[] = $row['category'];
        }
    }

    return $categories;
}