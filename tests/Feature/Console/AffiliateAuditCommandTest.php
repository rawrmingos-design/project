<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AffiliateAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_affiliate_audit_reports_expected_summary_in_json_mode(): void
    {
        User::factory()->create([
            'affiliate_status' => 'pending',
            'affiliate_requested_at' => Carbon::now()->subHours(30),
        ]);

        User::factory()->create([
            'affiliate_status' => 'pending',
            'affiliate_requested_at' => Carbon::now()->subHours(2),
        ]);

        User::factory()->create([
            'affiliate_status' => 'active',
            'affiliate_application_meta' => [
                'review_last' => [
                    'reviewed_at' => Carbon::now()->subDays(2)->toIso8601String(),
                    'notification' => [
                        'wa' => ['attempted' => true, 'success' => false],
                        'email' => ['attempted' => false, 'success' => null],
                    ],
                ],
            ],
        ]);

        User::factory()->create([
            'affiliate_status' => 'rejected',
            'affiliate_application_meta' => [
                'review_last' => [
                    'reviewed_at' => Carbon::now()->subDay()->toIso8601String(),
                    'notification' => [
                        'wa' => ['attempted' => true, 'success' => true],
                        'email' => ['attempted' => true, 'success' => true],
                    ],
                ],
            ],
        ]);

        Artisan::call('affiliate:audit', [
            '--json' => true,
            '--warn-hours' => 24,
        ]);

        $rawOutput = trim(Artisan::output());
        $payload = json_decode($rawOutput, true);

        $this->assertIsArray($payload);
        $this->assertSame(24, $payload['warn_hours'] ?? null);
        $this->assertSame(2, $payload['pending_total'] ?? null);
        $this->assertSame(1, $payload['pending_stale'] ?? null);
        $this->assertSame(1, $payload['active_total'] ?? null);
        $this->assertSame(1, $payload['rejected_total'] ?? null);
        $this->assertSame(2, $payload['recent_reviewed_7d'] ?? null);
        $this->assertSame(1, $payload['recent_notification_failed_7d'] ?? null);
        $this->assertSame('warning', $payload['status'] ?? null);
    }
}

