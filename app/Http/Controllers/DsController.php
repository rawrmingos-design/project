<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use Illuminate\Http\Request;
use App\Models\Pembelian;
use App\Models\Pembayaran;
use Illuminate\Support\Carbon;
use App\Models\Kategori;
use App\Models\AffiliateHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Auth;


class DsController extends Controller
{
    
    public function dashboard()
    {
        $today = Carbon::today(); // Mengambil waktu hari ini (00:00:00)
        $user = Auth::user();
        $username = $user->username;

        $normalizeStatus = static fn ($status): string => strtolower(trim((string) $status));
        $buildStats = static function (Collection $transactions) use ($normalizeStatus): array {
            $statuses = $transactions->pluck('status')->map($normalizeStatus);
            return [
                'totalTransactions' => $transactions->count(),
                'totalSales' => (int) round((float) $transactions->sum('harga')),
                'waiting' => $statuses->filter(fn ($status) => in_array($status, ['pending', 'menunggu'], true))->count(),
                'processing' => $statuses->filter(fn ($status) => in_array($status, ['proses', 'process', 'processing', 'diproses'], true))->count(),
                'success' => $statuses->filter(fn ($status) => in_array($status, ['sukses', 'success'], true))->count(),
                'failed' => $statuses->filter(fn ($status) => in_array($status, ['batal', 'gagal', 'failed', 'cancelled', 'canceled'], true))->count(),
            ];
        };

        $periodDefinitions = [
            '1d' => [
                'label' => 'Hari ini',
                'start' => (clone $today)->startOfDay(),
            ],
            '7d' => [
                'label' => '7 hari terakhir',
                'start' => (clone $today)->subDays(6)->startOfDay(),
            ],
            '30d' => [
                'label' => '30 hari terakhir',
                'start' => (clone $today)->subDays(29)->startOfDay(),
            ],
        ];

        $periodStats = [];
        foreach ($periodDefinitions as $periodKey => $periodDefinition) {
            $periodTransactions = Pembelian::query()
                ->where('username', $username)
                ->where('created_at', '>=', $periodDefinition['start'])
                ->get();

            $periodStats[$periodKey] = array_merge(
                ['label' => $periodDefinition['label']],
                $buildStats($periodTransactions)
            );
        }

        $defaultPeriod = '30d';
        $todaysStats = $periodStats['1d'] ?? ['totalTransactions' => 0, 'totalSales' => 0, 'waiting' => 0, 'processing' => 0, 'success' => 0, 'failed' => 0];

        $totalPembelian = $todaysStats['totalSales'];
        $banyakPembelian = $todaysStats['totalTransactions'];
        $banyakPembelianPending = $todaysStats['waiting'];
        $banyakPembelianProses = $todaysStats['processing'];
        $banyakPembelianSuccess = $todaysStats['success'];
        $banyakPembelianBatal = $todaysStats['failed'];

        // 3. Fix Tier Logic: Use LIFETIME Success Transactions
        $lifetimeSuccessCount = Pembelian::where('username', $username)
                                ->whereIn('status', ['Sukses', 'Success'])
                                ->count();

        $setting = \App\Models\SettingWeb::first();
        $goldThreshold = $setting->trx_count_gold ?? 50;
        $platinumThreshold = $setting->trx_count_platinum ?? 100;
        
        $currentCount = $lifetimeSuccessCount; // Updated to use lifetime count
        $currentRole = $user->role;
        $nextRole = '';
        $progress = 0;
        $target = 0;

        if ($currentRole == 'Member') {
            $target = $goldThreshold;
            $nextRole = 'Gold';
            $progress = ($target > 0) ? ($currentCount / $target) * 100 : 0;
        } elseif ($currentRole == 'Gold') {
            $target = $platinumThreshold;
            $nextRole = 'Platinum';
            $progress = ($target > 0) ? ($currentCount / $target) * 100 : 0;
        } else { // Platinum or Admin
            $progress = 100;
            $nextRole = 'Max Level';
            $target = $platinumThreshold; // Just for display
        }
        
        if ($progress > 100) $progress = 100;

        // --- Affiliate System Logic ---
        $referralCode = $user->referral_code ?? '-';
        $totalCommission = \App\Models\AffiliateHistory::where('uplink_id', $user->id)->sum('amount');
        $affiliateHistory = \App\Models\AffiliateHistory::where('uplink_id', $user->id)
                            ->latest()
                            ->take(5)
                            ->get();

        // 4. Fetch Recent Transactions for Dashboard Table (Limit 10)
        $recentTransactions = Pembelian::where('username', $username)
                                ->latest()
                                ->take(10)
                                ->get();

        return view('template.dashboard', [
            'data' => $recentTransactions, // Pass recent history instead of today's
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'total_pembelian' => $totalPembelian,
            'banyak_pembelian' => $banyakPembelian,
            'banyak_pembelian_pending' => $banyakPembelianPending,
            'banyak_pembelian_proses' => $banyakPembelianProses,
            'banyak_pembelian_success' => $banyakPembelianSuccess,
            'banyak_pembelian_batal' => $banyakPembelianBatal,
            'period_stats' => $periodStats,
            'period_default' => $defaultPeriod,
            // Tier Data
            'tier_progress' => $progress,
            'tier_current' => $currentRole,
            'tier_next' => $nextRole,
            'tier_count' => $currentCount,
            'tier_target' => $target,
            // Affiliate Data
            'referral_code' => $referralCode,
            'total_commission' => $totalCommission,
            'affiliate_history' => $affiliateHistory,
        ]);
    }
    
