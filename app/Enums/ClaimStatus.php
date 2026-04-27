<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case Reviewing = 'на розгляді';
    case Approved = 'схвалено';
    case Paid = 'виплачено';
    case Rejected = 'відхилено';

    public function label(): string
    {
        return match ($this) {
            self::Reviewing => 'На розгляді',
            self::Approved => 'Схвалено',
            self::Paid => 'Виплачено',
            self::Rejected => 'Відхилено',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Reviewing => 'warning',
            self::Approved => 'info',
            self::Paid => 'success',
            self::Rejected => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->toArray();
    }

    public static function values(): array
    {
        return array_map(
            fn (self $status) => $status->value,
            self::cases()
        );
    }

    public static function orderedValues(): array
    {
        return [
            self::Reviewing->value,
            self::Approved->value,
            self::Rejected->value,
            self::Paid->value,
        ];
    }

    public static function normalize(mixed $status): string
    {
        return $status instanceof self
            ? $status->value
            : (string) $status;
    }
}