<?php
require_once __DIR__ . '/../components/init.php';

// ถ้าไม่ส่ง mode มา ให้ถือเป็น single (ซื้อทีละชิ้น)
$mode = $_POST['mode'] ?? ($_GET['mode'] ?? 'single');

$items = [];
$total = 0;

if ($mode === 'cart') {

    $product_ids = $_POST['product_id'] ?? [];
    $variant_ids = $_POST['variant_id'] ?? [];
    $quantities  = $_POST['quantity'] ?? [];

    foreach ($product_ids as $i => $pid) {
        $pid = (int)$pid;
        $vid = (int)($variant_ids[$i] ?? 0);
        $qty = (int)($quantities[$i] ?? 1);
        if ($qty < 1) $qty = 1;

        // ---------- ดึงข้อมูลจริงจาก DB ----------
        if ($vid > 0) {
            $sql = "
                SELECT pv.id, pv.price, pv.stock,
                       pv.variant_name,
                       p.name AS product_name
                FROM product_variants pv
                JOIN products p ON pv.product_id = p.id
                WHERE pv.id = ? AND p.id = ?
            ";
            $res = db_query($conn, $sql, [$vid, $pid], "ii");
        } else {
            $sql = "
                SELECT id, price, stock,
                       name AS product_name
                FROM products
                WHERE id = ?
            ";
            $res = db_query($conn, $sql, [$pid], "i");
        }

        $row = $res ? $res->fetch_assoc() : null;
        if (!$row) continue;

        $stock        = (int)($row['stock'] ?? 0);
        $price        = (float)$row['price'];
        $product_name = $row['product_name'];
        $variant_name = $vid > 0 ? ($row['variant_name'] ?? null) : null;

        // ---------- เช็ค stock ----------
        if ($stock > 0 && $qty > $stock) {
            $qty = $stock;
            if ($qty <= 0) continue;
        }

        $line_total = $price * $qty;
        $total     += $line_total;

        $items[] = [
            'product_id'   => $pid,
            'variant_id'   => $vid,
            'product_name' => $product_name,
            'variant_name' => $variant_name,
            'quantity'     => $qty,
            'price'        => $price,
            'line_total'   => $line_total,
        ];
    }
} else {
    // ------------------- ✅ โหมด single (ซื้อทีละชิ้น) -------------------
    // รับจาก POST เป็นหลัก ถ้าเข้าตรงก็รองรับ GET ให้ด้วย
    $pid = (int)($_POST['product_id'] ?? ($_GET['product_id'] ?? 0));
    $vid = (int)($_POST['variant_id'] ?? ($_GET['variant_id'] ?? 0));
    $qty = (int)($_POST['quantity'] ?? ($_GET['quantity'] ?? 1));
    if ($qty < 1) $qty = 1;

    if ($pid > 0) {

        if ($vid > 0) {
            $sql = "
                SELECT pv.id, pv.price, pv.stock,
                       pv.variant_name,
                       p.name AS product_name
                FROM product_variants pv
                JOIN products p ON pv.product_id = p.id
                WHERE pv.id = ? AND p.id = ?
            ";
            $res = db_query($conn, $sql, [$vid, $pid], "ii");
        } else {
            $sql = "
                SELECT id, price, stock,
                       name AS product_name
                FROM products
                WHERE id = ?
            ";
            $res = db_query($conn, $sql, [$pid], "i");
        }

        $row = $res ? $res->fetch_assoc() : null;

        if ($row) {
            $stock        = (int)($row['stock'] ?? 0);
            $price        = (float)$row['price'];
            $product_name = $row['product_name'];
            $variant_name = $vid > 0 ? ($row['variant_name'] ?? null) : null;

            if ($stock > 0 && $qty > $stock) {
                $qty = $stock;
            }
            if ($qty > 0) {
                $line_total = $price * $qty;
                $total = $line_total;

                $items[] = [
                    'product_id'   => $pid,
                    'variant_id'   => $vid,
                    'product_name' => $product_name,
                    'variant_name' => $variant_name,
                    'quantity'     => $qty,
                    'price'        => $price,
                    'line_total'   => $line_total,
                ];
            }
        }
    }
}

