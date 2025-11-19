<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../Users/line-entry.php");
    exit;
}

require_once '../../utils/db_with_log.php';
include_once '../../bootstrap.php';

$conn = connectDBWithLog();
$user_id = $_SESSION['user_id'];

// ถ้าไม่ส่ง mode มา ให้ถือเป็น single (ซื้อทีละชิ้น)
$mode = $_POST['mode'] ?? 'single';

$items = [];
$total = 0;

if ($mode === 'cart') {
    // --------- โหมดจ่ายทั้งตะกร้า (รับมาจากฟอร์ม POST ของหน้า Cart) ---------
    $product_ids    = $_POST['product_id']    ?? [];
    $variant_ids    = $_POST['variant_id']    ?? [];
    $product_names  = $_POST['product_name']  ?? [];
    $variant_names  = $_POST['variant_name']  ?? [];
    $quantities     = $_POST['quantity']      ?? [];
    $prices         = $_POST['price']         ?? [];

    foreach ($product_ids as $i => $pid) {
        $pid    = (int)$pid;
        $vid    = (int)($variant_ids[$i] ?? 0);
        $pname  = $product_names[$i] ?? '';
        $vname  = $variant_names[$i] ?? null;
        $qty    = (int)($quantities[$i] ?? 1);
        $price  = (float)($prices[$i] ?? 0);

        if ($qty < 1) $qty = 1;

        $line_total = $price * $qty;
        $total     += $line_total;

        $items[] = [
            'product_id'   => $pid,
            'variant_id'   => $vid,
            'product_name' => $pname,
            'variant_name' => $vname,
            'quantity'     => $qty,
            'price'        => $price,
            'line_total'   => $line_total,
        ];
    }

} else {
    // --------- โหมดซื้อทีละชิ้น (เดิม) รับมาจาก Buyer.php ด้วย GET ---------
    $product_id  = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    $variant_id  = isset($_GET['variant_id']) ? (int)$_GET['variant_id'] : 0;
    $quantity    = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;

    if ($quantity < 1) $quantity = 1;

    // ต้องมีอย่างน้อย product_id หรือ variant_id
    if ($product_id === 0 && $variant_id === 0) {
        die("❌ ข้อมูลสินค้าไม่ถูกต้อง");
    }

    if ($variant_id) {
        $sql = ("
            SELECT pv.price, pv.variant_name, p.name AS product_name
            FROM product_variants pv
            JOIN products p ON pv.product_id = p.id
            WHERE pv.id = ? AND p.id = ?
        ");
        $res = db_query($conn, $sql, [$variant_id, $product_id], "ii");
    } else {
        $sql = ("
            SELECT price, name AS product_name
            FROM products 
            WHERE id = ?
        ");
        $res = db_query($conn, $sql, [$product_id], "i");
    }

    $data = $res ? $res->fetch_assoc() : null;

    if (!$data) {
        die("❌ ไม่พบข้อมูลสินค้า");
    }

    $price        = (float)$data['price'];
    $product_name = $data['product_name'];
    $variant_name = $variant_id ? $data['variant_name'] : null;

    $line_total = $price * $quantity;
    $total      = $line_total;

    // normalize ให้ใช้โค้ด HTML เดียวกับโหมด cart ได้
    $items = [[
        'product_id'   => $product_id,
        'variant_id'   => $variant_id,
        'product_name' => $product_name,
        'variant_name' => $variant_name,
        'quantity'     => $quantity,
        'price'        => $price,
        'line_total'   => $line_total,
    ]];
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน | Line-Shop</title>
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">Line-Shop</a>
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
                    <?php foreach ($items as $idx => $it): ?>
                        <input type="hidden" name="product_id[]"   value="<?= $it['product_id'] ?>">
                        <input type="hidden" name="variant_id[]"   value="<?= $it['variant_id'] ?>">
                        <input type="hidden" name="product_name[]" value="<?= htmlspecialchars($it['product_name']) ?>">
                        <input type="hidden" name="variant_name[]" value="<?= htmlspecialchars($it['variant_name'] ?? '') ?>">
                        <input type="hidden" name="quantity[]"     value="<?= $it['quantity'] ?>">
                        <input type="hidden" name="price[]"        value="<?= $it['price'] ?>">
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- โหมดซื้อทีละชิ้น -->
                    <?php $it = $items[0]; ?>
                    <input type="hidden" name="product_id"   value="<?= $it['product_id'] ?>">
                    <input type="hidden" name="variant_id"   value="<?= $it['variant_id'] ?>">
                    <input type="hidden" name="product_name" value="<?= htmlspecialchars($it['product_name']) ?>">
                    <input type="hidden" name="variant_name" value="<?= htmlspecialchars($it['variant_name'] ?? '') ?>">
                    <input type="hidden" name="quantity"     value="<?= $it['quantity'] ?>">
                    <input type="hidden" name="price"        value="<?= $it['price'] ?>">
                <?php endif; ?>

                <input type="hidden" name="total" value="<?= $total ?>">

                <div class="mb-3">
                    <label class="form-label">📤 อัปโหลดสลิป</label>
                    <input type="file" class="form-control" name="slip" accept="image/*" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">⏰ เวลาที่โอนเงิน</label>
                    <input type="datetime-local" name="payment_time" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-danger w-100">ยืนยันการชำระเงิน</button>
            </form>

        </div>
    </div>
</div>

<?php $conn->close(); ?>
</body>
</html>
