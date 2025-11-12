<?php
session_start();
require_once '../../config.php';   // ปรับ path ตามจริง
$conn = connectDB();

function clean($s)
{
    return trim(htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'));
}

// รับค่า GET จาก checkLineUser.php
$line_uid     = clean($_GET['line_uid']     ?? '');
$display_name = clean($_GET['display_name'] ?? '');
$picture_url = clean($_GET['picture_url'] ?? '');

// ถ้า POST (กดปุ่มสมัคร)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $line_uid     = clean($_POST['line_uid']     ?? '');
    $display_name = clean($_POST['display_name'] ?? '');
    $picture_url = clean($_POST['picture_url'] ?? '');
    $first_name   = clean($_POST['first_name']   ?? '');
    $last_name    = clean($_POST['last_name']    ?? '');
    $phone        = clean($_POST['phone']        ?? '');
    $citizen_id   = clean($_POST['citizen_id']   ?? '');

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
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = "กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง 10 หลัก (เฉพาะตัวเลข)";
    }
    if (!preg_match('/^[0-9]{13}$/', $citizen_id)) {
        $errors[] = "กรุณากรอกเลขบัตรประชาชนให้ถูกต้อง 13 หลัก (เฉพาะตัวเลข)";
    }

    if (empty($errors)) {
        // insert ลงตาราง users
        $sql = "INSERT INTO users (line_uid, display_name, picture_url, first_name, last_name, phone, citizen_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssss",
            $line_uid,
            $display_name,
            $picture_url,
            $first_name,
            $last_name,
            $phone,
            $citizen_id
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container my-5">
        <h2 class="text-center mb-4">สมัครสมาชิก (เชื่อมกับ LINE)</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e): ?>
                    <div><?php echo $e; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <!-- line_uid / display_name ซ่อน / แสดงแบบอ่านอย่างเดียว -->
            <input type="hidden" name="line_uid" value="<?php echo $line_uid; ?>">
            <input type="hidden" name="display_name" value="<?php echo $display_name; ?>">
            <input type="hidden" name="picture_url" value="<?php echo $picture_url; ?>">

            <div class="form-group">
                <?php if ($picture_url): ?>
                    <div class="d-flex justify-content-center">
                        <img src="<?php echo htmlspecialchars($picture_url); ?>"
                            alt="LINE Profile Picture" class="rounded-circle mb-3" width="100" height="100">
                    </div>
                <?php endif; ?>
                <p class="text-center h2"><strong><?php echo $display_name ?: '(ไม่ทราบ)'; ?></strong></p>
            </div>

            <div class="form-group mb-3">
                <label class="h2 font-weight-bold">ชื่อจริง:</label>
                <input type="text" class="form-control form-control-lg" name="first_name" required value="<?php echo $first_name ?? ''; ?>">
            </div>

            <div class="form-group mb-3">
                <label class="h2 font-weight-bold">นามสกุล:</label>
                <input type="text" class="form-control form-control-lg" name="last_name" required value="<?php echo $last_name ?? ''; ?>">
            </div>

            <div class="form-group mb-3">
                <label class="h2 font-weight-bold">เบอร์โทรศัพท์:</label>
                <input type="tel" class="form-control form-control-lg" name="phone" maxlength="10" pattern="^[0-9]{10}$" title="กรุณากรอกเบอร์โทรศัพท์ 10 หลัก (เฉพาะตัวเลข)" required value="<?php echo $phone ?? ''; ?>">
            </div>

            <div class="form-group mb-3">
                <label class="h2 font-weight-bold">เลขบัตรประชาชน 13 หลัก:</label>
                <input type="tel" class="form-control form-control-lg" name="citizen_id" maxlength="13" pattern="^[0-9]{13}$" title="กรุณากรอกเลขบัตรประชาชน 13 หลัก (เฉพาะตัวเลข)" required value="<?php echo $citizen_id ?? ''; ?>">
            </div>

            <div class="d-flex justify-content-center">
                <button type="submit" class="btn btn-primary btn-lg btn-block mb-4">สมัครสมาชิก</button>
            </div>
        </form>
    </div>

    <script>
        document.querySelector("form").addEventListener("submit", function(event) {
            var phone = document.getElementById("phone");
            var citizenId = document.getElementById("citizen_id");

            // รีเซ็ตข้อความผิดพลาด
            document.getElementById("phone-error").innerText = "";
            document.getElementById("citizen-id-error").innerText = "";

            // ตรวจสอบว่าเบอร์โทรศัพท์มีความยาวถูกต้องและไม่มีสัญลักษณ์พิเศษ
            if (!/^[0-9]{10}$/.test(phone.value)) {
                event.preventDefault(); // ป้องกันการส่งฟอร์ม
                document.getElementById("phone-error").innerText = "กรุณากรอกเบอร์โทรศัพท์ 10 หลัก (เฉพาะตัวเลข)";
            }

            // ตรวจสอบว่าเลขบัตรประชาชนมีความยาวถูกต้องและไม่มีสัญลักษณ์พิเศษ
            if (!/^[0-9]{13}$/.test(citizenId.value)) {
                event.preventDefault(); // ป้องกันการส่งฟอร์ม
                document.getElementById("citizen-id-error").innerText = "กรุณากรอกเลขบัตรประชาชน 13 หลัก (เฉพาะตัวเลข)";
            }
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

</body>

</html>