// ถ้าไม่มี items เลย ให้เด้งกลับร้าน (กัน error)
if (empty($items)) {
    header("Location: Buyer.php?error=empty_payment");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน | Line-Shop</title>
    <?php include BASE_PATH . '/shared/partials/bootstrap.php'; ?>
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="Buyer.php">Line-Shop</a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="card shadow-sm mx-auto" style="max-width: 600px;">
            <div class="card-body">
                <h4 class="text-center mb-4">💳 ชำระเงิน</h4>

                <table class="table table-bordered text-center align-middle">
                    <tr>
                        <th>สินค้า</th>
                        <th>จำนวน</th>
                        <th>ราคาต่อชิ้น</th>
                        <th>รวมย่อย</th>
                    </tr>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($it['product_name']) ?>
                                <?php if (!empty($it['variant_name'])): ?>
                                    <br><small class="text-muted">(<?= htmlspecialchars($it['variant_name']) ?>)</small>
                                <?php endif; ?>
                            </td>
                            <td><?= $it['quantity'] ?></td>
                            <td><?= number_format($it['price'], 2) ?> บาท</td>
                            <td><?= number_format($it['line_total'], 2) ?> บาท</td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="table-warning">
                        <th colspan="3">รวมทั้งหมด</th>
                        <th><strong><?= number_format($total, 2) ?> บาท</strong></th>
                    </tr>
                </table>

                <div class="text-center my-4">
                    <h6 class="fw-bold mb-2">📱 สแกนเพื่อชำระเงิน</h6>
                    <img src="../../uploads/qr-payment.png" class="img-fluid rounded border" style="max-width:220px;">
                </div>

                <form action="upload_slip.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="mode" value="<?= htmlspecialchars($mode) ?>">

                    <?php if ($mode === 'cart'): ?>
                        <?php foreach ($items as $it): ?>
                            <input type="hidden" name="product_id[]" value="<?= $it['product_id'] ?>">
                            <input type="hidden" name="variant_id[]" value="<?= $it['variant_id'] ?>">
                            <input type="hidden" name="product_name[]" value="<?= htmlspecialchars($it['product_name']) ?>">
                            <input type="hidden" name="variant_name[]" value="<?= htmlspecialchars($it['variant_name'] ?? '') ?>">
                            <input type="hidden" name="quantity[]" value="<?= $it['quantity'] ?>">
                            <input type="hidden" name="price[]" value="<?= $it['price'] ?>">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php $it = $items[0]; ?>
                        <input type="hidden" name="product_id" value="<?= $it['product_id'] ?>">
                        <input type="hidden" name="variant_id" value="<?= $it['variant_id'] ?>">
                        <input type="hidden" name="product_name" value="<?= htmlspecialchars($it['product_name']) ?>">
                        <input type="hidden" name="variant_name" value="<?= htmlspecialchars($it['variant_name'] ?? '') ?>">
                        <input type="hidden" name="quantity" value="<?= $it['quantity'] ?>">
                        <input type="hidden" name="price" value="<?= $it['price'] ?>">
                    <?php endif; ?>

                    <input type="hidden" name="total" value="<?= $total ?>">

                    <div class="mb-3">
                        <label class="form-label">📤 อัปโหลดสลิป</label>
                        <input type="file" class="form-control" name="slip" accept="image/*" required>
                    </div>

                    <div class="mb-3">
                        <label for="transfer_date" class="form-label">วันที่โอน (ตามสลิปที่อัปโหลด)</label>
                        <input type="date" name="transfer_date" id="transfer_date" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="transfer_time" class="form-label">เวลาโอน (ตามสลิปที่อัปโหลด)</label>
                        <input type="time" name="transfer_time" id="transfer_time" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-danger w-100">ยืนยันการชำระเงิน</button>
                </form>

            </div>
        </div>
    </div>

    <?php $conn->close(); ?>
</body>

</html>