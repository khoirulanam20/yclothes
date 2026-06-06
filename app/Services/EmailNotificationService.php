<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    /** @var list<string> */
    private const CUSTOMER_STATUS_KEYS = [
        'awaiting_verification',
        'confirmed',
        'processed',
        'shipped',
        'delivered',
        'completed',
        'cancelled',
    ];

    public function shouldSendCustomer(string $settingKey, bool $default = true): bool
    {
        return setting_bool($settingKey, $default);
    }

    public function shouldSendAdmin(string $settingKey, bool $default = true): bool
    {
        return setting_bool($settingKey, $default);
    }

    public function shouldSendStatusEmail(string $toStatus): bool
    {
        $key = 'email_customer_status_'.$toStatus;

        if (! in_array($toStatus, self::CUSTOMER_STATUS_KEYS, true)) {
            return false;
        }

        return setting_bool($key, true);
    }

    public function shouldSendPaymentExpiredEmail(): bool
    {
        return setting_bool('send_email_on_payment_expired', true);
    }

    /** @return list<string> */
    public function resolveAdminRecipients(): array
    {
        return $this->resolveAdminRecipientsFromRaw((string) setting('email_admin_recipients', ''));
    }

    /** @return list<string> */
    public function resolveAdminRecipientsFromRaw(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $emails = preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter(
            $emails,
            fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
        )));
    }

    public function queueToCustomer(Order $order, Mailable $mailable, string $settingKey, bool $default = true): void
    {
        if (! $this->shouldSendCustomer($settingKey, $default)) {
            return;
        }

        if (blank($order->customer_email)) {
            return;
        }

        try {
            Mail::to($order->customer_email)->queue($mailable);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function queueToAdmins(Mailable $mailable, string $settingKey, bool $default = true): void
    {
        if (! $this->shouldSendAdmin($settingKey, $default)) {
            return;
        }

        $recipients = $this->resolveAdminRecipients();

        if ($recipients === []) {
            Log::warning('Email admin tidak dikirim: email_admin_recipients kosong atau tidak valid.', [
                'setting' => $settingKey,
            ]);

            return;
        }

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->queue($mailable);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
