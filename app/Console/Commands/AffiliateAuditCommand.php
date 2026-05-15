<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AffiliateAuditCommand extends Command
{
    protected $signature = 'affiliate:audit 
        {--json : Output hasil audit dalam format JSON}
        {--warn-hours=24 : Ambang jam untuk pending yang dianggap stale}';

    protected $description = 'Audit kesehatan alur affiliate (pending queue, review, dan notifikasi).';

    public function handle(): int
    {
        $warnHours = max(1, (int) $this->option('warn-hours'));
        $warnThreshold = Carbon::now()->subHours($warnHours);
        $lookbackWindow = Carbon::now()->subDays(7);

        $pendingTotal = User::query()
            ->where('affiliate_status', 'pending')
            ->count();

        $pendingStale = User::query()
            ->where('affiliate_status', 'pending')
            ->whereNotNull('affiliate_requested_at')
            ->where('affiliate_requested_at', '<=', $warnThreshold)
            ->count();

        $activeTotal = User::query()
            ->where('affiliate_status', 'active')
            ->count();

        $rejectedTotal = User::query()
            ->where('affiliate_status', 'rejected')
            ->count();

        $recentReviewed = User::query()
            ->whereIn('affiliate_status', ['active', 'rejected'])
            ->whereNotNull('affiliate_application_meta')
            ->get(['affiliate_application_meta'])
            ->filter(function (User $user) use ($lookbackWindow): bool {
                $reviewedAt = data_get($user->affiliate_application_meta, 'review_last.reviewed_at');
                if (! is_string($reviewedAt) || trim($reviewedAt) === '') {
                    return false;
                }

                try {
                    return Carbon::parse($reviewedAt)->gte($lookbackWindow);
                } catch (\Throwable) {
                    return false;
                }
            });

        $notificationFailedRecent = $recentReviewed
            ->filter(function (User $user): bool {
                $waSuccess = data_get($user->affiliate_application_meta, 'review_last.notification.wa.success');
                $emailSuccess = data_get($user->affiliate_application_meta, 'review_last.notification.email.success');
                $waAttempted = (bool) data_get($user->affiliate_application_meta, 'review_last.notification.wa.attempted', false);
                $emailAttempted = (bool) data_get($user->affiliate_application_meta, 'review_last.notification.email.attempted', false);

                $waFailed = $waAttempted && $waSuccess === false;
                $emailFailed = $emailAttempted && $emailSuccess === false;

                return $waFailed || $emailFailed;
            })
            ->count();

        $report = [
            'generated_at' => Carbon::now()->toIso8601String(),
            'warn_hours' => $warnHours,
            'pending_total' => $pendingTotal,
            'pending_stale' => $pendingStale,
            'active_total' => $activeTotal,
            'rejected_total' => $rejectedTotal,
            'recent_reviewed_7d' => $recentReviewed->count(),
            'recent_notification_failed_7d' => $notificationFailedRecent,
            'status' => $pendingStale > 0 ? 'warning' : 'ok',
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info('Affiliate audit summary');
        $this->line('Pending total: ' . $pendingTotal);
        $this->line('Pending stale (>' . $warnHours . ' jam): ' . $pendingStale);
        $this->line('Active total: ' . $activeTotal);
        $this->line('Rejected total: ' . $rejectedTotal);
        $this->line('Reviewed 7 hari terakhir: ' . $recentReviewed->count());
        $this->line('Notifikasi gagal 7 hari terakhir: ' . $notificationFailedRecent);

        if ($pendingStale > 0) {
            $this->warn('Ada pengajuan affiliate pending melewati SLA review.');
        } else {
            $this->info('SLA review affiliate aman.');
        }

        return self::SUCCESS;
    }
}

