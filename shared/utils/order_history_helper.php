<?php
/**
 * Order History Helper
 * ใช้สำหรับหน้า order-history.php
 * - normalize items_json ให้เป็นรูปแบบเดียวกัน
 * - helper แสดงเงิน THB
 * - กรอง intent ที่หมดเวลา (expire) ไม่ให้ user เห็น
 * - จำกัดจำนวน card ล่าสุดต่อแท็บ (เช่น 5 / 10)
 */

declare(strict_types=1);

// ───────────────────────── safeJsonArray ─────────────────────────
if (!function_exists('safeJsonArray')) {
    function safeJsonArray(?string $json): array
    {
        if (empty($json)) return [];
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}

// ───────────────────────── normalizeItems ─────────────────────────
// แปลง items_json จากหลายรูปแบบ (cart / payment / legacy)
// ให้เป็น array มาตรฐานสำหรับ UI
if (!function_exists('normalizeItems')) {
    function normalizeItems(array $items): array
    {
        $out = [];

        foreach ($items as $it) {
            $pid = (int)($it['product_id'] ?? $it['pid'] ?? 0);
            $vid = (int)($it['variant_id'] ?? $it['vid'] ?? 0);

            $qty = (int)($it['qty'] ?? $it['quantity'] ?? 1);
            if ($qty < 1) $qty = 1;

            $price = (float)(
                $it['price']
                ?? $it['unit_price']
                ?? $it['product_price']
                ?? 0
            );

            $subtotal = (float)(
                $it['subtotal']
                ?? $it['total']
                ?? $it['line_total']
                ?? 0
            );

            // fallback คำนวณ subtotal เอง
            if ($subtotal <= 0 && $price > 0) {
                $subtotal = $price * $qty;
            }

            $name = (string)(
                $it['product_name']
                ?? $it['name']
                ?? $it['title']
                ?? 'สินค้า'
            );

            $variantName = (string)(
                $it['variant_name']
                ?? $it['variant']
                ?? ''
            );

            $out[] = [
                'product_id'   => $pid,
                'variant_id'   => $vid,
                'qty'          => $qty,
                'price'        => $price,
                'subtotal'     => $subtotal,
                'name'         => $name,
                'variant_name' => $variantName,
            ];
        }

        return $out;
    }
}

// ───────────────────────── moneyTHB ─────────────────────────
if (!function_exists('moneyTHB')) {
    function moneyTHB(float|int|string $amount): string
    {
        return '฿' . number_format((float)$amount, 2);
    }
}

// ───────────────────────── limitLatest ─────────────────────────
// ใช้กับ array ที่ถูก ORDER BY created_at DESC มาแล้ว
if (!function_exists('limitLatest')) {
    function limitLatest(array $rows, int $max): array
    {
        if ($max <= 0) return [];
        if (count($rows) <= $max) return $rows;
        return array_slice($rows, 0, $max);
    }
}

// ───────────────────────── filterVisibleIntents ─────────────────────────
// ซ่อน intent ที่หมดเวลา (timeRemaining <= 0) และเก็บ _time_remaining ไว้ใช้ใน UI
if (!function_exists('filterVisibleIntents')) {
    function filterVisibleIntents(array $intents, int $max): array
    {
        $visible = [];
        foreach ($intents as $it) {
            if (($it['status'] ?? '') !== 'active') continue;

            // ฟังก์ชันนี้มาจาก paymentIntentService.php (โปรเจกต์คุณมีอยู่แล้ว)
            $tr = getIntentTimeRemaining($it);
            if ($tr <= 0) continue; // ✅ หมดเวลา -> ไม่ให้ user เห็น

            $it['_time_remaining'] = $tr;
            $visible[] = $it;
        }

        return limitLatest($visible, $max);
    }
}

// ───────────────────────── buildItemsFromPaymentRow ─────────────────────────
// สร้าง items มาตรฐานจาก row ของ payments (รองรับ cart/single)
// payments ของคุณมี items_json ได้อยู่แล้ว (แนะนำใช้ก่อน)
if (!function_exists('buildItemsFromPaymentRow')) {
    function buildItemsFromPaymentRow(array $order): array
    {
        if (!empty($order['items_json'])) {
            return normalizeItems(safeJsonArray($order['items_json']));
        }

        // fallback legacy (กรณี single ไม่มี items_json)
        return normalizeItems([[
            'product_id'   => (int)($order['product_id'] ?? 0),
            'variant_id'   => (int)($order['variant_id'] ?? 0),
            'qty'          => 1,
            'price'        => 0,
            'subtotal'     => (float)($order['amount'] ?? 0),
            'product_name' => (string)($order['product_name'] ?? 'สินค้า'),
            'variant_name' => (string)($order['variant_name'] ?? ''),
        ]]);
    }
}
