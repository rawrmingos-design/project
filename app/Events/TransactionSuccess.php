<?php

namespace App\Events;

use App\Models\Pembelian;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionSuccess
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Pembelian $pembelian,
        public readonly ?User $user = null
    ) {}
}
