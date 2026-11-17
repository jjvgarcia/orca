<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Pix = 'pix';
    case Cash = 'cash';
    case Transfer = 'transfer';
    case Card = 'card';

    public function label(): string
    {
        return match ($this) {
            self::Pix => 'PIX',
            self::Cash => 'Dinheiro',
            self::Transfer => 'Transferência',
            self::Card => 'Cartão',
        };
    }
}
