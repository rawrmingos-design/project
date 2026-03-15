<?php

namespace App\Services;

use App\Models\User;
use App\Models\PointHistory;
use App\Models\Pembelian;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointService
{
    private function getRedeemDescription(string $productName): string
    {
        return 'Redeem poin untuk pembelian ' . $productName;
    }

    private function getRefundDescription(string $productName): string
    {
        return 'Refund poin dari pembelian ' . $productName;
    }

    /**
     * Tambah poin ke user setelah transaksi sukses.
     */
    public function earnPoints(User $user, Pembelian $order): void
    {
        try {
            $setting = DB::table('setting_webs')->where('id', 1)->first();
            if (!$setting) return;

            $pointPerNominal = $setting->point_per_nominal ?? 1;  // poin per Rp 1.000
            $hargaDasar = $order->harga ?? 0;                      // harga setelah diskon

            // Hitung poin: tiap Rp 1.000 dapat $pointPerNominal poin
            $points = (int) floor(($hargaDasar / 1000) * $pointPerNominal);
            if ($points <= 0) return;

            DB::transaction(function () use ($user, $order, $points) {
                // Tambah saldo poin
                User::where('id', $user->id)->increment('point_balance', $points);

                // Simpan riwayat
                PointHistory::create([
                    'user_id'     => $user->id,
                    'order_id'    => $order->order_id,
                    'type'        => 'earn',
                    'points'      => $points,
                    'description' => 'Poin dari pembelian ' . $order->layanan,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('PointService::earnPoints error', ['error' => $e->getMessage(), 'order' => $order->order_id]);
        }
    }

    /**
     * Kurangi poin user saat redeem di checkout.
     * Mengembalikan jumlah rupiah diskon, atau 0 jika gagal.
     */
    public function redeemPoints(User $user, int $pointsToUse, string $orderId, string $productName): int
    {
        try {
            if ($pointsToUse <= 0) {
                return 0;
            }

            return DB::transaction(function () use ($user, $pointsToUse, $orderId, $productName) {
                $setting = DB::table('setting_webs')->where('id', 1)->first();
                $pointValue = $setting->point_value ?? 100;
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

                if (!$lockedUser || $lockedUser->point_balance < $pointsToUse) {
                    return 0;
                }

                User::where('id', $lockedUser->id)->decrement('point_balance', $pointsToUse);

                PointHistory::create([
                    'user_id'     => $lockedUser->id,
                    'order_id'    => $orderId,
                    'type'        => 'redeem',
                    'points'      => $pointsToUse,
                    'description' => $this->getRedeemDescription($productName),
                ]);

                return $pointsToUse * $pointValue;
            });
        } catch (\Exception $e) {
            Log::error('PointService::redeemPoints error', ['error' => $e->getMessage(), 'order' => $orderId]);
            return 0;
        }
    }

    /**
     * Kembalikan poin yang sebelumnya sudah di-redeem untuk order tertentu.
     */
    public function refundPoints(User $user, int $pointsToRefund, string $orderId, string $productName): bool
    {
        try {
            if ($pointsToRefund <= 0) {
                return false;
            }

            return DB::transaction(function () use ($user, $pointsToRefund, $orderId, $productName) {
                $refundDescription = $this->getRefundDescription($productName);
                $alreadyRefunded = PointHistory::where('user_id', $user->id)
                    ->where('order_id', $orderId)
                    ->where('type', 'earn')
                    ->where('description', $refundDescription)
                    ->exists();

                if ($alreadyRefunded) {
                    return false;
                }

                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

                if (!$lockedUser) {
                    return false;
                }

                User::where('id', $lockedUser->id)->increment('point_balance', $pointsToRefund);

                PointHistory::create([
                    'user_id'     => $lockedUser->id,
                    'order_id'    => $orderId,
                    'type'        => 'earn',
                    'points'      => $pointsToRefund,
                    'description' => $refundDescription,
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('PointService::refundPoints error', ['error' => $e->getMessage(), 'order' => $orderId]);
            return false;
        }
    }

    /**
     * Refund poin berdasarkan data pembelian yang sudah tercatat.
     */
    public function refundRedeemedPoints(Pembelian $order): bool
    {
        $pointsToRefund = (int) ($order->used_points ?? 0);

        if ($pointsToRefund <= 0) {
            return false;
        }

        $user = $order->user ?: User::where('username', $order->username)->first();

        if (!$user) {
            return false;
        }

        return $this->refundPoints($user, $pointsToRefund, $order->order_id, $order->layanan);
    }

    /**
     * Hitung berapa maksimal poin yang bisa dipakai untuk harga tertentu.
     */
    public function calculateMaxRedeemable(int $harga, int $userPointBalance): array
    {
        try {
            $setting = DB::table('setting_webs')->where('id', 1)->first();
            $pointValue = $setting->point_value ?? 100;
            $maxPercent = $setting->max_point_usage_percent ?? 50;

            // Batas rupiah dari persentase max
            $maxDiscount = (int) floor($harga * ($maxPercent / 100));

            // Batas dari saldo poin user
            $userPointValue = $userPointBalance * $pointValue;

            // Ambil yang lebih kecil
            $actualMaxDiscount = min($maxDiscount, $userPointValue);

            // Berapa poin yang dibutuhkan
            $maxPoints = (int) floor($actualMaxDiscount / $pointValue);

            return [
                'max_points'   => $maxPoints,
                'max_discount' => $maxPoints * $pointValue,
                'point_value'  => $pointValue,
            ];
        } catch (\Exception $e) {
            return ['max_points' => 0, 'max_discount' => 0, 'point_value' => 100];
        }
    }
}
