<?php

namespace App\Tenancy;

/**
 * Daftar nama subdomain yang TIDAK boleh dipakai tenant.
 *
 * Ini penopang utama pengecekan: wildcard DNS (`*.test.jasakoding.web.id`)
 * menjawab untuk SEMUA nama, jadi resolusi DNS tidak bisa dipakai untuk
 * membedakan nama yang sudah dipesan dari yang belum. Daftar ini harus
 * diperbarui SETIAP kali ada service baru yang dipasang di zona ini.
 *
 * Disimpan di kode (bukan DB) supaya perubahan ter-review lewat diff dan
 * terkunci oleh test.
 */
class SubdomainReservationPolicy
{
    /** Alamat & path yang dipakai platform sendiri. */
    public const PLATFORM = [
        'admin',
        'api',
        'app',
        'assets',
        'cdn',
        'docs',
        'mail',
        'static',
        'support',
        'www',
    ];

    /** Infrastruktur email & DNS. */
    public const INFRA = [
        'smtp',
        'imap',
        'pop',
        'pop3',
        'mx',
        'ns1',
        'ns2',
        'webmail',
        'autodiscover',
        'autoconfig',
        'ftp',
        'cpanel',
        'whm',
    ];

    /** Host khusus environment — nyata dipakai di zona ini. */
    public const ENV_HOST = [
        'test',
        'dev',
        'demo',
        'ws',
        'n8n',
        'kiro',
        'notion',
        'notion-ai',
    ];

    /** Layanan yang sudah punya record/vhost nyata di bawah zona. */
    public const SERVICE = [
        'store-a',
        'store-b',
        'cekid',
        'mt5',
        'vorspad',
        'wagateway',
        'multindoku',
    ];

    /** Nama klien yang harus dilindungi. */
    public const CLIENT = [
        'imhaf',
    ];

    /** @return array<string> */
    public static function reserved(): array
    {
        return [
            ...self::PLATFORM,
            ...self::INFRA,
            ...self::ENV_HOST,
            ...self::SERVICE,
            ...self::CLIENT,
        ];
    }

    public static function isReserved(string $subdomain): bool
    {
        return in_array($subdomain, self::reserved(), true);
    }

    /** Alasan penolakan untuk nama reserved, atau null kalau tidak reserved. */
    public static function reasonFor(string $subdomain): ?string
    {
        return self::isReserved($subdomain)
            ? 'Nama ini tidak bisa dipakai. Silakan pilih nama lain.'
            : null;
    }
}
