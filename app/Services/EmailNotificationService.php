<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\TransactionMail;

class EmailNotificationService
{
    /**
     * Send email notification to user.
     *
     * @param string $email
     * @param array $data
     * @return bool
     */
    public function sendTransactionEmail($email, $data)
    {
        if (empty($email)) {
            Log::warning("EmailNotificationService: No email provided for Order ID: " . ($data['order_id'] ?? 'Unknown'));
            return false;
        }

        try {
            // Determine slug based on status
            $status = strtolower($data['status'] ?? '');
            $slug = 'transaction_pending'; // Default
            if ($status == 'success' || $status == 'sukses') {
                $slug = 'transaction_success';
            } elseif ($status == 'failed' || $status == 'gagal' || $status == 'expired') {
                $slug = 'transaction_failed';
            }

            // Fetch Template
            $template = \App\Models\EmailTemplate::where('slug', $slug)->where('is_active', true)->first();
            
            $subject = null;
            $content = null;

            if ($template) {
                $subject = $template->subject;
                $content = $template->content;

                // Replace variables
                foreach ($data as $key => $value) {
                    if (is_string($value) || is_numeric($value)) {
                        $subject = str_replace('{' . $key . '}', $value, $subject);
                        $content = str_replace('{' . $key . '}', $value, $content);
                    }
                }
                // Also replace {nickname} if not in data but in data (it should be)
                // Just in case, replace any remaining tags with empty or keep them?
                // Keeping them is better for debugging.
            }

            Mail::to($email)->send(new TransactionMail($data, $subject, $content));
            Log::info("Email sent successfully to {$email} for Order ID: " . ($data['order_id'] ?? 'Unknown'));
            return true;
        } catch (\Exception $e) {
            Log::error("EmailNotificationService Error: " . $e->getMessage());
            return false;
        }
    }
}
