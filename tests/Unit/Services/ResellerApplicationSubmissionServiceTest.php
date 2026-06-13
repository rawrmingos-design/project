<?php

namespace Tests\Unit\Services;

use App\Models\ResellerApplication;
use App\Models\ResellerApplicationReview;
use App\Models\ResellerDocument;
use App\Models\User;
use App\Services\ResellerApplicationEligibilityService;
use App\Services\ResellerApplicationSubmissionService;
use App\Services\ResellerDocumentStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ResellerApplicationSubmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResellerApplicationSubmissionService $service;
    private ResellerApplicationEligibilityService $eligibilityService;
    private ResellerDocumentStorageService $documentStorageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eligibilityService = $this->createMock(ResellerApplicationEligibilityService::class);
        $this->documentStorageService = $this->createMock(ResellerDocumentStorageService::class);

        $this->service = new ResellerApplicationSubmissionService(
            $this->eligibilityService,
            $this->documentStorageService
        );
    }

    /**
     * Test that submit creates application with correct data.
     */
    public function test_creates_application_with_correct_data(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        
        $this->mockEligibilityServiceAsEligible();
        $this->mockDocumentStorageService();

        $applicationData = [
            'business_name' => 'Test Business',
            'business_url' => 'https://testbusiness.com',
            'estimated_monthly_transactions' => 100,
            'application_reason' => 'Want to sell vouchers',
        ];

        $documents = $this->createFakeDocuments();

        $application = $this->service->submit($user, $applicationData, $documents, '127.0.0.1');

        $this->assertInstanceOf(ResellerApplication::class, $application);
        $this->assertEquals($user->id, $application->user_id);
        $this->assertEquals('Test Business', $application->business_name);
        $this->assertEquals('https://testbusiness.com', $application->business_url);
        $this->assertEquals(100, $application->estimated_transactions);
    }

    /**
     * Test that applied_at timestamp is set.
     */
    public function test_sets_applied_at_timestamp(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        
        $this->mockEligibilityServiceAsEligible();
        $this->mockDocumentStorageService();

        $application = $this->service->submit(
            $user,
            ['business_name' => 'Test'],
            $this->createFakeDocuments(),
            '127.0.0.1'
        );

        $this->assertNotNull($application->applied_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $application->applied_at);
    }

    /**
     * Test that default status is set to pending.
     */
    public function test_sets_default_status_to_pending(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        
        $this->mockEligibilityServiceAsEligible();
        $this->mockDocumentStorageService();

        $application = $this->service->submit(
            $user,
            ['business_name' => 'Test'],
            $this->createFakeDocuments(),
            '127.0.0.1'
        );

        $this->assertEquals('pending', $application->status);
        $this->assertTrue($application->isPending());
    }

    /**
     * Test that exception is thrown if user is ineligible.
     */
    public function test_throws_exception_if_user_ineligible(): void
    {
        $user = User::factory()->create();

        $this->eligibilityService
            ->expects($this->once())
            ->method('evaluate')
            ->with($user)
            ->willReturn([
                'can_apply' => false,
                'reasons' => ['User is not eligible'],
            ]);

        $this->expectException(ValidationException::class);

        $this->service->submit(
            $user,
            ['business_name' => 'Test'],
            $this->createFakeDocuments(),
            '127.0.0.1'
        );
    }

    /**
     * Test that submit returns ResellerApplication instance.
     */
    public function test_returns_application_instance(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        
        $this->mockEligibilityServiceAsEligible();
        $this->mockDocumentStorageService();

        $result = $this->service->submit(
            $user,
            ['business_name' => 'Test'],
            $this->createFakeDocuments(),
            '127.0.0.1'
        );

        $this->assertInstanceOf(ResellerApplication::class, $result);
    }

    /**
     * Test that business metadata is stored correctly.
     */
    public function test_stores_business_metadata_correctly(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        
        $this->mockEligibilityServiceAsEligible();
        $this->mockDocumentStorageService();

        $applicationData = [
            'business_name' => 'My Shop',
            'business_url' => 'https://myshop.com',
            'estimated_monthly_transactions' => 500,
            'application_reason' => 'Growing business',
        ];

        $application = $this->service->submit($user, $applicationData, $this->createFakeDocuments(), '127.0.0.1');

        $this->assertIsArray($application->business_meta);
        $this->assertEquals('My Shop', $application->business_meta['business_name']);
        $this->assertEquals('https://myshop.com', $application->business_meta['business_url']);
        $this->assertEquals(500, $application->business_meta['estimated_monthly_transactions']);
        $this->assertEquals('Growing business', $application->business_meta['application_reason']);
    }

    /**
     * Test that submission creates review record.
     */
    public function test_creates_submission_review_record(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        
        $this->mockEligibilityServiceAsEligible();
        $this->mockDocumentStorageService();

        $this->service->submit(
            $user,
            ['business_name' => 'Test'],
            $this->createFakeDocuments(),
            '127.0.0.1'
        );

        $this->assertDatabaseHas('reseller_application_reviews', [
            'user_id' => $user->id,
            'action' => 'submitted',
        ]);
    }

    /**
     * Test that resubmission is handled correctly.
     */
    public function test_handles_resubmission_correctly(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        
        // Create existing rejected application
        $existingApplication = ResellerApplication::factory()->rejected()->create([
            'user_id' => $user->id,
        ]);

        $this->mockEligibilityServiceAsEligible();
        $this->mockDocumentStorageService();

        $this->service->submit(
            $user,
            ['business_name' => 'Test'],
            $this->createFakeDocuments(),
            '127.0.0.1'
        );

        // Should create review with 'resubmitted' action
        $review = ResellerApplicationReview::where('user_id', $user->id)
            ->where('action', 'resubmitted')
            ->first();

        $this->assertNotNull($review);
        $this->assertEquals('resubmitted', $review->action);
    }

    /**
     * Test that IP address is stored.
     */
    public function test_stores_ip_address(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        
        $this->mockEligibilityServiceAsEligible();
        $this->mockDocumentStorageService();

        $application = $this->service->submit(
            $user,
            ['business_name' => 'Test'],
            $this->createFakeDocuments(),
            '192.168.1.100'
        );

        $this->assertEquals('192.168.1.100', $application->submitted_from_ip);
    }

    /**
     * Helper: Mock eligibility service to return eligible.
     */
    private function mockEligibilityServiceAsEligible(): void
    {
        $this->eligibilityService
            ->expects($this->once())
            ->method('evaluate')
            ->willReturn([
                'can_apply' => true,
                'reasons' => [],
            ]);
    }

    /**
     * Helper: Mock document storage service.
     */
    private function mockDocumentStorageService(): void
    {
        $this->documentStorageService
            ->expects($this->any())
            ->method('store')
            ->willReturn('reseller-documents/fake-path.jpg');

        $this->documentStorageService
            ->expects($this->any())
            ->method('replace')
            ->willReturn('reseller-documents/fake-path.jpg');
    }

    /**
     * Helper: Create fake uploaded files for documents.
     */
    private function createFakeDocuments(): array
    {
        return [
            'identity' => UploadedFile::fake()->image('ktp.jpg', 800, 600)->size(1000),
            'selfie' => UploadedFile::fake()->image('selfie.jpg', 800, 600)->size(1000),
            'business_proof' => UploadedFile::fake()->image('business.jpg', 800, 600)->size(1000),
        ];
    }
}
