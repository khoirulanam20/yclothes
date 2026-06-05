<?php

namespace App\Enums;

enum BadgePreset: string
{
    case None = 'none';
    case Sale = 'sale';
    case New = 'new';
    case Hot = 'hot';
    case Custom = 'custom';

    public function defaultLabel(): ?string
    {
        return match ($this) {
            self::Sale => 'Sale',
            self::New => 'New',
            self::Hot => 'Hot',
            self::None, self::Custom => null,
        };
    }

    public function defaultColor(): ?string
    {
        return match ($this) {
            self::Sale => '#DC2626',
            self::New => '#16A34A',
            self::Hot => '#EA580C',
            self::None => null,
            self::Custom => '#6366F1',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::None => 'Tanpa badge',
            self::Sale => 'Sale',
            self::New => 'New',
            self::Hot => 'Hot',
            self::Custom => 'Kustom',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
