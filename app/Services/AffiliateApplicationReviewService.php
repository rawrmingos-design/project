<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AffiliateApplicationReviewService
{
    public function __construct(
        private readonly AffiliateReviewNotificationService $affiliateReviewNotificationService,
    ) {
    }

    public function approve(User $applicant, ?User $reviewer = null, ?string $note = null): User
    {
        return $this->review($applicant, 'active', $reviewer, $note, false);
    }

    public function reject(User $applicant, ?User $reviewer = null, ?string $note = null): User
    {
        return $this->review($applicant, 'rejected', $reviewer, $note, true);
    }

    private function review(
        User $applicant,
        string $decisionStatus,
        ?User $reviewer,
        ?string $note,
        bool $noteRequired,
    ): User {
        $normalizedNote = blank($note) ? null : trim((string) $note);
        if ($noteRequired && blank($normalizedNote)) {
            throw ValidationException::withMessages([
                'review_note' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        $updated = DB::transaction(function () use ($applicant, $decisionStatus, $reviewer, $normalizedNote): User {
            /** @var User|null $target */
            $target = User::query()
                ->whereKey($applicant->id)
                ->lockForUpdate()
                ->first();

            if (! $target) {
                throw ValidationException::withMessages([
                    'affiliate_status' => 'Data pengajuan affiliate tidak ditemukan.',
                ]);
            }

            $currentStatus = strtolower(trim((string) ($target->affiliate_status ?? '')));
            if ($currentStatus !== 'pending') {
                throw ValidationException::withMessages([
                    'affiliate_status' => 'Pengajuan sudah diproses sebelumnya. Silakan refresh halaman.',
                ]);
            }

            if ($decisionStatus === 'active' && blank($target->referral_code)) {
                $target->referral_code = $this->generateUniqueReferralCode();
            }

            $meta = is_array($target->affiliate_application_meta) ? $target->affiliate_application_meta : [];
            $reviewHistory = data_get($meta, 'review_history');
            if (! is_array($reviewHistory)) {
                $reviewHistory = [];
            }

            $reviewEntry = [
                'decision' => $decisionStatus,
                'note' => $normalizedNote,
                'reviewed_at' => now()->toIso8601String(),
                'reviewed_by_id' => $reviewer?->id,
                'reviewed_by_username' => $reviewer?->username,
            ];

            $reviewHistory[] = $reviewEntry;
            $reviewHistory = array_slice($reviewHistory, -20);

            $meta['review_history'] = $reviewHistory;
            $meta['review_last'] = $reviewEntry;

            $target->affiliate_status = $decisionStatus;
            $target->affiliate_application_meta = $meta;
            $target->save();

            Log::info('affiliate.application.reviewed', [
                'applicant_id' => $target->id,
                'applicant_username' => $target->username,
                'decision' => $decisionStatus,
                'reviewer_id' => $reviewer?->id,
                'reviewer_username' => $reviewer?->username,
                'has_note' => filled($normalizedNote),
            ]);

            return $target->fresh();
        });

        try {
            $delivery = $this->affiliateReviewNotificationService->notifyReviewDecision($updated, $decisionStatus, $normalizedNote);
            $this->appendDeliveryMeta($updated, $delivery);
        } catch (\Throwable $exception) {
            Log::warning('affiliate.application.review_notification_failed', [
                'applicant_id' => $updated->id,
                'decision' => $decisionStatus,
                'error' => $exception->getMessage(),
            ]);
        }

        return $updated;
    }

    /**
     * @param array<string, mixed> $delivery
     */
    private function appendDeliveryMeta(User $user, array $delivery): void
    {
        try {
            $fresh = User::query()->find($user->id);
            if (! $fresh) {
                return;
            }

            $meta = is_array($fresh->affiliate_application_meta) ? $fresh->affiliate_application_meta : [];
            $reviewLast = is_array(data_get($meta, 'review_last')) ? data_get($meta, 'review_last') : [];
            $reviewLast['notification'] = array_merge($delivery, [
                'sent_at' => now()->toIso8601String(),
            ]);
            $meta['review_last'] = $reviewLast;

            $history = data_get($meta, 'review_history');
            if (is_array($history) && $history !== []) {
                $lastIndex = array_key_last($history);
                if ($lastIndex !== null && is_array($history[$lastIndex])) {
                    $history[$lastIndex]['notification'] = $reviewLast['notification'];
                    $meta['review_history'] = $history;
                }
            }

            $fresh->affiliate_application_meta = $meta;
            $fresh->save();
        } catch (\Throwable $exception) {
            Log::warning('affiliate.application.review_notification_meta_failed', [
                'applicant_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function generateUniqueReferralCode(): string
    {
        do {
            $code = 'REF-' . strtoupper(Str::random(6));
        } while (User::query()->where('referral_code', $code)->exists());

        return $code;
    }
}
