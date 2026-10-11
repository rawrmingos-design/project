<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Penjaga regresi untuk config/mail.php:
 *
 * 1. Tanpa MAIL_VERIFY_PEER/MAIL_PEER_FINGERPRINT, kunci itu TIDAK boleh ada di
 *    array mailer. Kalau ikut terkirim bernilai null, Symfony membaca
 *    `'' !== option && !filter_var(option)` -> verify_peer jadi false, artinya
 *    verifikasi TLS mati untuk semua pengiriman email tanpa disadari.
 * 2. Kalau diisi, nilainya harus diteruskan apa adanya (dipakai untuk mengunci
 *    sertifikat relay internal yang namanya tidak ada di SAN).
 */
final class MailConfigGuardTest extends TestCase
{
    public function test_kunci_tls_opsional_absen_kalau_env_kosong(): void
    {
        // Lingkungan test tidak mengisi keduanya -> mailer harus sama seperti semula.
        $smtp = config('mail.mailers.smtp');

        $this->assertArrayNotHasKey(
            'verify_peer',
            $smtp,
            'verify_peer bernilai null ikut terkirim -> Symfony mematikan verifikasi TLS diam-diam.'
        );
        $this->assertArrayNotHasKey('peer_fingerprint', $smtp);
    }

    public function test_mailer_smtp_punya_kunci_dasar(): void
    {
        $smtp = config('mail.mailers.smtp');

        foreach (['transport', 'host', 'port', 'encryption', 'username', 'password', 'timeout'] as $kunci) {
            $this->assertArrayHasKey($kunci, $smtp, "Kunci mailer '{$kunci}' hilang.");
        }
    }

    public function test_tidak_ada_mailer_yang_mematikan_verifikasi_tls(): void
    {
        // Aturan keselamatan: tidak ada mailer bawaan yang boleh punya verify_peer=false
        // tanpa fingerprint. Kalau perlu, pakai MAIL_PEER_FINGERPRINT, bukan mematikan verifikasi.
        foreach (config('mail.mailers') as $nama => $cfg) {
            if (! is_array($cfg)) {
                continue;
            }
            $mati = array_key_exists('verify_peer', $cfg) && filter_var($cfg['verify_peer'], FILTER_VALIDATE_BOOL) === false;
            $dikunci = ! empty($cfg['peer_fingerprint']);

            $this->assertFalse(
                $mati && ! $dikunci,
                "Mailer '{$nama}' mematikan verifikasi TLS tanpa mengunci sertifikat."
            );
        }
    }
}
