<?php
// services/ProductService.php

/**
 * ดึงสินค้าทั้งหมดพร้อม variants
 * โครง:
 * [
 *   product_id => [
 *      'id'        => ...,
 *      'name'      => ...,
 *      'price'     => ...,
 *      'image'     => ...,
 *      'description'=> ...,
 *      'category'  => ...,
 *      'stock'     => ...,
 *      'variants'  => [
 *          [
 *              'id'          => ...,
 *              'product_id'  => ...,
 *              'variant_name'=> ...,
 *              'price'       => ...,
 *              'stock'       => ...,
 *              'image'       => ...
 *          ],
 *          ...
 *      ]
 *   ],
 *   ...
 * ]
 */
function getAllProductsWithVariants(mysqli $conn): array
{
    $products = [];

    // ดึงสินค้าหลัก
    $sqlProducts = "
        SELECT id, name, price, image, description, category, stock
        FROM products
        ORDER BY id DESC
    ";
    $resProd = db_query($conn, $sqlProducts);

    if ($resProd && $resProd->num_rows > 0) {
        while ($row = $resProd->fetch_assoc()) {
            $pid = (int)$row['id'];
            $products[$pid] = $row;
            $products[$pid]['variants'] = [];
        }
    }

    if (empty($products)) {
        return [];
    }

    // ดึง variants ทีเดียวทั้งหมด
    $sqlVariants = "
        SELECT id, product_id, variant_name, price, stock, image
        FROM product_variants
        WHERE product_id IN (" . implode(',', array_keys($products)) . ")
        ORDER BY id ASC
    ";
    $resVar = db_query($conn, $sqlVariants);

    if ($resVar && $resVar->num_rows > 0) {
        while ($v = $resVar->fetch_assoc()) {
            $pid = (int)$v['product_id'];
            if (!isset($products[$pid])) {
                continue;
            }
            $products[$pid]['variants'][] = $v;
        }
    }

    // ถ้า product ไหนไม่มีรูปหลัก ให้ใช้รูปของ variant ตัวแรกที่มีรูปแทน
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

    // คำนวณราคา & stock จาก variants
    foreach ($products as $pid => &$prod) {
        if (!empty($prod['variants'])) {
            $totalStock = 0;
            $minPrice   = null;

            foreach ($prod['variants'] as $v) {
                $vStock = (int)($v['stock'] ?? 0);
                $vPrice = (float)($v['price'] ?? 0);

                if ($vStock > 0) {
                    $totalStock += $vStock;
                }
                if ($vPrice > 0 && ($minPrice === null || $vPrice < $minPrice)) {
                    $minPrice = $vPrice;
                }
            }

            $prod['stock'] = $totalStock;
            if ($minPrice !== null) {
                $prod['price'] = $minPrice;
            }
        }
    }
    unset($prod);

    return $products;
}

/**
 * ดึงสินค้าเดี่ยวพร้อม variants (ถ้าอยากใช้ในหน้าอื่นภายหลัง)
 */
function getProductByIdWithVariants(mysqli $conn, int $product_id): ?array
{
    $sqlP = "
        SELECT id, name, price, image, description, category, stock
        FROM products
        WHERE id = ?
        LIMIT 1
    ";
    $resP = db_query($conn, $sqlP, [$product_id], "i");

    if (!$resP || $resP->num_rows === 0) {
        return null;
    }

    $product = $resP->fetch_assoc();
    $product['variants'] = [];

    $sqlV = "
        SELECT id, product_id, variant_name, price, stock, image
        FROM product_variants
        WHERE product_id = ?
        ORDER BY id ASC
    ";
    $resV = db_query($conn, $sqlV, [$product_id], "i");

    if ($resV && $resV->num_rows > 0) {
        while ($v = $resV->fetch_assoc()) {
            $product['variants'][] = $v;
        }
    }

    // ถ้าไม่มีรูปหลักให้ลองเอารูปจาก variant ตัวแรก
    if (empty($product['image']) && !empty($product['variants'])) {
        foreach ($product['variants'] as $v) {
            if (!empty($v['image'])) {
                $product['image'] = $v['image'];
                break;
            }
        }
    }

    if (!empty($product['variants'])) {
        $totalStock = 0;
        $minPrice   = null;

        foreach ($product['variants'] as $v) {
            $vStock = (int)($v['stock'] ?? 0);
            $vPrice = (float)($v['price'] ?? 0);

            if ($vStock > 0) {
                $totalStock += $vStock;
            }
            if ($vPrice > 0 && ($minPrice === null || $vPrice < $minPrice)) {
                $minPrice = $vPrice;
            }
        }

        $product['stock'] = $totalStock;
        if ($minPrice !== null) {
            $product['price'] = $minPrice;
        }
    }

    return $product;
}

/**
 * ดึงหมวดหมู่สินค้าที่มีอยู่ทั้งหมด
 */
function getAllCategories(mysqli $conn): array
{
    $categories = [];

    $sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category <> ''";
    $res = db_query($conn, $sql);

    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $categories[] = $row['category'];
        }
    }

    return $categories;
}
