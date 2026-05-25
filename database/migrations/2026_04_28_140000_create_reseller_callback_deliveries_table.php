<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reseller_callback_deliveries')) {
            Schema::drop('reseller_callback_deliveries');
        }

        Schema::create('reseller_callback_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('reseller_integration_id')->nullable();
            $table->foreignId('reseller_callback_profile_id')->nullable();
            $table->foreignId('pembelian_id')->nullable();
            $table->string('environment')->default('live');
            $table->string('event_name');
            $table->string('order_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('callback_url');
            $table->string('signature_algorithm')->default('sha256');
            $table->json('payload');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->string('status')->default('pending');
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedSmallInteger('last_response_status')->nullable();
            $table->text('last_response_body')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'rcd_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('reseller_integration_id', 'rcd_integration_fk')
                ->references('id')
                ->on('reseller_integrations')
                ->nullOnDelete();
            $table->foreign('reseller_callback_profile_id', 'rcd_profile_fk')
                ->references('id')
                ->on('reseller_callback_profiles')
                ->nullOnDelete();
            $table->foreign('pembelian_id', 'rcd_pembelian_fk')
                ->references('id')
                ->on('pembelians')
                ->nullOnDelete();

            $table->index(['pembelian_id', 'status'], 'rcd_pembelian_status_idx');
            $table->index(['user_id', 'environment'], 'rcd_user_env_idx');
            $table->index(['reseller_integration_id', 'status'], 'rcd_integration_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_callback_deliveries');
    }
};
