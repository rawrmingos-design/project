<?php

namespace Tests\Feature\Bot;

use Tests\TestCase;

/**
 * Direktori upload Banner Menu Utama bot (`assets/bot`) WAJIB disiapkan oleh
 * konfigurasi deploy, bukan hanya oleh field Filament.
 *
 * Kenapa ini layak dikunci: field admin menulis ke disk `uploads.disk`. Di
 * deployment yang memakai disk lokal (`assets`), berkas yang tidak berada di
 * volume akan tersimpan di LAYER CONTAINER dan HILANG setiap deploy — kolom
 * `bot_menu_banner` tetap menunjuk path lama, jadi banner mendadak kosong tanpa
 * satu pun error di log. Kejadian ini lolos di staging hanya karena staging
 * memakai disk `r2` (CloudFront), sehingga bug-nya baru muncul di produksi.
 *
 * Dua tempat yang harus sepakat, dan keduanya diperiksa dari FILE REPO:
 *   1. `docker-compose.yml` — volume `public_bot` didefinisikan DAN dipasang.
 *   2. `docker/scripts/post-deploy-app.sh` — `mkdir -p public/assets/bot`.
 *
 * Sengaja memeriksa berkas, bukan menjalankan docker: yang dikunci adalah
 * isi konfigurasi yang di-deploy, dan proses itu tidak tersedia di CI.
 */
class BotMenuBannerStorageTest extends TestCase
{
    private function composeContents(): string
    {
        return (string) file_get_contents(base_path('docker-compose.yml'));
    }

    private function postDeployContents(): string
    {
        return (string) file_get_contents(base_path('docker/scripts/post-deploy-app.sh'));
    }

    public function test_volume_banner_bot_didefinisikan_di_compose(): void
    {
        $compose = $this->composeContents();

        $this->assertMatchesRegularExpression(
            '/^\s{2}public_bot:\s*$/m',
            $compose,
            'Volume `public_bot` harus terdaftar di blok `volumes:` — tanpa itu '
            . 'mount-nya gagal dan banner tersimpan di layer container.'
        );
    }

    public function test_volume_banner_bot_dipasang_ke_direktori_assets_bot(): void
    {
        $compose = $this->composeContents();

        $this->assertStringContainsString(
            'public_bot:/var/www/html/public/assets/bot',
            $compose,
            'Volume `public_bot` harus dipasang ke /var/www/html/public/assets/bot, '
            . 'kalau tidak admin bisa mengunggah banner tetapi berkasnya hilang saat deploy.'
        );
    }

    public function test_post_deploy_membuat_direktori_assets_bot(): void
    {
        $script = $this->postDeployContents();

        $this->assertStringContainsString(
            'public/assets/bot',
            $script,
            'Skrip post-deploy harus menjalankan mkdir -p public/assets/bot supaya '
            . 'direktori upload ada di container yang baru dibuat.'
        );
    }
}
