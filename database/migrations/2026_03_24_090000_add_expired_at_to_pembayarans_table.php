<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pembayarans')) {
            return;
        }

        if (! Schema::hasColumn('pembayarans', 'expired_at')) {
            Schema::table('pembayarans', function (Blueprint $table) {
                $table->timestamp('expired_at')->nullable()->after('paid_at')->index();
            });
        }

        DB::table('pembayarans')
            ->whereNull('expired_at')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $createdAt = $row->created_at ? Carbon::parse($row->created_at) : now();

                    DB::table('pembayarans')
                        ->where('id', $row->id)
                        ->update([
                            'expired_at' => $createdAt->copy()->addHours(3),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pembayarans') || ! Schema::hasColumn('pembayarans', 'expired_at')) {
            return;
        }

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropColumn('expired_at');
        });
    }
};
