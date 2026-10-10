<?php

namespace App\Tenancy\Contracts;

/**
 * Membaca daftar hostname yang punya record DNS eksplisit di Cloudflare.
 *
 * Dibuat sebagai interface supaya test bisa memakai implementasi palsu
 * (tanpa jaringan) dan supaya guard tidak terikat ke HTTP client tertentu.
 */
interface CloudflareDnsClientInterface
{
    /**
     * Hostname yang punya record eksplisit di bawah `suffix`.
     *
     * Implementasi WAJIB:
     *  - menyaring record wildcard (`*`) — wildcard bukan "nama terpakai";
     *  - mengembalikan nama host lengkap (lowercase);
     *  - melempar exception kalau gagal (guard akan fail-open).
     *
     * @return array<string>
     *
     * @throws \RuntimeException
     */
    public function hostnamesUnder(string $zoneId, string $suffix): array;
}
