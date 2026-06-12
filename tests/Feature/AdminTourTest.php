<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AdminTourTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_auth_includes_completed_tour_variants(): void
    {
        $admin = User::where('is_admin', true)->first();
        $admin->update([
            'admin_tours_completed' => [
                'dashboard' => ['index'],
                'orders' => ['index', 'show'],
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.admin.completedTourVariants', [
                    'dashboard' => ['index'],
                    'orders' => ['index', 'show'],
                ])
            );
    }

    public function test_admin_can_mark_tour_variant_complete(): void
    {
        $admin = User::where('is_admin', true)->first();

        $this->actingAs($admin)
            ->postJson(route('admin.tours.complete', ['tourKey' => 'products']), [
                'variant' => 'index',
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'completedTourVariants' => [
                    'products' => ['index'],
                ],
            ]);

        $this->assertTrue($admin->fresh()->hasCompletedAdminTourVariant('products', 'index'));
        $this->assertFalse($admin->fresh()->hasCompletedAdminTourVariant('products', 'create'));
    }

    public function test_mark_complete_is_idempotent_per_variant(): void
    {
        $admin = User::where('is_admin', true)->first();
        $admin->markAdminTourVariantCompleted('orders', 'index');

        $this->actingAs($admin)
            ->postJson(route('admin.tours.complete', ['tourKey' => 'orders']), [
                'variant' => 'index',
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'completedTourVariants' => [
                    'orders' => ['index'],
                ],
            ]);
    }

    public function test_invalid_tour_key_returns_validation_error(): void
    {
        $admin = User::where('is_admin', true)->first();

        $this->actingAs($admin)
            ->postJson(route('admin.tours.complete', ['tourKey' => 'invalid-key']), [
                'variant' => 'index',
            ])
            ->assertUnprocessable();
    }

    public function test_invalid_variant_returns_validation_error(): void
    {
        $admin = User::where('is_admin', true)->first();

        $this->actingAs($admin)
            ->postJson(route('admin.tours.complete', ['tourKey' => 'products']), [
                'variant' => 'invalid-variant',
            ])
            ->assertUnprocessable();
    }

    public function test_missing_variant_returns_validation_error(): void
    {
        $admin = User::where('is_admin', true)->first();

        $this->actingAs($admin)
            ->postJson(route('admin.tours.complete', ['tourKey' => 'products']))
            ->assertUnprocessable();
    }

    public function test_other_admin_not_affected(): void
    {
        $admin = User::where('is_admin', true)->first();
        $other = User::factory()->create([
            'is_admin' => true,
            'admin_role_id' => null,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.tours.complete', ['tourKey' => 'dashboard']), [
                'variant' => 'index',
            ])
            ->assertOk();

        $this->assertTrue($admin->fresh()->hasCompletedAdminTourVariant('dashboard', 'index'));
        $this->assertFalse($other->fresh()->hasCompletedAdminTourVariant('dashboard', 'index'));
    }

    public function test_guest_cannot_mark_tour_complete(): void
    {
        $this->postJson(route('admin.tours.complete', ['tourKey' => 'dashboard']), [
            'variant' => 'index',
        ])
            ->assertUnauthorized();
    }

    public function test_legacy_tour_format_is_migrated_to_all_variants(): void
    {
        $migrationPath = 'database/migrations/2026_06_11_150001_migrate_admin_tours_to_variant_format.php';

        Artisan::call('migrate:rollback', [
            '--path' => $migrationPath,
            '--force' => true,
        ]);

        $admin = User::factory()->create([
            'is_admin' => true,
            'admin_role_id' => null,
            'admin_tours_completed' => ['products', 'orders'],
        ]);

        Artisan::call('migrate', [
            '--path' => $migrationPath,
            '--force' => true,
        ]);

        $progress = $admin->fresh()->adminTourProgress();
        $allVariants = config('admin-tours.variants');

        $this->assertEquals($allVariants, $progress['products']);
        $this->assertEquals($allVariants, $progress['orders']);
    }
}
