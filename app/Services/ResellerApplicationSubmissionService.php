<?php

namespace App\Services;

use App\Models\ResellerApplication;
use App\Models\ResellerApplicationReview;
use App\Models\ResellerDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResellerApplicationSubmissionService
{
    public function __construct(
        private readonly ResellerApplicationEligibilityService $eligibilityService,
        private readonly ResellerDocumentStorageService $documentStorageService,
    ) {
    }

    /**
     * @param array<string, mixed> $applicationData
     * @param array<string, UploadedFile> $documents
     */
    public function submit(User $user, array $applicationData, array $documents, string $ipAddress): ResellerApplication
    {
        $eligibility = $this->eligibilityService->evaluate($user);

        if (! $eligibility['can_apply']) {
            throw ValidationException::withMessages([
                'reseller_application' => $eligibility['reasons'],
            ]);
        }

        return DB::transaction(function () use ($user, $applicationData, $documents, $ipAddress): ResellerApplication {
            $application = ResellerApplication::query()->firstOrNew([
                'user_id' => $user->id,
            ]);

            $isResubmission = $application->exists && $application->status === 'rejected';

            $application->fill([
                'status' => 'pending',
                'applied_at' => now(),
                'approved_at' => null,
                'rejected_at' => null,
                'reviewed_by' => null,
                'rejection_reason' => null,
                'business_meta' => [
                    'business_name' => $applicationData['business_name'] ?? null,
                    'business_url' => $applicationData['business_url'] ?? null,
                    'estimated_monthly_transactions' => isset($applicationData['estimated_monthly_transactions'])
                        ? (int) $applicationData['estimated_monthly_transactions']
                        : null,
                    'application_reason' => $applicationData['application_reason'] ?? null,
                ],
                'submitted_from_ip' => $ipAddress,
            ]);
            $application->save();

            $documentMap = [
                'identity' => $documents['identity'] ?? null,
                'selfie' => $documents['selfie'] ?? null,
                'business_proof' => $documents['business_proof'] ?? null,
            ];

            foreach ($documentMap as $documentType => $uploadedFile) {
                if (! $uploadedFile instanceof UploadedFile) {
                    continue;
                }

                $existingDocument = ResellerDocument::query()
                    ->where('user_id', $user->id)
                    ->where('document_type', $documentType)
                    ->first();

                // Get file metadata BEFORE storing (temp file will be deleted after store)
                $fileName = $uploadedFile->getClientOriginalName();
                $fileSize = (int) $uploadedFile->getSize();
                $mimeType = (string) $uploadedFile->getMimeType();

                $relativePath = $existingDocument
                    ? $this->documentStorageService->replace($existingDocument->file_path, $user->id, $uploadedFile, $documentType)
                    : $this->documentStorageService->store($user->id, $uploadedFile, $documentType);

                ResellerDocument::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'document_type' => $documentType,
                    ],
                    [
                        'file_path' => $relativePath,
                        'file_name' => $fileName,
                        'file_size' => $fileSize,
                        'mime_type' => $mimeType,
                        'status' => 'pending',
                        'notes' => null,
                    ]
                );
            }

            ResellerApplicationReview::query()->create([
                'user_id' => $user->id,
                'action' => $isResubmission ? 'resubmitted' : 'submitted',
                'reviewed_by' => null,
                'notes' => null,
            ]);

            return $application->fresh(['user']);
        });
    }
}
