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

class ApplicationSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private array $createdUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->disableCaptchaForTests();
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
     * Eligible user can submit complete application successfully.
     */
    public function test_eligible_user_can_submit_complete_application(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validSubmissionPayload());

        $response->assertRedirect(route('reseller.registry.form'));
        $response->assertSessionHas('submission_success', true);
        $response->assertSessionHas('success_message');

        $this->assertDatabaseHas('reseller_applications', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Submission creates application record with correct business data.
     */
    public function test_submission_creates_application_with_business_data(): void
    {
        $user = $this->createEligibleUser();

        $this->actingAs($user)->post('/id/reseller/registry', $this->validSubmissionPayload([
            'business_name' => 'My Test Shop',
            'business_url' => 'https://mytestshop.example.com',
            'estimated_monthly_transactions' => 5000000,
            'application_reason' => 'I want to expand my business',
        ]));

        $application = ResellerApplication::where('user_id', $user->id)->first();

        $this->assertNotNull($application);
        $this->assertEquals('My Test Shop', $application->business_meta['business_name']);
        $this->assertEquals('https://mytestshop.example.com', $application->business_meta['business_url']);
        $this->assertEquals(5000000, $application->business_meta['estimated_monthly_transactions']);
        $this->assertEquals('I want to expand my business', $application->business_meta['application_reason']);
    }

    /**
     * Submission creates document records for all three documents.
     */
    public function test_submission_creates_document_records(): void
    {
        $user = $this->createEligibleUser();

        $this->actingAs($user)->post('/id/reseller/registry', $this->validSubmissionPayload());

        $documents = ResellerDocument::where('user_id', $user->id)->get();

        $this->assertCount(3, $documents);

        $types = $documents->pluck('document_type')->toArray();
        $this->assertContains('identity', $types);
        $this->assertContains('selfie', $types);
        $this->assertContains('business_proof', $types);
    }

    /**
     * Submission creates review record with submitted action.
     */
    public function test_submission_creates_review_record(): void
    {
        $user = $this->createEligibleUser();

        $this->actingAs($user)->post('/id/reseller/registry', $this->validSubmissionPayload());

        $this->assertDatabaseHas('reseller_application_reviews', [
            'user_id' => $user->id,
            'action' => 'submitted',
        ]);
    }

    /**
     * Cannot submit without business name.
     */
    public function test_cannot_submit_without_business_name(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validSubmissionPayload([
            'business_name' => '',
        ]));

        $response->assertSessionHasErrors('business_name');

        $this->assertDatabaseMissing('reseller_applications', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Cannot submit with invalid business URL format.
     */
    public function test_cannot_submit_with_invalid_business_url(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validSubmissionPayload([
            'business_url' => 'not-a-valid-url',
        ]));

        $response->assertSessionHasErrors('business_url');
    }

    /**
     * Can submit with optional business URL empty.
     */
    public function test_can_submit_with_empty_optional_fields(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validSubmissionPayload([
            'business_url' => null,
            'estimated_monthly_transactions' => null,
            'application_reason' => null,
        ]));

        $response->assertRedirect(route('reseller.registry.form'));
        $response->assertSessionHas('submission_success', true);
    }

    /**
     * Cannot submit without identity document.
     */
    public function test_cannot_submit_without_identity_document(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validSubmissionPayload([
            'identity' => null,
        ]));

        $response->assertSessionHasErrors('identity');
    }

    /**
     * Cannot submit without selfie document.
     */
    public function test_cannot_submit_without_selfie_document(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validSubmissionPayload([
            'selfie' => null,
        ]));

        $response->assertSessionHasErrors('selfie');
    }

    /**
     * Cannot submit without business proof document.
     */
    public function test_cannot_submit_without_business_proof_document(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validSubmissionPayload([
            'business_proof' => null,
        ]));

        $response->assertSessionHasErrors('business_proof');
    }

    /**
     * Document files are stored in correct location.
     */
    public function test_documents_are_stored_in_public_directory(): void
    {
        $user = $this->createEligibleUser();

        $this->actingAs($user)->post('/id/reseller/registry', $this->validSubmissionPayload());

        $documents = ResellerDocument::where('user_id', $user->id)->get();

        foreach ($documents as $document) {
            $this->assertFileExists(public_path($document->file_path));
        }
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

    private function validSubmissionPayload(array $overrides = []): array
    {
        $defaults = [
            'business_name' => 'Test Business Shop',
            'business_url' => 'https://test-business-shop.example.com',
            'estimated_monthly_transactions' => 100,
            'application_reason' => 'Testing complete application submission flow.',
            'identity' => UploadedFile::fake()->image('identity.jpg')->size(500),
            'selfie' => UploadedFile::fake()->image('selfie.jpg')->size(500),
            'business_proof' => UploadedFile::fake()->image('business-proof.jpg')->size(500),
        ];

        return array_merge($defaults, $overrides);
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
