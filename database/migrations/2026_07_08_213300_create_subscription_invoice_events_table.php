<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscription_invoice_events')) {
            return;
        }

        Schema::create('subscription_invoice_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_invoice_id')->constrained('subscription_invoices')->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('gateway')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->string('reference')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_invoice_events');
    }
};
