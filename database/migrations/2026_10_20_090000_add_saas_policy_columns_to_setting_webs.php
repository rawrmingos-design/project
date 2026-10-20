<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kebijakan SaaS & billing jadi data platform, bukan .env.
     *
     * Kenapa di `setting_webs`, bukan tabel `platform_settings` baru:
     * tabel ini SUDAH menjadi setelan platform (single row, halaman Settings
     * berwhitelist, dan `DatabaseSettingsBridge` yang menerapkannya ke config
     * untuk jalur web maupun queue worker). Menambah tabel key-value kedua akan
     * membuat dua sumber kebenaran untuk hal yang sama — persis masalah yang
     * dulu terjadi pada `telegram_required_channels`, di mana isian panel
     * diabaikan karena .env ikut dibaca.
     *
     * Semua kolom NULLABLE, lalu di-backfill dengan nilai efektif saat migrasi
     * berjalan. Deploy karena itu tidak mengubah perilaku sama sekali: apa yang
     * tadinya dibaca dari .env sekarang tersimpan sebagai angka yang sama, dan
     * sesudahnya admin bisa mengubahnya dari panel.
     */
    public function up(): void
    {
        if (! Schema::hasTable('setting_webs')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table): void {
            if (! Schema::hasColumn('setting_webs', 'saas_enabled')) {
                // Saklar utama SaaS: subdomain toko + pendaftaran mandiri.
                $table->boolean('saas_enabled')->nullable()->after('id');
            }

            if (! Schema::hasColumn('setting_webs', 'subdomain_guard_enabled')) {
                // Pemeriksaan nama subdomain (reserved/Cloudflare), terpisah dari
                // saklar utama supaya bisa dimatikan tanpa mematikan SaaS.
                $table->boolean('subdomain_guard_enabled')->nullable()->after('saas_enabled');
            }

            if (! Schema::hasColumn('setting_webs', 'billing_renewal_enabled')) {
                $table->boolean('billing_renewal_enabled')->nullable()->after('subdomain_guard_enabled');
            }

            if (! Schema::hasColumn('setting_webs', 'billing_reminder_days_before')) {
                $table->unsignedSmallInteger('billing_reminder_days_before')->nullable()->after('billing_renewal_enabled');
            }

            if (! Schema::hasColumn('setting_webs', 'billing_grace_days')) {
                $table->unsignedSmallInteger('billing_grace_days')->nullable()->after('billing_reminder_days_before');
            }

            if (! Schema::hasColumn('setting_webs', 'billing_late_fee')) {
                $table->unsignedInteger('billing_late_fee')->nullable()->after('billing_grace_days');
            }

            if (! Schema::hasColumn('setting_webs', 'billing_payment_link_minutes')) {
                $table->unsignedSmallInteger('billing_payment_link_minutes')->nullable()->after('billing_late_fee');
            }
        });

        $this->backfillFromConfig();
    }

    /**
     * Isi nilai awal dari konfigurasi yang sedang berlaku.
     *
     * Kalau kolom dibiarkan NULL, panel admin menampilkan nilai kosong dan
     * admin yang menyimpan tanpa menyentuh apa pun bisa mematikan SaaS tanpa
     * sadar. Backfill membuat layar panel menampilkan angka yang benar-benar
     * dipakai sekarang.
     */
    private function backfillFromConfig(): void
    {
        $row = DB::table('setting_webs')->where('id', 1)->first();

        if ($row === null) {
            return; // belum ada baris setelan; nanti dibuat dari panel
        }

        $updates = [];

        if (($row->saas_enabled ?? null) === null) {
            // `tenancy.disabled` = true berarti SaaS mati.
            $updates['saas_enabled'] = ! (bool) config('tenancy.disabled', true);
        }

        if (($row->subdomain_guard_enabled ?? null) === null) {
            $updates['subdomain_guard_enabled'] = (bool) config('tenancy.subdomain_guard_enabled', true);
        }

        if (($row->billing_renewal_enabled ?? null) === null) {
            $updates['billing_renewal_enabled'] = (bool) config('billing.renewal_enabled', false);
        }

        if (($row->billing_reminder_days_before ?? null) === null) {
            $updates['billing_reminder_days_before'] = max(0, (int) config('billing.reminder_days_before', 3));
        }

        if (($row->billing_grace_days ?? null) === null) {
            $updates['billing_grace_days'] = max(0, (int) config('billing.grace_days', 3));
        }

        if (($row->billing_late_fee ?? null) === null) {
            $updates['billing_late_fee'] = max(0, (int) config('billing.late_fee', 10000));
        }

        if (($row->billing_payment_link_minutes ?? null) === null) {
            $updates['billing_payment_link_minutes'] = max(1, (int) config('billing.payment_link_lifetime_minutes', 1440));
        }

        if ($updates !== []) {
            DB::table('setting_webs')->where('id', 1)->update($updates);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('setting_webs')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table): void {
            foreach ([
                'saas_enabled',
                'subdomain_guard_enabled',
                'billing_renewal_enabled',
                'billing_reminder_days_before',
                'billing_grace_days',
                'billing_late_fee',
                'billing_payment_link_minutes',
            ] as $column) {
                if (Schema::hasColumn('setting_webs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
