<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Números sequenciais por ano. O advisory lock é transacional: dois
 * vendedores não recebem o mesmo número, mesmo em requisições concorrentes.
 */
final class Sequence
{
    public static function nextQuotationNumber(int $year): string
    {
        return self::next('quotations', 'Q-'.$year.'-%', 'Q-', $year);
    }

    public static function nextOrderNumber(int $year): string
    {
        return self::next('orders', $year.'-%', '', $year);
    }

    private static function next(string $table, string $pattern, string $prefix, int $year): string
    {
        DB::select('select pg_advisory_xact_lock(hashtext(?))', ['orca:'.$table.':'.$year]);

        $last = DB::table($table)->where('number', 'like', $pattern)->orderByDesc('number')->value('number');
        $sequence = $last === null ? 1 : ((int) substr((string) $last, -4)) + 1;

        return $prefix.$year.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
