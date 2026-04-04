<?php

namespace App\Http\Controllers\provider;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use App\Services\Providers\BangJeffService;
use App\Support\PembelianStatus;
use Illuminate\Http\Request;

class BangJeffController extends Controller
{
    private BangJeffService $service;

    public function __construct(array $config = [])
    {
        $this->service = new BangJeffService($config);
    }

    public function balance(): array
    {
        return $this->service->balance();
    }

    public function getProduct(): array
    {
        return $this->service->getProductsRaw();
    }

    public function listVariant(string $productCode = 'MLBB'): array
    {
        return $this->service->listVariant($productCode);
    }

    public function detailVariant($productCode): array
    {
        return $this->service->detailVariant((string) $productCode);
    }

    public function order($code, $ref, $qty, $input, ?array $price = null): array
    {
        return $this->service->order(
            (string) $code,
            (string) $ref,
            (int) $qty,
            is_array($input) ? $input : [],
            $price,
        );
    }

    public function checkOrder($invoice): array
    {
        return $this->service->checkOrder((string) $invoice);
    }

    public function checkOrderByReference(string $referenceNumber): array
    {
        return $this->service->checkOrderByReference($referenceNumber);
    }

    public function go($url, $data = []): array
    {
        return $this->service->go((string) $url, is_array($data) ? $data : []);
    }

    public function handleCallback(Request $request): void
    {
        $json = $request->getContent();
        $data = json_decode($json, true);

        $poid = $data['invoice_number'] ?? null;
        $voucher = $data['voucher'] ?? null;
        $normalizedStatus = PembelianStatus::preferredDatabaseLabel((string) ($data['status_code'] ?? ''));

        \Log::info(json_encode($data));

        if (! $poid) {
            return;
        }

        $pembelian = Pembelian::where('provider_order_id', $poid)->first();

        if ($pembelian) {
            $updateData = [
                'status' => $normalizedStatus,
            ];

            if ($pembelian->tipe_transaksi == 'voucher') {
                $updateData['voucher'] = $voucher;
            }

            $pembelian->update($updateData);
        }
    }
}
