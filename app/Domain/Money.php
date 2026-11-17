<?php

namespace App\Domain;

use InvalidArgumentException;
use Stringable;

/**
 * Dinheiro em centavos. Nenhum float participa de cálculo monetário:
 * toda divisão é inteira e o resto vai para a primeira parcela.
 */
final readonly class Money implements Stringable
{
    public function __construct(public int $cents) {}

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * Aceita "1.234,56", "1234,56", "1234.56" e "1234". Sem float: só dígitos.
     */
    public static function fromDecimalString(string $value): self
    {
        $normalized = preg_replace('/[^\d,.-]/', '', trim($value)) ?? '';

        if ($normalized === '' || $normalized === '-' || $normalized === '.' || $normalized === ',') {
            throw new InvalidArgumentException('Valor monetário inválido.');
        }

        $negative = str_starts_with($normalized, '-');
        $normalized = ltrim($normalized, '-');

        if (str_contains($normalized, ',')) {
            $normalized = str_replace('.', '', $normalized);
            [$reais, $centavos] = array_pad(explode(',', $normalized, 2), 2, '0');
        } elseif (substr_count($normalized, '.') === 1 && strlen(substr(strrchr($normalized, '.'), 1)) <= 2) {
            [$reais, $centavos] = explode('.', $normalized, 2);
        } else {
            $reais = str_replace('.', '', $normalized);
            $centavos = '0';
        }

        if ($reais === '') {
            $reais = '0';
        }

        if (! ctype_digit($reais) || ! ctype_digit($centavos)) {
            throw new InvalidArgumentException('Valor monetário inválido.');
        }

        $centavos = str_pad($centavos, 3, '0');
        $extra = (int) substr($centavos, 2);
        $centavos = substr($centavos, 0, 2);

        $cents = ((int) $reais) * 100 + (int) $centavos + ($extra >= 5 ? 1 : 0);

        return new self($negative ? -$cents : $cents);
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function multiply(int $factor): self
    {
        return new self($this->cents * $factor);
    }

    /**
     * Formato de edição: 1.234,56 (sem símbolo).
     */
    public function toDecimalString(): string
    {
        $absolute = abs($this->cents);
        $reais = self::groupThousands(intdiv($absolute, 100));
        $centavos = str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);

        return ($this->cents < 0 ? '-' : '').$reais.','.$centavos;
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }

    /**
     * Divisão inteira com o resto na primeira parcela.
     * 1000,00 em 3x => 333,34 / 333,33 / 333,33.
     *
     * @return list<self>
     */
    public function split(int $installments): array
    {
        if ($installments < 1) {
            throw new InvalidArgumentException('O número de parcelas deve ser maior que zero.');
        }

        $base = intdiv($this->cents, $installments);
        $remainder = $this->cents - ($base * $installments);

        $parts = array_fill(0, $installments, new self($base));
        $parts[0] = new self($base + $remainder);

        return $parts;
    }

    public function format(): string
    {
        $absolute = abs($this->cents);
        $reais = self::groupThousands(intdiv($absolute, 100));
        $centavos = str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);

        return ($this->cents < 0 ? '-' : '').'R$ '.$reais.','.$centavos;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private static function groupThousands(int $value): string
    {
        $digits = strrev((string) $value);
        $groups = str_split($digits, 3);

        return strrev(implode('.', $groups));
    }
}
