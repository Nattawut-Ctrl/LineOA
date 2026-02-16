<?php

/**
 * Helper สำหรับจัดการ path ของรูปภาพให้กลายเป็น URL เต็ม
 * - รองรับ path จากฐานข้อมูลที่อาจมี ../, ./, หรือ backslash แบบ Windows
 * - ถ้าเป็น URL เต็มอยู่แล้ว (ขึ้นต้นด้วย http/https) จะส่งกลับโดยไม่แก้ไข
 */
if (!function_exists('buildImageUrl')) {
    function buildImageUrl(?string $path): string
    {
        if (empty($path)) {
            return '';
        }

        $path = trim($path);
        $path = str_replace('\\', '/', $path);

        // ถ้าเป็น URL เต็มอยู่แล้ว เช่น https://... หรือ http://...
        if (preg_match('#^https?://#', $path)) {
            return $path;
        }

        // ลบ ../ และ ./ ที่ขึ้นต้น path ออก เพื่อไม่ให้รบกวน BASE_URL
        while (strpos($path, '../') === 0 || strpos($path, './') === 0) {
            if (strpos($path, '../') === 0) {
                $path = substr($path, 3);
            } elseif (strpos($path, './') === 0) {
                $path = substr($path, 2);
            }
        }

        // ประกอบกับ BASE_URL ให้เป็น URL สมบูรณ์
        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }
}
