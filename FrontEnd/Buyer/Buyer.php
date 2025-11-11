<?php
session_start();

// ถ้าไม่มี user_id แปลว่ายังไม่ login จาก LINE
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Users/line-entry.php");   // ให้กลับไปเริ่มที่ LIFF อีกครั้ง
    exit;
}

// ถ้าอยากดึงข้อมูล user จาก DB ด้วย
require_once '../../config.php';
$conn = connectDB();
$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// จากนี้ค่อยเป็น HTML เดิมของ Buyer.php
?>
<!DOCTYPE html>
<html lang="th">
<head>...</head>
<body>
    <p>สวัสดีคุณ <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
    <!-- โค้ดหน้า Buyer เดิมของคุณ -->
</body>
</html>
