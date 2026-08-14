<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailService
{
    /**
     * Send HTML notification email
     *
     * @param string $email
     * @param string $subject
     * @param string $title
     * @param string $bodyHtml
     * @return void
     */
    public static function sendNotification($email, $subject, $title, $bodyHtml)
    {
        try {
            $appName = env('APP_NAME', 'BaknusAttend');
            
            $refCode = strtoupper(substr(md5(uniqid('', true)), 0, 6));
            
            $html = "
            <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>
                <div style='background-color: #3b82f6; padding: 24px; color: white; text-align: center;'>
                    <h2 style='margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 0.5px;'>[{$appName} Notifikasi]</h2>
                </div>
                <div style='padding: 24px; color: #333; line-height: 1.6; background-color: #ffffff;'>
                    <h3 style='margin-top: 0; color: #1e3a8a; font-size: 18px; border-bottom: 2px solid #eff6ff; padding-bottom: 10px;'>{$title}</h3>
                    <div style='margin-top: 15px;'>
                        {$bodyHtml}
                    </div>
                </div>
                <div style='background-color: #f9fafb; padding: 15px; text-align: center; font-size: 12px; color: #6b7280; border-top: 1px solid #e5e7eb;'>
                    &copy; " . date('Y') . " SMK Bhakti Nusantara 666. All rights reserved. <span style='display: inline-block; margin-left: 8px; color: #9ca3af; font-family: monospace;'>#{$refCode}</span>
                </div>
            </div>
            ";

            Log::info("[MailService] Preparing to send email notification to '{$email}' with subject: '{$subject}'");

            Mail::html($html, function ($message) use ($email, $subject) {
                $uniqueId = uniqid('baknus', true) . '.' . time();
                $domain = substr(strrchr($email, '@'), 1) ?: 'smk.baktinusantara666.sch.id';
                $fromAddr = config('mail.from.address', 'admin@smk.baktinusantara666.sch.id');
                $fromName = config('mail.from.name', 'BaknusAttend');

                $message->from($fromAddr, $fromName)
                        ->to($email)
                        ->subject($subject);

                // Set unique Message-ID via Symfony Mailer headers
                $message->getHeaders()
                    ->addIdHeader('Message-ID', $uniqueId . '@' . $domain);
            });
            
            Log::info("[MailService] SUCCESS: Email notification delivered to '{$email}' with subject: '{$subject}'");
        } catch (\Throwable $e) {
            Log::error("[MailService] ERROR: Failed to send email notification to '{$email}': " . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
