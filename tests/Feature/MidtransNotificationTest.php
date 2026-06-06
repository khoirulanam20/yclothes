<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MidtransNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_midtrans_notification_rejects_invalid_payload(): void
    {
        $response = $this->post('/midtrans/notification', [
            'invalid' => true,
        ]);

        $response->assertStatus(400);
    }
}
