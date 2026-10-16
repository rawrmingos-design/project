<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Satu pintu untuk pertanyaan identitas user.
 *
 * Sebelum ini, aturan "satu orang satu akun" dijalankan di tiap controller
 * dengan caranya sendiri, dan tiap jalur lupa hal yang berbeda:
 *
 *   - `RegisterController` / `Api\AuthController` : validasi `unique:users,email`
 *   - `GoogleAuthController`                      : cari google_id, lalu LOWER(email)
 *   - `TenantRegistrationService`                 : cari email PERSIS (case-sensitive)
 *   - `BotCommandHandler`                         : bikin sendiri, suffix random
 *
 * Kolom `users.email` dan `users.username` TIDAK punya unique index di database,
 * jadi validasi aplikasi adalah satu-satunya penjaga. Begitu satu jalur lupa,
 * duplikat langsung masuk. Semua pencarian & pengecekan di sini memakai
 * pembanding case-insensitive yang sama supaya keempat jalur berperilaku identik.
 *
 * Pencocokan case-insensitive: kolom `users.email`/`username` memakai collation
 * `utf8mb4_unicode_ci`, jadi `where('email', $x)` sudah case-insensitive di MySQL.
 * Kami tetap memakai `whereRaw('LOWER(...) = ?')` dengan nilai yang sudah
 * di-lower, supaya perilakunya sama juga di SQLite (dipakai test).
 */
class UserIdentityService
{
    /** Normalisasi email: trim + lowercase. Dipakai semua jalur. */
    public function normalizeEmail(?string $email): string
    {
        return strtolower(trim((string) $email));
    }

    /** Cari user lewat email, case-insensitive. */
    public function findByEmail(?string $email): ?User
    {
        return $this->findAllByEmail($email)->first();
    }

    /**
     * SEMUA user dengan email ini.
     *
     * Kolom `email` tidak punya unique index, jadi satu email bisa menempel ke
     * beberapa akun (di staging: 515 email dipakai >1 akun). Jalur Google wajib
     * tahu itu: menautkan `google_id` ke "yang pertama ketemu" berarti menautkan
     * ke akun yang mungkin salah. Kalau hasilnya >1, pemanggil harus menolak.
     */
    public function findAllByEmail(?string $email): \Illuminate\Support\Collection
    {
        $normalized = $this->normalizeEmail($email);

        if ($normalized === '') {
            return collect();
        }

        return User::query()->whereRaw('LOWER(email) = ?', [$normalized])->orderBy('id')->get();
    }

    /** Cari user lewat google_id. */
    public function findByGoogleId(?string $sub): ?User
    {
        $sub = trim((string) $sub);

        if ($sub === '') {
            return null;
        }

        return User::query()->where('google_id', $sub)->first();
    }

    /**
     * Cari user lewat username, case-insensitive.
     * `username` adalah kunci login (LoginController pakai Auth::attempt(['username', ...])).
     */
    public function findByUsername(?string $username): ?User
    {
        $username = trim((string) $username);

        if ($username === '') {
            return null;
        }

        return User::query()->whereRaw('LOWER(username) = ?', [strtolower($username)])->first();
    }

    /** Username sudah dipakai? `$exceptUserId` untuk update user yang sama. */
    public function usernameTaken(string $candidate, ?int $exceptUserId = null): bool
    {
        $query = User::query()->whereRaw('LOWER(username) = ?', [strtolower(trim($candidate))]);

        if ($exceptUserId !== null) {
            $query->where('id', '!=', $exceptUserId);
        }

        return $query->exists();
    }

    /** Email sudah dipakai? `$exceptUserId` untuk update user yang sama. */
    public function emailTaken(string $email, ?int $exceptUserId = null): bool
    {
        $normalized = $this->normalizeEmail($email);

        if ($normalized === '') {
            return false;
        }

        $query = User::query()->whereRaw('LOWER(email) = ?', [$normalized]);

        if ($exceptUserId !== null) {
            $query->where('id', '!=', $exceptUserId);
        }

        return $query->exists();
    }

    /**
     * Username unik dari nama dasar, dengan suffix angka bila sudah terpakai.
     *
     * Dipakai `GoogleAuthController` dan `TenantRegistrationService`. Logika lama
     * keduanya berbeda (suffix `1,2,3` vs `-1,-2,-3`) — disatukan di sini, tapi
     * pemanggil boleh menyalurkan `$separator` untuk mempertahankan bentuk lama.
     */
    public function uniqueUsername(string $base, string $separator = '', ?int $exceptUserId = null): string
    {
        $candidate = trim($base) !== '' ? trim($base) : 'user';

        if (strlen($candidate) < 3) {
            $candidate = str_pad($candidate, 3, '0');
        }

        $head = \Illuminate\Support\Str::limit($candidate, 240, '');
        $result = $candidate;
        $sequence = 1;

        while ($this->usernameTaken($result, $exceptUserId)) {
            $result = $head . $separator . $sequence;
            $sequence++;
        }

        return $result;
    }

    /**
     * Sanitasi nama jadi basis username: huruf kecil, ASCII, tanpa simbol.
     * (Dipindah dari GoogleAuthController supaya jalur lain bisa pakai juga.)
     */
    public function usernameBaseFrom(string $name, string $fallback = 'user'): string
    {
        $sanitized = \Illuminate\Support\Str::of($name)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->value();

        $sanitized = trim($sanitized);

        return $sanitized !== '' ? $sanitized : $fallback;
    }

    // ------------------------------------------------------------------
    // Satu user, satu peran: tenant owner dan reseller H2H saling eksklusif
    // ------------------------------------------------------------------

    /**
     * Reseller H2H dikenali dari RELASI, bukan dari `role`.
     *
     * `role` Gold/Platinum adalah penentu harga jual dan hasil tangga transaksi
     * (`TierService`), jadi role bukan penanda "ini reseller". Yang menentukan
     * akses Reseller Hub adalah `resellerIntegrations()`.
     */
    public function isReseller(User $user): bool
    {
        return $user->resellerIntegrations()->exists();
    }

    /** Punya toko tenant (status apa pun)? */
    public function isTenantOwner(User $user): bool
    {
        if ($user->tenant_id !== null) {
            return true;
        }

        return \Illuminate\Support\Facades\Schema::hasTable('tenants')
            && \App\Models\Tenant::query()->where('owner_user_id', $user->id)->exists();
    }

    /**
     * User ini boleh punya tenant? Tidak, kalau dia akun reseller H2H.
     * Dipanggil dari jalur pendaftaran tenant.
     */
    public function assertCanOwnTenant(User $user): void
    {
        if ($this->isReseller($user)) {
            throw ValidationException::withMessages([
                'email' => 'Email ini terdaftar sebagai akun reseller H2H. Satu akun hanya boleh untuk satu jenis usaha — pakai email lain untuk membuka toko tenant.',
            ]);
        }
    }

    /**
     * User ini boleh jadi reseller H2H? Tidak, kalau dia sudah punya toko tenant.
     * Dipanggil dari jalur pengajuan reseller.
     */
    public function assertCanBeReseller(User $user): void
    {
        if ($this->isTenantOwner($user)) {
            throw ValidationException::withMessages([
                'business_name' => 'Akun ini sudah terdaftar sebagai pemilik toko tenant. Satu akun hanya boleh untuk satu jenis usaha — pakai email lain untuk mendaftar reseller H2H.',
            ]);
        }
    }
}
