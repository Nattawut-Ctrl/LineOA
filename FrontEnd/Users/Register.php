<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';

$conn = connectDBWithLog();

function clean($s)
{
    return trim($s ?? '');
}

$errors = [];
// ใช้เช็คว่าสมัครสำเร็จไหม
$register_success = false;

// รับค่า GET จาก checkLineUser.php
$line_uid     = clean($_GET['line_uid']     ?? '');
$display_name = clean($_GET['display_name'] ?? '');
$picture_url  = clean($_GET['picture_url']  ?? '');

$first_name = '';
$last_name  = '';
$phone      = '';
$citizen_id = '';
$title      = '';

// ถ้า POST (กดปุ่มสมัคร)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $line_uid     = clean($_POST['line_uid']     ?? '');
    $display_name = clean($_POST['display_name'] ?? '');
    $picture_url  = clean($_POST['picture_url']  ?? '');
    $title        = clean($_POST['title']        ?? '');
    $first_name   = clean($_POST['first_name']   ?? '');
    $last_name    = clean($_POST['last_name']    ?? '');
    $phone        = clean($_POST['phone']        ?? '');
    $citizen_id   = clean($_POST['citizen_id']   ?? '');

    $errors = [];

    // ดึงค่าดิบจากฟอร์ม
    $phone_raw      = $_POST['phone']      ?? '';
    $citizen_raw    = $_POST['citizen_id'] ?? '';

    // เอาเฉพาะตัวเลข
    $phone_digits   = preg_replace('/\D/', '', $phone_raw);
    $citizen_digits = preg_replace('/\D/', '', $citizen_raw);

    $phone      = $phone_digits;
    $citizen_id = $citizen_digits;

    if ($line_uid === '') {
        $errors[] = "ไม่พบ LINE UID กรุณาเข้าสมัครผ่านปุ่มใน LINE อีกครั้ง";
    }
    if ($title === '') {
        $errors[] = "กรุณาเลือกคำนำหน้า";
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

        // ✅ INSERT ผ่าน db_query → จะเขียน log ให้อัตโนมัติ (action=insert, table=users)
        $sql = "
            INSERT INTO users 
                (line_uid, display_name, picture_url, title, first_name, last_name, phone, citizen_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";

        try {
            db_query(
                $conn,
                $sql,
                [
                    $line_uid,
                    $display_name,
                    $picture_url,
                    $title,
                    $first_name,
                    $last_name,
                    $phone,
                    $citizen_id
                ],
                "ssssssss"
            );

            // ดึง id ที่เพิ่ง insert
            $user_id = $conn->insert_id;
            $_SESSION['user_id'] = $user_id;

            // สมัครสำเร็จแล้ว ให้แสดงหน้า success
            $register_success = true;
        } catch (Throwable $e) {
            // ถ้า INSERT fail db_query จะเขียน log status=error ให้แล้ว
            $errors[] = "บันทึกข้อมูลไม่สำเร็จ: " . $e->getMessage();
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
    <?php include BASE_PATH . '/partials/bootstrap.php'; ?>
    <?php include FRONTEND_PATH . '/partials/register_style.php'; ?>

</head>

<body class="auth-bg">

    <div class="container d-flex align-items-center justify-content-center min-vh-100 py-5">
        <div class="card auth-card w-100" style="max-width: 480px;">

            <?php if ($register_success): ?>
                <!-- Header: สมัครสมาชิกสำเร็จ -->
                <div class="card-header auth-card-header text-center py-4">
                    <h2 class="mb-0 fw-bold">สมัครสมาชิกสำเร็จ</h2>
                    <small class="text-white-50">บัญชีของคุณถูกบันทึกเรียบร้อยแล้ว</small>
                </div>

                <div class="card-body p-4 text-center">
                    <div class="mb-4">
                        <span class="display-3">🎉</span>
                    </div>
                    <p class="fs-5 mb-4">
                        ขอบคุณที่สมัครใช้งานระบบ <strong>Line Shop</strong><br>
                        คุณสามารถเริ่มเลือกซื้อสินค้าได้ทันที
                    </p>

                    <div class="d-grid gap-2">
                        <a href="../Buyer/Buyer.php"
                            class="btn btn-success btn-lg fw-bold rounded-2 py-3">
                            ไปหน้าเลือกซื้อสินค้า
                        </a>

                        <button type="button"
                            class="btn btn-outline-secondary btn-lg fw-bold rounded-2 py-3"
                            onclick="if(window.liff){ liff.closeWindow(); } else { window.history.back(); }">
                            กลับหน้าก่อนหน้า
                        </button>
                    </div>
                </div>

            <?php else: ?>
                <!-- Header: สมัครสมาชิก -->
                <div class="card-header auth-card-header text-center py-4">
                    <h2 class="mb-0 fw-bold">สมัครสมาชิก</h2>
                    <small class="text-white-50">เชื่อมกับ LINE</small>
                </div>

                <div class="card-body p-4">
                    <!-- Error messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                            <div class="fw-bold mb-2">⚠️ พบข้อผิดพลาด:</div>
                            <?php foreach ($errors as $e): ?>
                                <div class="small mb-1">✗ <?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endforeach; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <!-- Hidden fields -->
                        <input type="hidden" name="line_uid" value="<?php echo htmlspecialchars($line_uid, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="display_name" value="<?php echo htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="picture_url" value="<?php echo htmlspecialchars($picture_url, ENT_QUOTES, 'UTF-8'); ?>">

                        <!-- Profile section -->
                        <div class="text-center mb-4">
                            <?php if ($picture_url): ?>
                                <img src="<?php echo htmlspecialchars($picture_url, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="LINE Profile Picture" class="rounded-circle shadow-sm mb-3" width="120" height="120" style="border: 4px solid #fff; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 120px; height: 120px; border: 4px solid #fff;">
                                    <span class="fs-1 text-white">👤</span>
                                </div>
                            <?php endif; ?>
                            <h4 class="fw-bold text-dark mt-2"><?php echo htmlspecialchars($display_name ?: '(ไม่ทราบ)', ENT_QUOTES, 'UTF-8'); ?></h4>
                            <small class="text-muted">จาก LINE</small>
                        </div>

                        <!-- Form fields -->
                        <div class="mb-4">
                            <label class="form-label fw-bold fs-6 text-dark">คำนำหน้า <span class="text-danger">*</span></label>
                            <select name="title" class="form-select">
                                <option value="">--เลือกคำนำหน้า--</option>
                                <option value="นาย" <?php echo (isset($title) && $title === 'นาย') ? 'selected' : ''; ?>>นาย</option>
                                <option value="นาง" <?php echo (isset($title) && $title === 'นาง') ? 'selected' : ''; ?>>นาง</option>
                                <option value="นางสาว" <?php echo (isset($title) && $title === 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold fs-6 text-dark">ชื่อจริง <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg rounded-2 border-2" name="first_name" required
                                value="<?php echo htmlspecialchars($first_name ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="กรอกชื่อจริง">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold fs-6 text-dark">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg rounded-2 border-2" name="last_name" required
                                value="<?php echo htmlspecialchars($last_name ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="กรอกนามสกุล">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold fs-6 text-dark">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control form-control-lg rounded-2 border-2" id="phone" name="phone"
                                maxlength="12" inputmode="numeric" required
                                value="<?php echo htmlspecialchars($phone ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="xxx-xxx-xxxx">
                            <small id="phone-error" class="text-danger d-block mt-1"></small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold fs-6 text-dark">เลขบัตรประชาชน 13 หลัก <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control form-control-lg rounded-2 border-2" id="citizen_id" name="citizen_id"
                                maxlength="17" inputmode="numeric" required
                                value="<?php echo htmlspecialchars($citizen_id ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="1-2345-67890-12-3">
                            <small id="citizen-id-error" class="text-danger d-block mt-1"></small>
                        </div>

                        <div class="d-grid gap-2 mt-5">
                            <button type="submit" class="btn btn-auth-primary btn-lg fw-bold rounded-2 py-3 text-white shadow-sm">
                                ✓ สมัครสมาชิก
                            </button>
                        </div>

                        <p class="text-center text-muted small mt-4 mb-0">
                            การสมัครแสดงว่าคุณยอมรับเงื่อนไขการใช้บริการ
                        </p>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JS เดิมของการ format เบอร์/บัตร ประชาชน -->
    <script>
        const form = document.querySelector("form");
        if (form) {
            form.addEventListener("submit", function(event) {
                var phone = document.getElementById("phone");
                var citizenId = document.getElementById("citizen_id");

                document.getElementById("phone-error").innerText = "";
                document.getElementById("citizen-id-error").innerText = "";

                const phoneDigits = phone.value.replace(/\D/g, '');
                const citizenDigits = citizenId.value.replace(/\D/g, '');

                if (!/^[0-9]{10}$/.test(phoneDigits)) {
                    event.preventDefault();
                    document.getElementById("phone-error").innerText =
                        "กรุณากรอกเบอร์โทรศัพท์ 10 หลัก (เฉพาะตัวเลข)";
                }

                if (!/^[0-9]{13}$/.test(citizenDigits)) {
                    event.preventDefault();
                    document.getElementById("citizen-id-error").innerText =
                        "กรุณากรอกเลขบัตรประชาชน 13 หลัก (เฉพาะตัวเลข)";
                }
            });

            const phoneInput = document.getElementById("phone");
            if (phoneInput) {
                phoneInput.addEventListener("input", function() {
                    let value = this.value.replace(/\D/g, '');

                    if (value.length > 3 && value.length <= 6) {
                        this.value = value.slice(0, 3) + '-' + value.slice(3);
                    } else if (value.length > 6) {
                        this.value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 10);
                    } else {
                        this.value = value;
                    }
                });
            }

            const citizenIdInput = document.getElementById("citizen_id");
            if (citizenIdInput) {
                citizenIdInput.addEventListener("input", function() {
                    let value = this.value.replace(/\D/g, '');
                    let len = value.length;
                    let result = '';

                    if (len > 0) result = value.slice(0, 1);
                    if (len > 1) result += "-" + value.slice(1, 5);
                    if (len > 5) result += "-" + value.slice(5, 10);
                    if (len > 10) result += "-" + value.slice(10, 12);
                    if (len > 12) result += "-" + value.slice(12, 13);

                    this.value = result;
                });
            }
        }
    </script>

</body>

</html>