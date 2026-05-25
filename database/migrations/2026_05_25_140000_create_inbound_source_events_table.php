<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbound_source_events', function (Blueprint $table): void {
            $table->id();
            $table->string('source_domain')->nullable();
            $table->string('source_name')->nullable();
            $table->string('route_uri')->nullable();
            $table->string('route_name')->nullable();
            $table->string('method', 12)->nullable();
            $table->string('resolved_client_ip', 64)->nullable();
            $table->string('normalized_client_ip', 64)->nullable();
            $table->string('mode', 32)->nullable();
            $table->string('decision', 64);
            $table->string('reason', 64)->nullable();
            $table->unsignedBigInteger('matched_entry_id')->nullable();
            $table->string('matched_entry_value')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['source_domain', 'source_name'], 'ise_source_idx');
            $table->index(['decision', 'created_at'], 'ise_decision_created_idx');
            $table->index(['created_at'], 'ise_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_source_events');
    }
};
