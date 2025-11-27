<?php
require_once __DIR__ . '/../config.php';
require_once BASE_PATH . '/vendor/autoload.php';

\Cloudinary\Configuration\Configuration::instance([
    'cloud' => [
        'cloud_name' => 'YOUR_CLOUD_NAME',
        'api_key'    => 'YOUR_API_KEY',
        'api_secret' => 'YOUR_API_SECRET',
    ],
    'url' => [
        'secure' => true, // ใช้ https เสมอ
    ],
]);
