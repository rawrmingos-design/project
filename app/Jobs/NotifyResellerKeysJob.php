<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyResellerKeysJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected User $user;
    protected ?string $liveApiKey;
    protected ?string $sandboxApiKey;
    protected string $context; // 'approval' or 'rotation'
    protected ?User $admin; // Admin who approved/rotated

    /**
     * Create a new job instance.
     *
     * @param User $user The reseller user
     * @param string|null $liveApiKey Full live API key (if generated/rotated)
     * @param string|null $sandboxApiKey Full sandbox API key (if generated/rotated)
     * @param string $context 'approval' or 'rotation'
     * @param User|null $admin Admin who performed the action (for notification)
     */
    public function __construct(User $user, ?string $liveApiKey, ?string $sandboxApiKey, string $context = 'approval', ?User $admin = null)
    {
        $this->user = $user;
        $this->liveApiKey = $liveApiKey;
        $this->sandboxApiKey = $sandboxApiKey;
        $this->context = $context;
        $this->admin = $admin;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get settings from database
        $settings = DB::table('setting_webs')->where('id', 1)->first();
        
        if (!$settings) {
            Log::error('NotifyResellerKeysJob: setting_webs not found');
            return;
        }

        $siteName = $settings->title ?? 'Our Platform';
        $supportEmail = $settings->email ?? 'support@example.com';
        $docsUrl = 'https://' . (env('DOCS_DOMAIN') ?: config('app.url') . '/docs');
        $credentialsUrl = url('/id/reseller/credentials');

        // Prepare message content
        $subject = $this->context === 'approval' 
            ? "[URGENT] Your API Credentials - Save This Message" 
            : "[URGENT] Your Rotated API Key - Save This Message";

        // Send Email
        try {
            if ($this->user->email) {
                $this->sendEmail($subject, $siteName, $supportEmail, $docsUrl, $credentialsUrl);
            }
        } catch (\Exception $e) {
            Log::error('NotifyResellerKeysJob: Email failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Send WhatsApp (if configured and user has phone)
        try {
            if ($this->user->no_wa && $this->shouldSendWhatsApp($settings)) {
                $this->sendWhatsApp($siteName, $credentialsUrl);
            }
        } catch (\Exception $e) {
            Log::error('NotifyResellerKeysJob: WhatsApp failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Send notification to Admin (if provided)
        if ($this->admin && $this->context === 'approval') {
            try {
                if ($this->admin->no_wa && $this->shouldSendWhatsApp($settings)) {
                    $this->sendAdminNotification($siteName);
                }
            } catch (\Exception $e) {
                Log::error('NotifyResellerKeysJob: Admin notification failed', [
                    'admin_id' => $this->admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Log successful delivery
        Log::info('NotifyResellerKeysJob: Notifications sent', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'phone' => $this->user->no_wa,
            'context' => $this->context,
            'live_key_sent' => !empty($this->liveApiKey),
            'sandbox_key_sent' => !empty($this->sandboxApiKey),
            'admin_notified' => $this->admin ? true : false,
        ]);
    }

    protected function sendEmail(string $subject, string $siteName, string $supportEmail, string $docsUrl, string $credentialsUrl): void
    {
        $userName = $this->user->name ?? $this->user->username;
        
        $emailBody = $this->buildEmailBody($userName, $siteName, $supportEmail, $docsUrl, $credentialsUrl);

        Mail::raw($emailBody, function ($message) use ($subject) {
            $message->to($this->user->email)
                ->subject($subject);
        });
    }

    protected function buildEmailBody(string $userName, string $siteName, string $supportEmail, string $docsUrl, string $credentialsUrl): string
    {
        $greeting = $this->context === 'approval' 
            ? "Your reseller application has been APPROVED! 🎉" 
            : "Your API key has been rotated successfully! 🔄";

        $content = "Hi {$userName},\n\n";
        $content .= "{$greeting}\n\n";
        $content .= "IMPORTANT: Save this email - API keys are only shown once.\n\n";
        $content .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        if ($this->liveApiKey) {
            $content .= "🔑 LIVE API KEY (Production)\n";
            $content .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $content .= "{$this->liveApiKey}\n\n";
        }

        if ($this->sandboxApiKey) {
            $content .= "🧪 SANDBOX API KEY (Testing)\n";
            $content .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $content .= "{$this->sandboxApiKey}\n\n";
        }

        $content .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $content .= "⚠️ SECURITY NOTICE:\n";
        $content .= "- Store these keys securely\n";
        $content .= "- Never share them publicly\n";
        $content .= "- Use LIVE key for production orders\n";
        $content .= "- Use SANDBOX key for testing\n\n";
        $content .= "📖 API Documentation: {$docsUrl}\n";
        $content .= "🔄 Rotate keys at: {$credentialsUrl}\n\n";
        $content .= "Questions? Contact support: {$supportEmail}\n\n";
        $content .= "Best regards,\n";
        $content .= "{$siteName} Team";

        return $content;
    }

    protected function sendWhatsApp(string $siteName, string $credentialsUrl): void
    {
        $userName = $this->user->name ?? $this->user->username;
        
        $greeting = $this->context === 'approval' 
            ? "🎉 *Reseller Application APPROVED*" 
            : "🔄 *API Key Rotated*";

        $message = "{$greeting}\n\n";
        $message .= "Hi {$userName}!\n\n";

        if ($this->liveApiKey) {
            $message .= "🔑 *LIVE KEY*\n";
            $message .= "`{$this->liveApiKey}`\n\n";
        }

        if ($this->sandboxApiKey) {
            $message .= "🧪 *SANDBOX KEY*\n";
            $message .= "`{$this->sandboxApiKey}`\n\n";
        }

        $message .= "⚠️ *SAVE THIS MESSAGE*\n";
        $message .= "Keys are only shown once!\n\n";
        $message .= "🔄 Rotate key: {$credentialsUrl}\n\n";
        $message .= "_{$siteName}_";

        // Send via WhatsApp service
        $waService = new \App\Services\WhatsappNotificationService();
        $result = $waService->sendMessage($this->user->no_wa, $message);

        Log::info('NotifyResellerKeysJob: WhatsApp notification sent', [
            'user_id' => $this->user->id,
            'phone' => $this->user->no_wa,
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? 'Unknown',
        ]);

        if (!($result['success'] ?? false)) {
            Log::warning('NotifyResellerKeysJob: WhatsApp delivery failed', [
                'user_id' => $this->user->id,
                'phone' => $this->user->no_wa,
                'error' => $result['message'] ?? 'Unknown error',
            ]);
        }
    }

    protected function sendAdminNotification(string $siteName): void
    {
        $userName = $this->user->username;
        $userRealName = $this->user->name ?? $userName;
        $adminName = $this->admin->username ?? 'Admin';
        
        $message = "🎉 *New Reseller Approved*\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━\n";
        $message .= "*Reseller Details:*\n";
        $message .= "• Username: {$userName}\n";
        $message .= "• Name: {$userRealName}\n";
        $message .= "• Email: {$this->user->email}\n";
        $message .= "• Phone: {$this->user->no_wa}\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Approved by: {$adminName}\n";
        $message .= "Time: " . now()->format('d M Y, H:i') . "\n\n";
        $message .= "✅ API keys telah dikirim ke reseller.\n";
        $message .= "📱 Portal: " . url('/admin') . "\n\n";
        $message .= "_{$siteName}_";

        // Send via WhatsApp service
        $waService = new \App\Services\WhatsappNotificationService();
        $result = $waService->sendMessage($this->admin->no_wa, $message);

        Log::info('NotifyResellerKeysJob: Admin WhatsApp notification sent', [
            'admin_id' => $this->admin->id,
            'admin_phone' => $this->admin->no_wa,
            'reseller_username' => $userName,
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? 'Unknown',
        ]);

        if (!($result['success'] ?? false)) {
            Log::warning('NotifyResellerKeysJob: Admin WhatsApp delivery failed', [
                'admin_id' => $this->admin->id,
                'admin_phone' => $this->admin->no_wa,
                'error' => $result['message'] ?? 'Unknown error',
            ]);
        }
    }

    protected function shouldSendWhatsApp($settings): bool
    {
        // Check if WhatsApp notifications are enabled in settings
        // Adjust this based on your actual settings structure
        return !empty($this->user->no_wa);
    }
}
