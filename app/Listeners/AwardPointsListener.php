<?php

namespace App\Listeners;

use App\Events\TransactionSuccess;
use App\Services\PointService;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AwardPointsListener
{
    public function __construct(protected PointService $pointService) {}

    public function handle(TransactionSuccess $event): void
    {
        $pembelian = $event->pembelian;

        // Cari user dari Pembelian (via username)
        $user = $event->user ?? User::where('username', $pembelian->username)->first();

        // Hanya award poin jika user terdaftar (bukan guest/anonim)
        if (!$user) return;

        $this->pointService->earnPoints($user, $pembelian);
    }
}
