<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_admin', 'admin_role_id', 'admin_tours_completed'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'admin_tours_completed' => 'array',
        ];
    }

    public function adminRole(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'admin_role_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_admin && ! $this->admin_role_id;
    }

    public function canAccessAdmin(): bool
    {
        return $this->is_admin || $this->admin_role_id !== null;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->adminRole?->hasPermission($permission) ?? false;
    }

    /** @return array<string, list<string>> */
    public function adminTourProgress(): array
    {
        $completed = $this->admin_tours_completed ?? [];

        if (! is_array($completed)) {
            return [];
        }

        if (array_is_list($completed)) {
            return [];
        }

        /** @var array<string, list<string>> $completed */
        return $completed;
    }

    public function hasCompletedAdminTourVariant(string $tourKey, string $variant): bool
    {
        $variants = $this->adminTourProgress()[$tourKey] ?? [];

        return in_array($variant, $variants, true);
    }

    public function markAdminTourVariantCompleted(string $tourKey, string $variant): void
    {
        $progress = $this->adminTourProgress();
        $variants = $progress[$tourKey] ?? [];

        if (in_array($variant, $variants, true)) {
            return;
        }

        $variants[] = $variant;
        $progress[$tourKey] = $variants;

        $this->forceFill(['admin_tours_completed' => $progress])->save();
    }
}
