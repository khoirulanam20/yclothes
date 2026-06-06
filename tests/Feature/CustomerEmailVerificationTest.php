<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CustomerEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_verification_url_uses_customer_route(): void
    {
        Notification::fake();

        $customer = Customer::factory()->unverified()->create();
        $customer->sendEmailVerificationNotification();

        Notification::assertSentTo($customer, VerifyEmail::class, function (VerifyEmail $notification, array $channels) use ($customer) {
            $mail = $notification->toMail($customer);

            return str_contains($mail->actionUrl, '/account/verify-email/');
        });
    }

    public function test_signed_customer_verification_route_is_registered(): void
    {
        $customer = Customer::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'customer.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $customer->getKey(),
                'hash' => sha1($customer->getEmailForVerification()),
            ],
        );

        $this->get($url)->assertRedirect();
    }
}
