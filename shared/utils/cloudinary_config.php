<?php
require_once __DIR__ . '/../../config.php';
require_once BASE_PATH . '/vendor/autoload.php';

$cacertPath = BASE_PATH . '/certs/cacert.pem';

if (file_exists($cacertPath)) {
    // บังคับให้ cURL และ OpenSSL ใช้ไฟล์นี้ตรวจ SSL
    ini_set('curl.cainfo', $cacertPath);
    ini_set('openssl.cafile', $cacertPath);
}

\Cloudinary\Configuration\Configuration::instance([
    'cloud' => [
        'cloud_name' => 'dfs4n2p9b',
        'api_key'    => '353129815736385',
        'api_secret' => 'oK5Bh9QoxwEZzz_gpc2rPWADAw4',
    ],
    'url' => [
        'secure' => true,
    ],
]);
