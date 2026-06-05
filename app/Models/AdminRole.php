<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminRole extends Model
{
    public const PERMISSIONS = [
        'products.view',
        'products.manage',
        'orders.view',
        'orders.manage',
        'customers.view',
        'settings.manage',
        'cms.manage',
        'inventory.manage',
        'promotions.manage',
        'reports.view',
        'staff.manage',
    ];

    protected $fillable = ['name', 'description', 'permissions'];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'admin_role_id');
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        if (in_array('*', $permissions, true)) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }
}
