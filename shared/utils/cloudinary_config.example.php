<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once BASE_PATH . '/vendor/autoload.php';

\Cloudinary\Configuration\Configuration::instance([
    'cloud' => [
        'cloud_name' => 'YOUR_CLOUD_NAME',
        'api_key'    => 'YOUR_API_KEY',
        'api_secret' => 'YOUR_API_SECRET',
    ],
    'url' => [
        'secure' => true,
    ],
]);
