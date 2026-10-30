<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Services\RewardService;
use App\Support\PublicThemeRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicRewardController extends Controller
{
    private const TIER_RANK = ['Member' => 1, 'Gold' => 2, 'Platinum' => 3, 'Admin' => 99];

    /** Katalog hadiah + saldo poin member. */
    public function index()
    {
        $user = Auth::user();

        $rewards = Reward::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        $tierRank = self::TIER_RANK;

        return $this->page('template.rewards.catalog', compact('user', 'rewards', 'tierRank'));
    }

    /** Detail hadiah + form alamat pengiriman. */
    public function show(Reward $reward)
    {
        abort_unless($reward->is_active, 404);

        $user = Auth::user();
        $tierRank = self::TIER_RANK;

        return $this->page('template.rewards.show', compact('user', 'reward', 'tierRank'));
    }

    /** Proses penukaran. */
    public function redeem(Request $request, Reward $reward)
    {
        $data = $request->validate([
            'recipient_name'  => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:30',
            'address'         => 'required|string|max:1000',
            'city'            => 'required|string|max:255',
            'province'        => 'nullable|string|max:255',
            'postal_code'     => 'nullable|string|max:20',
        ], [], [
            'recipient_name'  => 'nama penerima',
            'recipient_phone' => 'nomor HP',
            'address'         => 'alamat',
            'city'            => 'kota',
        ]);

        try {
            $redemption = app(RewardService::class)->redeem(Auth::user(), $reward->id, $data);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('reward_error', $e->getMessage());
        }

        return redirect()
            ->route('rewards.history')
            ->with('reward_success', 'Penukaran berhasil! Kode: ' . $redemption->code . '. Hadiah akan segera diproses & dikirim.');
    }

    /** Riwayat penukaran member. */
    public function history()
    {
        $user = Auth::user();

        $redemptions = RewardRedemption::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return $this->page('template.rewards.history', compact('user', 'redemptions'));
    }

    /**
     * Render halaman hadiah dengan sikap tema yang eksplisit
     * (kontrak PublicThemeSurfaceContractTest).
     *
     * Fitur hadiah saat ini hanya memiliki view legacy Blade. Gate renderer tetap
     * dikonsultasikan: untuk theme default (legacy) halaman ini memang Blade, dan
     * untuk theme modern (Inertia) pun di-serve Blade yang sama sampai versi
     * Inertia-nya dibuat. Flag diteruskan ke view agar bisa menyesuaikan nanti.
     */
    private function page(string $view, array $data)
    {
        $data['isLegacyTheme'] = PublicThemeRegistry::rendersLegacyBlade(
            PublicThemeRegistry::activeForEnvironment()
        );

        return view($view, $data);
    }
}
