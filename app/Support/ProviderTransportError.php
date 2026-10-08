<?php

namespace App\Support;

/**
 * Klasifikasi kegagalan TRANSPORT (timeout / koneksi) pada pemanggilan provider.
 *
 * Kegagalan transport BUKAN vonis provider atas sebuah order: request-nya tidak
 * pernah sampai (atau balasannya tidak pernah terbaca), jadi tidak ada informasi
 * status apa pun. Memperlakukannya sebagai `Gagal` pernah membuat order yang
 * SUDAH dibayar ikut ditandai gagal lalu di-refund — padahal provider mungkin
 * sudah menerima order tersebut.
 *
 * Aturan mainnya:
 * - 1-2 kegagalan transport beruntun  → status order TIDAK diubah (tunggu poll berikutnya).
 * - Mencapai batas beruntun (default 3) → baru diputuskan `Gagal`, karena provider
 *   sudah tidak bisa dihubungi berulang kali dan order tidak boleh menggantung.
 *
 * Setiap status sukses dari provider mereset hitungannya.
 */
final class ProviderTransportError
{
    /** Batas kegagalan transport beruntun sebelum order diputus Gagal. */
    public const DEFAULT_MAX_CONSECUTIVE = 3;

    /**
     * Penanda pesan kegagalan transport. Sengaja sempit supaya tidak salah
     * menangkap pesan bisnis biasa dari provider.
     */
    private const MARKERS = [
        'connection error',
        'curl error',
        'connection timed out',
        'connection timeout',
        'timed out after',
        'timeout after',
        'could not resolve host',
        'connection refused',
        'operation timed out',
        'network is unreachable',
        'ssl connection timeout',
        'empty reply from server',
    ];

    public static function maxConsecutive(): int
    {
        return max(1, (int) config(
            'providers.digiflazz.max_consecutive_transport_failures',
            self::DEFAULT_MAX_CONSECUTIVE,
        ));
    }

    /**
     * Apakah pesan ini menunjukkan kegagalan transport (bukan status dari provider)?
     */
    public static function isTransportFailure(?string $message): bool
    {
        $haystack = strtolower(trim((string) $message));

        if ($haystack === '') {
            return false;
        }

        foreach (self::MARKERS as $marker) {
            if (str_contains($haystack, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pesan kegagalan transport yang sudah BASI tidak boleh menempel pada status
     * non-final. Kasus nyata: status digelembungkan kembali ke `Processing` oleh
     * poll berikutnya, tapi `keterangan_sn` masih berisi "Connection Error: cURL
     * error 28 ..." — admin melihat order `Processing` dengan keterangan gagal.
     */
    public static function isStaleFailureMessage(?string $message): bool
    {
        return self::isTransportFailure($message);
    }

    /**
     * Pesan yang aman ditampilkan untuk status non-final.
     */
    public static function neutralProcessingNote(): string
    {
        return 'Sedang Diproses';
    }
}
