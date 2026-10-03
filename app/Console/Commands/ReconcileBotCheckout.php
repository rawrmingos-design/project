<?php

namespace App\Console\Commands;

use App\Models\BotCheckoutIntent;
use App\Services\Checkout\BotCheckoutIntentService;
use App\Services\Checkout\BotCheckoutReconciler;
use Illuminate\Console\Command;

/**
 * Membaca status `requires_reconciliation` pada `bot_checkout_intents`.
 *
 * Sebelum command ini ada, status itu ditulis `markFailure()` dan dibalas ke
 * user dengan kalimat "sedang direkonsiliasi, jangan membuat transaksi ulang",
 * tetapi TIDAK ADA satu pun command/job/scheduler yang membacanya — jebakan
 * permanen, bukan sisa kode mati.
 *
 * NONAKTIF secara default (`bot.reconcile_checkout_enabled`): jalur ini
 * menyentuh uang. Tanpa `--apply`, command hanya MELAPORKAN (dry-run) dan
 * tidak menulis apa pun — jadi status bisa diperiksa di produksi lebih dulu.
 */
class ReconcileBotCheckout extends Command
{
    protected $signature = 'bot:reconcile-checkout
        {--apply : Tulis perubahan (tanpa flag ini hanya melaporkan)}
        {--limit=200 : Jumlah maksimum intent yang diperiksa}';

    protected $description = 'Rekonsiliasi intent checkout bot yang menggantung (requires_reconciliation)';

    public function handle(
        BotCheckoutIntentService $intentService,
        BotCheckoutReconciler $reconciler,
    ): int {
        $limit = max(1, (int) $this->option('limit'));
        $apply = (bool) $this->option('apply');
        $enabled = (bool) config('bot.reconcile_checkout_enabled', false);

        $intents = BotCheckoutIntent::query()
            ->where('status', BotCheckoutIntent::STATUS_REQUIRES_RECONCILIATION)
            ->orderBy('provider_dispatched_at')
            ->limit($limit)
            ->get();

        $due = $intents->filter(
            fn (BotCheckoutIntent $intent): bool => $intentService->isReconciliationDue($intent),
        );

        $this->info(sprintf(
            'requires_reconciliation: %d ditemukan, %d jatuh tempo%s.',
            $intents->count(),
            $due->count(),
            $apply ? '' : ' (dry-run)',
        ));

        if (! $enabled) {
            $this->warn('Saklar bot.reconcile_checkout_enabled MATI — tidak ada perubahan status yang ditulis.');

            foreach ($due as $intent) {
                $this->line(sprintf(
                    '  · %s umur %s menit (menunggu izin)',
                    (string) $intent->intent_id,
                    $intent->provider_dispatched_at
                        ? (string) $intent->provider_dispatched_at->diffInMinutes(now())
                        : '-',
                ));
            }

            return self::SUCCESS;
        }

        foreach ($due as $intent) {
            $orderId = $reconciler->resolveOrderForIntent($intent);

            if (! $apply) {
                $this->line(sprintf(
                    '  · %s → %s%s',
                    (string) $intent->intent_id,
                    $orderId ? 'completed' : 'failed_retryable',
                    $orderId ? " ({$orderId})" : ' (reconciled_no_order)',
                ));

                continue;
            }

            $intentService->markReconciled($intent, $orderId !== null, $orderId);

            $this->line(sprintf(
                '  · %s → %s',
                (string) $intent->intent_id,
                $orderId ? "completed ({$orderId})" : 'failed_retryable (reconciled_no_order)',
            ));
        }

        if (! $apply) {
            $this->warn('Dry-run: jalankan ulang dengan --apply untuk menulis.');
        }

        return self::SUCCESS;
    }
}
