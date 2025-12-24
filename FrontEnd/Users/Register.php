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

    $phone_raw   = $_POST['phone']      ?? '';
    $citizen_raw = $_POST['citizen_id'] ?? '';

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

            $user_id = $conn->insert_id;
            $_SESSION['user_id'] = $user_id;

            $register_success = true;
        } catch (Throwable $e) {
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
    <?php include BASE_PATH . '/shared/partials/bootstrap.php'; ?>
    <!-- ฟอนต์ Kanit (ถ้ายังไม่ได้โหลดใน bootstrap.php) -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <?php include FRONTEND_PATH . '/partials/register_style.php'; ?>
</head>

<body>

    <!-- ================= INTRO SHOPPING SCREEN ================= -->
    <div id="introScreen">
        <div class="intro-card">
            <div class="intro-header">
                <div class="intro-icon-wrap">
                    <i class="bi bi-cart-check"></i>
                    <div class="intro-tag">
                        <i class="bi bi-stars"></i>
                        <span>NEW</span>
                    </div>
                </div>
                <div>
                    <div class="intro-title-main">กำลังเตรียมร้านให้คุณ...</div>
                    <div class="intro-title-sub">เชื่อมต่อระบบและดึงข้อมูลจาก LINE</div>
                </div>
            </div>

            <div class="intro-cart-track">
                <div class="intro-cart">
                    <div class="intro-cart-inner">
                        <div class="intro-cart-item"></div>
                        <div class="intro-cart-item"></div>
                        <div class="intro-cart-item"></div>
                    </div>
                    <div class="intro-cart-wheel left"></div>
                    <div class="intro-cart-wheel right"></div>
                </div>
                <div class="intro-track-line"></div>
            </div>

            <div class="intro-progress-wrap">
                <div class="intro-progress-bar">
                    <div class="intro-progress-fill"></div>
                </div>
            </div>

            <div class="intro-bottom">
                <span>กำลังโหลดแบบฟอร์มสมัครสมาชิก...</span>
                <button type="button" class="intro-skip-btn" id="introSkipBtn">
                    ข้าม
                </button>
            </div>
        </div>
    </div>

    <!-- ================= REGISTER FORM ================= -->
    <div class="container container-register d-flex align-items-center justify-content-center min-vh-100 py-4">
        <div class="card-register" id="registerWrapper">
            <?php if ($register_success): ?>
                <!-- โหมดสมัครสำเร็จ ใช้การ์ดโทนเดียวกัน -->
                <div class="card-header-cute">
                    <div class="header-top-line">
                        <div class="header-brand">
                            <div class="header-logo">
                                <i class="bi bi-bag-check"></i>
                            </div>
                            <div>
                                <div class="header-text-main">สมัครสมาชิกสำเร็จ</div>
                                <div class="header-text-sub">บัญชีของคุณถูกบันทึกเรียบร้อยแล้ว</div>
                            </div>
                        </div>
                        <div class="header-pill">
                            <i class="bi bi-check-circle me-1"></i> Ready to shop
                        </div>
                    </div>
                </div>
                <div class="card-body-cute text-center">
                    <div class="success-icon-big">🎉</div>
                    <div class="success-text-main mb-1">
                        ขอบคุณที่สมัครใช้งานระบบ <strong>Line Shop</strong>
                    </div>
                    <div class="success-text-sub mb-4">
                        คุณสามารถเริ่มเลือกซื้อสินค้าได้ทันที หรือกลับไปที่ LINE ของคุณ
                    </div>

                    <div class="d-grid gap-2">
                        <a href="../Buyer/Buyer.php"
                           class="btn btn-success btn-success-soft">
                            <i class="bi bi-cart-plus me-1"></i> ไปหน้าเลือกซื้อสินค้า
                        </a>
                        <button type="button"
                                class="btn btn-outline-secondary btn-outline-soft"
                                onclick="if(window.liff){ liff.closeWindow(); } else { window.history.back(); }">
                            กลับหน้าก่อนหน้า
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <!-- โหมดฟอร์มสมัครสมาชิก -->
                <div class="card-header-cute">
                    <div class="header-top-line">
                        <div class="header-brand">
                            <div class="header-logo">
                                <i class="bi bi-chat-heart"></i>
                            </div>
                            <div>
                                <div class="header-text-main">LINE Member</div>
                                <div class="header-text-sub">เชื่อมบัญชีอย่างปลอดภัย</div>
                            </div>
                        </div>
                        <div class="header-pill">
                            <i class="bi bi-shield-check me-1"></i> Verified
                        </div>
                    </div>

                    <div class="header-main-title">
                        <h2>สมัครสมาชิกใหม่</h2>
                        <p>ล็อกอินด้วย LINE แล้วกรอกข้อมูลไม่กี่ข้อ ก็เริ่มใช้งานระบบได้ทันที</p>

                        <div class="loading-dots" aria-hidden="true">
                            <div class="dot"></div>
                            <div class="dot"></div>
                            <div class="dot"></div>
                        </div>
                    </div>
                </div>

                <div class="card-body-cute">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-soft alert-dismissible fade show mb-3" role="alert">
                            <div class="alert-soft-title">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                <span>กรุณาตรวจสอบข้อมูลอีกครั้ง</span>
                            </div>
                            <ul class="mb-0">
                                <?php foreach ($errors as $e): ?>
                                    <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" id="registerForm">
                        <!-- Hidden fields -->
                        <input type="hidden" name="line_uid"
                            value="<?php echo htmlspecialchars($line_uid, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="display_name"
                            value="<?php echo htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="picture_url"
                            value="<?php echo htmlspecialchars($picture_url, ENT_QUOTES, 'UTF-8'); ?>">

                        <!-- Profile -->
                        <div class="profile-box">
                            <div class="profile-avatar-wrap">
                                <div class="profile-avatar-inner">
                                    <?php if ($picture_url): ?>
                                        <img src="<?php echo htmlspecialchars($picture_url, ENT_QUOTES, 'UTF-8'); ?>"
                                             alt="LINE Profile Picture" class="profile-img">
                                    <?php else: ?>
                                        <div class="profile-placeholder">
                                            <span>👤</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="profile-name">
                                <?php echo htmlspecialchars($display_name ?: '(ไม่ทราบชื่อจาก LINE)', ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div class="profile-sub">
                                ข้อมูลจาก LINE Official Account
                            </div>
                        </div>

                        <!-- Fields -->
                        <div class="mb-3">
                            <label class="field-label">
                                <span>คำนำหน้า <span class="required">*</span></span>
                            </label>
                            <div class="input-shell">
                                <select name="title" class="select-cute" required>
                                    <option value="">-- เลือกคำนำหน้า --</option>
                                    <option value="นาย" <?php echo ($title === 'นาย') ? 'selected' : ''; ?>>นาย</option>
                                    <option value="นาง" <?php echo ($title === 'นาง') ? 'selected' : ''; ?>>นาง</option>
                                    <option value="นางสาว" <?php echo ($title === 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                                </select>
                                <div class="input-highlight"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="field-label">
                                <span>ชื่อจริง <span class="required">*</span></span>
                            </label>
                            <div class="input-shell">
                                <input type="text"
                                    class="input-cute"
                                    name="first_name" required
                                    value="<?php echo htmlspecialchars($first_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="กรอกชื่อจริงของคุณ">
                                <div class="input-highlight"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="field-label">
                                <span>นามสกุล <span class="required">*</span></span>
                            </label>
                            <div class="input-shell">
                                <input type="text"
                                    class="input-cute"
                                    name="last_name" required
                                    value="<?php echo htmlspecialchars($last_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="กรอกนามสกุลของคุณ">
                                <div class="input-highlight"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="field-label">
                                <span>เบอร์โทรศัพท์ <span class="required">*</span></span>
                                <span class="field-hint">ใช้สำหรับติดต่อยืนยันการใช้งาน</span>
                            </label>
                            <div class="input-shell">
                                <input type="tel"
                                    class="input-cute"
                                    id="phone" name="phone"
                                    maxlength="12" inputmode="numeric" required
                                    value="<?php echo htmlspecialchars($phone ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="0xx-xxx-xxxx">
                                <div class="input-highlight"></div>
                            </div>
                            <div id="phone-error" class="error-text"></div>
                        </div>

                        <div class="mb-2">
                            <label class="field-label">
                                <span>เลขบัตรประชาชน 13 หลัก <span class="required">*</span></span>
                                <span class="field-hint">ข้อมูลจะถูกเก็บรักษาอย่างปลอดภัย</span>
                            </label>
                            <div class="input-shell">
                                <input type="tel"
                                    class="input-cute"
                                    id="citizen_id" name="citizen_id"
                                    maxlength="17" inputmode="numeric" required
                                    value="<?php echo htmlspecialchars($citizen_id ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="1-2345-67890-12-3">
                                <div class="input-highlight"></div>
                            </div>
                            <div id="citizen-id-error" class="error-text"></div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn-cute-primary">
                                <span>สมัครสมาชิก</span>
                                <i class="bi bi-arrow-right-short"></i>
                            </button>
                        </div>

                        <p class="bottom-note">
                            การสมัครแสดงว่าคุณยอมรับเงื่อนไขการใช้บริการและนโยบายความเป็นส่วนตัวของระบบ
                        </p>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JS validate + format เบอร์ / บัตร -->
    <script>
        (function () {
            const form = document.getElementById("registerForm");
            if (form) {
                form.addEventListener("submit", function (event) {
                    const phone = document.getElementById("phone");
                    const citizenId = document.getElementById("citizen_id");

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
                    phoneInput.addEventListener("input", function () {
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
                    citizenIdInput.addEventListener("input", function () {
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
        })();
    </script>

    <!-- JS คุม intro → ไปหน้า register -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const intro = document.getElementById("introScreen");
            const skipBtn = document.getElementById("introSkipBtn");
            const wrapper = document.getElementById("registerWrapper");
            const hasErrors = <?php echo !empty($errors) ? 'true' : 'false'; ?>;
            const isSuccess = <?php echo $register_success ? 'true' : 'false'; ?>;

            function showRegister() {
                if (intro) intro.classList.add("hidden-intro");
                if (wrapper) wrapper.classList.add("show");
            }

            // ถ้ามี error หรือสมัครเสร็จแล้ว → ไม่ต้องโชว์ intro
            if (hasErrors || isSuccess) {
                showRegister();
                return;
            }

            setTimeout(showRegister, 2400);

            if (skipBtn) {
                skipBtn.addEventListener("click", function () {
                    showRegister();
                });
            }
        });
    </script>

</body>
</html>
