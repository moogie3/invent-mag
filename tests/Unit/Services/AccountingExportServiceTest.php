<?php

namespace Tests\Unit\Services;

use App\Services\AccountingExportService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class AccountingExportServiceTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected AccountingExportService $exporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->exporter = new AccountingExportService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_export_to_csv_returns_streamed_response()
    {
        $response = $this->exporter->exportToCsv(function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Header 1', 'Header 2']);
            fclose($file);
        }, 'test.csv');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_export_to_csv_has_correct_content_type()
    {
        $response = $this->exporter->exportToCsv(function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Test']);
            fclose($file);
        }, 'test.csv');

        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_export_to_csv_has_attachment_disposition()
    {
        $response = $this->exporter->exportToCsv(function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Test']);
            fclose($file);
        }, 'chart-of-accounts.csv');

        $this->assertStringContainsString('chart-of-accounts.csv', $response->headers->get('Content-Disposition'));
    }

    public function test_export_to_csv_has_no_cache_headers()
    {
        $response = $this->exporter->exportToCsv(function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Test']);
            fclose($file);
        }, 'test.csv');

        $this->assertEquals('no-cache', $response->headers->get('Pragma'));
    }
}
