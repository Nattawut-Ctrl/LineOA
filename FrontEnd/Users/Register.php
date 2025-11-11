<?php
session_start();
require_once '../../config.php';   // ปรับ path ตามจริง
$conn = connectDB();

function clean($s) {
    return trim(htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'));
}

// รับค่า GET จาก checkLineUser.php
$line_uid     = clean($_GET['line_uid']     ?? '');
$display_name = clean($_GET['display_name'] ?? '');

// ถ้า POST (กดปุ่มสมัคร)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $line_uid     = clean($_POST['line_uid']     ?? '');
    $display_name = clean($_POST['display_name'] ?? '');
    $first_name   = clean($_POST['first_name']   ?? '');
    $last_name    = clean($_POST['last_name']    ?? '');
    $phone        = clean($_POST['phone']        ?? '');
    $email        = clean($_POST['email']        ?? '');

    $errors = [];

    if ($line_uid === '') {
        $errors[] = "ไม่พบ LINE UID กรุณาเข้าสมัครผ่านปุ่มใน LINE อีกครั้ง";
    }
    if ($first_name === '') {
        $errors[] = "กรุณากรอกชื่อจริง";
    }
    if ($last_name === '') {
        $errors[] = "กรุณากรอกนามสกุล";
    }

    if (empty($errors)) {
        // insert ลงตาราง users
        $sql = "INSERT INTO users (line_uid, display_name, first_name, last_name, phone, email)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssss",
            $line_uid,
            $display_name,
            $first_name,
            $last_name,
            $phone,
            $email
        );

        if ($stmt->execute()) {
            // ดึง id ที่เพิ่ง insert
            $user_id = $stmt->insert_id;
            $_SESSION['user_id'] = $user_id;

            // สมัครสำเร็จ → ไปหน้า Buyer
            header("Location: ../Buyer/Buyer.php");
            exit;
        } else {
            $errors[] = "บันทึกข้อมูลไม่สำเร็จ: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สมัครสมาชิก</title>
</head>
<body>
    <h2>สมัครสมาชิก (เชื่อมกับ LINE)</h2>

    <?php if (!empty($errors)): ?>
        <div style="color:red">
            <?php foreach ($errors as $e): ?>
                <div><?php echo $e; ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <!-- line_uid / display_name ซ่อน / แสดงแบบอ่านอย่างเดียว -->
        <input type="hidden" name="line_uid" value="<?php echo $line_uid; ?>">
        <input type="hidden" name="display_name" value="<?php echo $display_name; ?>">

        <p>LINE ชื่อ: <strong><?php echo $display_name ?: '(ไม่ทราบ)'; ?></strong></p>

        <div>
            <label>ชื่อจริง:</label>
            <input type="text" name="first_name" value="<?php echo $first_name ?? ''; ?>">
        </div>

        <div>
            <label>นามสกุล:</label>
            <input type="text" name="last_name" value="<?php echo $last_name ?? ''; ?>">
        </div>

        <div>
            <label>เบอร์โทรศัพท์:</label>
            <input type="text" name="phone" value="<?php echo $phone ?? ''; ?>">
        </div>

        <div>
            <label>อีเมล (ถ้ามี):</label>
            <input type="email" name="email" value="<?php echo $email ?? ''; ?>">
        </div>

        <button type="submit">สมัครสมาชิก</button>
    </form>
</body>
</html>
