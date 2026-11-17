<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Open = 'open';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberto',
            self::Fulfilled => 'Concluído',
            self::Cancelled => 'Cancelado',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open => 'clay',
            self::Fulfilled => 'pine',
            self::Cancelled => 'neutral',
        };
    }
}
