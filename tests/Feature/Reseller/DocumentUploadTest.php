<?php

namespace Tests\Feature\Reseller;

use App\Models\ResellerDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
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
     * Identity document must be uploaded.
     */
    public function test_identity_document_is_required(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validPayload([
            'identity' => null,
        ]));

        $response->assertSessionHasErrors('identity');
        $this->assertDatabaseMissing('reseller_documents', [
            'user_id' => $user->id,
            'document_type' => 'identity',
        ]);
    }

    /**
     * Selfie document must be uploaded.
     */
    public function test_selfie_document_is_required(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validPayload([
            'selfie' => null,
        ]));

        $response->assertSessionHasErrors('selfie');
        $this->assertDatabaseMissing('reseller_documents', [
            'user_id' => $user->id,
            'document_type' => 'selfie',
        ]);
    }

    /**
     * Business proof document must be uploaded.
     */
    public function test_business_proof_document_is_required(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validPayload([
            'business_proof' => null,
        ]));

        $response->assertSessionHasErrors('business_proof');
        $this->assertDatabaseMissing('reseller_documents', [
            'user_id' => $user->id,
            'document_type' => 'business_proof',
        ]);
    }

    /**
     * Invalid identity file type should be rejected.
     */
    public function test_cannot_upload_invalid_identity_file_type(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validPayload([
            'identity' => UploadedFile::fake()->create('identity.exe', 100, 'application/x-msdownload'),
        ]));

        $response->assertSessionHasErrors('identity');
        $this->assertEquals(0, ResellerDocument::where('user_id', $user->id)->count());
    }

    /**
     * Invalid selfie file type should be rejected.
     */
    public function test_cannot_upload_invalid_selfie_file_type(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validPayload([
            'selfie' => UploadedFile::fake()->create('selfie.pdf', 100, 'application/pdf'),
        ]));

        $response->assertSessionHasErrors('selfie');
        $this->assertEquals(0, ResellerDocument::where('user_id', $user->id)->count());
    }

    /**
     * Oversized identity file should be rejected.
     */
    public function test_cannot_upload_oversized_identity_file(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validPayload([
            'identity' => UploadedFile::fake()->image('identity.jpg')->size(6000),
        ]));

        $response->assertSessionHasErrors('identity');
        $this->assertEquals(0, ResellerDocument::where('user_id', $user->id)->count());
    }

    /**
     * Successful submission stores document metadata for all required files.
     */
    public function test_successful_upload_stores_document_metadata(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validPayload([
            'identity' => UploadedFile::fake()->image('identity-custom.jpg')->size(700),
            'selfie' => UploadedFile::fake()->image('selfie-custom.png')->size(800),
            'business_proof' => UploadedFile::fake()->create('proof-custom.pdf', 900, 'application/pdf'),
        ]));

        $response->assertRedirect(route('reseller.registry.form'));

        $this->assertDatabaseHas('reseller_documents', [
            'user_id' => $user->id,
            'document_type' => 'identity',
            'file_name' => 'identity-custom.jpg',
            'mime_type' => 'image/jpeg',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('reseller_documents', [
            'user_id' => $user->id,
            'document_type' => 'selfie',
            'file_name' => 'selfie-custom.png',
            'mime_type' => 'image/png',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('reseller_documents', [
            'user_id' => $user->id,
            'document_type' => 'business_proof',
            'file_name' => 'proof-custom.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'pending',
        ]);
    }

    /**
     * All three document records should be created together on successful submit.
     */
    public function test_successful_upload_creates_exactly_three_document_records(): void
    {
        $user = $this->createEligibleUser();

        $response = $this->actingAs($user)->post('/id/reseller/registry', $this->validPayload());

        $response->assertRedirect(route('reseller.registry.form'));

        $documents = ResellerDocument::where('user_id', $user->id)->get();

        $this->assertCount(3, $documents);
        $this->assertEqualsCanonicalizing(
            ['identity', 'selfie', 'business_proof'],
            $documents->pluck('document_type')->all()
        );
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
            'business_name' => 'Document Upload Test Business',
            'business_url' => 'https://document-upload.example.com',
            'estimated_monthly_transactions' => 100,
            'application_reason' => 'Testing document upload validation.',
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
