<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inbound_source_entries')) {
            return;
        }

        Schema::create('inbound_source_entries', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('policy_id');
            $table->string('value', 255);
            $table->string('value_type', 50)->default('ipv4');
            $table->string('label', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('policy_id')
                ->references('id')
                ->on('inbound_source_policies')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_source_entries');
    }
};
