<?php

namespace App\Services;

use App\Models\User;

class ResellerApplicationEligibilityService
{
    /**
     * @return array{can_apply: bool, reasons: array<int, string>}
     */
    public function evaluate(User $user): array
    {
        $reasons = [];

        if ($user->hasResellerAccess()) {
            $reasons[] = 'Akun sudah memiliki akses reseller.';
        }

        $application = $user->resellerApplication;

        if ($application && $application->isPending()) {
            $reasons[] = 'Aplikasi reseller sedang dalam proses review.';
        }

        if ($application && $application->isRejected() && $application->rejected_at) {
            $cooldownEnd = $application->rejected_at->copy()->addDays(30);

            if ($cooldownEnd->isFuture()) {
                $daysLeft = (int) now()->diffInDays($cooldownEnd, false);
                if ($daysLeft < 0) {
                    $daysLeft = 0;
                }

                $reasons[] = 'Pengajuan ulang reseller dapat dilakukan setelah masa tunggu 30 hari berakhir.';
            }
        }

        if ($user->created_at && $user->created_at->diffInDays(now()) < 7) {
            $reasons[] = 'Umur akun minimal 7 hari untuk mengajukan reseller.';
        }

        return [
            'can_apply' => $reasons === [],
            'reasons' => $reasons,
        ];
    }

    public function canApply(User $user): bool
    {
        return $this->evaluate($user)['can_apply'];
    }

    /**
     * @return array<int, string>
     */
    public function reasons(User $user): array
    {
        return $this->evaluate($user)['reasons'];
    }
}
