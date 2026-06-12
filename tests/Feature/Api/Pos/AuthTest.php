<?php

namespace Tests\Feature\Api\Pos;

use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthTest extends PosApiTestCase
{
    public function test_pos_login_returns_token_for_authorized_admin(): void
    {
        $response = $this->postJson('/api/pos/login', [
            'email' => $this->posUser->email,
            'password' => 'admin123',
            'device_name' => 'terminal-1',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.email', $this->posUser->email)
            ->assertJsonStructure(['data' => ['token', 'user' => ['permissions']]]);
    }

    public function test_pos_login_rejects_invalid_credentials(): void
    {
        $this->postJson('/api/pos/login', [
            'email' => $this->posUser->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    public function test_pos_login_rejects_admin_without_pos_permission(): void
    {
        $role = AdminRole::query()->create([
            'name' => 'Tanpa POS',
            'permissions' => ['orders.view'],
        ]);

        $user = User::query()->create([
            'name' => 'Staff Non POS',
            'email' => 'nonpos@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'admin_role_id' => $role->id,
        ]);

        $this->postJson('/api/pos/login', [
            'email' => $user->email,
            'password' => 'admin123',
        ])->assertUnprocessable();
    }

    public function test_authenticated_pos_user_can_fetch_profile(): void
    {
        $this->withHeaders($this->posHeaders())
            ->getJson('/api/pos/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $this->posUser->id);
    }

    public function test_authenticated_pos_user_can_logout(): void
    {
        $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/logout')
            ->assertOk()
            ->assertJsonPath('data.loggedOut', true);
    }
}
