<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class AdminTourService
{
    /** @return list<string> */
    public function allowedKeys(): array
    {
        return config('admin-tours.keys', []);
    }

    /** @return list<string> */
    public function allowedVariants(): array
    {
        return config('admin-tours.variants', []);
    }

    public function isValidKey(string $key): bool
    {
        return in_array($key, $this->allowedKeys(), true);
    }

    public function isValidVariant(string $variant): bool
    {
        return in_array($variant, $this->allowedVariants(), true);
    }

    /**
     * @return array{ok: true, completedTourVariants: array<string, list<string>>}
     */
    public function markCompleted(User $user, string $tourKey, string $variant): array
    {
        if (! $this->isValidKey($tourKey)) {
            throw ValidationException::withMessages([
                'tourKey' => ['Tour tidak valid.'],
            ]);
        }

        if (! $this->isValidVariant($variant)) {
            throw ValidationException::withMessages([
                'variant' => ['Variant tour tidak valid.'],
            ]);
        }

        $user->markAdminTourVariantCompleted($tourKey, $variant);

        return [
            'ok' => true,
            'completedTourVariants' => $user->fresh()->adminTourProgress(),
        ];
    }
}
