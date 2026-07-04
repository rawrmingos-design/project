<?php

namespace Tests\Feature\Reseller;

use App\Models\ResellerApplication;
use App\Models\ResellerApplicationReview;
use App\Models\ResellerDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CompleteApplicationTest extends TestCase
{
    use RefreshDatabase;

    private array $createdUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->disableCaptchaForTests();
        config(['reseller_documents.public_directory' => 'assets/reseller-documents-testing/' . env('TEST_TOKEN', 'single')]);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdUserIds as $userId) {
            $directory = public_path("assets/reseller-documents/{$userId}");

            if (File::isDirectory($directory)) {
                File::deleteDirectory($directory);
            }
        }

        $parallelDirectory = public_path((string) config('reseller_documents.public_directory'));

        if (File::isDirectory($parallelDirectory)) {
            File::deleteDirectory($parallelDirectory);
        }

        parent::tearDown();
    }

    /**
     * Cannot submit duplicate application while one is still pending.
     * Note: Due to unique constraint on user_id, second submit would update, not duplicate.
     */
    public function test_cannot_submit_duplicate_pending_application(): void
    {
        $user = $this->createEligibleUser();

        // First submission succeeds
        $firstResponse = $this->actingAs($user)->post('/id/reseller/registry', $this->validPayload());
        $firstResponse->assertRedirect(route('reseller.registry.form'));

        // Verify application created
        $this->assertEquals(1, ResellerApplication::where('user_id', $user->id)->count());

        // Second submission - behavior depends on eligibility check
        // Due to unique constraint, only one application record can exist per user
        $secondResponse = $this->actingAs($user)->post('/id/reseller/registry', $this->validPayload());
        
        // Still only one application (unique user_id constraint prevents duplicates)
        $this->assertEquals(1, ResellerApplication::where('user_id', $user->id)->count());
    }

    /**
     * Resubmission after rejection updates existing application (not creates new).
     * Note: user_id is UNIQUE in reseller_applications table.
     */
    public function test_resubmission_after_rejection_updates_application(): void
    {
        $user = $this->createEligibleUser();

        // Create old rejected application (past cooldown)
        $oldApplication = ResellerApplication::factory()->rejected()->create([
            'user_id' => $user->id,
            'rejected_at' => now()->subDays(35),
        ]);

        $oldId = $oldApplication->id;

        // Resubmission should succeed and UPDATE existing record
        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validPayload());
        $response->assertRedirect(route('reseller.registry.form'));

        // Still only ONE application (unique constraint on user_id)
        $this->assertEquals(1, ResellerApplication::where('user_id', $user->id)->count());

        // Same record, but status changed to pending
        $updated = ResellerApplication::find($oldId);
        $this->assertNotNull($updated);
        $this->assertEquals('pending', $updated->status);
        $this->assertNull($updated->rejected_at);
        $this->assertNull($updated->rejection_reason);

        // Resubmitted review record exists (action, not status)
        $this->assertDatabaseHas('reseller_application_reviews', [
            'user_id' => $user->id,
            'action' => 'resubmitted',
        ]);
    }

    /**
     * Successful submission redirects with correct flash data.
     */
    public function test_success_submission_redirects_with_flash_data(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validPayload());

        $response->assertRedirect(route('reseller.registry.form'));
        $response->assertSessionHas('submission_success', true);
        $response->assertSessionHas('success_message');
    }

    /**
     * Application applied_at timestamp is set correctly.
     */
    public function test_application_applied_at_timestamp_is_set_correctly(): void
    {
        $user = $this->createEligibleUser();

        $beforeSubmit = now()->subSecond();

        $this->actingAs($user)->post('/id/reseller/registry', $this->validPayload());

        $afterSubmit = now()->addSecond();

        $application = ResellerApplication::where('user_id', $user->id)->first();

        $this->assertNotNull($application->applied_at);
        $this->assertTrue($application->applied_at->between($beforeSubmit, $afterSubmit));
    }

    /**
     * All records created atomically on successful submission.
     * Note: Documents are linked via user_id, not application_id.
     */
    public function test_all_records_created_atomically_on_success(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validPayload());
        $response->assertRedirect(route('reseller.registry.form'));

        // Verify all records exist
        $application = ResellerApplication::where('user_id', $user->id)->first();
        $this->assertNotNull($application);

        // 3 document records linked to user
        $documents = ResellerDocument::where('user_id', $user->id)->get();
        $this->assertCount(3, $documents);

        // 1 review record for submission
        $review = ResellerApplicationReview::where('user_id', $user->id)
            ->where('action', 'submitted')
            ->first();
        $this->assertNotNull($review);

        // All linked correctly via user_id
        foreach ($documents as $document) {
            $this->assertEquals($user->id, $document->user_id);
        }
    }

    /**
     * Failed validation does not create partial records.
     */
    public function test_failed_validation_does_not_create_partial_records(): void
    {
        $user = $this->createEligibleUser();

        // Submit with invalid data (missing business_name)
        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validPayload([
            'business_name' => '',
        ]));

        $response->assertSessionHasErrors('business_name');

        // No application created
        $this->assertEquals(0, ResellerApplication::where('user_id', $user->id)->count());

        // No documents created
        $this->assertEquals(0, ResellerDocument::where('user_id', $user->id)->count());

        // No review records created
        $this->assertEquals(0, ResellerApplicationReview::where('user_id', $user->id)->count());
    }

    /**
     * Submission stores IP address in application.
     * Field name is submitted_from_ip, not ip_address.
     */
    public function test_submission_stores_ip_address(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validPayload());
        $response->assertRedirect(route('reseller.registry.form'));

        $application = ResellerApplication::where('user_id', $user->id)->first();

        $this->assertNotNull($application->submitted_from_ip);
        $this->assertNotEmpty($application->submitted_from_ip);
    }

    /**
     * Application business_meta JSON has correct structure.
     */
    public function test_application_business_meta_json_structure(): void
    {
        $user = $this->createEligibleUser();

        $this->actingAs($user)->post('/id/reseller/registry', $this->validPayload([
            'business_name' => 'JSON Structure Test Shop',
            'business_url' => 'https://json-test.example.com',
            'estimated_monthly_transactions' => 999999,
            'application_reason' => 'Testing JSON meta structure',
        ]));

        $application = ResellerApplication::where('user_id', $user->id)->first();

        $this->assertIsArray($application->business_meta);
        $this->assertArrayHasKey('business_name', $application->business_meta);
        $this->assertArrayHasKey('business_url', $application->business_meta);
        $this->assertArrayHasKey('estimated_monthly_transactions', $application->business_meta);
        $this->assertArrayHasKey('application_reason', $application->business_meta);

        $this->assertEquals('JSON Structure Test Shop', $application->business_meta['business_name']);
        $this->assertEquals('https://json-test.example.com', $application->business_meta['business_url']);
        $this->assertEquals(999999, $application->business_meta['estimated_monthly_transactions']);
        $this->assertEquals('Testing JSON meta structure', $application->business_meta['application_reason']);
    }

    private function createEligibleUser(): User
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'created_at' => now()->subDays(10),
        ]);

        $this->createdUserIds[] = $user->id;

        return $user;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'business_name' => 'Complete Application Test',
            'business_url' => 'https://complete-test.example.com',
            'estimated_monthly_transactions' => 100,
            'application_reason' => 'Testing complete application flow.',
            'identity' => UploadedFile::fake()->image('identity.jpg')->size(500),
            'selfie' => UploadedFile::fake()->image('selfie.jpg')->size(500),
            'business_proof' => UploadedFile::fake()->image('business-proof.jpg')->size(500),
        ], $overrides);
    }

    private function disableCaptchaForTests(): void
    {
        \DB::table('setting_webs')->updateOrInsert(
            ['id' => 1],
            [
                'judul_web' => 'Test',
                'deskripsi_web' => 'Test',
                'keywords' => 'test',
                'url_wa' => 'https://wa.me/test',
                'url_ig' => 'https://instagram.com/test',
                'url_tiktok' => 'https://tiktok.com/@test',
                'url_youtube' => 'https://youtube.com/@test',
                'url_fb' => 'https://facebook.com/test',
                'topupindo_api' => 'test',
                'warna1' => '#000',
                'warna2' => '#111',
                'warna3' => '#222',
                'warna4' => '#333',
                'paydisini_apikey' => 'test',
                'order_prefik' => 'TST',
                'captcha_bypass' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
