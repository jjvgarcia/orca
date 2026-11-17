<?php

namespace App\Support;

use App\Models\Quotation;
use Illuminate\Support\Facades\URL;

/**
 * O link do cliente não usa senha nem tabela de tokens: é uma URL assinada
 * com validade de 30 dias. A decisão é terminal, então o link deixa de
 * permitir ação depois da primeira resposta.
 */
final class PublicLink
{
    public const VALID_DAYS = 30;

    public static function to(Quotation $quotation): string
    {
        return URL::temporarySignedRoute('quotation.public', self::expiresAt(), ['quotation' => $quotation->id]);
    }

    public static function decision(Quotation $quotation): string
    {
        return URL::temporarySignedRoute('quotation.decision', self::expiresAt(), ['quotation' => $quotation->id]);
    }

    public static function pdf(Quotation $quotation): string
    {
        return URL::temporarySignedRoute('quotation.pdf', self::expiresAt(), ['quotation' => $quotation->id]);
    }

    private static function expiresAt(): \DateTimeInterface
    {
        return now()->addDays(self::VALID_DAYS);
    }
}
