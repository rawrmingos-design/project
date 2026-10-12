<?php

namespace App\Tenancy;

/**
 * D9: nama tenant disensor di halaman publik.
 *
 * Aturan: huruf pertama + bintang sejumlah karakter yang diganti + huruf
 * terakhir. Jumlah bintang selalu sama dengan jumlah karakter yang diganti.
 *   n <= 1 → tampil apa adanya
 *   n == 2 → 'a*'
 *   n >= 3 → 'i****a'
 *
 * Hanya label pertama alamat (subdomain / custom domain) yang disensor —
 * basis domain dibiarkan supaya alamatnya tetap benar.
 *
 * Value object murni: tidak membaca config, tidak menyentuh DB.
 */
class TenantAddressMasker
{
    public function maskLabel(string $label): string
    {
        $length = mb_strlen($label);

        if ($length <= 1) {
            return $label;
        }

        if ($length === 2) {
            return mb_substr($label, 0, 1) . '*';
        }

        return mb_substr($label, 0, 1)
            . str_repeat('*', $length - 2)
            . mb_substr($label, -1);
    }

    /**
     * Sensor label tenant pada sebuah host.
     *
     * `$baseHost` adalah basis domain platform (mis. `topupengine.test`).
     * Tanpa basis itu label tenant tidak bisa dipastikan, jadi label pertama
     * tetap disensor — gagal-tertutup, bukan gagal-terbuka.
     *
     * @param string $host     Host mentah, boleh menyertakan port.
     * @param string $baseHost Basis domain platform.
     */
    public function maskHost(string $host, string $baseHost): string
    {
        $host = strtolower(trim($host));

        if ($host === '') {
            return '';
        }

        $port = '';

        if (preg_match('/^(.+?)(:\d+)$/', $host, $matches) === 1) {
            $host = $matches[1];
            $port = $matches[2];
        }

        // localhost dan IP bukan identitas tenant.
        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return $host . $port;
        }

        $baseHost = strtolower(trim($baseHost));

        $label = null;

        if ($baseHost !== '' && str_ends_with($host, '.' . $baseHost)) {
            $candidate = substr($host, 0, -1 * (strlen($baseHost) + 1));

            // Subdomain bersarang tidak dikenali ResolveTenant sebagai tenant;
            // biarkan apa adanya supaya alamatnya tidak salah.
            if ($candidate !== '' && ! str_contains($candidate, '.')) {
                $label = $candidate;
            }
        } elseif ($baseHost === '' && str_contains($host, '.')) {
            $label = strstr($host, '.', true) ?: null;
        }

        if ($label === null) {
            return $host . $port;
        }

        return $this->maskLabel($label) . substr($host, strlen($label)) . $port;
    }
}
