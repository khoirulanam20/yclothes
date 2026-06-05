<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnPolicy extends Model
{
    protected $fillable = [
        'default_return_window_days', 'default_warranty_days', 'return_reasons', 'policy_text',
    ];

    protected function casts(): array
    {
        return [
            'return_reasons' => 'array',
            'default_return_window_days' => 'integer',
            'default_warranty_days' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'default_return_window_days' => 7,
            'default_warranty_days' => 30,
            'return_reasons' => [
                'Barang rusak / cacat',
                'Barang tidak sesuai pesanan',
                'Ukuran / warna salah',
                'Barang tidak lengkap',
                'Lainnya',
            ],
            'policy_text' => 'Retur dapat diajukan dalam 7 hari setelah pesanan selesai. Barang harus dalam kondisi belum dipakai dengan kemasan asli.',
        ]);
    }
}
