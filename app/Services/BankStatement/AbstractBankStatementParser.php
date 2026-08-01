<?php

namespace App\Services\BankStatement;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Base implementation for bank statement CSV parsers.
 *
 * Handles header detection, delimiter sniffing, Indonesian/US number
 * normalization and date parsing. Concrete parsers only need to declare
 * the bank name and their header aliases.
 */
abstract class AbstractBankStatementParser implements BankStatementParser
{
    protected string $bankName;

    /**
     * Column aliases mapped to canonical columns. Values are matched against
     * normalized (lowercased, non-alphanumeric stripped) CSV headers.
     */
    protected array $columnAliases = [
        'date' => ['tanggal', 'date', 'tgl', 'tanggal transaksi'],
        'description' => ['keterangan', 'description', 'uraian', 'keterangan transaksi', 'transaksi'],
        'debit' => ['debet', 'debit', 'mutasi debet', 'mutasi debit', 'db', 'pengeluaran'],
        'credit' => ['kredit', 'credit', 'mutasi kredit', 'cr', 'pemasukan'],
        'reference' => ['berita', 'referensi', 'reference', 'no', 'nomor', 'noref'],
        'saldo' => ['saldo', 'balance', 'sisa saldo'],
    ];

    public function bank(): string
    {
        return $this->bankName;
    }

    public function parse(string $content): array
    {
        $rows = $this->readCsvRows($content);

        if (empty($rows)) {
            return [];
        }

        $headerIndex = $this->findHeaderRow($rows);

        if ($headerIndex === null) {
            throw new InvalidArgumentException(
                'Could not detect the statement header row. Make sure the file is a valid bank e-statement.'
            );
        }

        $headers = array_map(fn ($col) => $this->normalizeHeader($col), $rows[$headerIndex]);

        $lines = [];

        foreach (array_slice($rows, $headerIndex + 1) as $row) {
            $line = $this->normalizeRow($row, $headers);

            if ($line !== null) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    protected function readCsvRows(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        $lines = array_values(array_filter(explode("\n", $content), fn ($line) => trim($line) !== ''));

        if (empty($lines)) {
            return [];
        }

        $delimiter = $this->detectDelimiter($lines);

        $rows = [];

        foreach ($lines as $line) {
            $row = str_getcsv($line, $delimiter);

            if (count($row) === 1 && trim($row[0] ?? '') === '') {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    protected function detectDelimiter(array $lines): string
    {
        $commas = 0;
        $semicolons = 0;

        foreach (array_slice($lines, 0, 5) as $line) {
            $commas += substr_count($line, ',');
            $semicolons += substr_count($line, ';');
        }

        return $semicolons > $commas ? ';' : ',';
    }

    protected function findHeaderRow(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            $headers = array_map(fn ($col) => $this->normalizeHeader($col), $row);

            $hasDate = $this->columnIndex($headers, 'date') !== null;
            $hasAmount = $this->columnIndex($headers, 'debit') !== null
                || $this->columnIndex($headers, 'credit') !== null;

            if ($hasDate && $hasAmount) {
                return $index;
            }
        }

        return null;
    }

    protected function normalizeRow(array $row, array $headers): ?array
    {
        $dateIndex = $this->columnIndex($headers, 'date');
        $descriptionIndex = $this->columnIndex($headers, 'description');
        $debitIndex = $this->columnIndex($headers, 'debit');
        $creditIndex = $this->columnIndex($headers, 'credit');
        $referenceIndex = $this->columnIndex($headers, 'reference');

        $date = $dateIndex !== null
            ? $this->parseDate($row[$dateIndex] ?? null)
            : null;

        if ($date === null) {
            return null;
        }

        $debit = $debitIndex !== null ? $this->parseAmount($row[$debitIndex] ?? '') : null;
        $credit = $creditIndex !== null ? $this->parseAmount($row[$creditIndex] ?? '') : null;

        if ($debit === null && $credit === null) {
            return null;
        }

        $type = $debit !== null && (float) $debit != 0 ? 'debit' : 'credit';
        $amount = $type === 'debit' ? (float) $debit : (float) $credit;

        if (round($amount, 2) == 0) {
            return null;
        }

        $description = $descriptionIndex !== null
            ? trim((string) ($row[$descriptionIndex] ?? ''))
            : '';

        $reference = $referenceIndex !== null
            ? trim((string) ($row[$referenceIndex] ?? ''))
            : null;

        return [
            'date' => $date->toDateString(),
            'description' => $description !== '' ? $description : 'Bank statement line',
            'reference' => $reference !== '' ? $reference : null,
            'type' => $type,
            'amount' => round($amount, 2),
        ];
    }

    protected function columnIndex(array $headers, string $canonical): ?int
    {
        foreach ($this->columnAliases[$canonical] ?? [] as $alias) {
            $aliasNormalized = $this->normalizeHeader($alias);
            $index = array_search($aliasNormalized, $headers, true);

            if ($index !== false) {
                return $index;
            }
        }

        return null;
    }

    protected function normalizeHeader(string $column): string
    {
        $column = trim($column);
        $column = trim($column, "\"'");
        $column = mb_strtolower($column);

        return preg_replace('/[^a-z0-9]/', '', $column);
    }

    protected function parseDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        foreach (['d/m/Y', 'd-m-Y', 'd/m/y', 'Y-m-d', 'Y/m/d', 'm/d/Y', 'd/m/Y H:i:s', 'Y-m-d H:i:s', 'Y-m-d\TH:i:s'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);

                if ($date !== false) {
                    return $date->startOfDay();
                }
            } catch (\Exception $e) {
                // try next format
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function parseAmount($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $clean = trim((string) $value);
        $clean = trim($clean, "\"'");
        $clean = str_replace(["\xc2\xa0", ' '], '', $clean);

        if ($clean === '' || $clean === '-' || $clean === '0') {
            return null;
        }

        if (preg_match('/^-?[0-9.,]+$/', $clean) !== 1) {
            return null;
        }

        $negative = str_starts_with($clean, '-');
        $clean = ltrim($clean, '-');

        $lastDot = strrpos($clean, '.');
        $lastComma = strrpos($clean, ',');

        if ($lastComma !== false && $lastComma > $lastDot) {
            // Comma is the decimal separator (e.g. 1.234.567,89)
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif ($lastComma === false && $lastDot !== false) {
            // Only dots present. Treat the last dot as decimal only when there
            // are exactly two digits after it (e.g. 1000000.00), otherwise it is
            // a thousands separator (e.g. 1.234.567).
            $afterDot = strlen($clean) - $lastDot - 1;
            if ($afterDot !== 2) {
                $clean = str_replace('.', '', $clean);
            }
        } elseif ($lastComma !== false && $lastDot === false) {
            // Only commas present. Same rule as dots.
            $afterComma = strlen($clean) - $lastComma - 1;
            if ($afterComma !== 2) {
                $clean = str_replace(',', '', $clean);
            } else {
                $clean = str_replace(',', '.', $clean);
            }
        }

        if (! is_numeric($clean)) {
            return null;
        }

        $amount = (float) $clean;

        return $negative ? -$amount : $amount;
    }
}
