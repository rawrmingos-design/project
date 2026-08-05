<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pembelian;
use App\Models\Layanan;
use App\Models\Pembayaran;
use App\Http\Controllers\DigiFlazzController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\provider\BangJeffController;
use App\Http\Controllers\provider\MoogoldController;
use App\Http\Controllers\provider\VipResellerController;
use App\Http\Controllers\provider\ApiGamesController;
use App\Http\Controllers\provider\TopupediaController;
use App\Libraries\Provider\ElitediasProvider;
use App\Libraries\Provider\GameShopProvider;
use App\Libraries\Provider\StrleyaShopProvider;
use App\Libraries\Provider\YezzpayProvider;
use App\Jobs\PollSufPaymentStatusJob;
use App\Services\OrderProcessingService;

class OrderController extends Controller
{
    public function create()
    {
        $data = Pembelian::orderBy('pembelians.id', 'desc')
            ->join('pembayarans', 'pembelians.order_id', '=', 'pembayarans.order_id')
            ->leftJoin('data_joki', 'pembelians.order_id', '=', 'data_joki.order_id') 
            ->leftJoin('methods', DB::raw('pembayarans.metode COLLATE utf8mb4_unicode_ci'), '=', DB::raw('methods.code COLLATE utf8mb4_unicode_ci'))
            ->select(
                'pembelians.*',
                'pembayarans.status AS status_pembayaran',
                'pembayarans.metode',
                'pembayarans.no_pembeli AS nomor_hp',
                'pembelians.username AS username',
                'pembelians.profit AS profit',
                'data_joki.nickname_joki',
                'methods.name AS metode_name'
            )
            ->where('pembayarans.metode', '!=', 'MANUAL')
            ->get();

        return view('components.admin.transaction', ['data' => $data]);
    }
    
    public function reorder($order_id)
    {
        $invoice = Pembayaran::where('order_id', $order_id)->first();
        $pembelian = Pembelian::where('order_id', $order_id)->first();

        if (! $pembelian) {
            return back()->with('error', 'Pesanan tidak ditemukan dengan ID #' . $order_id);
        }

        if ($pembelian->hasStatus(['processing', 'success'])) {
            return back()->with('info', 'Pesanan sudah diproses sebelumnya dengan ID #' . $order_id);
        }

        $result = app(OrderProcessingService::class)->process($pembelian);

        if ($result['success']) {
            if ($invoice) {
                $invoice->update(['status' => 'Lunas']);
            }

            $snValue = trim((string) ($result['sn'] ?? '')) ?: ($pembelian->keterangan_sn ?: 'Sedang Diproses');
            $nextStatus = ($result['order_status'] ?? 'Pending') === 'Sukses' ? 'Sukses' : 'Proses';

            $providerOrderId = $result['transaction_id'] ?? $pembelian->provider_order_id;

            $pembelian->update([
                'provider_order_id' => $providerOrderId,
                'active_attempt_token' => $providerOrderId,
                'status' => $nextStatus,
                'keterangan_sn' => $snValue,
                'log' => json_encode(['result' => $result])
            ]);

            $freshPembelian = $pembelian->fresh(['pembayaran']);
            if ($freshPembelian) {
                PollSufPaymentStatusJob::dispatchIfNeeded($freshPembelian, $providerOrderId, $result['order_status'] ?? $nextStatus);
            }

            if (($result['provider'] ?? null) !== 'joki') {
                $pesanPembeli = 
                    "*Pembayaran Berhasil*\n\n" .
                    "No Invoice: *$order_id*\n" .
                    "Layanan : *$pembelian->layanan*\n" .
                    "ID : *$pembelian->user_id*\n" .
                    "Server : *$pembelian->zone*\n" .
                    "Nickname : *$pembelian->nickname*\n" .
                    "Harga : *Rp. " . number_format($pembelian->harga, 0, '.', ',') . "*\n" .
                    "Status Pembelian: *Diproses*\n" .
                    "Estimasi Proses: *1-5 Menit Max 24 Jam*\n\n" .
                    "INI ADALAH PESAN OTOMATIS";

                $this->msg($pembelian->no_pembeli, $pesanPembeli);
            } else {
                $pesanJoki =
                    "*Pembayaran Berhasil*\n\n" .
                    "No Invoice: *$order_id*\n" .
                    "Layanan: *$pembelian->layanan*\n" .
                    "ID: *$pembelian->user_id*\n" .
                    "Server: *$pembelian->zone*\n" .
                    "Nickname: *$pembelian->nickname*\n" .
                    "Harga: *Rp. " . number_format($pembelian->harga, 0, '.', ',') . "*\n" .
                    "Status Pembelian: *Diproses*\n" .
                    "Joki akan segera memulai permainan Anda.\n\n" .
                    "INI ADALAH PESAN OTOMATIS";

                $this->msg($pembelian->no_pembeli, $pesanJoki);
            }

            return back()->with('success', 'Berhasil melakukan reprocess dengan ID #' . $order_id);
        }

        $pembelian->update([
            'status' => 'Pending',
            'log' => json_encode(['error' => $result['message']])
        ]);

        if ($invoice !== null) {
            $invoice->update(['status' => 'Lunas']);
        }

        return back()->with('error', 'Gagal melakukan reprocess dengan ID #' . $order_id . ': ' . $result['message']);
    }

    public function update($order_id, $status)
    {
        $pembelian = Pembelian::where('order_id', $order_id)->firstOrFail();
        $pembelian->update([
            'status' => $status,
        ]);

        // Kirim pesan saat status diperbarui menjadi 'Sukses'
        if ($status == 'Sukses') {
            if ($pembelian && $pembelian->tipe_transaksi != 'joki') {
                $pesanSukses =
                    "*Diamond Berhasil Dikirim*\n\n" .
                    "No Invoice: *$order_id*\n" .
                    "Layanan: *$pembelian->layanan*\n" .
                    "ID: *$pembelian->user_id*\n" .
                    "Server: *$pembelian->zone*\n" .
                    "Nickname: *$pembelian->nickname*\n" .
                    "Harga: *Rp. " . number_format($pembelian->harga, 0, '.', ',') . "*\n" .
                    "Status Pembelian: *Success*\n\n" .
                    "Terima kasih telah bertransaksi dengan kami.";

                $this->msg($pembelian->no_pembeli, $pesanSukses);
            }
        }

        return back()->with('success', 'Berhasil memperbarui status ID #' . $order_id);        
    }

    public function msg($nomor, $msg)
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();
        $apiUrl = 'https://api.fonnte.com/send';
        $token = $api->wa_key;
    
        $postData = [
            'target' => $nomor,
            'message' => $msg,
        ];
    
        $headers = [
            'Authorization: ' . $token,
        ];
    
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => $headers,
        ]);
    
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
        curl_close($curl);
    
        if ($statusCode === 200) {
            return [
                'success' => true,
                'message' => 'Message sent successfully',
                'response' => $response,
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to send message',
                'response' => $response,
            ];
        }
    }
}
