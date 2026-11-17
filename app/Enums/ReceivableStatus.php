<?php

namespace App\Enums;

enum ReceivableStatus: string
{
    case Open = 'open';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberta',
            self::Partial => 'Parcial',
            self::Paid => 'Paga',
            self::Overdue => 'Vencida',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open => 'neutral',
            self::Partial => 'amber',
            self::Paid => 'pine',
            self::Overdue => 'rust',
        };
    }
}
