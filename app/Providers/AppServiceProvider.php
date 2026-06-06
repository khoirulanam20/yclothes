<?php

namespace App\Providers;

use App\Events\OrderStatusChanged;
use App\Listeners\CompleteReturnOnReplacementOrderCompleted;
use App\Listeners\SendOrderStatusEmail;
use App\Services\MailSettingsService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        try {
            app(MailSettingsService::class)->apply();
        } catch (\Throwable) {
            // settings table may not exist during migrate
        }

        Event::listen(OrderStatusChanged::class, SendOrderStatusEmail::class);
        Event::listen(OrderStatusChanged::class, CompleteReturnOnReplacementOrderCompleted::class);

        VerifyEmail::createUrlUsing(function (object $notifiable) {
            if ($notifiable instanceof Customer) {
                return URL::temporarySignedRoute(
                    'customer.verification.verify',
                    Carbon::now()->addMinutes(60),
                    [
                        'id' => $notifiable->getKey(),
                        'hash' => sha1($notifiable->getEmailForVerification()),
                    ]
                );
            }

            return URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            if ($notifiable instanceof Customer) {
                return url(route('customer.password.reset', [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ], false));
            }

            return url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
        });
    }
}
