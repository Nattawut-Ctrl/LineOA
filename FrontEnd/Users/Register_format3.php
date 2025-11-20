<?php
session_start();

// ✅ ใช้ db_with_log (มี connectDBWithLog + db_query + writeLog ในตัว)
require_once '../../utils/db_with_log.php';

$conn = connectDBWithLog();

function clean($s)
{
    return trim($s ?? '');
}

$errors = [];

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

            // สมัครสำเร็จ → ไปหน้า Buyer
            header("Location: ../Buyer/Buyer.php");
            exit;

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
    <?php include '../../bootstrap.php'; ?>

    <style>
        /* ฟีลเว็บราชการ: ฟ้า-กรมท่า-ขาว สะอาด เป็นทางการ */

        body.gov-body {
            min-height: 100vh;
            margin: 0;
            background-color: #f3f6fb;
            background-image:
                linear-gradient(to bottom, #e3edf9 0, #f3f6fb 160px),
                radial-gradient(circle at top left, rgba(33, 150, 243, 0.12) 0, transparent 55%);
            font-family: "Sarabun", "TH Sarabun New", -apple-system, BlinkMacSystemFont,
                "Segoe UI", sans-serif;
            color: #1f2933;
        }

        .gov-topbar {
            background-color: #0d47a1;
            border-bottom: 4px solid #ffc107;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.18);
        }

        .gov-topbar .brand-text {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .gov-topbar .brand-sub {
            font-size: 0.78rem;
            opacity: 0.85;
        }

        .gov-emblem-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 20%, #ffe082 0, #f9a825 40%, #f57f17 90%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #4e342e;
            font-size: 1.4rem;
            box-shadow: 0 0 0 2px rgba(255, 241, 118, 0.8);
        }

        .gov-wrapper {
            padding-top: 1.5rem;
            padding-bottom: 2rem;
        }

        .gov-card {
            background-color: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid #d1d9e6;
            box-shadow:
                0 6px 18px rgba(15, 23, 42, 0.12),
                0 0 0 1px rgba(255, 255, 255, 0.6);
            overflow: hidden;
        }

        .gov-card-header {
            background: linear-gradient(90deg, #1565c0, #1976d2);
            color: #ffffff;
            padding-top: 1.1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .gov-card-header h2 {
            font-size: 1.2rem;
            margin-bottom: 0.15rem;
        }

        .gov-card-header small {
            font-size: 0.8rem;
        }

        .gov-badge-sub {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 999px;
            padding: 0.15rem 0.7rem;
            font-size: 0.78rem;
        }

        .gov-badge-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: #c8e6c9;
        }

        .gov-card-body {
            padding: 1.6rem 1.8rem 1.5rem;
        }

        @media (max-width: 576px) {
            .gov-wrapper {
                padding-top: 1rem;
                padding-bottom: 1.5rem;
            }

            .gov-card-body {
                padding: 1.25rem 1.1rem 1.25rem;
            }
        }

        .gov-label {
            font-weight: 600;
            font-size: 0.92rem;
            color: #1f2933;
        }

        .gov-label span.text-danger {
            font-weight: 700;
        }

        .gov-input,
        .gov-select {
            font-size: 0.95rem;
            background-color: #f9fbff;
            border-radius: 0.5rem;
            border: 1px solid #c5cfde;
            color: #111827;
        }

        .gov-input::placeholder {
            color: #9ea8ba;
        }

        .gov-input:focus,
        .gov-select:focus {
            background-color: #ffffff;
            border-color: #1565c0;
            box-shadow: 0 0 0 0.18rem rgba(21, 101, 192, 0.18);
            color: #111827;
        }

        .gov-profile-img {
            border: 3px solid #ffffff;
            box-shadow:
                0 0 0 2px #1565c0,
                0 6px 12px rgba(15, 23, 42, 0.28);
        }

        .gov-profile-fallback {
            background: radial-gradient(circle at top, #e3f2fd 0, #bbdefb 45%, #90caf9 100%);
            border: 3px solid #ffffff;
            box-shadow:
                0 0 0 2px #1565c0,
                0 6px 12px rgba(15, 23, 42, 0.28);
            color: #0d47a1;
        }

        .gov-helper {
            font-size: 0.8rem;
            color: #6b7280;
        }

        .gov-alert {
            background-color: #fff8e1;
            border-color: #ffe082;
            color: #795548;
            font-size: 0.88rem;
        }

        .gov-alert .fw-bold {
            font-size: 0.9rem;
        }

        .btn-gov-primary {
            background: linear-gradient(90deg, #1565c0, #0d47a1);
            border-color: #0d47a1;
            color: #ffffff;
            letter-spacing: 0.03em;
            font-size: 0.95rem;
        }

        .btn-gov-primary:hover {
            background: linear-gradient(90deg, #0d47a1, #0b3c91);
            border-color: #0b3c91;
        }

        .btn-gov-primary:active {
            background: #0b3c91 !important;
            border-color: #082b68 !important;
        }

        @media (max-width: 576px) {
            .btn-gov-primary {
                font-size: 0.9rem;
                padding-top: 0.7rem !important;
                padding-bottom: 0.7rem !important;
            }
        }
    </style>
</head>

<body class="gov-body">

    <!-- แถบหัวเว็บแบบหน่วยงานรัฐ -->
    <header class="gov-topbar">
        <div class="container py-2">
            <div class="d-flex align-items-center gap-2">
                <div class="gov-emblem-circle">
                    <span>⚖️</span>
                </div>
                <div class="ms-2">
                    <div class="text-white brand-text">
                        ระบบขึ้นทะเบียนผู้ใช้งาน
                    </div>
                    <div class="text-white-50 brand-sub">
                        หน่วยงานภาครัฐ / องค์กรภาครัฐ
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container gov-wrapper d-flex align-items-center justify-content-center">
        <div class="gov-card w-100" style="max-width: 520px;">

            <!-- Header -->
            <div class="gov-card-header text-center">
                <h2 class="mb-1 fw-bold">แบบฟอร์มสมัครสมาชิก</h2>
                <div>
                    <small>สำหรับผู้ใช้งานที่เชื่อมบัญชีผ่าน LINE Official</small>
                </div>
                <div class="mt-2 gov-badge-sub">
                    <span class="gov-badge-dot"></span>
                    <span>กรอกข้อมูลให้ถูกต้องและครบถ้วนก่อนยืนยันการสมัคร</span>
                </div>
            </div>

            <div class="gov-card-body">
                <!-- Error messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert gov-alert alert-dismissible fade show rounded-3" role="alert">
                        <div class="fw-bold mb-1">พบข้อผิดพลาดในการบันทึกข้อมูล</div>
                        <?php foreach ($errors as $e): ?>
                            <div class="mb-1">• <?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                            aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <!-- Hidden fields -->
                    <input type="hidden" name="line_uid"
                        value="<?php echo htmlspecialchars($line_uid, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="display_name"
                        value="<?php echo htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="picture_url"
                        value="<?php echo htmlspecialchars($picture_url, ENT_QUOTES, 'UTF-8'); ?>">

                    <!-- Profile section -->
                    <div class="text-center mb-4">
                        <?php if ($picture_url): ?>
                            <img src="<?php echo htmlspecialchars($picture_url, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="LINE Profile Picture"
                                class="rounded-circle mb-2 gov-profile-img"
                                width="110" height="110" style="object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle mb-2 d-inline-flex align-items-center justify-content-center gov-profile-fallback"
                                style="width: 110px; height: 110px;">
                                <span class="fs-2">👤</span>
                            </div>
                        <?php endif; ?>
                        <div class="fw-bold">
                            <?php echo htmlspecialchars($display_name ?: '(ไม่ทราบชื่อจาก LINE)', ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div class="gov-helper">
                            ข้อมูลชื่อโปรไฟล์จากบัญชี LINE ที่ใช้เชื่อมต่อ
                        </div>
                    </div>

                    <!-- Form fields -->

                    <div class="mb-3">
                        <label class="form-label gov-label">
                            คำนำหน้า <span class="text-danger">*</span>
                        </label>
                        <select name="title"
                            class="form-select form-select-lg gov-select" required>
                            <option value="">-- เลือกคำนำหน้า --</option>
                            <option value="นาย" <?php echo ($title === 'นาย') ? 'selected' : ''; ?>>นาย</option>
                            <option value="นาง" <?php echo ($title === 'นาง') ? 'selected' : ''; ?>>นาง</option>
                            <option value="นางสาว" <?php echo ($title === 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label gov-label">
                            ชื่อจริง <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            class="form-control form-control-lg gov-input"
                            name="first_name" required
                            value="<?php echo htmlspecialchars($first_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="ระบุชื่อจริงตามบัตรประชาชน">
                    </div>

                    <div class="mb-3">
                        <label class="form-label gov-label">
                            นามสกุล <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            class="form-control form-control-lg gov-input"
                            name="last_name" required
                            value="<?php echo htmlspecialchars($last_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="ระบุนามสกุลตามบัตรประชาชน">
                    </div>

                    <div class="mb-3">
                        <label class="form-label gov-label">
                            เบอร์โทรศัพท์ติดต่อ <span class="text-danger">*</span>
                        </label>
                        <input type="tel"
                            class="form-control form-control-lg gov-input"
                            id="phone" name="phone"
                            maxlength="12" inputmode="numeric" required
                            value="<?php echo htmlspecialchars($phone ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="ตัวอย่าง 081-234-5678">
                        <small id="phone-error" class="text-danger d-block mt-1"></small>
                        <small class="gov-helper">กรุณาระบุเบอร์โทรศัพท์ที่สามารถติดต่อได้จริง</small>
                    </div>

                    <div class="mb-2">
                        <label class="form-label gov-label">
                            เลขประจำตัวประชาชน 13 หลัก <span class="text-danger">*</span>
                        </label>
                        <input type="tel"
                            class="form-control form-control-lg gov-input"
                            id="citizen_id" name="citizen_id"
                            maxlength="17" inputmode="numeric" required
                            value="<?php echo htmlspecialchars($citizen_id ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="ตัวอย่าง 1-2345-67890-12-3">
                        <small id="citizen-id-error" class="text-danger d-block mt-1"></small>
                        <small class="gov-helper">ข้อมูลนี้ใช้เพื่อยืนยันตัวตนตามหลักเกณฑ์ของหน่วยงานภาครัฐ</small>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit"
                            class="btn btn-lg fw-bold rounded-2 py-3 btn-gov-primary">
                            ✓ ยืนยันการสมัครสมาชิก
                        </button>
                    </div>

                    <p class="text-center gov-helper mt-4 mb-0">
                        การกดปุ่ม “ยืนยันการสมัครสมาชิก” ถือว่าท่านได้อ่านและยอมรับเงื่อนไขการใช้บริการแล้ว
                    </p>
                </form>
            </div>
        </div>
    </div>

    <!-- JS เดิมของการ format เบอร์/บัตร ประชาชน เหมือนที่มีอยู่แล้ว -->
    <script>
        document.querySelector("form").addEventListener("submit", function(event) {
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

        const citizenIdInput = document.getElementById("citizen_id");
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
    </script>

</body>
</html>
