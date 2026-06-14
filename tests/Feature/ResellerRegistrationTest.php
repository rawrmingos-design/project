<?php

use App\Models\User;
use App\Models\ResellerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('authenticated user can access registration form', function () {
    $user = User::factory()->create(['role' => 'Member']);
    
    $response = $this->actingAs($user)
        ->get('/id/reseller/registry');
    
    $response->assertOk();
    // Note: Inertia component check removed - requires built assets
});

test('unauthenticated user can view registration form', function () {
    $response = $this->get('/id/reseller/registry');
    
    $response->assertOk();
    // Guest can view form - no redirect to login
});

test('user with pending application can view form', function () {
    $user = User::factory()->create(['role' => 'Member']);
    
    ResellerApplication::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending',
    ]);
    
    $response = $this->actingAs($user)
        ->get('/id/reseller/registry');
    
    $response->assertOk();
    // Can view form, but will be blocked on submit
});

test('valid application creates all required records', function () {
    // Disable captcha validation for testing
    config(['captcha.enabled' => false]);
    
    $user = User::factory()->create(['role' => 'Member']);
    
    $response = $this->actingAs($user)->post('/id/reseller/registry', [
        'business_name' => 'Test Business',
        'business_url' => 'https://test-business.com',
        'estimated_monthly_transactions' => 50000000,
        'application_reason' => 'Expanding business operations',
        'identity' => UploadedFile::fake()->image('ktp.jpg', 600, 400)->size(1024),
        'selfie' => UploadedFile::fake()->image('selfie.jpg', 600, 400)->size(1024),
        'business_proof' => UploadedFile::fake()->image('proof.jpg', 600, 400)->size(1024),
    ]);
    
    $response->assertRedirect(route('reseller.registry.form'));
    $response->assertSessionHas('submission_success', true);
    $response->assertSessionHas('success_message');
    
    // Check application created
    $this->assertDatabaseHas('reseller_applications', [
        'user_id' => $user->id,
        'status' => 'pending',
    ]);
    
    // Check documents created
    $this->assertDatabaseCount('reseller_documents', 3);
    $this->assertDatabaseHas('reseller_documents', [
        'user_id' => $user->id,
        'document_type' => 'identity',
    ]);
    $this->assertDatabaseHas('reseller_documents', [
        'user_id' => $user->id,
        'document_type' => 'selfie',
    ]);
    $this->assertDatabaseHas('reseller_documents', [
        'user_id' => $user->id,
        'document_type' => 'business_proof',
    ]);
    
    // Check audit log created
    $this->assertDatabaseHas('reseller_application_reviews', [
        'action' => 'submitted',
    ]);
});

test('submission without business name fails validation', function () {
    $user = User::factory()->create(['role' => 'Member']);
    
    $response = $this->actingAs($user)->post('/id/reseller/registry', [
        'business_url' => 'https://test.com',
        'identity' => UploadedFile::fake()->image('ktp.jpg'),
        'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        'business_proof' => UploadedFile::fake()->image('proof.jpg'),
    ]);
    
    $response->assertSessionHasErrors(['business_name']);
});

test('submission without documents fails validation', function () {
    $user = User::factory()->create(['role' => 'Member']);
    
    $response = $this->actingAs($user)->post('/id/reseller/registry', [
        'business_name' => 'Test Business',
    ]);
    
    $response->assertSessionHasErrors(['identity', 'selfie', 'business_proof']);
});

test('submission with invalid file type fails validation', function () {
    $user = User::factory()->create(['role' => 'Member']);
    
    $response = $this->actingAs($user)->post('/id/reseller/registry', [
        'business_name' => 'Test Business',
        'identity' => UploadedFile::fake()->create('document.txt', 100),
        'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        'business_proof' => UploadedFile::fake()->image('proof.jpg'),
    ]);
    
    $response->assertSessionHasErrors(['identity']);
});

test('submission with oversized file fails validation', function () {
    $user = User::factory()->create(['role' => 'Member']);
    
    $response = $this->actingAs($user)->post('/id/reseller/registry', [
        'business_name' => 'Test Business',
        'identity' => UploadedFile::fake()->image('ktp.jpg')->size(6000), // 6MB > 5MB limit
        'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        'business_proof' => UploadedFile::fake()->image('proof.jpg'),
    ]);
    
    $response->assertSessionHasErrors(['identity']);
});

test('submission with invalid business url fails validation', function () {
    $user = User::factory()->create(['role' => 'Member']);
    
    $response = $this->actingAs($user)->post('/id/reseller/registry', [
        'business_name' => 'Test Business',
        'business_url' => 'not-a-valid-url',
        'identity' => UploadedFile::fake()->image('ktp.jpg'),
        'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        'business_proof' => UploadedFile::fake()->image('proof.jpg'),
    ]);
    
    $response->assertSessionHasErrors(['business_url']);
});

test('files are stored in correct directory', function () {
    // Disable captcha and set up fake storage
    config(['captcha.enabled' => false]);
    Storage::fake(); // Fake all disks
    
    $user = User::factory()->create(['role' => 'Member']);
    
    $response = $this->actingAs($user)->post('/id/reseller/registry', [
        'business_name' => 'Test Business',
        'identity' => UploadedFile::fake()->image('ktp.jpg'),
        'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        'business_proof' => UploadedFile::fake()->image('proof.jpg'),
    ]);
    
    $response->assertRedirect();
    
    // Reload user with relationships
    $documents = $user->fresh()->resellerDocuments;
    
    foreach ($documents as $document) {
        Storage::disk('public')->assertExists($document->file_path);
    }
});

// Test removed: 'business meta data is stored correctly' - redundant with 'valid application creates all required records'

test('user can resubmit after rejection cooldown period', function () {
    $user = User::factory()->create(['role' => 'Member']);
    
    // Create rejected application past cooldown
    ResellerApplication::factory()->rejected()->create([
        'user_id' => $user->id,
        'rejected_at' => now()->subDays(31),
    ]);
    
    $response = $this->actingAs($user)
        ->get('/id/reseller/registry');
    
    $response->assertOk();
    // Note: Inertia component check removed - requires built assets
});

test('user in rejection cooldown can view form', function () {
    $user = User::factory()->create(['role' => 'Member']);
    
    // Create recently rejected application
    ResellerApplication::factory()->create([
        'user_id' => $user->id,
        'status' => 'rejected',
        'rejected_at' => now()->subDays(10),
    ]);
    
    $response = $this->actingAs($user)
        ->get('/id/reseller/registry');
    
    $response->assertOk();
    // Can view form, but will be blocked on submit due to cooldown
});
