<?php

namespace App\Tenancy\Concerns;

use App\Models\SettingWeb;

/**
 * Kontak platform untuk halaman publik (D8).
 *
 * Dipakai halaman status tenant non-aktif DAN halaman tagihan ber-token —
 * dua halaman yang harus menawarkan jalur pulang yang sama. Satu definisi,
 * supaya tidak menyimpang saat salah satu diubah.
 */
trait ResolvesPlatformContact
{
    /**
     * Utama `setting_webs.url_wa` → `setting_webs.nomor_admin` →
     * `config('app.url')`.
     *
     * Q5: kalau dua setting pertama kosong, hasilnya bukan tautan WhatsApp —
     * pemanggil membedakannya lewat `contactIsWhatsapp()` untuk memilih label.
     */
    protected function platformContactUrl(): string
    {
        $setting = SettingWeb::query()->find(1);

        $langsung = trim((string) ($setting?->url_wa ?? ''));

        if ($langsung !== '') {
            return $langsung;
        }

        $admin = preg_replace('/\D+/', '', (string) ($setting?->nomor_admin ?? '')) ?? '';

        if ($admin !== '') {
            return 'https://wa.me/' . $admin;
        }

        return (string) config('app.url');
    }

    protected function contactIsWhatsapp(string $contactUrl): bool
    {
        return str_contains($contactUrl, 'wa.me');
    }
}
