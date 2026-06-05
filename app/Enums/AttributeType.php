<?php

namespace App\Enums;

enum AttributeType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Select = 'select';
    case Multiselect = 'multiselect';
    case Boolean = 'boolean';
    case Decimal = 'decimal';
    case Price = 'price';

    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::Multiselect], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Teks',
            self::Textarea => 'Textarea',
            self::Select => 'Pilihan tunggal',
            self::Multiselect => 'Pilihan ganda',
            self::Boolean => 'Ya/Tidak',
            self::Decimal => 'Angka desimal',
            self::Price => 'Harga',
        };
    }
}
