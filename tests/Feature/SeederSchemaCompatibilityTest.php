<?php

namespace Tests\Feature;

use Database\Seeders\LegacyImportBootstrapSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\UsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SeederSchemaCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_and_legacy_seeders_match_removed_api_key_columns(): void
    {
        $this->assertRemovedApiKeyColumnsAreAbsent();

        $this->artisan('db:seed', ['--class' => UsersSeeder::class])
            ->assertSuccessful();

        $this->artisan('db:seed', ['--class' => UserSeeder::class])
            ->assertSuccessful();

        $this->artisan('db:seed', ['--class' => LegacyImportBootstrapSeeder::class])
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'username' => 'superadmin',
        ]);
        $this->assertDatabaseHas('providers', [
            'code' => 'digiflazz',
        ]);
    }

    public function test_default_database_seeder_matches_latest_schema(): void
    {
        $this->assertRemovedApiKeyColumnsAreAbsent();

        $this->artisan('db:seed')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'username' => 'superadmin',
        ]);
        $this->assertDatabaseHas('kategoris', [
            'id' => 2,
            'server_id' => 0,
        ]);
        $this->assertDatabaseHas('layanans', [
            'id' => 368,
            'kategori_id' => '2',
        ]);
    }

    private function assertRemovedApiKeyColumnsAreAbsent(): void
    {
        $this->assertFalse(Schema::hasColumn('users', 'api_key'));
        $this->assertFalse(Schema::hasColumn('users', 'api_key_hint'));
        $this->assertFalse(Schema::hasColumn('users', 'api_key_prefix'));
        $this->assertFalse(Schema::hasColumn('users', 'api_key_rotated_at'));
        $this->assertFalse(Schema::hasColumn('users', 'sandbox_api_key_hash'));
        $this->assertFalse(Schema::hasColumn('users', 'sandbox_api_key_hint'));
        $this->assertFalse(Schema::hasColumn('users', 'sandbox_api_key_rotated_at'));
        $this->assertFalse(Schema::hasColumn('users', 'sandbox_api_key_last_used_at'));

        $this->assertFalse(Schema::hasColumn('providers', 'api_username'));
        $this->assertFalse(Schema::hasColumn('providers', 'api_key'));
        $this->assertFalse(Schema::hasColumn('providers', 'api_sign'));
    }
}
