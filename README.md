# 1. Run server : php -S 0.0.0.0:3000

# 2. Run Simulation Hosting : ngrok http 3000

Line Shop / LineOA Web App (PHP)

ระบบร้านค้า/จัดการสต็อก + อัปโหลดสลิป + แอดมินตรวจสอบการชำระเงิน รองรับการใช้งานผ่านหน้าเว็บ (สามารถนำไปผูกกับ LINE OA / LIFF ได้ตามลิงก์ที่คุณตั้งค่า)

โครงสร้างโปรเจกต์ (ภาพรวม)

โดยแนวคิดของโปรเจกต์นี้จะแยกเป็น 2 ฝั่งหลัก:

backEnd/ : ส่วนแอดมิน (จัดการสินค้า/สต็อก/ตรวจสลิป/อนุมัติ)

frontEnd/ : ส่วนผู้ใช้ (ดูสินค้า/สั่งซื้อ/ชำระเงิน/อัปโหลดสลิป/ประวัติ)

โฟลเดอร์ที่อยู่นอก backEnd/ frontEnd/ จะเป็นส่วนที่ “ใช้ร่วมกัน” เช่น assets/, partials/, services/, utils/, uploads/

เทคโนโลยีที่ใช้

PHP (แนะนำ PHP 8.0+)

MySQL / MariaDB

Composer

Cloudinary SDK (อัปโหลดรูปไป Cloudinary)

PHPMailer (ส่งอีเมลแจ้งเตือน)

Dropzone (อัปโหลดไฟล์แบบ drag & drop)

Requirements

PHP >= 8.0

MySQL/MariaDB

Apache/Nginx (แนะนำใช้ Apache + mod_rewrite ถ้าทำ routing เพิ่ม)

Composer

(ตัวเลือก) Node.js + npm (ถ้าจะติดตั้ง Dropzone ผ่าน npm)

Installation
1) Clone/วางโปรเจกต์ลงเว็บเซิร์ฟเวอร์

ตัวอย่าง (Apache/XAMPP):

วางไว้ใน htdocs/LineShop หรือ htdocs/LineOA-main เป็นต้น

2) ติดตั้ง PHP Dependencies (Composer)

ที่ root โปรเจกต์ รัน:

composer install


หากต้องการติดตั้งแบบระบุแพ็กเกจเอง (อ้างอิงจาก composer.json):

composer require cloudinary/cloudinary_php:^3.1
composer require phpmailer/phpmailer:^7.0


หมายเหตุ: โปรเจกต์นี้เรียกใช้งาน vendor/autoload.php ดังนั้นต้องมี vendor/ จาก composer

3) (ตัวเลือก) ติดตั้ง Dropzone ผ่าน npm

