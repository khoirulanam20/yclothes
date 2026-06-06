<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

class MailSettingsService
{
    public function apply(): void
    {
        if (! setting_bool('mail_enabled')) {
            return;
        }

        $mailer = setting('mail_mailer', 'smtp') ?: 'smtp';
        Config::set('mail.default', $mailer);

        if ($host = setting('mail_host')) {
            Config::set('mail.mailers.smtp.host', $host);
        }

        if ($port = setting('mail_port')) {
            Config::set('mail.mailers.smtp.port', (int) $port);
        }

        if ($username = setting('mail_username')) {
            Config::set('mail.mailers.smtp.username', $username);
        }

        if ($password = setting('mail_password')) {
            Config::set('mail.mailers.smtp.password', $password);
        }

        $encryption = setting('mail_encryption');
        Config::set('mail.mailers.smtp.encryption', filled($encryption) ? $encryption : null);

        if ($from = setting('mail_from_address')) {
            Config::set('mail.from.address', $from);
        }

        if ($name = setting('mail_from_name')) {
            Config::set('mail.from.name', $name);
        }
    }
}
