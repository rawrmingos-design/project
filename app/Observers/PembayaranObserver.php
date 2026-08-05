<?php

namespace App\Observers;

use App\Models\Pembayaran;
use App\Services\TikTokDeliveryService;

class PembayaranObserver
{
    public function __construct(
        private readonly TikTokDeliveryService $tikTokDeliveryService,
    ) {
    }

    public function created(Pembayaran $pembayaran): void
    {
        $this->dispatchIfPaid($pembayaran);
    }

    public function updated(Pembayaran $pembayaran): void
    {
        if ($pembayaran->wasChanged('status')) {
            $this->dispatchIfPaid($pembayaran);
        }
    }

    private function dispatchIfPaid(Pembayaran $pembayaran): void
    {
        if (! in_array($pembayaran->normalizedStatus(), ['lunas', 'paid', 'success'], true)) {
            return;
        }

        $pembelian = $pembayaran->pembelian()->first();

        if ($pembelian) {
            $this->tikTokDeliveryService->dispatchForEligibleOrder($pembelian);
        }
    }
}
