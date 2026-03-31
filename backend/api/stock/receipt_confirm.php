<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();
$conn = connectDBWithLog();

$REDIRECT_CREATE = rtrim(BACKEND_URL, '/') . '/pages/stock/receipt_create.php';
$REDIRECT_RECEIPTS = rtrim(BACKEND_URL, '/') . '/pages/stock/receipts.php';

function go(string $url, string $qs): void
{
    header('Location: ' . $url . (str_contains($qs, '?') ? $qs : ('?' . ltrim($qs, '?'))));
    exit;
}

function fiscalYearThaiFromDate(string $dateYmd): int
{
    $ts = strtotime($dateYmd);
    if ($ts === false) {
        $y = (int)date('Y');
        $m = (int)date('n');
    } else {
        $y = (int)date('Y', $ts);
        $m = (int)date('n', $ts);
    }
    $fyCe = ($m >= 10) ? ($y + 1) : $y; // ปีงบราชการ
    return $fyCe + 543;                 // เก็บเป็น พ.ศ.
}

function nextReceiptNo(mysqli $conn, int $fyThai): string
{
    $prefix = 'GR-' . $fyThai . '-';
    $res = db_query(
        $conn,
        "SELECT receipt_no FROM stock_receipts WHERE fiscal_year = ? AND receipt_no LIKE ? ORDER BY id DESC LIMIT 1",
        [$fyThai, $prefix . '%'],
        "is"
    );
    $next = 1;
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $last = (string)($row['receipt_no'] ?? '');
        if (preg_match('/-(\d+)$/', $last, $m)) {
            $next = (int)$m[1] + 1;
        }
    }
    return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
}

function nextLotCode(mysqli $conn, int $fyThai): string
{
    $res = db_query(
        $conn,
        "SELECT lot_code
         FROM stock_lots
         WHERE fiscal_year = ?
         ORDER BY id DESC
         LIMIT 1",
        [$fyThai],
        "i"
    );

    $next = 1;
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $last = (string)($row['lot_code'] ?? '');

        if (preg_match('/^(\d+)\//', $last, $m)) {
            $next = ((int)$m[1]) + 1;
        }
    }
    return $next . '/' . $fyThai;
}

// ------------------------------
// รับค่าจากฟอร์ม
// ------------------------------
$receipt_date  = $_POST['receipt_date'] ?? '';
$supplier_name = trim((string)($_POST['supplier_name'] ?? ''));
$reference_no  = trim((string)($_POST['reference_no'] ?? ''));
$note          = trim((string)($_POST['note'] ?? ''));
$update_price  = isset($_POST['update_price']) && $_POST['update_price'] === '1';

$product_ids = $_POST['product_id'] ?? [];
$variant_ids = $_POST['variant_id'] ?? [];
$qtys        = $_POST['qty'] ?? [];
$costs       = $_POST['cost_price'] ?? [];
$sells       = $_POST['sell_price'] ?? [];

if (empty($receipt_date) || !is_array($product_ids) || empty($product_ids)) {
    go($REDIRECT_CREATE, 'error=invalid_input');
}

$fyThai = fiscalYearThaiFromDate($receipt_date);

$items = [];

//  ถ้าสินค้ามี variant ต้องระบุ variant เสมอ (กัน lot ที่ variant_id = NULL)
$pidList = array_values(array_unique(array_map(fn($it) => (int)$it['product_id'], $items)));
$productsWithVariants = [];
if (!empty($pidList)) {
    $inPlaceholders = implode(',', array_fill(0, count($pidList), '?'));
    $types = str_repeat('i', count($pidList));
    $resHas = db_query(
        $conn,
        "SELECT DISTINCT product_id FROM product_variants WHERE product_id IN ($inPlaceholders)",
        $pidList,
        $types
    );
    if ($resHas) {
        while ($r = $resHas->fetch_assoc()) {
            $productsWithVariants[(int)$r['product_id']] = true;
        }
    }
}

foreach ($items as $it) {
    $pid = (int)$it['product_id'];
    if (!empty($productsWithVariants[$pid]) && $it['variant_id'] === null) {
        go($REDIRECT_CREATE, 'error=variant_required');
    }
}