    public function editProfile()
    {
         return view('template.profile',[
        'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
        'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        ]);
    }
    
    public function saveEditProfile(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'username' => 'required|min:3|max:255|unique:users,username,'.Auth()->user()->id,
            'password' => 'nullable|min:6|max:255'
        ], [
            'nama.required' => 'Harap isi kolom nama!',
            'username.required' => 'Harap isi kolom username!',
            'username.min' => 'Panjang username minimal 3 huruf',
            'username.unique' => 'Username telah digunakan',
            'username.max' => 'Panjang username maximal 255 huruf',
            'password.min' => 'Panjang password minimal 6 huruf',
            'password.max' => 'Panjang password maximal 255 huruf',
            'no_wa.required' => 'Harap isi no whatsapp!',
            'no_wa.numeric' => 'No whatsapp tidak valid!',
            'no_wa.unique' => 'No whatsapp telah digunakan',
        ]);

        
        $data = [
          'name' => $request->name,
          'username' => $request->username,
        ];
        
        if(!empty($request->password)){
            
            $data['password'] = bcrypt($request->password);
            
        }
        
        \App\Models\User::where('id',Auth()->user()->id)->update($data);
        
        return redirect()->back()->with('success', 'Berhasil mengedit profile!');

    }
    

    public function affiliate(Request $request)
    {
        $user = Auth::user();
        $affiliateStatus = $this->normalizeAffiliateStatus((string) ($user->affiliate_status ?? ''));

        if ($request->isMethod('post')) {
            return $this->submitAffiliateRequest($request, $user, $affiliateStatus);
        }

        if ($request->query('action') === 'request') {
            return redirect()->route('affiliate')->with('error', 'Silakan isi formulir pengajuan affiliate terbaru terlebih dahulu.');
        }

        $referral_code = '-';
        $total_commission = 0;
        $affiliate_history = collect();

        if ($user && $user->role !== "Admin") {
            if ($affiliateStatus !== 'inactive' && blank($user->referral_code)) {
                do {
                    $user->referral_code = 'REF-' . strtoupper(Str::random(6));
                } while (\App\Models\User::where('referral_code', $user->referral_code)->exists());
                $user->save();
            }

            if ($affiliateStatus !== 'inactive') {
                $referral_code = $user->referral_code;

                $total_commission = AffiliateHistory::where('uplink_id', $user->id)
                    ->sum('amount');

                $affiliate_history = AffiliateHistory::where('uplink_id', $user->id)
                    ->latest()
                    ->paginate(10);
            }
        }

        return view('template.affiliate', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'affiliate_history' => $affiliate_history,
            'referral_code' => $referral_code,
            'total_commission' => $total_commission,
            'kategoris' => Kategori::where('status', 'active')->orderBy('nama', 'asc')->get(),
            'affiliate_status_normalized' => $affiliateStatus,
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

    private function submitAffiliateRequest(Request $request, $user, string $affiliateStatus)
    {
        if (! $user || $user->role === 'Admin') {
            return redirect()->route('dashboard')->with('error', 'Akses tidak diizinkan.');
        }

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
        $promotionChannelUrl = trim((string) $validated['promotion_channel_url']);
        $submitLockKey = 'affiliate-request-submit:' . sha1(implode('|', [
            (string) $user->id,
            (string) $normalizedWhatsapp,
            (string) $promotionChannelUrl,
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
            DB::transaction(function () use ($user, $validated, $normalizedWhatsapp, $promotionChannelUrl, $reviewHistory, $request): void {
                $user->no_wa = $normalizedWhatsapp;
                $user->affiliate_status = 'pending';
                $user->affiliate_requested_at = now();
                $user->affiliate_requirement_acknowledged_at = now();
                $user->affiliate_application_note = blank($validated['notes'] ?? null) ? null : trim((string) $validated['notes']);
                $user->affiliate_application_meta = [
                    'promotion_channel_url' => $promotionChannelUrl,
                    'submitted_via' => 'blade_affiliate_form',
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

    public function withdrawal()
    {
        $user = Auth::user();
        if (! $this->isAffiliateActiveUser($user)) {
            return redirect()->route('dashboard')->with('error', 'Fitur redeem saldo hanya tersedia untuk akun affiliate yang sudah aktif.');
        }

        $withdrawals = \App\Models\Withdrawal::where('user_id', $user->id)
            ->latest()
            ->paginate(5);

        // Check if user already requested today
        $hasRequestedToday = \App\Models\Withdrawal::where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        return view('template.withdrawal', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'withdrawals' => $withdrawals,
            'hasRequestedToday' => $hasRequestedToday,
        ]);
    }

    public function processWithdrawal(Request $request)
    {
        $user = \App\Models\User::find(Auth::id());
        if (! $this->isAffiliateActiveUser($user)) {
            return redirect()->route('dashboard')->with('error', 'Fitur redeem saldo hanya tersedia untuk akun affiliate yang sudah aktif.');
        }

        $currentBalance = (int) round((float) ($user->balance ?? 0));
        if ($currentBalance < 10000) {
            return back()->withInput()->with('error', 'Saldo saat ini belum memenuhi minimal penarikan Rp 10.000.');
        }

        $validated = $request->validate([
            'bank_destination' => ['required', 'string', 'max:80'],
            'account_number' => ['required', 'digits_between:8,24'],
            'account_name' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'min:10000', 'max:' . $currentBalance],
        ], [
            'amount.max' => 'Jumlah penarikan tidak boleh melebihi saldo saat ini.',
            'account_number.digits_between' => 'Nomor rekening harus terdiri dari 8 sampai 24 digit.',
        ]);

        $amount = (int) round((float) $validated['amount']);
        $accountNumber = preg_replace('/\D+/', '', (string) $validated['account_number']);
        $lockKey = 'withdraw-submit:' . sha1(implode('|', [
            (string) $user->id,
            (string) $amount,
            strtoupper(trim((string) $validated['bank_destination'])),
            $accountNumber,
        ]));

        if (! Cache::add($lockKey, true, 30)) {
            return back()->withInput()->with('error', 'Permintaan sebelumnya masih diproses. Mohon tunggu sebentar.');
        }

        try {
            DB::transaction(function () use ($user, $validated, $amount, $accountNumber) {
                $lockedUser = \App\Models\User::query()
                    ->whereKey($user->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedUser || (int) round((float) $lockedUser->balance) < $amount) {
                    throw ValidationException::withMessages([
                        'amount' => 'Jumlah penarikan tidak boleh melebihi saldo saat ini.',
                    ]);
                }

                $hasRequestedToday = \App\Models\Withdrawal::query()
                    ->where('user_id', $lockedUser->id)
                    ->whereDate('created_at', now()->toDateString())
                    ->lockForUpdate()
                    ->exists();

                if ($hasRequestedToday) {
                    throw ValidationException::withMessages([
                        'amount' => 'Anda hanya dapat melakukan penarikan 1 kali dalam sehari. Silakan coba lagi besok.',
                    ]);
                }

                $lockedUser->balance = (int) round((float) $lockedUser->balance) - $amount;
                $lockedUser->save();

                \App\Models\Withdrawal::create([
                    'user_id' => $lockedUser->id,
                    'rekening' => strtoupper(trim((string) $validated['bank_destination'])) . ' - ' . $accountNumber . ' - ' . trim((string) $validated['account_name']),
                    'total_transfer' => $amount,
                    'biaya_admin' => 0,
                    'status' => 'pending',
                ]);
            });
        } catch (ValidationException $validationException) {
            return back()->withInput()->withErrors($validationException->errors());
        } finally {
            Cache::forget($lockKey);
        }

        return redirect()->back()->with('success', 'Permintaan penarikan berhasil dikirim. Menunggu persetujuan Admin.');
    }

    private function isAffiliateActiveUser($user): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isAffiliateActive')) {
            return (bool) $user->isAffiliateActive();
        }

        return strtolower(trim((string) ($user->affiliate_status ?? ''))) === 'active';
    }
}
