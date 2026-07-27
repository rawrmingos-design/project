<?php

namespace Tests\Feature\Reseller;

use App\Models\ResellerApplication;
use App\Models\User;
use App\Services\ResellerDocumentStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mockery\MockInterface;
use Tests\TestCase;

class EligibilityTest extends TestCase
{
    use RefreshDatabase;

    private array $createdUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->disableCaptchaForTests();

        $this->mock(ResellerDocumentStorageService::class, function (MockInterface $mock) {
            $mock->shouldReceive('store')->andReturn('assets/reseller-documents/fake.jpg');
            $mock->shouldReceive('replace')->andReturn('assets/reseller-documents/fake.jpg');
            $mock->shouldReceive('delete')->andReturn(null);
        });
    }

    protected function tearDown(): void
    {
        foreach ($this->createdUserIds as $userId) {
            $directory = public_path("assets/reseller-documents/{$userId}");

            if (File::isDirectory($directory)) {
                File::deleteDirectory($directory);
            }
        }

        parent::tearDown();
    }

    /**
     * New accounts below 7 days should be blocked on submit.
     */
    public function test_user_with_new_account_sees_eligibility_error_on_submit(): void
    {
        $user = $this->createMemberUser(daysOld: 3);

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validSubmissionPayload());

        $response->assertSessionHasErrors('eligibility');
        $this->assertDatabaseMissing('reseller_applications', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Accounts older than 7 days should be able to submit if otherwise eligible.
     */
    public function test_user_with_old_account_can_submit_application(): void
    {
        $user = $this->createMemberUser(daysOld: 10);

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validSubmissionPayload());

        $response->assertRedirect(route('reseller.registry.form'));
        $response->assertSessionHas('submission_success', true);

        $this->assertDatabaseHas('reseller_applications', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Users with reseller access should be redirected instead of creating another application.
     */
    public function test_user_with_reseller_access_is_redirected_on_submit(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'Gold',
            'created_at' => now()->subDays(10),
        ]);

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validSubmissionPayload());

        $response->assertRedirect(route('reseller.dashboard'));
        $response->assertSessionHas('flash_info');

        $this->assertDatabaseMissing('reseller_applications', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Pending application should block duplicate submission.
     */
    public function test_user_with_pending_application_is_blocked(): void
    {
        $user = $this->createMemberUser(daysOld: 10);

        ResellerApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validSubmissionPayload());

        $response->assertSessionHasErrors('eligibility');

        $this->assertEquals(1, ResellerApplication::where('user_id', $user->id)->count());
    }

    /**
     * Recent rejection should enforce cooldown.
     */
    public function test_user_with_recent_rejection_is_blocked_by_cooldown(): void
    {
        $user = $this->createMemberUser(daysOld: 10);

        ResellerApplication::factory()->rejected()->create([
            'user_id' => $user->id,
            'rejected_at' => now()->subDays(15),
        ]);

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validSubmissionPayload());

        $response->assertSessionHasErrors('eligibility');

        $this->assertDatabaseHas('reseller_applications', [
            'user_id' => $user->id,
            'status' => 'rejected',
        ]);
    }

    /**
     * Old rejection after cooldown should allow resubmission.
     */
    public function test_user_with_old_rejection_can_reapply(): void
    {
        $user = $this->createMemberUser(daysOld: 45);

        ResellerApplication::factory()->rejected()->create([
            'user_id' => $user->id,
            'rejected_at' => now()->subDays(31),
        ]);

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validSubmissionPayload());

        $response->assertRedirect(route('reseller.registry.form'));
        $response->assertSessionHas('submission_success', true);

        $this->assertDatabaseHas('reseller_applications', [
            'user_id' => $user->id,
            'status' => 'pending',
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        $this->assertDatabaseHas('reseller_application_reviews', [
            'user_id' => $user->id,
            'action' => 'resubmitted',
        ]);
    }

    private function createMemberUser(int $daysOld): User
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'created_at' => now()->subDays($daysOld),
        ]);

        $this->createdUserIds[] = $user->id;

        return $user;
    }

    private function validSubmissionPayload(): array
    {
        return [
            'business_name' => 'Test Business',
            'business_url' => 'https://test-business.example.com',
            'estimated_monthly_transactions' => 100,
            'application_reason' => 'Testing reseller eligibility flow.',
            'identity' => UploadedFile::fake()->image('identity.jpg')->size(500),
            'selfie' => UploadedFile::fake()->image('selfie.jpg')->size(500),
            'business_proof' => UploadedFile::fake()->image('business-proof.jpg')->size(500),
        ];
    }

    private function disableCaptchaForTests(): void
    {
        DB::table('setting_webs')->updateOrInsert(
            ['id' => 1],
            [
                'judul_web' => 'Test App',
                'deskripsi_web' => 'Test description',
                'keywords' => 'test',
                'url_wa' => 'https://wa.me/6281234567890',
                'url_ig' => 'https://instagram.com/test',
                'url_tiktok' => 'https://tiktok.com/@test',
                'url_youtube' => 'https://youtube.com/@test',
                'url_fb' => 'https://facebook.com/test',
                'topupindo_api' => 'test',
                'warna1' => '#000000',
                'warna2' => '#111111',
                'warna3' => '#222222',
                'warna4' => '#333333',
                'paydisini_apikey' => 'test',
                'order_prefik' => 'TST',
                'captcha_bypass' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
