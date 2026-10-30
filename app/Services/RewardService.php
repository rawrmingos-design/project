<?php

namespace App\Services;

use App\Models\User;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\PointHistory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RewardService
{
    private const TIER_RANK = ['Member' => 1, 'Gold' => 2, 'Platinum' => 3, 'Admin' => 99];

    /**
     * Tukar poin user dengan sebuah hadiah. Poin & stok langsung dipotong (dikunci
     * dengan lockForUpdate agar aman dari dobel-klik / rebutan stok).
     *
     * @param array{recipient_name:string,recipient_phone:string,address:string,city:string,province?:string,postal_code?:string} $shipping
     * @throws \RuntimeException pesan yang bisa langsung ditampilkan ke user jika gagal.
     */
    public function redeem(User $user, int $rewardId, array $shipping): RewardRedemption
    {
        return DB::transaction(function () use ($user, $rewardId, $shipping) {
            /** @var Reward|null $reward */
            $reward = Reward::where('id', $rewardId)->lockForUpdate()->first();

            if (!$reward || !$reward->is_active) {
                throw new \RuntimeException('Hadiah tidak tersedia.');
            }

            $now = now();
            if ($reward->starts_at && $now->lt($reward->starts_at)) {
                throw new \RuntimeException('Hadiah ini belum dapat ditukar.');
            }
            if ($reward->expires_at && $now->gt($reward->expires_at)) {
                throw new \RuntimeException('Masa penukaran hadiah ini sudah berakhir.');
            }
            if ($reward->stock <= 0) {
                throw new \RuntimeException('Stok hadiah sudah habis.');
            }
            if ($reward->min_tier && !$this->tierAllows((string) $user->role, (string) $reward->min_tier)) {
                throw new \RuntimeException('Hadiah ini khusus member ' . $reward->min_tier . '.');
            }

            /** @var User|null $lockedUser */
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            if (!$lockedUser) {
                throw new \RuntimeException('Akun tidak ditemukan.');
            }
            if ((int) $lockedUser->point_balance < (int) $reward->point_cost) {
                throw new \RuntimeException('Poin kamu tidak cukup untuk menukar hadiah ini.');
            }

            // Potong poin & stok
            User::where('id', $lockedUser->id)->decrement('point_balance', $reward->point_cost);
            Reward::where('id', $reward->id)->decrement('stock');

            $code = $this->generateCode();

            $redemption = RewardRedemption::create([
                'code'            => $code,
                'user_id'         => $lockedUser->id,
                'reward_id'       => $reward->id,
                'reward_name'     => $reward->name,
                'points_spent'    => $reward->point_cost,
                'status'          => RewardRedemption::STATUS_PENDING,
                'recipient_name'  => $shipping['recipient_name'],
                'recipient_phone' => $shipping['recipient_phone'],
                'address'         => $shipping['address'],
                'city'            => $shipping['city'],
                'province'        => $shipping['province'] ?? null,
                'postal_code'     => $shipping['postal_code'] ?? null,
            ]);

            PointHistory::create([
                'user_id'     => $lockedUser->id,
                'order_id'    => $code,
                'type'        => 'redeem',
                'points'      => $reward->point_cost,
                'description' => 'Tukar hadiah: ' . $reward->name,
            ]);

            return $redemption;
        });
    }

    /**
     * Kembalikan poin & stok saat penukaran ditolak/dibatalkan. Idempotent:
     * tidak akan refund dua kali untuk penukaran yang sama.
     */
    public function refund(RewardRedemption $redemption, string $reason = ''): bool
    {
        try {
            return DB::transaction(function () use ($redemption, $reason) {
                /** @var RewardRedemption|null $fresh */
                $fresh = RewardRedemption::where('id', $redemption->id)->lockForUpdate()->first();

                if (!$fresh || $fresh->refunded_at !== null) {
                    return false;
                }

                User::where('id', $fresh->user_id)->increment('point_balance', $fresh->points_spent);
                if ($fresh->reward_id) {
                    Reward::where('id', $fresh->reward_id)->increment('stock');
                }

                PointHistory::create([
                    'user_id'     => $fresh->user_id,
                    'order_id'    => $fresh->code,
                    'type'        => 'earn',
                    'points'      => $fresh->points_spent,
                    'description' => 'Refund hadiah: ' . $fresh->reward_name,
                ]);

                $fresh->update([
                    'status'      => RewardRedemption::STATUS_REJECTED,
                    'rejected_at' => now(),
                    'refunded_at' => now(),
                    'admin_note'  => trim(($fresh->admin_note ? $fresh->admin_note . ' | ' : '') . $reason),
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            Log::error('RewardService::refund error', ['error' => $e->getMessage(), 'redemption' => $redemption->id]);
            return false;
        }
    }

    private function tierAllows(string $userRole, string $minTier): bool
    {
        return (self::TIER_RANK[$userRole] ?? 0) >= (self::TIER_RANK[$minTier] ?? 999);
    }

    private function generateCode(): string
    {
        do {
            $code = 'RWD-' . strtoupper(Str::random(8));
        } while (RewardRedemption::where('code', $code)->exists());

        return $code;
    }
}
