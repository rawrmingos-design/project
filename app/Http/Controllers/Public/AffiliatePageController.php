<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DsController as LegacyDashboardController;
use App\Models\AffiliateHistory;
use App\Models\Kategori;
use App\Models\User;
use App\Services\PublicSiteConfigService;
use App\Support\PublicThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AffiliatePageController extends Controller
{
    public function __invoke(
        Request $request,
        PublicSiteConfigService $siteConfigService,
        LegacyDashboardController $legacyDashboardController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application|RedirectResponse {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyDashboardController->affiliate($request);
        }

        $user = Auth::user();
        $affiliateStatus = $this->normalizeAffiliateStatus((string) ($user->affiliate_status ?? ''));
        $isAffiliateActive = method_exists($user, 'isAffiliateActive')
            ? (bool) $user->isAffiliateActive()
            : $affiliateStatus === 'active';

        if ($request->isMethod('post')) {
            return $this->submitAffiliateRequest($request, $user, $affiliateStatus);
        }

        if ($request->query('action') === 'request') {
            return redirect()->route('affiliate')->with('error', 'Silakan isi formulir pengajuan affiliate terbaru terlebih dahulu.');
        }

        if (in_array($affiliateStatus, ['pending', 'active'], true) && blank($user->referral_code)) {
            $user->referral_code = $this->generateUniqueReferralCode();
            $user->save();
        }

        $referralCode = (string) ($user->referral_code ?: '-');
        $commissionQuery = AffiliateHistory::query()->where('uplink_id', $user->id);
        $totalCommission = (int) round((float) (clone $commissionQuery)->sum('amount'));
        $commissionThisMonth = (int) round((float) (clone $commissionQuery)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount'));
        $latestCommissionAt = (clone $commissionQuery)->latest('created_at')->value('created_at');
        $downlineCount = User::query()->where('uplink', $user->username)->count();
        $recentDownlines = $this->buildRecentDownlines($user);

        $historyPaginator = AffiliateHistory::query()
            ->with('downlink')
            ->where('uplink_id', $user->id)
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        $histories = $historyPaginator->getCollection()
            ->map(function (AffiliateHistory $history): array {
                return [
                    'createdAt' => $this->formatDateTime($history->created_at, 'd M Y H:i') ?? '-',
                    'downlink' => (string) optional($history->downlink)->username ?: 'Unknown',
                    'orderId' => (string) ($history->order_id ?: '-'),
                    'amount' => (int) round((float) ($history->amount ?? 0)),
                    'status' => 'Sukses',
                ];
            })
            ->values()
            ->all();

        $categoryLinks = Kategori::query()
            ->where('status', 'active')
            ->orderBy('nama')
            ->get(['nama', 'kode'])
            ->map(function (Kategori $category): array {
                return [
                    'label' => (string) $category->nama,
                    'url' => url('/id/' . $category->kode),
                ];
            })
            ->values()
            ->all();

        array_unshift($categoryLinks, [
            'label' => 'Halaman Utama (Default)',
            'url' => url('/id'),
        ]);

        return Inertia::render('Public/Affiliate', [
            'affiliate' => [
                'status' => $affiliateStatus,
                'referralCode' => $referralCode,
                'availableBalance' => (int) round((float) ($user->balance ?? 0)),
                'totalCommission' => $totalCommission,
                'commissionThisMonth' => $commissionThisMonth,
                'downlineCount' => $downlineCount,
                'latestCommissionAt' => $this->formatDateTime($latestCommissionAt, 'd M Y H:i'),
                'categories' => $categoryLinks,
                'application' => [
                    'defaultWhatsapp' => (string) ($user->no_wa ?: ''),
                    'requirements' => [
                        'Data akun harus valid dan memakai nomor WhatsApp aktif.',
                        'Cantumkan URL channel promosi/sosial media yang digunakan untuk referral.',
                        'Pengajuan affiliate akan ditinjau admin maksimal 1x24 jam kerja.',
                        'Verifikasi tambahan hanya diminta jika memang dibutuhkan saat review.',
                    ],
                    'allowedFilesLabel' => 'Tidak perlu upload dokumen pada tahap pendaftaran awal.',
                    'lastSubmission' => [
                        'requestedAt' => $this->formatDateTime($user->affiliate_requested_at, 'd M Y, H:i'),
                    ],
                    'lastReview' => [
                        'decision' => (string) (data_get($user->affiliate_application_meta, 'review_last.decision') ?: ''),
                        'note' => (string) (data_get($user->affiliate_application_meta, 'review_last.note') ?: ''),
                        'reviewedAt' => (string) (data_get($user->affiliate_application_meta, 'review_last.reviewed_at') ?: ''),
                        'reviewedBy' => (string) (data_get($user->affiliate_application_meta, 'review_last.reviewed_by_username') ?: ''),
                    ],
                ],
                'histories' => $histories,
                'commissionHistory' => $histories,
                'recentDownlines' => $recentDownlines,
                'pagination' => [
                    'currentPage' => $historyPaginator->currentPage(),
                    'lastPage' => $historyPaginator->lastPage(),
                    'perPage' => $historyPaginator->perPage(),
                    'total' => $historyPaginator->total(),
                    'prevPageUrl' => $historyPaginator->previousPageUrl(),
                    'nextPageUrl' => $historyPaginator->nextPageUrl(),
                ],
                'flash' => [
                    'success' => session('success'),
                    'error' => session('error'),
                ],
                'links' => [
                    'dashboard' => route('dashboard'),
                    'transactions' => route('riwayat'),
                    'mutation' => route('reload'),
                    'affiliate' => route('affiliate'),
                    'withdrawal' => route('withdrawal'),
                    'canWithdraw' => $isAffiliateActive,
                    'request' => route('affiliate.request'),
                    'affiliateProgramTerms' => route('affiliate.program.terms'),
                    'terms' => route('terms'),
                    'privacyPolicy' => route('policy'),
                    'canShowAffiliate' => true,
                ],
            ],
            'meta' => [
                'title' => "Program Afiliasi - {$settings->judul_web}",
                'description' => 'Kelola referral, pantau komisi affiliate, dan lihat riwayat komisi terbaru.',
                'keywords' => "affiliate, referral, komisi, {$settings->judul_web}",
                'canonical' => url('/id/affiliate'),
                'image' => url($siteConfigService->normalizeAssetPath($settings->logo_favicon)),
            ],
        ]);
    }

    private function normalizeAffiliateStatus(string $statusRaw): string
    {
        $status = strtolower(trim($statusRaw));

        if ($status === '') {
            return 'inactive';
        }

        return match ($status) {
            'active', 'pending', 'rejected', 'inactive' => $status,
            default => 'inactive',
        };
    }

    private function generateUniqueReferralCode(): string
    {
        do {
            $code = 'REF-' . strtoupper(Str::random(6));
        } while (User::query()->where('referral_code', $code)->exists());

        return $code;
    }

    private function submitAffiliateRequest(Request $request, User $user, string $affiliateStatus): RedirectResponse
    {
        if (! in_array($affiliateStatus, ['inactive', 'rejected'], true)) {
            return redirect()->route('affiliate')->with('error', 'Permintaan tidak dapat diproses untuk status akun saat ini.');
        }

        $validated = $request->validate([
            'whatsapp' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s]{8,30}$/'],
            'promotion_channel_url' => ['required', 'url', 'max:255'],
            'notes' => ['nullable', 'string', 'max:600'],
            'agree_terms' => ['accepted'],
            'agree_affiliate_policy' => ['accepted'],
        ], [
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex' => 'Format nomor WhatsApp belum valid.',
            'promotion_channel_url.required' => 'URL channel promosi wajib diisi.',
            'promotion_channel_url.url' => 'URL channel promosi tidak valid.',
            'agree_terms.accepted' => 'Kamu wajib menyetujui syarat affiliate.',
            'agree_affiliate_policy.accepted' => 'Kamu wajib menyetujui kebijakan data affiliate.',
        ]);

        $normalizedWhatsapp = preg_replace('/\D+/', '', (string) $validated['whatsapp']);
        $promotionChannelUrl = blank($validated['promotion_channel_url'] ?? null) ? null : trim((string) $validated['promotion_channel_url']);
        $submitLockKey = 'affiliate-request-submit:' . sha1(implode('|', [
            (string) $user->id,
            (string) $normalizedWhatsapp,
            (string) ($promotionChannelUrl ?? ''),
        ]));

        if (! Cache::add($submitLockKey, true, 20)) {
            return redirect()->route('affiliate')->with('error', 'Permintaan sebelumnya masih diproses. Mohon tunggu sebentar.');
        }

        $existingMeta = is_array($user->affiliate_application_meta) ? $user->affiliate_application_meta : [];
        $reviewHistory = data_get($existingMeta, 'review_history');
        if (! is_array($reviewHistory)) {
            $reviewHistory = [];
        }

        try {
            DB::transaction(function () use (
                $user,
                $validated,
                $normalizedWhatsapp,
                $promotionChannelUrl,
                $reviewHistory,
                $request
            ): void {
                $user->no_wa = $normalizedWhatsapp;
                $user->affiliate_status = 'pending';
                $user->affiliate_requested_at = now();
                $user->affiliate_requirement_acknowledged_at = now();
                $user->affiliate_application_note = blank($validated['notes'] ?? null) ? null : trim((string) $validated['notes']);
                $user->affiliate_application_meta = [
                    'promotion_channel_url' => $promotionChannelUrl,
                    'submitted_via' => 'inertia_affiliate_form',
                    'submitted_ip' => $request->ip(),
                    'submitted_user_agent' => Str::limit((string) $request->userAgent(), 255),
                    'review_history' => $reviewHistory,
                    'review_last' => null,
                ];
                $user->save();
            });
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('affiliate')
                ->with('error', 'Terjadi kendala saat mengirim pengajuan. Silakan coba lagi.');
        } finally {
            Cache::forget($submitLockKey);
        }

        $successMessage = $affiliateStatus === 'rejected'
            ? 'Pengajuan ulang affiliate berhasil dikirim. Data kamu sedang ditinjau admin.'
            : 'Pengajuan affiliate berhasil dikirim. Data kamu sedang ditinjau admin.';

        return redirect()->route('affiliate')->with('success', $successMessage);
    }

    private function buildRecentDownlines(User $user): array
    {
        $downlines = User::query()
            ->where('uplink', $user->username)
            ->latest('created_at')
            ->limit(8)
            ->get(['id', 'name', 'username', 'created_at']);

        if ($downlines->isEmpty()) {
            return [];
        }

        $downlineIds = $downlines->pluck('id')->map(fn ($id): string => (string) $id)->all();
        $commissionSummary = AffiliateHistory::query()
            ->selectRaw('downlink_id, count(*) as commission_orders, sum(amount) as commission_total, max(created_at) as latest_commission_at')
            ->where('uplink_id', $user->id)
            ->whereIn('downlink_id', $downlineIds)
            ->groupBy('downlink_id')
            ->get()
            ->keyBy(fn (AffiliateHistory $history): string => (string) $history->downlink_id);

        return $downlines
            ->map(function (User $downline) use ($commissionSummary): array {
                $summary = $commissionSummary->get((string) $downline->id);

                return [
                    'username' => (string) ($downline->username ?: '-'),
                    'name' => (string) ($downline->name ?: $downline->username ?: '-'),
                    'joinedAt' => $this->formatDateTime($downline->created_at, 'd M Y'),
                    'orderCount' => (int) ($summary?->commission_orders ?? 0),
                    'totalCommission' => (int) round((float) ($summary?->commission_total ?? 0)),
                    'latestCommissionAt' => $this->formatDateTime($summary?->latest_commission_at, 'd M Y H:i'),
                ];
            })
            ->values()
            ->all();
    }

    private function formatDateTime(mixed $value, string $format): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->timezone(config('app.timezone'))->format($format);
    }
}
