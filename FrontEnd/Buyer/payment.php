<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../Users/line-entry.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['name']) || empty($_POST['price'])) {
    echo "ไม่มีข้อมูลสินค้า";
    exit;
}

$product_name = htmlspecialchars($_POST['name']);
$product_price = htmlspecialchars($_POST['price']); // เป็นรูปแบบ '250 บาท'
$product_category = htmlspecialchars($_POST['category'] ?? 'ไม่ระบุ');

require_once '../../config.php';
$conn = connectDB();
$user_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// แปลงราคาที่เป็นตัวเลข
$price_number = (int) filter_var($product_price, FILTER_SANITIZE_NUMBER_INT);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand text-white" href="#">Line-Shop</a>
    </div>
</nav>

<div class="container my-4">
    <h2 class="text-center mb-4">สรุปการสั่งซื้อ</h2>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">ลูกค้า: <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?php echo $product_name; ?></h5>
            <p class="card-text">หมวดหมู่: <?php echo $product_category; ?></p>

            <!-- ปุ่มเพิ่ม/ลดจำนวน -->
            <div class="d-flex align-items-center mb-3 mt-auto">
                <button type="button" class="btn btn-secondary" id="decrease">-</button>
                <input type="text" id="quantity" class="form-control mx-2 text-center" value="1" style="width:60px;" readonly>
                <button type="button" class="btn btn-secondary" id="increase">+</button>
            </div>

            <p class="card-text h5">ราคารวม: <span id="total-price"><?php echo $price_number; ?></span> บาท</p>
        </div>
    </div>

    <form action="confirm_payment.php" method="post">
        <input type="hidden" name="name" value="<?php echo $product_name; ?>">
        <input type="hidden" name="price" id="final-price" value="<?php echo $price_number; ?>">
        <input type="hidden" name="category" value="<?php echo $product_category; ?>">
        <input type="hidden" name="quantity" id="final-quantity" value="1">

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-success btn-lg">ชำระเงิน</button>
        </div>
    </form>
</div>

<script>
const pricePerUnit = <?php echo $price_number; ?>;
let quantity = 1;

const quantityInput = document.getElementById('quantity');
const totalPrice = document.getElementById('total-price');
const finalPrice = document.getElementById('final-price');
const finalQuantity = document.getElementById('final-quantity');

document.getElementById('increase').addEventListener('click', () => {
    quantity++;
    updateUI();
});

document.getElementById('decrease').addEventListener('click', () => {
    if (quantity > 1) {
        quantity--;
        updateUI();
    }
});

function updateUI() {
    quantityInput.value = quantity;
    const total = pricePerUnit * quantity;
    totalPrice.innerText = total;
    finalPrice.value = total;
    finalQuantity.value = quantity;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