foreach ($product_ids as $i => $pidRaw) {
    $pid = (int)$pidRaw;
    if ($pid <= 0) continue;

    $vid = isset($variant_ids[$i]) ? (int)$variant_ids[$i] : 0;
    $vid = ($vid > 0) ? $vid : null;

    $qty = isset($qtys[$i]) ? (int)$qtys[$i] : 0;
    if ($qty <= 0) continue;

    $cost = isset($costs[$i]) ? (float)$costs[$i] : 0;
    if ($cost < 0) $cost = 0;

    $sell = isset($sells[$i]) ? (float)$sells[$i] : 0;
    if ($sell <= 0) $sell = null;

    $items[] = [
        'product_id' => $pid,
        'variant_id' => $vid,
        'qty' => $qty,
        'cost_price' => $cost,
        'sell_price' => $sell,
    ];
}

if (empty($items)) {
    go($REDIRECT_CREATE, 'error=no_items');
}

try {
    $conn->begin_transaction();

    $receiptNo = nextReceiptNo($conn, $fyThai);

    $insReceipt = db_exec(
        $conn,
        "INSERT INTO stock_receipts (receipt_no, fiscal_year, supplier_name, reference_no, receipt_date, note, status, created_by, created_at)
         VALUES (?,?,?,?,?,?, 'confirmed', ?, NOW())",
        [$receiptNo, $fyThai, $supplier_name ?: null, $reference_no ?: null, $receipt_date, $note ?: null, (int)($_SESSION['admin_id'] ?? 0)],
        "sissssi"
    );
    if (!($insReceipt['ok'] ?? false)) {
        $conn->rollback();
        go($REDIRECT_CREATE, 'error=insert_receipt_failed');
    }
    $receiptId = (int)($insReceipt['insert_id'] ?? 0);
    if ($receiptId <= 0) {
        $conn->rollback();
        go($REDIRECT_CREATE, 'error=insert_receipt_failed');
    }

    // เพื่อ redirect ไป addStock: ใช้ product_id ตัวแรก
    $firstProductId = (int)$items[0]['product_id'];

    foreach ($items as $it) {
        $pid = (int)$it['product_id'];
        $vid = $it['variant_id'];
        $qty = (int)$it['qty'];
        $cost = (float)$it['cost_price'];
        $sell = $it['sell_price'];

        // insert receipt item
        if ($vid !== null) {
            $insItem = db_exec(
                $conn,
                "INSERT INTO stock_receipt_items (receipt_id, product_id, variant_id, qty, cost_price, sell_price) VALUES (?,?,?,?,?,?)",
                [$receiptId, $pid, $vid, $qty, $cost, $sell],
                "iiiidd"
            );
        } else {
            $insItem = db_exec(
                $conn,
                "INSERT INTO stock_receipt_items (receipt_id, product_id, variant_id, qty, cost_price, sell_price) VALUES (?,?,NULL,?,?,?)",
                [$receiptId, $pid, $qty, $cost, $sell],
                "iiidd"
            );
        }
        if (!($insItem['ok'] ?? false)) {
            $conn->rollback();
            go($REDIRECT_CREATE, 'error=insert_item_failed');
        }
        $receiptItemId = (int)($insItem['insert_id'] ?? 0);
        if ($receiptItemId <= 0) {
            $conn->rollback();
            go($REDIRECT_CREATE, 'error=insert_item_failed');
        }

        // create lot (1 item = 1 lot)
        $lotCode = nextLotCode($conn, $fyThai);
        if ($vid !== null) {
            $insLot = db_exec(
                $conn,
                "INSERT INTO stock_lots (fiscal_year, lot_code, product_id, variant_id, receipt_id, receipt_item_id, cost_price, qty_received, qty_available, status, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?, 'active', NOW())",
                [$fyThai, $lotCode, $pid, $vid, $receiptId, $receiptItemId, $cost, $qty, $qty],
                "isiiiidii"
            );
        } else {
            $insLot = db_exec(
                $conn,
                "INSERT INTO stock_lots (fiscal_year, lot_code, product_id, variant_id, receipt_id, receipt_item_id, cost_price, qty_received, qty_available, status, created_at)
                 VALUES (?,?,?,NULL,?,?,?,?,?, 'active', NOW())",
                [$fyThai, $lotCode, $pid, $receiptId, $receiptItemId, $cost, $qty, $qty],
                "isiiidii"
            );
        }

        if (!($insLot['ok'] ?? false)) {
            $conn->rollback();
            go($REDIRECT_CREATE, 'error=insert_lot_failed');
        }

        // เพิ่ม stock รวม (ให้ระบบเดิมยังใช้งานได้)
        if ($vid !== null) {
            $up = db_exec(
                $conn,
                "UPDATE product_variants SET stock = stock + ? WHERE id = ? AND product_id = ?",
                [$qty, $vid, $pid],
                "iii"
            );
            if (!($up['ok'] ?? false)) {
                $conn->rollback();
                go($REDIRECT_CREATE, 'error=update_variant_stock_failed');
            }
        } else {
            $up = db_exec(
                $conn,
                "UPDATE products SET stock = stock + ? WHERE id = ?",
                [$qty, $pid],
                "ii"
            );
            if (!($up['ok'] ?? false)) {
                $conn->rollback();
                go($REDIRECT_CREATE, 'error=update_product_stock_failed');
            }
        }

        // อัปเดตราคาขาย (optional)
        if ($update_price && $sell !== null && $sell > 0) {
            if ($vid !== null) {
                db_exec(
                    $conn,
                    "UPDATE product_variants SET price = ? WHERE id = ? AND product_id = ?",
                    [$sell, $vid, $pid],
                    "dii"
                );
            } else {
                db_exec(
                    $conn,
                    "UPDATE products SET price = ? WHERE id = ?",
                    [$sell, $pid],
                    "di"
                );
            }
        }
    }

    // ถ้ามี variants: ปรับ stock รวมของ product ตาม SUM(variants.stock)
    // (ทำเฉพาะ product ที่มี variant ในใบนี้ เพื่อไม่หนักเกิน)
    $productIdsToRecalc = [];
    foreach ($items as $it) {
        if ($it['variant_id'] !== null) {
            $productIdsToRecalc[(int)$it['product_id']] = true;
        }
    }
    foreach (array_keys($productIdsToRecalc) as $pid) {

        // 1) รวมสต็อก
        $sumRes = db_query(
            $conn,
            "SELECT COALESCE(SUM(stock),0) AS total_stock
         FROM product_variants
         WHERE product_id = ?",
            [$pid],
            "i"
        );
        $totalStock = 0;
        if ($sumRes) {
            $row = $sumRes->fetch_assoc();
            $totalStock = (int)($row['total_stock'] ?? 0);
        }

        // 2) (สำคัญ) สรุปราคา product = ราคาต่ำสุดของ variants (ตัดราคา 0 ทิ้ง)
        $minRes = db_query(
            $conn,
            "SELECT COALESCE(MIN(NULLIF(price, 0)), 0) AS min_price
         FROM product_variants
         WHERE product_id = ?",
            [$pid],
            "i"
        );
        $minPrice = 0.0;
        if ($minRes) {
            $row = $minRes->fetch_assoc();
            $minPrice = (float)($row['min_price'] ?? 0);
        }

        // 3) อัปเดต products ทั้ง stock และ price
        db_exec(
            $conn,
            "UPDATE products SET stock = ?, price = ? WHERE id = ?",
            [$totalStock, $minPrice, $pid],
            "idi"
        );
    }

    $conn->commit();

    // workflow: ไปต่อที่หน้า addStock ด้วย product_id ตัวแรก (ตามที่คุณต้องการ)
    $goAddStock = rtrim(BACKEND_URL, '/') . '/pages/stock/addStock.php?success=receipt_confirmed&product_id=' . urlencode((string)$firstProductId);
    header('Location: ' . $goAddStock);
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    go($REDIRECT_CREATE, 'error=exception');
}
