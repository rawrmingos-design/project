<?php

namespace App\Console\Commands;

use App\Jobs\NotifyResellerKeysJob;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestResellerNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:reseller-notification 
                            {username : Username of the reseller to notify}
                            {--admin= : Username of admin (optional, defaults to reseller)}
                            {--no-live : Skip live key generation}
                            {--no-sandbox : Skip sandbox key generation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test reseller notification system (WhatsApp + Email)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username');
        $adminUsername = $this->option('admin');
        
        // Find reseller user
        $user = User::where('username', $username)->first();
        
        if (!$user) {
            $this->error("❌ User with username '{$username}' not found!");
            return 1;
        }

        $this->info("📋 Testing notification for reseller:");
        $this->info("   Username: {$user->username}");
        $this->info("   Email: {$user->email}");
        $this->info("   Phone: {$user->no_wa}");
        $this->newLine();

        // Find admin user
        $admin = null;
        if ($adminUsername) {
            $admin = User::where('username', $adminUsername)->first();
            if (!$admin) {
                $this->warn("⚠️  Admin username '{$adminUsername}' not found, skipping admin notification");
            } else {
                $this->info("👤 Admin notification will be sent to:");
                $this->info("   Username: {$admin->username}");
                $this->info("   Phone: {$admin->no_wa}");
                $this->newLine();
            }
        }

        // Generate test API keys
        $liveKey = null;
        $sandboxKey = null;

        if (!$this->option('no-live')) {
            $liveKey = 'rliv_' . Str::random(40);
            $this->line("🔑 Generated test LIVE key: {$liveKey}");
        }

        if (!$this->option('no-sandbox')) {
            $sandboxKey = 'rsbx_' . Str::random(40);
            $this->line("🧪 Generated test SANDBOX key: {$sandboxKey}");
        }

        $this->newLine();

        // Confirm before sending
        if (!$this->confirm('Send test notifications now?', true)) {
            $this->warn('Cancelled.');
            return 0;
        }

        $this->info('📤 Dispatching notification job...');

        try {
            NotifyResellerKeysJob::dispatch(
                $user,
                $liveKey,
                $sandboxKey,
                'approval',
                $admin
            );

            $this->newLine();
            $this->info('✅ Notification job dispatched successfully!');
            $this->newLine();
            $this->info('📝 Check the logs for details:');
            $this->line('   tail -f storage/logs/laravel.log');
            $this->newLine();
            $this->info('Expected notifications:');
            
            if ($user->email) {
                $this->line("   ✉️  Email to: {$user->email}");
            } else {
                $this->warn("   ⚠️  No email (user has no email set)");
            }

            if ($user->no_wa) {
                $this->line("   📱 WhatsApp to: {$user->no_wa}");
            } else {
                $this->warn("   ⚠️  No WhatsApp (user has no phone set)");
            }

            if ($admin && $admin->no_wa) {
                $this->line("   👤 Admin WhatsApp to: {$admin->no_wa}");
            }

            $this->newLine();
            $this->comment('Note: WhatsApp messages are logged. Check your WhatsApp service integration to see actual delivery.');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error dispatching notification: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
