<?php

namespace App\Services\BankStatement;

use InvalidArgumentException;

class BankStatementParserFactory
{
    public static function make(string $bank): BankStatementParser
    {
        return match (strtolower(trim($bank))) {
            'bca' => new BcaStatementParser,
            'mandiri' => new MandiriStatementParser,
            'bni' => new BniStatementParser,
            'bri' => new BriStatementParser,
            default => throw new InvalidArgumentException("Unsupported bank statement format: {$bank}"),
        };
    }

    public static function supportedBanks(): array
    {
        return [
            'bca' => 'BCA',
            'mandiri' => 'Mandiri',
            'bni' => 'BNI',
            'bri' => 'BRI',
        ];
    }
}
