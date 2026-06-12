<?php

use App\Support\WilayahCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $codeColumns = [
        'customer_addresses' => ['province_code', 'regency_code', 'district_code', 'village_code'],
        'orders' => ['province_code', 'regency_code', 'district_code', 'village_code'],
        'shipping_costs' => ['regency_code'],
    ];

    public function up(): void
    {
        foreach ($this->codeColumns as $table => $columns) {
            if (! $this->tableExists($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! $this->columnExists($table, $column)) {
                    continue;
                }

                DB::table($table)
                    ->whereNotNull($column)
                    ->where($column, '!=', '')
                    ->orderBy('id')
                    ->chunkById(200, function ($rows) use ($table, $column) {
                        foreach ($rows as $row) {
                            $normalized = WilayahCode::normalize($row->{$column});
                            if ($normalized !== null && $normalized !== $row->{$column}) {
                                DB::table($table)->where('id', $row->id)->update([$column => $normalized]);
                            }
                        }
                    });
            }
        }
    }

    public function down(): void
    {
        // Irreversible — old emsifa format cannot be reliably restored.
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    private function columnExists(string $table, string $column): bool
    {
        return DB::getSchemaBuilder()->hasColumn($table, $column);
    }
};
