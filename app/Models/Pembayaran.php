<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\PembelianStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use BelongsToTenant, HasFactory;
    
    protected $guarded = [];

    protected $casts = [
        'harga' => 'integer',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'order_id', 'order_id');
    }

    public function normalizedStatus(): string
    {
        return strtolower(trim((string) $this->status));
    }

    public function isExpiredUnpaid(): bool
    {
        if (! in_array($this->normalizedStatus(), ['belum lunas', 'unpaid', 'pending'], true)) {
            return false;
        }

        if (! $this->expired_at) {
            return false;
        }

        return $this->expired_at->lte(now());
    }

    public function syncExpiredStatus(): bool
    {
        if ($this->normalizedStatus() === 'expired') {
            return $this->syncExpiredPembelianStatus();
        }

        if (! $this->isExpiredUnpaid()) {
            return false;
        }

        $this->forceFill([
            'status' => 'Expired',
        ])->saveQuietly();

        $this->refresh();
        $this->syncExpiredPembelianStatus();

        return true;
    }

    public function syncExpiredPembelianStatus(): bool
    {
        if ($this->normalizedStatus() !== 'expired') {
            return false;
        }

        $pembelian = $this->pembelian()->first();

        if (! $pembelian || $pembelian->hasStatus(PembelianStatus::EXPIRED)) {
            return false;
        }

        if (PembelianStatus::shouldIgnoreTransition($pembelian->status, PembelianStatus::EXPIRED)) {
            return false;
        }

        $pembelian->update([
            'status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::EXPIRED),
            'log' => $this->appendBoundedLog(
                $pembelian->log,
                'Payment expired at ' . now()->format('Y-m-d H:i:s'),
            ),
        ]);

        return true;
    }

    private function appendBoundedLog(?string $existingLog, string $entry, int $limit = 1000): string
    {
        $existingLog = trim((string) $existingLog);
        $entry = trim($entry);

        $combined = $existingLog !== ''
            ? $existingLog . PHP_EOL . $entry
            : $entry;

        if (mb_strlen($combined) <= $limit) {
            return $combined;
        }

        return mb_substr($combined, -$limit);
    }
}
