<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('telegram_update_receipts')) {
            return;
        }

        Schema::create('telegram_update_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('bot_scope', 100);
            $table->unsignedBigInteger('update_id');
            $table->timestamp('processed_at');
            $table->timestamps();
            $table->unique(['bot_scope', 'update_id'], 'telegram_update_receipt_scope_update_unique');
            $table->index('processed_at', 'telegram_update_receipt_processed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_update_receipts');
    }
};
