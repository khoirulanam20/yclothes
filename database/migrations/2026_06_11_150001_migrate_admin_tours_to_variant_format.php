<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** @return list<string> */
    private function allVariants(): array
    {
        return config('admin-tours.variants', []);
    }

    public function up(): void
    {
        $allVariants = $this->allVariants();

        User::query()
            ->whereNotNull('admin_tours_completed')
            ->each(function (User $user) use ($allVariants) {
                $completed = $user->admin_tours_completed;

                if ($completed === null || $completed === []) {
                    return;
                }

                if ($this->isLegacyList($completed)) {
                    $mapped = [];
                    foreach ($completed as $tourKey) {
                        if (is_string($tourKey)) {
                            $mapped[$tourKey] = $allVariants;
                        }
                    }

                    $user->forceFill(['admin_tours_completed' => $mapped])->save();
                }
            });
    }

    public function down(): void
    {
        User::query()
            ->whereNotNull('admin_tours_completed')
            ->each(function (User $user) {
                $completed = $user->admin_tours_completed;

                if (! is_array($completed) || $this->isLegacyList($completed)) {
                    return;
                }

                $keys = array_keys($completed);
                $user->forceFill(['admin_tours_completed' => $keys])->save();
            });
    }

    /** @param  array<mixed>  $completed */
    private function isLegacyList(array $completed): bool
    {
        return array_is_list($completed);
    }
};
