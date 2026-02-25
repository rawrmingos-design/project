<?php
// 1. Konfigurasi
$url = "https://istanatopup.imhaf.online/wejizy/digi/payload"; // URL Webhook Anda
$secret = "WEJIZYSEC18"; // Harus sama dengan di Controller & Dashboard

// 2. Data simulasi dari Digiflazz
$data = [
    "data" => [
        "ref_id" => "EM1817moEkkhg0",
        "status" => "Success", // Ubah jadi Success untuk ngetes sukses
        "rc" => "00",
        "sn" => "TEST-SN-12345",
        "message" => "Transaksi Sukses"
    ]
];

$payload = json_encode($data);

// 3. Membuat Signature HMAC-SHA1
$signature = "sha1=" . hash_hmac('sha1', $payload, $secret);

// 4. Kirim menggunakan CURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Hub-Signature: ' . $signature,
    'X-Digiflazz-Event: update'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";