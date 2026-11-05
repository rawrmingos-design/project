<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Withdrawal;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pencairan komisi tenant — MANUAL.
 *
 * Pemilik toko mengajukan penarikan dari saldo komisi yang SUDAH terkumpul.
 * Saldo langsung dipotong saat pengajuan (uang "dipesan"), dan Admin
 * mentransfer manual lalu menandai lunas lewat Filament (WithdrawalResource):
 * isi bukti transfer -> status `success`.
 *
 * Kalau pengajuan ditolak Admin, saldo harus dikembalikan; itu tanggung jawab
 * aksi Tolak di sisi admin, bukan di sini — di sini hanya memastikan uang tidak
 * bisa ditarik dua kali.
 */
class WithdrawalController extends Controller
{
    public function index(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->get();
        $owner = $this->authorizeOwner($request, $tenant);

        $withdrawals = Withdrawal::query()
            ->where('user_id', $owner->id)
            ->latest()
            ->limit(50)
            ->get();

        return view('tenant.withdrawals', [
            'tenant' => $tenant,
            'owner' => $owner,
            'balance' => (int) $owner->balance,
            'withdrawals' => $withdrawals,
        ]);
    }

    public function store(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->get();
        $owner = $this->authorizeOwner($request, $tenant);

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'bank_destination' => ['required', 'string', 'max:50'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_name' => ['required', 'string', 'max:100'],
        ]);

        $amount = (int) $validated['amount'];
        $rekening = strtoupper(trim($validated['bank_destination']))
            . ' - ' . trim($validated['account_number'])
            . ' - ' . trim($validated['account_name']);

        // Kunci per-owner supaya dua klik cepat tidak memotong saldo dua kali.
        $lockKey = 'tenant-withdrawal:' . $owner->id;

        try {
            Cache::lock($lockKey, 10)->block(5, function () use ($owner, $amount, $rekening): void {
                DB::transaction(function () use ($owner, $amount, $rekening): void {
                    $lockedUser = User::withoutGlobalScopes()
                        ->whereKey($owner->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $lockedUser) {
                        throw ValidationException::withMessages([
                            'amount' => 'Akun pemilik toko tidak ditemukan.',
                        ]);
                    }

                    $balance = (int) round((float) $lockedUser->balance);

                    if ($amount > $balance) {
                        throw ValidationException::withMessages([
                            'amount' => 'Jumlah penarikan melebihi saldo komisi yang tersedia '
                                . '(Rp ' . number_format($balance, 0, ',', '.') . ').',
                        ]);
                    }

                    $lockedUser->balance = $balance - $amount;
                    $lockedUser->save();

                    Withdrawal::create([
                        'user_id' => $lockedUser->id,
                        'rekening' => $rekening,
                        'total_transfer' => $amount,
                        'biaya_admin' => 0,
                        'status' => 'pending',
                    ]);
                });
            });
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('tenant.withdrawals')
            ->with('success', 'Permintaan pencairan terkirim. Menunggu persetujuan admin.');
    }

    /**
     * Hanya pemilik toko yang boleh menyentuh uang toko.
     *
     * @return User
     */
    private function authorizeOwner(Request $request, $tenant): User
    {
        abort_unless($tenant, 404);

        $user = $request->user();
        abort_unless($user && (int) $user->id === (int) $tenant->owner_user_id, 403);

        return $user;
    }
}
