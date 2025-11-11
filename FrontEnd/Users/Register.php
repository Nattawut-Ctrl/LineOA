<?php
$name = $surname = $id_card = $dob = $phone = "";
$name_err = $surname_err = $id_card_err = $dob_err = $phone_err = "";
$success_msg = "";
$general_err = "";

// รวมไฟล์เชื่อมต่อฐานข้อมูล
include('../../config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ตรวจสอบข้อมูลที่ได้รับจากฟอร์ม
    if (empty($_POST["name"])) {
        $name_err = "กรุณากรอกชื่อ";
    } else {
        $name = test_input($_POST["name"]);
    }

    if (empty($_POST["surname"])) {
        $surname_err = "กรุณากรอกนามสกุล";
    } else {
        $surname = test_input($_POST["surname"]);
    }

    if (empty($_POST["id_card"])) {
        $id_card_err = "กรุณากรอกรหัสบัตรประชาชน";
    } elseif (!preg_match("/^\d{13}$/", $_POST["id_card"])) {
        $id_card_err = "รหัสบัตรประชาชนต้องมี 13 หลัก";
    } else {
        $id_card = test_input($_POST["id_card"]);
    }

    if (empty($_POST["dob"])) {
        $dob_err = "กรุณากรอกวันเกิด";
    } else {
        $dob = test_input($_POST["dob"]);
    }

    if (empty($_POST["phone"])) {
        $phone_err = "กรุณากรอกเบอร์โทรศัพท์";
    } elseif (!preg_match("/^\d{10}$/", $_POST["phone"])) {
        $phone_err = "เบอร์โทรศัพท์ต้องมี 10 หลัก";
    } else {
        $phone = test_input($_POST["phone"]);
    }

    // ถ้าไม่มีข้อผิดพลาด ให้บันทึกลงฐานข้อมูล
   if (empty($name_err) && empty($surname_err) && empty($id_card_err) && empty($dob_err) && empty($phone_err)) {
        $conn = connectDB(); // เชื่อมต่อฐานข้อมูล

        // เตรียมคำสั่ง SQL
        $stmt = mysqli_prepare($conn, "INSERT INTO users (name, surname, id_card, dob, phone) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt === false) {
            $general_err = "เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . mysqli_error($conn);
        } else {
            // ผูกตัวแปรกับคำสั่ง SQL (ใช้สัญญาณ ? เพื่อป้องกัน SQL Injection)
            mysqli_stmt_bind_param($stmt, "sssss", $name, $surname, $id_card, $dob, $phone);

            // Execute the query
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "สมัครสมาชิกเรียบร้อยแล้ว";
                // ล้างฟิลด์หลังสมัครสำเร็จ
                $name = $surname = $id_card = $dob = $phone = "";
            } else {
                $general_err = "เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่";
            }

            // ปิดคำสั่ง SQL
            mysqli_stmt_close($stmt);
        }

        // ปิดการเชื่อมต่อฐานข้อมูล
        mysqli_close($conn);
    }
}


// ฟังก์ชันกรองข้อมูลที่ได้รับจากฟอร์ม
function test_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้า Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</head>
<body>

<div class="container mt-5">
    <h2 class="fs-2 lh-lg text-center">ลงทะเบียน</h2>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>
    <?php if (!empty($general_err)): ?>
        <div class="alert alert-danger"><?php echo $general_err; ?></div>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

        <!-- ชื่อ -->
        <div class="mb-3">
            <label for="name" class="form-label fs-5">ชื่อ</label>
            <input type="text" class="form-control <?php echo !empty($name_err) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo $name; ?>">
            <div class="invalid-feedback fs-5"><?php echo $name_err; ?></div>
        </div>

        <!-- นามสกุล -->
        <div class="mb-3">
            <label for="surname" class="form-label fs-5">นามสกุล</label>
            <input type="text" class="form-control <?php echo !empty($surname_err) ? 'is-invalid' : ''; ?>" id="surname" name="surname" value="<?php echo $surname; ?>">
            <div class="invalid-feedback fs-5"><?php echo $surname_err; ?></div>
        </div>

        <!-- รหัสบัตรประชาชน -->
        <div class="mb-3">
            <label for="id_card" class="form-label fs-5">รหัสบัตรประชาชน</label>
            <input type="text" class="form-control <?php echo !empty($id_card_err) ? 'is-invalid' : ''; ?>" id="id_card" name="id_card" value="<?php echo $id_card; ?>" maxlength="13">
            <div class="invalid-feedback fs-5"><?php echo $id_card_err; ?></div>
        </div>

        <!-- วัน/เดือน/ปีเกิด -->
        <div class="mb-3">
            <label for="dob" class="form-label fs-5">วัน/เดือน/ปีเกิด</label>
            <input type="date" class="form-control <?php echo !empty($dob_err) ? 'is-invalid' : ''; ?>" id="dob" name="dob" value="<?php echo $dob; ?>">
            <div class="invalid-feedback fs-5"><?php echo $dob_err; ?></div>
        </div>

        <!-- เบอร์โทรศัพท์ -->
        <div class="mb-3">
            <label for="phone" class="form-label fs-5">เบอร์โทรศัพท์</label>
            <input type="text" class="form-control <?php echo !empty($phone_err) ? 'is-invalid' : ''; ?>" id="phone" name="phone" value="<?php echo $phone; ?>" maxlength="10" >
            <div class="invalid-feedback fs-5"><?php echo $phone_err; ?></div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg py-3 px-5 mb-3">สมัครสมาชิก</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
