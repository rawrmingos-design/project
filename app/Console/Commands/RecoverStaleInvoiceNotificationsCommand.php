<?php

namespace App\Console\Commands;

use App\Models\InvoiceNotificationDelivery;
use Illuminate\Console\Command;

class RecoverStaleInvoiceNotificationsCommand extends Command
{
    protected $signature = 'notifications:recover-stale
        {--minutes=15 : Age in minutes after which a sending delivery is stale}
        {--dry-run : Show stale deliveries without changing them}';

    protected $description = 'Recover invoice notification deliveries left in sending state by a crashed worker';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        if ($minutes < 1) {
            $this->error('The --minutes option must be at least 1.');

            return self::FAILURE;
        }

        $cutoff = now()->subMinutes($minutes);
        $query = InvoiceNotificationDelivery::query()
            ->where('status', InvoiceNotificationDelivery::STATUS_SENDING)
            ->whereNotNull('locked_at')
            ->where('locked_at', '<=', $cutoff);

        $count = (clone $query)->count();
        if ((bool) $this->option('dry-run')) {
            $this->info("{$count} stale invoice notification delivery(ies) found.");

            return self::SUCCESS;
        }

        $updated = $query->update([
            'status' => InvoiceNotificationDelivery::STATUS_PENDING,
            'locked_at' => null,
            'next_attempt_at' => now(),
        ]);

        $this->info("{$updated} stale invoice notification delivery(ies) recovered.");

        return self::SUCCESS;
    }
}
