<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConsoleBootstrapSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite' && ! $this->sqliteTableExists('setting_webs')) {
            DB::statement('CREATE TABLE setting_webs (id integer primary key autoincrement)');
            DB::table('setting_webs')->insert(['id' => 1]);
        }
    }

    public function test_route_list_command_succeeds(): void
    {
        $this->artisan('route:list')
            ->assertExitCode(0);
    }

    public function test_about_command_succeeds(): void
    {
        $this->artisan('about')
            ->assertExitCode(0);
    }

    private function sqliteTableExists(string $table): bool
    {
        $result = DB::select(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name = ? LIMIT 1",
            [$table],
        );

        return ! empty($result);
    }
}
