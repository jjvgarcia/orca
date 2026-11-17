<?php

use App\Domain\Money;

it('divide sem float e joga o resto na primeira parcela', function (int $total, int $parts, array $expected) {
    $split = Money::fromCents($total)->split($parts);

    expect(array_map(fn (Money $money) => $money->cents, $split))->toBe($expected)
        ->and(array_sum($expected))->toBe($total);
})->with([
    '1000,00 em 3x' => [100_000, 3, [33_334, 33_333, 33_333]],
    '1000,00 em 7x' => [100_000, 7, [14_290, 14_285, 14_285, 14_285, 14_285, 14_285, 14_285]],
    '1000,00 em 11x' => [100_000, 11, [9_100, 9_090, 9_090, 9_090, 9_090, 9_090, 9_090, 9_090, 9_090, 9_090, 9_090]],
    '1 centavo em 3x' => [1, 3, [1, 0, 0]],
]);

it('formata centavos sem float', function (int $cents, string $expected) {
    expect(Money::fromCents($cents)->format())->toBe($expected);
})->with([
    [123_456, 'R$ 1.234,56'],
    [100, 'R$ 1,00'],
    [0, 'R$ 0,00'],
    [-100, '-R$ 1,00'],
    [123_456_789, 'R$ 1.234.567,89'],
]);

it('lê valores digitados em pt-BR', function (string $input, int $expected) {
    expect(Money::fromDecimalString($input)->cents)->toBe($expected);
})->with([
    ['1.234,56', 123_456],
    ['1234,56', 123_456],
    ['1234.56', 123_456],
    ['12', 1_200],
    ['0,5', 50],
    ['R$ 2.500,00', 250_000],
]);

it('recusa valor digitado inválido', function (string $input) {
    Money::fromDecimalString($input);
})->with(['', 'abc', ',', '-'])->throws(InvalidArgumentException::class);

it('multiplica quantidade mantendo inteiro', function () {
    $total = Money::fromCents(18_000)->multiply(6);

    expect($total->cents)->toBe(108_000);
});
