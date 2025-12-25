# Line Shop Management System

## Description
Line Shop Management System คือระบบร้านค้าออนไลน์ที่เชื่อมต่อกับ LINE Official Account (LINE OA)  
ผู้ใช้งานสามารถเลือกซื้อสินค้า ชำระเงิน และอัปโหลดสลิปผ่านหน้าเว็บ  
ในขณะที่ผู้ดูแลระบบสามารถจัดการสินค้า สต็อก และตรวจสอบการชำระเงินผ่านระบบหลังบ้าน

โปรเจกต์นี้ถูกออกแบบโดยเน้นโครงสร้างที่ชัดเจน แยกหน้าที่ของโค้ด  
เพื่อให้ง่ายต่อการดูแล ปรับปรุง และต่อยอดในอนาคต

---

## Features

### ฝั่งผู้ซื้อ (Buyer / Frontend)
- Login ด้วย LINE OA
- จัดการข้อมูลผู้ใช้ (Profile)
- ระบบตะกร้าสินค้า
- ดูรายละเอียดสินค้าและตัวเลือกสินค้า
- อัปโหลดสลิปการชำระเงิน
- ระบบแจ้งเตือนสถานะคำสั่งซื้อ

### ฝั่งผู้ดูแลระบบ (Admin / Backend)
- ระบบหลังบ้านสำหรับผู้ดูแล
- จัดการสินค้าและตัวเลือกสินค้า (Variants)
- จัดการสต็อกสินค้า
- ตรวจสอบและอนุมัติการชำระเงิน
- ระบบแจ้งเตือนฝั่งผู้ดูแล

---

## Tech Stack

### Core Technologies
- PHP
- MySQL
- HTML / CSS
- JavaScript

### UI / Framework
- Bootstrap 5
- AdminLTE 4

### External Tools & Libraries
- LINE Official Account (LINE OA)
- LINE LIFF (Login & User Integration)
- PHPMailer (ส่งอีเมลแจ้งเตือน)
- Cloudinary (รองรับการจัดการรูปภาพภายนอก – ใช้งานได้ในอนาคต)
- Composer (จัดการ PHP dependencies)

---

## Project Structure
```text
LineOA/
├── frontend/ # ฝั่งผู้ซื้อ
│ ├── Buyer/ # หน้าใช้งานหลักของผู้ซื้อ
│ ├── Users/ # Login / Register
│ ├── utils/ # Utility เฉพาะฝั่ง frontend
│ └── services/ # Service ฝั่ง frontend
│
├── backend/ # ฝั่งผู้ดูแลระบบ
│ ├── Stock/ # จัดการสินค้าและสต็อก
│ ├── Users/ # Admin login
│ ├── payments/ # ตรวจสอบการชำระเงิน
│ ├── partials/ # Layout / Navbar / Sidebar
│ └── assets/ # CSS / JS สำหรับ backend
│
├── shared/ # โค้ดใช้ร่วมกันทั้งระบบ
│ ├── services/ # Business Logic กลาง
│ ├── utils/ # DB, Auth Guard, Upload, Image Helper
│ ├── partials/ # Bootstrap / SweetAlert
│ └── assets/ # รูป fallback และ static assets
│
├── storage/ # ไฟล์ runtime (ไม่ควร commit)
│ ├── uploads/ # ไฟล์อัปโหลด
│ │ ├── slips/ # สลิปการชำระเงิน
│ │ └── variants/ # รูปสินค้า / ตัวเลือกสินค้า
│ └── logs/ # Log ของระบบ
│
├── vendor/ # Composer dependencies
├── config.php # การตั้งค่าหลักของระบบ
├── database.sql # โครงสร้างฐานข้อมูล
└── README.md


---

## File Upload Management

ไฟล์ทั้งหมดในระบบจะถูกจัดเก็บไว้ภายใต้โฟลเดอร์:
```text
storage/uploads/

ตัวอย่าง:
- สลิปการชำระเงิน  
  `storage/uploads/slips/xxxx.png`
- รูปสินค้า / ตัวเลือกสินค้า  
  `storage/uploads/variants/xxxx.jpg`

📌 ค่า path ที่เก็บในฐานข้อมูลจะเป็น:
```text
storage/uploads/...


ระบบจะไม่ใช้ path แบบ `uploads/...` แบบเดิมอีกต่อไป

---

## Configuration

ไฟล์ `config.php` ใช้สำหรับตั้งค่าหลักของระบบ เช่น:
- Database connection
- Base URL
- Path สำหรับ uploads
- Path สำหรับ shared resources

ตัวอย่างค่าที่สำคัญ:
```php
define('UPLOAD_BASE_DIR', BASE_PATH . '/storage/uploads');
define('UPLOAD_BASE_URL_PATH', 'storage/uploads');

define('FRONTEND_URL', BASE_URL . '/frontend');
define('BACKEND_URL',  BASE_URL . '/backend');

ระบบรองรับการ deploy ใน sub-directory
เช่น https://example.com/LineOA/

## Getting Started
1. Import ไฟล์ database.sql เข้าสู่ MySQL
2. ตั้งค่าการเชื่อมต่อฐานข้อมูลใน config.php
3. สร้างโฟลเดอร์:
```text
storage/uploads
storage/logs

4. ตรวจสอบ permission ให้ระบบสามารถเขียนไฟล์ได้
5. เปิดใช้งานผ่าน Web Server (Apache / Nginx)

## Development Notes
- หลีกเลี่ยงการ hardcode path แบบ relative (../../)
- ใช้ constants และ helper ที่กำหนดไว้ในระบบ
- แยก Business Logic ออกจาก UI เสมอ
- ใช้ Service Layer เป็นศูนย์กลางของการทำงาน

## License

This project is developed for educational and demonstration purposes.
All rights reserved.