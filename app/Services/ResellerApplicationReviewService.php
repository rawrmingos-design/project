<?php

namespace App\Services;

use App\Models\ResellerApplication;
use App\Models\ResellerApplicationReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResellerApplicationReviewService
{
    public function __construct(
        private readonly ResellerProvisioningService $provisioningService,
    ) {
    }

    public function approve(ResellerApplication $application, User $admin, array $context = []): ResellerApplication
    {
        return DB::transaction(function () use ($application, $admin, $context): ResellerApplication {
            $target = ResellerApplication::query()
                ->whereKey($application->id)
                ->lockForUpdate()
                ->first();

            if (! $target) {
                throw ValidationException::withMessages([
                    'reseller_application' => 'Pengajuan reseller tidak ditemukan.',
                ]);
            }

            if (! $target->isPending()) {
                throw ValidationException::withMessages([
                    'reseller_application' => 'Pengajuan reseller sudah diproses sebelumnya.',
                ]);
            }

            $target->fill([
                'status' => 'approved',
                'approved_at' => now(),
                'rejected_at' => null,
                'reviewed_by' => $admin->id,
                'rejection_reason' => null,
            ]);
            $target->save();

            ResellerApplicationReview::query()->create([
                'user_id' => $target->user_id,
                'action' => 'approved',
                'reviewed_by' => $admin->id,
                'notes' => $context['note'] ?? null,
            ]);

            // Provision integrations and get generated API keys
            $keys = $this->provisioningService->provision($target->user()->firstOrFail());

            // Dispatch notification job to send keys via email/WhatsApp
            if ($keys['live_key'] || $keys['sandbox_key']) {
                \App\Jobs\NotifyResellerKeysJob::dispatch(
                    $target->user,
                    $keys['live_key'],
                    $keys['sandbox_key'],
                    'approval',
                    $admin
                );
            }

            return $target->fresh(['user', 'reviewer']);
        });
    }

    public function reject(ResellerApplication $application, User $admin, string $reason): ResellerApplication
    {
        $normalizedReason = trim($reason);

        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($application, $admin, $normalizedReason): ResellerApplication {
            $target = ResellerApplication::query()
                ->whereKey($application->id)
                ->lockForUpdate()
                ->first();

            if (! $target) {
                throw ValidationException::withMessages([
                    'reseller_application' => 'Pengajuan reseller tidak ditemukan.',
                ]);
            }

            if (! $target->isPending()) {
                throw ValidationException::withMessages([
                    'reseller_application' => 'Pengajuan reseller sudah diproses sebelumnya.',
                ]);
            }

            $target->fill([
                'status' => 'rejected',
                'approved_at' => null,
                'rejected_at' => now(),
                'reviewed_by' => $admin->id,
                'rejection_reason' => $normalizedReason,
            ]);
            $target->save();

            ResellerApplicationReview::query()->create([
                'user_id' => $target->user_id,
                'action' => 'rejected',
                'reviewed_by' => $admin->id,
                'notes' => $normalizedReason,
            ]);

            return $target->fresh(['user', 'reviewer']);
        });
    }
}
