<?php

namespace App\Services\BankStatement;

/**
 * Contract for parsing a bank e-statement into normalized lines.
 *
 * Each parser returns an array of normalized lines:
 * [
 *   ['date' => 'Y-m-d', 'description' => string, 'reference' => string|null,
 *    'type' => 'debit'|'credit', 'amount' => float],
 *   ...
 * ]
 */
interface BankStatementParser
{
    public function bank(): string;

    public function parse(string $content): array;
}
