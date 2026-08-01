<?php

namespace Tests\Unit\Services;

use App\Services\BankStatement\BankStatementParserFactory;
use Tests\TestCase;

class BankStatementParserTest extends TestCase
{
    public function test_factory_supports_expected_banks(): void
    {
        $this->assertEquals(
            ['bca' => 'BCA', 'mandiri' => 'Mandiri', 'bni' => 'BNI', 'bri' => 'BRI'],
            BankStatementParserFactory::supportedBanks()
        );
    }

    public function test_factory_rejects_unknown_bank(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        BankStatementParserFactory::make('bca_unknown');
    }

    public function test_parses_bca_comma_separated_with_indonesian_numbers(): void
    {
        $csv = <<<'CSV'
Tanggal;Keterangan;Debet;Kredit;Saldo
"25/05/2025";"TRSF EDC 1234567890";;1.250.000,00;12.500.000,00
"26/05/2025";"LAIN-LAIN AUTO DEBIT";500.000,00;;12.000.000,00
"27/05/2025";"BIAYA ADMIN BULANAN";25.000,00;;11.975.000,00
CSV;

        $lines = BankStatementParserFactory::make('bca')->parse($csv);

        $this->assertCount(3, $lines);

        $this->assertEquals('2025-05-25', $lines[0]['date']);
        $this->assertEquals('credit', $lines[0]['type']);
        $this->assertEquals(1250000.0, $lines[0]['amount']);
        $this->assertEquals('TRSF EDC 1234567890', $lines[0]['description']);

        $this->assertEquals('debit', $lines[1]['type']);
        $this->assertEquals(500000.0, $lines[1]['amount']);

        $this->assertEquals(25000.0, $lines[2]['amount']);
    }

    public function test_parses_mandiri_semicolon_separated_with_header_preamble(): void
    {
        $csv = <<<'CSV'
"e-Statement Rekening Giro"
"PT TEST INDONESIA"
"Tanggal","Keterangan","Cab","Berita","Mutasi Debet","Mutasi Kredit","Saldo"
"2025-05-01","TRANSFER MASUK","001","TRANSFER DARI 1234",,"5.000.000,00","50.000.000,00"
"2025-05-02","BIAYA ADMIN","001","ADM",15.000,,"49.985.000,00"
CSV;

        $lines = BankStatementParserFactory::make('mandiri')->parse($csv);

        $this->assertCount(2, $lines);

        $this->assertEquals('credit', $lines[0]['type']);
        $this->assertEquals(5000000.0, $lines[0]['amount']);
        $this->assertEquals('TRANSFER MASUK', $lines[0]['description']);

        $this->assertEquals('debit', $lines[1]['type']);
        $this->assertEquals(15000.0, $lines[1]['amount']);
    }

    public function test_parses_plain_dot_decimal_amounts(): void
    {
        $csv = <<<'CSV'
Tanggal;Keterangan;Debet;Kredit
01/06/2025;SALARY PAYROLL;2500000.00;
02/06/2025;CUSTOMER PAYMENT;;1000000.00
CSV;

        $lines = BankStatementParserFactory::make('bni')->parse($csv);

        $this->assertCount(2, $lines);
        $this->assertEquals(2500000.0, $lines[0]['amount']);
        $this->assertEquals(1000000.0, $lines[1]['amount']);
    }

    public function test_parses_us_date_format(): void
    {
        $csv = <<<'CSV'
Date,Description,Debit,Credit
06/01/2025,PAYMENT INCOMING,,90000
CSV;

        $lines = BankStatementParserFactory::make('bri')->parse($csv);

        $this->assertCount(1, $lines);
        $this->assertEquals('credit', $lines[0]['type']);
        $this->assertEquals(90000.0, $lines[0]['amount']);
    }

    public function test_skips_zero_amount_lines(): void
    {
        $csv = <<<'CSV'
Tanggal;Keterangan;Debet;Kredit
01/06/2025;NON TRANSACTION;0;0
02/06/2025;REAL TRANSACTION;50000;
CSV;

        $lines = BankStatementParserFactory::make('bca')->parse($csv);

        $this->assertCount(1, $lines);
        $this->assertEquals('REAL TRANSACTION', $lines[0]['description']);
    }

    public function test_throws_when_no_header_row_found(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        BankStatementParserFactory::make('bca')->parse("just some random text\nno headers here");
    }

    public function test_returns_empty_for_empty_content(): void
    {
        $lines = BankStatementParserFactory::make('bca')->parse('');

        $this->assertSame([], $lines);
    }
}
