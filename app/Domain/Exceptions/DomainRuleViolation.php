<?php

namespace App\Domain\Exceptions;

use RuntimeException;

/**
 * Violação de regra de domínio. O status HTTP acompanha o erro para que
 * a mesma regra valha no painel, no endpoint e nos testes.
 */
final class DomainRuleViolation extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422)
    {
        parent::__construct($message);
    }

    public static function conflict(string $message): self
    {
        return new self($message, 409);
    }

    public static function unprocessable(string $message): self
    {
        return new self($message, 422);
    }
}
