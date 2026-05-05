<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupTenant extends Command
{
    protected $signature = 'backup:tenant {tenant_id : The tenant ID} {type : db_only or full} {filename : Backup filename}';

    protected $description = 'Create backup for a specific tenant';

    protected array $tablesWithTenantId = [
        'accounting_audit_logs',
        'accounts',
        'activity_logs',
        'categories',
        'currency_settings',
        'customer_credits',
        'customer_interactions',
        'customers',
        'journal_entries',
        'opening_balances',
        'payments',
        'pipeline_stages',
        'po',
        'po_items',
        'products',
        'product_warehouse',
        'purchase_return_items',
        'purchase_returns',
        'recurring_journal_entries',
        'sales',
        'sales_items',
        'sales_opportunities',
        'sales_opportunity_items',
        'sales_pipelines',
        'sales_return_items',
        'sales_returns',
        'stock_adjustments',
        'stock_opnames',
        'supplier_interactions',
        'suppliers',
        'taxes',
        'transactions',
        'units',
        'users',
        'warehouses',
    ];

    protected array $childTablesWithoutTenantId = [
        'stock_opname_items' => [
            'parent_table' => 'stock_opnames',
            'foreign_key' => 'stock_opname_id',
        ],
    ];

    protected function dumpTable(string $table, string $tempDir, string $mysqldumpPath, string $host, int $port, string $username, ?string $password, string $database, int $tenantId): void
    {
        $this->info("Dumping table: {$table}");
        
        $outputFile = "{$tempDir}/{$table}.sql";
        
        $cmd = [
            "{$mysqldumpPath}/mysqldump.exe",
            '-h', $host,
            '-P', $port,
            '-u', $username,
        ];
        
        if ($password) {
            $cmd[] = '-p' . $password;
        }
        
        $cmd[] = $database;
        $cmd[] = $table;
        $cmd[] = '--skip-lock-tables';
        $cmd[] = '--single-transaction';
        $cmd[] = '--quick';
        $cmd[] = '--where';
        $cmd[] = "tenant_id={$tenantId}";
        
        $process = Process::fromShellCommandLine(implode(' ', array_map('escapeshellarg', $cmd)));
        $process->setWorkingDirectory(base_path());
        $process->run();
        
        if ($process->isSuccessful()) {
            file_put_contents($outputFile, $process->getOutput());
        } else {
            $this->warn("Warning dumping {$table}: " . $process->getErrorOutput());
        }
    }

    protected function dumpTableAndAppend($fp, string $table, string $mysqldumpPath, string $host, int $port, string $username, ?string $password, string $database, int $tenantId): void
    {
        $this->info("Dumping table: {$table}");
        
        $cmd = [
            "{$mysqldumpPath}/mysqldump.exe",
            '-h', $host,
            '-P', $port,
            '-u', $username,
        ];
        
        if ($password) {
            $cmd[] = '-p' . $password;
        }
        
        $cmd[] = $database;
        $cmd[] = $table;
        $cmd[] = '--skip-lock-tables';
        $cmd[] = '--single-transaction';
        $cmd[] = '--quick';
        $cmd[] = '--add-drop-table';
        $cmd[] = '--where';
        $cmd[] = "tenant_id={$tenantId}";
        
        $process = Process::fromShellCommandLine(implode(' ', array_map('escapeshellarg', $cmd)));
        $process->setWorkingDirectory(base_path());
        $process->run();
        
        if ($process->isSuccessful()) {
            fwrite($fp, "-- Table: {$table}\n");
            fwrite($fp, $process->getOutput());
            fwrite($fp, "\n");
        }
    }

    protected function dumpChildTable(string $table, string $tempDir, string $mysqldumpPath, string $host, int $port, string $username, ?string $password, string $database, int $tenantId, array $config): void
    {
        $this->info("Dumping child table: {$table}");
        
        $outputFile = "{$tempDir}/{$table}.sql";
        
        $parentTable = $config['parent_table'];
        $foreignKey = $config['foreign_key'];
        
        $cmd = [
            "{$mysqldumpPath}/mysqldump.exe",
            '-h', $host,
            '-P', $port,
            '-u', $username,
        ];
        
        if ($password) {
            $cmd[] = '-p' . $password;
        }
        
        $cmd[] = $database;
        $cmd[] = $table;
        $cmd[] = '--skip-lock-tables';
        $cmd[] = '--single-transaction';
        $cmd[] = '--quick';
        $cmd[] = '--where';
        $cmd[] = "{$foreignKey} IN (SELECT id FROM {$parentTable} WHERE tenant_id={$tenantId})";
        
        $process = Process::fromShellCommandLine(implode(' ', array_map('escapeshellarg', $cmd)));
        $process->setWorkingDirectory(base_path());
        $process->run();
        
        if ($process->isSuccessful()) {
            file_put_contents($outputFile, $process->getOutput());
        }
    }

    protected function dumpChildTableAndAppend($fp, string $table, string $mysqldumpPath, string $host, int $port, string $username, ?string $password, string $database, int $tenantId, array $config): void
    {
        $this->info("Dumping child table: {$table}");
        
        $parentTable = $config['parent_table'];
        $foreignKey = $config['foreign_key'];
        
        $cmd = [
            "{$mysqldumpPath}/mysqldump.exe",
            '-h', $host,
            '-P', $port,
            '-u', $username,
        ];
        
        if ($password) {
            $cmd[] = '-p' . $password;
        }
        
        $cmd[] = $database;
        $cmd[] = $table;
        $cmd[] = '--skip-lock-tables';
        $cmd[] = '--single-transaction';
        $cmd[] = '--quick';
        $cmd[] = '--add-drop-table';
        $cmd[] = '--where';
        $cmd[] = "{$foreignKey} IN (SELECT id FROM {$parentTable} WHERE tenant_id={$tenantId})";
        
        $process = Process::fromShellCommandLine(implode(' ', array_map('escapeshellarg', $cmd)));
        $process->setWorkingDirectory(base_path());
        $process->run();
        
        if ($process->isSuccessful()) {
            fwrite($fp, "-- Table: {$table}\n");
            fwrite($fp, $process->getOutput());
            fwrite($fp, "\n");
        }
    }

    public function handle(): int
    {
        $tenantId = $this->argument('tenant_id');
        $type = $this->argument('type');
        $filename = $this->argument('filename');

        $this->info("Starting tenant-specific backup for tenant ID: {$tenantId}");

        // Get database configuration
        $dbConfig = config('database.connections.mysql');
        $database = $dbConfig['database'];
        $username = $dbConfig['username'];
        $password = $dbConfig['password'];
        $host = $dbConfig['host'];
        $port = $dbConfig['port'];
        $mysqldumpPath = $dbConfig['dump']['dump_binary_path'] ?? 'C:/xampp/mysql/bin';

        // Create temp directory for SQL files
        $tempDir = storage_path('app/backup-temp/' . $tenantId);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Clear temp directory
        array_map('unlink', glob("{$tempDir}/*"));

        // Dump each table with tenant_id filter and merge into single file
        $singleSqlFile = "{$tempDir}/backup.sql";
        $fp = fopen($singleSqlFile, 'w');
        
        // Get tenant info
        $tenant = \App\Models\Tenant::find($tenantId);
        $tenantName = $tenant ? $tenant->name : "Tenant {$tenantId}";
        
        // Write header
        fwrite($fp, "-- Backup: {$tenantName}\n");
        fwrite($fp, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
        fwrite($fp, "SET FOREIGN_KEY_CHECKS = 0;\n\n");
        
        foreach ($this->tablesWithTenantId as $table) {
            $this->dumpTableAndAppend($fp, $table, $mysqldumpPath, $host, $port, $username, $password, $database, $tenantId);
        }

        // Handle child tables without tenant_id
        foreach ($this->childTablesWithoutTenantId as $table => $config) {
            $this->dumpChildTableAndAppend($fp, $table, $mysqldumpPath, $host, $port, $username, $password, $database, $tenantId, $config);
        }
        
        fwrite($fp, "\nSET FOREIGN_KEY_CHECKS = 1;\n");
        fclose($fp);

        // Create ZIP file with single SQL file
        $backupPath = storage_path('app/backups');
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        
        $zipPath = "{$backupPath}/{$filename}";
        
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            if (filesize($singleSqlFile) > 0) {
                $zip->addFile($singleSqlFile, 'backup.sql');
            }
            $zip->close();
            $this->info("Backup created: {$zipPath}");
        } else {
            $this->error("Failed to create ZIP file");
            return Command::FAILURE;
        }

        // Clean up temp directory
        unlink($singleSqlFile);
        rmdir($tempDir);

        $this->info("Backup completed successfully!");
        return Command::SUCCESS;
    }
}