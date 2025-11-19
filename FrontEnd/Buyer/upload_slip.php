<?php
session_start();

// ป้องกันคนที่ยังไม่ได้ล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Users/line-entry.php");
    exit;
}

require_once '../../utils/db_with_log.php';

$conn = connectDBWithLog();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $mode = $_POST['mode'] ?? 'single';
    $payment_time = $_POST['payment_time'] ?? null;

    // ตรวจสอบการอัปโหลดไฟล์
    if (isset($_FILES['slip']) && $_FILES['slip']['error'] === 0) {

        $fileTmp   = $_FILES['slip']['tmp_name'];
        $fileName  = time() . '_' . basename($_FILES['slip']['name']);
        $uploadDir = '../../uploads/slips';
        $uploadPath = $uploadDir . '/' . $fileName;

        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (move_uploaded_file($fileTmp, $uploadPath)) {

            if ($mode === 'cart') {
                // ---------- จ่ายทั้งตะกร้า ----------
                $product_ids   = $_POST['product_id']   ?? [];
                $variant_ids   = $_POST['variant_id']   ?? [];
                $product_names = $_POST['product_name'] ?? [];
                $variant_names = $_POST['variant_name'] ?? [];
                $quantities    = $_POST['quantity']     ?? [];
                $prices        = $_POST['price']        ?? [];
                $total_all     = (float)($_POST['total'] ?? 0); // ถ้าอยากใช้ทีหลัง

                $sqlInsertPay = 
                    "INSERT INTO payments
                    (user_id, product_id, variant_id, product_name, variant_name, quantity, price, total, slip_image, payment_time)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                foreach ($product_ids as $i => $pid) {
                    $pid   = (int)$pid;
                    $vid   = (int)($variant_ids[$i] ?? 0);
                    $name  = $product_names[$i] ?? '';
                    $vname = $variant_names[$i] ?? null;
                    $qty   = (int)($quantities[$i] ?? 1);
                    $price = (float)($prices[$i] ?? 0);
                    $lineTotal = $price * $qty;   // เก็บยอดของแต่ละชิ้น

                    try {
                        db_query(
                            $conn,
                            $sqlInsertPay,
                            [
                                $user_id,
                                $pid,
                                $vid,
                                $name,
                                $vname,
                                $qty,
                                $price,
                                $lineTotal,
                                $fileName,
                                $payment_time
                            ],
                            "iiissiddss"
                        );
                    } catch (Exception $e) {
                        die('DB ERROR (Insert payments cart):' . $e->getMessage());
                    }
                }

                // 🧹 หลังบันทึก payment เสร็จ — ลบสินค้าที่จ่ายออกจากตะกร้า
                $sqlDelCart = "
                    DELETE FROM cart_items 
                    WHERE user_id = ? AND product_id = ?
                ";

                foreach ($product_ids as $i => $pid) {
                    $pid = (int)$pid;
                    db_query(
                        $conn,
                        $sqlDelCart,
                        [$user_id, $pid],
                        "ii"
                    );
                }
                $message = "<div class='alert alert-success text-center'>✅ อัปโหลดสลิปเรียบร้อย (ตะกร้าทั้งหมด) รอตรวจสอบการชำระเงิน</div>";
            } else {
                // ---------- ซื้อทีละชิ้น ----------
                $product_id   = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
                $variant_id   = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;
                $product_name = $_POST['product_name'] ?? '';
                $variant_name = $_POST['variant_name'] ?? null;

                $quantity = (int)($_POST['quantity'] ?? 1);
                $price    = (float)($_POST['price'] ?? 0);
                $total    = (float)($_POST['total'] ?? 0);

                $sqlInsertSingle = "
                    INSERT INTO payments
                    (user_id, product_id, variant_id, product_name, variant_name, quantity, price, total, slip_image, payment_time)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";

                db_query(
                    $conn,
                    $sqlInsertSingle,
                    [
                        $user_id,
                        $product_id,
                        $variant_id,
                        $product_name,
                        $variant_name,
                        $quantity,
                        $price,
                        $total,
                        $fileName,
                        $payment_time
                    ],
                    "iiissiddss"
                );

                // 🧹 ลบรายการนี้ออกจากตะกร้า
                $sqlDelSingle = "
                    DELETE FROM cart_items 
                    WHERE user_id = ? AND product_id = ? AND (variant_id = ?
                ";
                db_query(
                    $conn,
                    $sqlDelSingle,
                    [$user_id, $product_id, $variant_id],
                    "iii"
                );

                $message = "<div class='alert alert-success text-center'>✅ อัปโหลดสลิปเรียบร้อย รอตรวจสอบการชำระเงิน</div>";
            }
        } else {
            $message = "<div class='alert alert-danger text-center'>❌ ไม่สามารถอัปโหลดไฟล์ได้</div>";
        }
    } else {
        $message = "<div class='alert alert-warning text-center'>⚠️ กรุณาเลือกไฟล์สลิปก่อนส่ง</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อัปโหลดสลิปสำเร็จ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex flex-column justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow-sm" style="max-width: 400px; width: 100%;">
        <!-- แสดงข้อความตามผลการอัปโหลด -->
        <?= $message ?? "<div class='alert alert-secondary text-center'>ไม่มีข้อมูลอัปโหลด</div>"; ?>
        <!-- ปุ่มกลับไปหน้าร้านค้า -->
        <a href="Buyer.php" class="btn btn-dark w-100 mt-3">กลับหน้าร้านค้า</a>
    </div>
</body>

</html>