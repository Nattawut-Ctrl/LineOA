<?php
session_start();
require_once '../../config.php';
$conn = connectDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_name = $_POST['product_name'];
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $total = (float)$_POST['total'];
    $payment_time = $_POST['payment_time'];

    // ตรวจสอบการอัปโหลดไฟล์
    if (isset($_FILES['slip']) && $_FILES['slip']['error'] === 0) {
        $fileTmp = $_FILES['slip']['tmp_name'];
        $fileName = time() . '_' . basename($_FILES['slip']['name']);
        $uploadPath = '../../uploads/slips/' . $fileName;

        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!is_dir('../../uploads/slips')) {
            mkdir('../../uploads/slips', 0777, true);
        }

        if (move_uploaded_file($fileTmp, $uploadPath)) {
            $stmt = $conn->prepare("INSERT INTO payments (user_id, product_name, quantity, price, total, slip_image, payment_time) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isiddss", $user_id, $product_name, $quantity, $price, $total, $fileName, $payment_time);
            $stmt->execute();
            
            $message = "<div class='alert alert-success text-center'>✅ อัปโหลดสลิปเรียบร้อย รอตรวจสอบการชำระเงิน</div>";
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
    <title>อัปโหลดสลิปสำเร็จ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex flex-column justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow" style="max-width: 400px; width: 100%;">
        <?= $message ?? "<div class='alert alert-secondary text-center'>ไม่มีข้อมูลอัปโหลด</div>"; ?>
        <a href="shop.php" class="btn btn-dark w-100 mt-3">กลับหน้าร้านค้า</a>
    </div>
</body>
</html>
