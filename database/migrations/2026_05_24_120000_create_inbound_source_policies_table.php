<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inbound_source_policies')) {
            return;
        }

        Schema::create('inbound_source_policies', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('source_domain', 100);
            $table->string('source_name', 150);
            $table->string('route_scope', 255)->nullable();
            $table->string('mode', 50)->default('log_only');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(0);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['source_domain', 'source_name'], 'inbound_source_policies_domain_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_source_policies');
    }
};
