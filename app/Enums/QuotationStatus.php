<?php

namespace App\Enums;

enum QuotationStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Sent => 'Enviado',
            self::Approved => 'Aprovado',
            self::Rejected => 'Recusado',
            self::Expired => 'Expirado',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Sent => 'clay',
            self::Approved => 'pine',
            self::Rejected => 'rust',
            self::Expired => 'amber',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected], true);
    }
}
