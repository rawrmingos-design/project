<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class LogTriageCommand extends Command
{
    protected $signature = 'log:triage
                            {--dry-run : Show report without sending alerts}
                            {--env= : Environment name for alert (default: APP_ENV)}
                            {--hours=24 : Analyze logs from last N hours}';

    protected $description = 'Analyze Laravel logs, categorize errors, and send Telegram alerts if thresholds exceeded';

    /**
     * Error thresholds per category (alert if exceeded)
     */
    protected array $thresholds = [
        'whatsapp_timeout' => 5,
        'missing_config' => 5,
        'api_check_controller' => 3,
        'order_gateway_failed' => 2,
        'duitku_failed' => 2,
        'bangjeff_webhook' => 3,
        'provider_balance_job' => 50, // Higher threshold for noisy staging warnings
    ];

    public function handle(): int
    {
        $this->info('🔍 Log Triage Analysis');
        $this->newLine();

        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            $this->error("❌ Log file not found: $logPath");
            return 1;
        }

        $hours = (int) $this->option('hours');
        $this->line("📅 Analyzing logs from last {$hours} hours...");
        $this->newLine();

        // Parse log file
        $stats = $this->parseLogFile($logPath, $hours);

        // Display console report
        $this->displayReport($stats);

        // Check thresholds
        $exceeded = $this->checkThresholds($stats);

        if (!empty($exceeded)) {
            $this->newLine();
            $this->warn('⚠️  ' . count($exceeded) . ' categories exceeded threshold!');

            if ($this->option('dry-run')) {
                $this->info('🔕 Dry-run mode: Skipping Telegram alert');
            } else {
                $this->info('📤 Sending Telegram alert...');
                $sent = $this->sendTelegramAlert($exceeded, $stats);

                if ($sent) {
                    $this->info('✅ Telegram alert sent successfully');
                } else {
                    $this->error('❌ Failed to send Telegram alert');
                }
            }
        } else {
            $this->newLine();
            $this->info('✅ All error counts within thresholds');
        }

        return 0;
    }

    /**
     * Parse log file and categorize errors
     */
    protected function parseLogFile(string $path, int $hours): array
    {
        $stats = [
            'whatsapp_timeout' => 0,
            'missing_config' => 0,
            'api_check_controller' => 0,
            'order_gateway_failed' => 0,
            'duitku_failed' => 0,
            'bangjeff_webhook' => 0,
            'provider_balance_job' => 0,
            'other_errors' => 0,
            'total_errors' => 0,
            'total_warnings' => 0,
        ];

        $cutoffTime = Carbon::now()->subHours($hours);
        $content = File::get($path);
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            // Extract timestamp and check if within time window
            if (!preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                continue;
            }

            $timestamp = Carbon::parse($matches[1]);

            if ($timestamp->lt($cutoffTime)) {
                continue; // Skip old logs
            }

            // Count by severity
            if (str_contains($line, '.ERROR:')) {
                $stats['total_errors']++;
            } elseif (str_contains($line, '.WARNING:')) {
                $stats['total_warnings']++;
            }

            // Categorize
            $category = $this->categorizeLogLine($line);

            if ($category) {
                $stats[$category]++;
            } elseif (str_contains($line, '.ERROR:')) {
                $stats['other_errors']++;
            }
        }

        return $stats;
    }

    /**
     * Match log line to error category
     */
    protected function categorizeLogLine(string $line): ?string
    {
        // WhatsApp timeout (cURL error 28 or connection timeout)
        if (preg_match('/WhatsappNotificationService.*(cURL error 28|timeout|connection|timed out)/i', $line)) {
            return 'whatsapp_timeout';
        }

        // Missing configuration (WA/Fonnte credentials, etc.)
        if (preg_match('/(Missing configuration|not configured|credentials.*(empty|missing|belum lengkap))/i', $line)) {
            return 'missing_config';
        }

        // ApiCheckController errors (user/provider not found)
        if (preg_match('/ApiCheckController.*(not found|failed|error)/i', $line)) {
            return 'api_check_controller';
        }

        // Order Store Gateway Failed
        if (preg_match('/(Order.*Store.*Gateway.*Failed|SendPembelianToProviderJob.*failed)/i', $line)) {
            return 'order_gateway_failed';
        }

        // Duitku invoice creation failed
        if (preg_match('/(Duitku.*create invoice failed|DuitkuInvoiceService.*failed)/i', $line)) {
            return 'duitku_failed';
        }

        // BangJeff webhook signature verification failed
        if (preg_match('/BangJeff.*webhook.*signature.*verification.*failed/i', $line)) {
            return 'bangjeff_webhook';
        }

        // CheckProviderBalanceJob warnings
        if (preg_match('/CheckProviderBalanceJob.*(skipped|failed|not found)/i', $line)) {
            return 'provider_balance_job';
        }

        return null;
    }

    /**
     * Display console report table
     */
    protected function displayReport(array $stats): void
    {
        $this->table(
            ['Category', 'Count', 'Threshold', 'Status'],
            [
                ['WhatsApp Timeout', $stats['whatsapp_timeout'], $this->thresholds['whatsapp_timeout'], $this->getStatus($stats['whatsapp_timeout'], $this->thresholds['whatsapp_timeout'])],
                ['Missing Config', $stats['missing_config'], $this->thresholds['missing_config'], $this->getStatus($stats['missing_config'], $this->thresholds['missing_config'])],
                ['API Check Controller', $stats['api_check_controller'], $this->thresholds['api_check_controller'], $this->getStatus($stats['api_check_controller'], $this->thresholds['api_check_controller'])],
                ['Order Gateway Failed', $stats['order_gateway_failed'], $this->thresholds['order_gateway_failed'], $this->getStatus($stats['order_gateway_failed'], $this->thresholds['order_gateway_failed'])],
                ['Duitku Failed', $stats['duitku_failed'], $this->thresholds['duitku_failed'], $this->getStatus($stats['duitku_failed'], $this->thresholds['duitku_failed'])],
                ['BangJeff Webhook', $stats['bangjeff_webhook'], $this->thresholds['bangjeff_webhook'], $this->getStatus($stats['bangjeff_webhook'], $this->thresholds['bangjeff_webhook'])],
                ['Provider Balance Job', $stats['provider_balance_job'], $this->thresholds['provider_balance_job'], $this->getStatus($stats['provider_balance_job'], $this->thresholds['provider_balance_job'])],
                ['Other Errors', $stats['other_errors'], '-', '-'],
            ]
        );

        $this->newLine();
        $this->line("📊 Total Errors: {$stats['total_errors']}");
        $this->line("⚠️  Total Warnings: {$stats['total_warnings']}");
    }

    /**
     * Get status indicator for threshold comparison
     */
    protected function getStatus(int $count, int $threshold): string
    {
        if ($count === 0) {
            return '✅ OK';
        }

        if ($count > $threshold) {
            return '🔴 EXCEEDED';
        }

        return '🟡 Within';
    }

    /**
     * Check which categories exceeded thresholds
     */
    protected function checkThresholds(array $stats): array
    {
        $exceeded = [];

        foreach ($this->thresholds as $category => $threshold) {
            if ($stats[$category] > $threshold) {
                $exceeded[$category] = [
                    'count' => $stats[$category],
                    'threshold' => $threshold,
                    'diff' => $stats[$category] - $threshold,
                ];
            }
        }

        return $exceeded;
    }

    /**
     * Send Telegram alert for exceeded thresholds
     */
    protected function sendTelegramAlert(array $exceeded, array $stats): bool
    {
        $botToken = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');
        $chatId = config('services.telegram.chat_id') ?? env('TELEGRAM_CHAT_ID');

        if (!$botToken || !$chatId) {
            $this->warn('⚠️  Telegram credentials not configured (TELEGRAM_BOT_TOKEN or TELEGRAM_CHAT_ID missing)');
            Log::warning('LogTriageCommand: Telegram credentials not configured');
            return false;
        }

        $environment = $this->option('env') ?? config('app.env', 'unknown');
        $hours = (int) $this->option('hours');
        $timestamp = Carbon::now()->format('Y-m-d H:i');

        // Build message
        $message = "🚨 *Log Alert - {$environment}*\n\n";
        $message .= "⚠️ *Errors exceeded threshold:*\n";

        foreach ($exceeded as $category => $data) {
            $label = $this->getCategoryLabel($category);
            $message .= "• {$label}: *{$data['count']}* (threshold: {$data['threshold']}, +{$data['diff']})\n";
        }

        $message .= "\n📊 *Full Report (last {$hours}h):*\n";
        $message .= "• WhatsApp Timeout: {$stats['whatsapp_timeout']}\n";
        $message .= "• Missing Config: {$stats['missing_config']}\n";
        $message .= "• API Check: {$stats['api_check_controller']}\n";
        $message .= "• Order Gateway: {$stats['order_gateway_failed']}\n";
        $message .= "• Duitku Failed: {$stats['duitku_failed']}\n";
        $message .= "• BangJeff Webhook: {$stats['bangjeff_webhook']}\n";
        $message .= "• Provider Balance: {$stats['provider_balance_job']}\n";
        $message .= "• Other Errors: {$stats['other_errors']}\n\n";
        $message .= "📈 Total Errors: {$stats['total_errors']}\n";
        $message .= "⚠️ Total Warnings: {$stats['total_warnings']}\n\n";
        $message .= "🕐 Time: {$timestamp}";

        try {
            $response = Http::timeout(10)
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown',
                ]);

            if ($response->successful()) {
                Log::info('LogTriageCommand: Telegram alert sent', [
                    'exceeded_count' => count($exceeded),
                    'environment' => $environment,
                ]);
                return true;
            }

            Log::warning('LogTriageCommand: Telegram API returned non-success', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('LogTriageCommand: Failed to send Telegram alert', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get human-readable category label
     */
    protected function getCategoryLabel(string $category): string
    {
        return match ($category) {
            'whatsapp_timeout' => 'WhatsApp Timeout',
            'missing_config' => 'Missing Config',
            'api_check_controller' => 'API Check Controller',
            'order_gateway_failed' => 'Order Gateway Failed',
            'duitku_failed' => 'Duitku Failed',
            'bangjeff_webhook' => 'BangJeff Webhook',
            'provider_balance_job' => 'Provider Balance Job',
            default => ucwords(str_replace('_', ' ', $category)),
        };
    }
}