โปรเจกต์มี assets/dropzone/* อยู่แล้ว (สามารถใช้งานได้ทันทีโดยไม่ต้อง npm)

แต่ถ้าคุณต้องการติดตั้ง/อัปเดตผ่าน npm ให้รัน:

npm install


หรือ

npm install dropzone@^6.0.0-beta.2


ถ้าใช้ npm แล้ว คุณต้องปรับ <script src> / <link href> ให้ชี้ไป node_modules/ หรือทำขั้น build/copy ไฟล์เข้่า assets/ เอง

Configuration
1) ตั้งค่า Database

สร้างฐานข้อมูล เช่น line_shop

import ไฟล์ database.sql

ตัวอย่าง:

CREATE DATABASE line_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE line_shop;
-- import database.sql


จากนั้นไปแก้ค่าการเชื่อมต่อ DB ในไฟล์ config หลักของโปรเจกต์ (ตัวอย่างชื่อ):

config.php (บางโปรเจกต์) หรือ

config/config.php (LineShop โครงสร้างใหม่)

ตัวอย่างค่าที่ต้องมี:

host (ปกติ localhost)

user (เช่น root)

pass

dbname (เช่น line_shop)

2) ตั้งค่า BASE_URL (กรณีใช้ ngrok/โดเมนจริง)

โปรเจกต์รองรับ “auto BASE_URL ตาม host” อยู่แล้ว แต่ถ้าคุณต้อง fix โดเมน (เช่น ngrok/โดเมนบริษัท) ให้กำหนด BASE_URL ให้ชัดเจนใน config

ตัวอย่างแนวทาง:

ใช้ https://xxxx.ngrok-free.dev

หรือ https://yourdomain.com

3) ตั้งค่า Cloudinary (อัปโหลดรูป)

มี 2 แนวในโปรเจกต์:

แบบตัวอย่างไฟล์ utils/cloudinary_config.example.php (LineOA)

แบบไฟล์จริง config/cloudinary_config.php (LineShop)

วิธีแนะนำ (ปลอดภัย):

สร้างไฟล์จริงจากตัวอย่าง

คัดลอก utils/cloudinary_config.example.php ไปเป็น utils/cloudinary_config.php
หรือใช้ config/cloudinary_config.php แล้วกรอกค่า

ใส่ค่าจาก Cloudinary Dashboard:

cloud_name

api_key

api_secret

ตัวอย่าง:

\Cloudinary\Configuration\Configuration::instance([
  'cloud' => [
    'cloud_name' => 'YOUR_CLOUD_NAME',
    'api_key'    => 'YOUR_API_KEY',
    'api_secret' => 'YOUR_API_SECRET',
  ],
  'url' => ['secure' => true],
]);


แนะนำเพิ่ม cloudinary_config.php เข้า .gitignore เพื่อไม่ให้คีย์หลุด

4) ตั้งค่า Email (PHPMailer)

ใน LineShop มีไฟล์ตัวอย่าง config/mail_config.php ซึ่งกำหนด:

SMTP_HOST, SMTP_PORT

SMTP_USER, SMTP_PASS (แนะนำใช้ App Password)

SMTP_FROM_EMAIL, SMTP_FROM_NAME

ADMIN_NOTIFY_EMAIL (อีเมลแอดมินที่รับแจ้งเตือน)

ตัวอย่าง:

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'app_password');
define('ADMIN_NOTIFY_EMAIL', 'admin@example.com');


ถ้าใช้โดเมนบริษัท ให้เปลี่ยน SMTP_HOST/PORT และ credential ให้ตรงกับผู้ให้บริการ (เช่น Google Workspace / Microsoft 365 / SMTP ของบริษัท)

โฟลเดอร์ที่ต้องให้เขียนได้ (Permissions)

ตรวจสอบให้ web server เขียนได้:

uploads/ (เช่น uploads/products, uploads/variants, uploads/slips)

ถ้ามีการเก็บไฟล์ชั่วคราว/รูปที่อัปโหลดก่อนส่ง Cloudinary

การใช้งานระบบ (User Guide)
1) ฝั่งผู้ใช้ (FrontEnd)

ตำแหน่งโดยทั่วไป:

frontEnd/shop/view/ หรือ FrontEnd/Buyer/ (ชื่ออาจต่างตามโครงสร้าง)

Flow หลัก:

ผู้ใช้เข้าหน้า Shop/Buyer (รายการสินค้า)

กดดูรายละเอียดสินค้า (product detail)

เพิ่มสินค้าเข้าตะกร้า / ไปหน้าชำระเงิน

อัปโหลดสลิป (อาจใช้ Dropzone)

ดูประวัติคำสั่งซื้อ/แจ้งเตือน

เมนูทั่วไป:

หน้าแรก (สินค้า)

ออเดอร์/ประวัติ

แจ้งเตือน

โปรไฟล์

2) ฝั่งแอดมิน (BackEnd)

ตำแหน่งโดยทั่วไป:

backEnd/admin/view/ หรือ BackEnd/

ความสามารถหลัก:

เข้าสู่ระบบแอดมิน (auth)

Dashboard

จัดการสินค้า/สต็อก (เพิ่ม/แก้/ลบ, variant, อัปโหลดรูป)

ตรวจสอบการชำระเงิน (payments)

เปลี่ยนสถานะการชำระเงิน (อนุมัติ/ปฏิเสธ)

(ถ้าตั้งค่า PHPMailer) ส่งอีเมลแจ้งเตือนแอดมินเมื่อมีสลิปใหม่

Assets / Libraries
Dropzone

ไฟล์อยู่ที่:

assets/dropzone/dropzone-min.js

assets/dropzone/dropzone.css (บางโปรเจกต์)

การใช้งาน:

ใช้สำหรับอัปโหลดรูป/ไฟล์แบบ drag & drop ในหน้าเพิ่มสินค้า/อัปโหลดสลิป

Cloudinary

ใช้สำหรับ:

อัปโหลดรูปสินค้า/รูป variant ไปเก็บภายนอก

ลดพื้นที่จัดเก็บในเซิร์ฟเวอร์

PHPMailer

ใช้สำหรับ:

ส่งอีเมลแจ้งเตือนแอดมิน (เช่น เมื่อมีการอัปโหลดสลิปใหม่)

Troubleshooting

Composer แล้วหา vendor/autoload.php ไม่เจอ
→ รัน composer install ที่ root โปรเจกต์

อัปโหลดรูปแล้วไม่ขึ้น / permission denied
→ ตรวจสอบสิทธิ์เขียน uploads/ และ path ที่บันทึกไฟล์

Cloudinary อัปโหลดไม่สำเร็จ
→ ตรวจสอบ cloud_name/api_key/api_secret และการ include config cloudinary

ส่งเมลไม่ได้
→ ตรวจสอบ SMTP + App Password (ห้ามใช้รหัส Gmail จริง), เปิดการใช้งาน SMTP/Allow ตามผู้ให้บริการ

Security Notes (แนะนำ)

อย่า commit คีย์ Cloudinary/SMTP ลง Git

แนะนำใช้ .env หรือไฟล์ config ที่อยู่ใน .gitignore

ตรวจสอบ admin_guard และ user_guard ให้ครอบคลุมทุกหน้าที่ต้องป้องกัน