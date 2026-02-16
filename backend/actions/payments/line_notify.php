<?php

// ฟังก์ชันส่งแจ้งเตือนสถานะการชำระเงิน
function sendLineNotification($line_uid, $orderId, $amount, $status, $items)
{
    require_once dirname(__DIR__, 3) . '/config.php';

    $accessToken = LINE_ACCESS_TOKEN;

    $statusText = $status === 'approved' ? '✅ ชำระเงินสำเร็จ' : '❌ การชำระเงินถูกปฏิเสธ';
    $statusColor = $status === 'approved' ? '#27ae60' : '#e74c3c';

    $itemBoxes = [];

    foreach ($items as $item) {

        $name = $item['product_name'];
        if (!empty($item['variant_name'])) {
            $name .= " (" . $item['variant_name'] . ")";
        }

        $itemBoxes[] = [
            "type" => "box",
            "layout" => "horizontal",
            "contents" => [
                [
                    "type" => "text",
                    "text" => $name,
                    "size" => "sm",
                    "flex" => 4,
                    "wrap" => true
                ],
                [
                    "type" => "text",
                    "text" => "x" . $item['quantity'],
                    "size" => "sm",
                    "align" => "center",
                    "flex" => 1
                ],
                [
                    "type" => "text",
                    "text" => number_format($item['quantity'] * $item['unit_price'], 2),
                    "size" => "sm",
                    "align" => "end",
                    "flex" => 2
                ]
            ]
        ];
    }

    $flex = [
        "type" => "flex",
        "altText" => "อัปเดตคำสั่งซื้อ #$orderId",
        "contents" => [
            "type" => "bubble",
            "body" => [
                "type" => "box",
                "layout" => "vertical",
                "spacing" => "md",
                "contents" => array_merge([
                    [
                        "type" => "text",
                        "text" => $statusText,
                        "weight" => "bold",
                        "size" => "lg",
                        "color" => $statusColor
                    ],
                    [
                        "type" => "text",
                        "text" => "คำสั่งซื้อ #$orderId",
                        "size" => "sm",
                        "color" => "#666666"
                    ],
                    [
                        "type" => "separator",
                        "margin" => "md"
                    ]
                ], $itemBoxes, [
                    [
                        "type" => "separator",
                        "margin" => "md"
                    ],
                    [
                        "type" => "text",
                        "text" => "รวมทั้งหมด: " . number_format($amount, 2) . " บาท",
                        "weight" => "bold",
                        "align" => "end",
                        "margin" => "md"
                    ]
                ])
            ]
        ]
    ];

    $data = [
        "to" => $line_uid,
        "messages" => [$flex]
    ];

    $ch = curl_init('https://api.line.me/v2/bot/message/push');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ปิดการตรวจสอบ SSL (ทดสอบเท่านั้น)

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode !== 200) {
        error_log("LINE Error: " . $response);
    }
}

function buildTrackingUrl($carrier, $trackingNo)
{
    switch ($carrier) {
        case 'thpost':
            return 'https://track.thailandpost.co.th/?trackNumber=' . $trackingNo;

        case 'flash':
            return 'https://www.flashexpress.co.th/tracking?se=' . $trackingNo;

        case 'kerry':
            return 'https://th.kerryexpress.com/th/track/?track=' . $trackingNo;

        case 'jnt':
            return 'https://www.jtexpress.co.th/index/query/gzquery.html?billcodes=' . $trackingNo;

        default:
            return '#';
    }
}

// ฟังก์ชันส่งแจ้งเตือนการจัดส่ง
function sendShippingNotification($line_uid, $orderId, $trackingNo, $carrier)
{
    require_once dirname(__DIR__, 3) . '/config.php';

    $accessToken = LINE_ACCESS_TOKEN;

    $trackingUrl = buildTrackingUrl($carrier, $trackingNo);

    $flex = [
        "type" => "flex",
        "altText" => "สินค้าของคุณถูกจัดส่งแล้ว",
        "contents" => [
            "type" => "bubble",
            "body" => [
                "type" => "box",
                "layout" => "vertical",
                "spacing" => "md",
                "contents" => [
                    [
                        "type" => "text",
                        "text" => "📦 สินค้าของคุณถูกจัดส่งแล้ว",
                        "weight" => "bold",
                        "size" => "lg",
                        "color" => "#2e86de"
                    ],
                    [
                        "type" => "text",
                        "text" => "คำสั่งซื้อ #$orderId",
                        "size" => "sm",
                        "color" => "#666666"
                    ],
                    [
                        "type" => "text",
                        "text" => "ขนส่ง: " . strtoupper($carrier),
                        "size" => "sm"
                    ],
                    [
                        "type" => "text",
                        "text" => "เลขพัสดุ: $trackingNo",
                        "margin" => "md",
                        "weight" => "bold"
                    ]
                ]
            ],
            "footer" => [
                "type" => "box",
                "layout" => "vertical",
                "contents" => [
                    [
                        "type" => "button",
                        "style" => "primary",
                        "action" => [
                            "type" => "uri",
                            "label" => "ติดตามพัสดุ",
                            "uri" => $trackingUrl
                        ]
                    ]
                ]
            ]
        ]
    ];

    $data = [
        "to" => $line_uid,
        "messages" => [$flex]
    ];

    $ch = curl_init('https://api.line.me/v2/bot/message/push');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ปิดการตรวจสอบ SSL (ทดสอบเท่านั้น)

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode !== 200) {
        error_log("LINE Error: " . $response);
    }
}
