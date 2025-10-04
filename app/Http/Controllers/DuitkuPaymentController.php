<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DuitkuController extends Controller
{
    private $merchantCode;
    private $apiKey;

    public function __construct()
    {
        // Ganti dengan Merchant Code dan API Key yang Anda terima dari Duitku
        $this->merchantCode = 'DS20373';
        $this->apiKey = '3b4e7d89ce2d78cc9ab844300572f06e';
    }

    public function createPayment($amount, $customerName, $email, $paymentMethod)
    {
        // Buat ID pesanan yang unik
        $merchantOrderId = Str::random();

        // Membuat tanda tangan untuk permintaan
        $signature = md5($this->merchantCode . $merchantOrderId . $amount . $this->apiKey);

        // Data untuk dikirim ke Duitku
        $data = [
            'merchantCode' => $this->merchantCode,
            'paymentAmount' => $amount,
            'paymentMethod' => $paymentMethod,
            'merchantOrderId' => $merchantOrderId,
            'customerVaName' => $customerName,
            'email' => $email,
            'signature' => $signature,
        ];

        // Kirim permintaan ke Duitku
        $response = Http::post('https://sandbox.duitku.com/webapi/api/merchant/paymentmethod/getpaymentmethod', $data);

        if ($response->status() == 200) {
            $result = $response->json();

            // Lakukan sesuatu dengan hasil yang diterima, seperti mengarahkan pelanggan ke halaman pembayaran Duitku
            // Contoh: return redirect($result['paymentUrl']);
            return [
                'status' => 'success',
                'paymentUrl' => $result['paymentUrl'],
                'reference' => $result['reference'],
            ];
        } else {
            // Penanganan error jika permintaan ke Duitku gagal
            return [
                'status' => 'error',
                'message' => 'Permintaan ke Duitku gagal'
            ];
        }
    }

    public function handle(Request $request)
    {
        // Validasi callback dari Duitku
        $signature = $request->input('signature');
        $data = $request->all();

        // Verifikasi tanda tangan
        $expectedSignature = md5($data['merchantCode'] . $data['merchantOrderId'] . $data['paymentAmount'] . $this->apiKey);

        if ($signature == $expectedSignature) {
            // Tanda tangan valid, proses callback
            // Anda dapat memperbarui status transaksi di database atau melakukan tindakan lain yang diperlukan
            // Contoh: Update status pembayaran menjadi 'Lunas'
            // Contoh: Kirim email notifikasi kepada pengguna atau admin
            return response('Callback valid', 200);
        } else {
            // Tanda tangan tidak valid, abaikan callback ini
            return response('Callback tidak valid', 400);
        }
    }
}
