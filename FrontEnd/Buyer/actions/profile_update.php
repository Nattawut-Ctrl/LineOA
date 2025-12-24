<?php
require_once __DIR__ . '/../components/init.php';

// รับค่า + trim
$title      = trim($_POST['title'] ?? '');
$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$phone      = trim($_POST['phone'] ?? '');

// ------------------------
// 1) Validation เบื้องต้น
// ------------------------

// จำกัดคำนำหน้า (กันคนยิงค่าแปลก ๆ)
$allowedTitles = ['', 'นาย', 'นาง', 'นางสาว'];
if (!in_array($title, $allowedTitles, true)) {
    header("Location: profile_edit.php?error=invalid_title");
    exit;
}

// ชื่อ/นามสกุล (ไม่บังคับก็ได้ แต่ถ้าจะบังคับให้กรอก ให้แก้เงื่อนไขนี้)
if ($first_name !== '' && mb_strlen($first_name) > 100) {
    header("Location: profile_edit.php?error=first_name_too_long");
    exit;
}
if ($last_name !== '' && mb_strlen($last_name) > 100) {
    header("Location: profile_edit.php?error=last_name_too_long");
    exit;
}

// เบอร์โทร: ถ้าใส่มาให้เป็นเลข 9-10 หลัก (ไทย) หรือเริ่ม 0 10 หลัก
// คุณจะปรับกฎได้ตามต้องการ
if ($phone !== '') {
    // เอาอักขระที่ไม่ใช่ตัวเลขออก (กันใส่ - หรือ เว้นวรรค)
    $phoneDigits = preg_replace('/\D+/', '', $phone);

    // ตัวอย่างกฎ: ต้อง 9-10 หลัก และถ้า 10 หลักต้องขึ้นต้นด้วย 0
    if (!(strlen($phoneDigits) === 9 || strlen($phoneDigits) === 10)) {
        header("Location: profile_edit.php?error=invalid_phone");
        exit;
    }
    if (strlen($phoneDigits) === 10 && $phoneDigits[0] !== '0') {
        header("Location: profile_edit.php?error=invalid_phone");
        exit;
    }

    // เก็บเป็นตัวเลขล้วนใน DB
    $phone = $phoneDigits;
}

// ------------------------
// 2) Update ผ่าน Service
// ------------------------
$data = [
    'title'      => $title,
    'first_name' => $first_name,
    'last_name'  => $last_name,
    'phone'      => $phone,
];

// updateUser จะอัปเดตเฉพาะ key ที่ส่งไป
// (ถ้าคุณไม่อยากให้ "ค่าว่าง" ไปทับของเดิม ให้คุณตัด key ที่เป็น '' ออกก่อน)
// ตัวอย่าง: ถ้าค่าว่างให้ข้าม
foreach (['title','first_name','last_name','phone'] as $k) {
    if (!array_key_exists($k, $data)) continue;
    // ถ้าคุณต้องการ "อนุญาตลบค่าให้ว่างได้" ให้ลบบล็อกนี้ออก
    if ($data[$k] === '') unset($data[$k]);
}

try {
    $ok = updateUser($conn, (int)$user_id, $data);

    // ถ้าไม่มี field ให้ update (เช่น user ไม่ได้แก้อะไร) ก็ถือว่าสำเร็จได้
    header("Location: profile_edit.php?success=1");
    exit;

} catch (Throwable $e) {
    // ในงานจริงอาจ log $e->getMessage()
    header("Location: profile_edit.php?error=update_failed");
    exit;
}
