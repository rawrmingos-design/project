<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('public_push_notification_deliveries')) {
            return;
        }

        Schema::create('public_push_notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('public_push_subscription_id')->nullable()->constrained('public_push_subscriptions')->nullOnDelete();
            $table->string('order_id', 120);
            $table->string('event', 80);
            $table->string('endpoint_hash', 64)->nullable();
            $table->string('status', 32)->default('pending');
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'event', 'public_push_subscription_id'], 'public_push_delivery_unique');
            $table->index(['order_id', 'event']);
            $table->index(['endpoint_hash', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_push_notification_deliveries');
    }
};
