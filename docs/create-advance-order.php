<?php
require 'tokopay.lib.php';
/*
merchant id dan secret_key bisa di temukan di link ini :
https://dash.tokopay.id/pengaturan/secret-key
*/
$merchant_id = "YOUR_MERCHANT_ID"; //put your merchant id here
$secret_key = "YOUR_SECRET_KEY"; //put your secret key here
$ref_id = "YOUR_REFERENCE_ID";
$channel = "QRIS"; //list channel bisa di cek di https://docs.tokopay.id/persiapan-awal/metode-pembayaran atau https://dash.tokopay.id/metode-pembayaran
$tokopay = New Tokopay($merchant_id,$secret_key);
$generateSignature = $tokopay->generateSignature($ref_id);
$data = [
    'merchant_id' => $merchant_id,
    'kode_channel' => $channel,
    'reff_id' => $ref_id,
    'amount' => 160000,
    'customer_name' => "Joko Susilo",
    'customer_email' => "joko.susilo98@gmail.com",
    'customer_phone' => "082277665544",
    'redirect_url' => "https://tokokamu.com/transaksi/INV231010JKALTES1",
    'expired_ts' => 0,
    'signature'=>$generateSignature,
    'items'=> [
        [
            'product_code'=>'PR-01',
            'name'=>"Celana Panjang Hitam",
            'price'=>90000,
            'product_url'=>'https://example.com/product',
            'image_url'=>'https://example.com/image.jpg'
        ],
        [
            'product_code'=>'PR-01',
            'name'=>"Kaos Pendek Hitam",
            'price'=>70000,
            'product_url'=>'https://example.com/product',
            'image_url'=>'https://example.com/image.jpg'
        ]
    ]
];
echo $tokopay->createAdvanceOrder($data);