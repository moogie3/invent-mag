<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountService;
use App\Services\AccountingExportService;
use App\Services\AccountingReportService;
use App\Services\ChartOfAccountsService;
use App\Services\GeneralLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;
use Database\Seeders\AccountSeeder;
use Database\Seeders\RoleSeeder;

class AccountingControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected $accountServiceMock;
    protected $generalLedgerServiceMock;
    protected $accountingReportServiceMock;
    protected $accountingExportServiceMock;
    protected $chartOfAccountsServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');

        // Seed accounts within the tenant context
        $this->seed(AccountSeeder::class);

        // Mock the services
        $this->accountServiceMock = Mockery::mock(AccountService::class);
        $this->generalLedgerServiceMock = Mockery::mock(GeneralLedgerService::class);
        $this->accountingReportServiceMock = Mockery::mock(AccountingReportService::class);
        $this->accountingExportServiceMock = Mockery::mock(AccountingExportService::class);
        $this->chartOfAccountsServiceMock = Mockery::mock(ChartOfAccountsService::class);

        $this->app->instance(AccountService::class, $this->accountServiceMock);
        $this->app->instance(GeneralLedgerService::class, $this->generalLedgerServiceMock);
        $this->app->instance(AccountingReportService::class, $this->accountingReportServiceMock);
        $this->app->instance(AccountingExportService::class, $this->accountingExportServiceMock);
        $this->app->instance(ChartOfAccountsService::class, $this->chartOfAccountsServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_display_the_chart_of_accounts()
    {
        $response = $this->actingAs($this->user)->get(route('admin.accounting.chart'));

        $response->assertRedirect(route('admin.accounting.accounts.index'));
    }

    #[Test]
    public function it_can_display_the_general_journal()
    {
        $this->generalLedgerServiceMock->shouldReceive('getJournalEntries')
            ->andReturn([
                'entries' => new LengthAwarePaginator(collect([]), 0, 20),
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
            ]);

        $response = $this->actingAs($this->user)->get(route('admin.accounting.journal'));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.journal');
        $response->assertViewHas('entries');
    }

    #[Test]
    public function it_can_display_the_general_ledger()
    {
        $this->accountServiceMock->shouldReceive('getAllAccountsOrdered')
            ->andReturn(collect([]));

        $response = $this->actingAs($this->user)->get(route('admin.accounting.ledger'));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.general-ledger');
        $response->assertViewHas('accounts');
    }

    #[Test]
    public function it_can_filter_the_general_ledger()
    {
        $tenantId = app('currentTenant')->id;
        $account = Account::where('code', '1110-' . $tenantId)->first();

        $this->accountServiceMock->shouldReceive('getAllAccountsOrdered')
            ->andReturn(collect([$account]));
        
        $this->generalLedgerServiceMock->shouldReceive('getGeneralLedger')
            ->andReturn([
                'account' => $account,
                'transactions' => new LengthAwarePaginator(collect([]), 0, 50),
                'opening_balance' => 0,
                'closing_balance' => 0,
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
            ]);

        $response = $this->actingAs($this->user)->get(route('admin.accounting.ledger', [
            'account_id' => $account->id,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.general-ledger');
        $response->assertViewHas('selectedAccount');
        $response->assertViewHas('transactions');
    }

    #[Test]
    public function it_can_display_the_trial_balance()
    {
        $this->accountingReportServiceMock->shouldReceive('generateTrialBalance')
            ->andReturn([
                'report_data' => [],
                'total_debits' => 0,
                'total_credits' => 0,
                'is_balanced' => true,
                'end_date' => '2024-01-31',
            ]);

        $response = $this->actingAs($this->user)->get(route('admin.accounting.trial_balance'));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.trial-balance');
        $response->assertViewHas('reportData');
        $response->assertViewHas('totalDebits');
        $response->assertViewHas('totalCredits');
    }

    #[Test]
    public function it_can_display_the_accounts_index_page()
    {
        $this->accountServiceMock->shouldReceive('getAccountsWithChildren')
            ->andReturn(collect([]));

        $response = $this->actingAs($this->user)->get(route('admin.accounting.accounts.index'));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.accounts.index');
        $response->assertViewHas('accounts');
    }

    #[Test]
    public function it_can_display_the_accounts_create_page()
    {
        $this->accountServiceMock->shouldReceive('getAllAccountsOrdered')
            ->andReturn(collect([]));

        $response = $this->actingAs($this->user)->get(route('admin.accounting.accounts.create'));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.accounts.create');
        $response->assertViewHas('accounts');
    }

    #[Test]
    public function it_can_store_a_new_account()
    {
        $tenantId = app('currentTenant')->id;
        $parentAccount = Account::where('code', '1000-' . $tenantId)->first();
        $this->assertNotNull($parentAccount, 'Parent account with code 1000 not found.');

        $this->accountServiceMock->shouldReceive('createAccount')
            ->once()
            ->andReturnUsing(function ($data) {
                return Account::factory()->create($data);
            });

        $response = $this->actingAs($this->user)->post(route('admin.accounting.accounts.store'), [
            'name' => 'Test Account',
            'code' => '9000',
            'type' => 'asset',
            'parent_id' => $parentAccount->id,
            'description' => 'A test account',
        ]);

        $response->assertRedirect(route('admin.accounting.accounts.index'));
        $response->assertSessionHas('success', 'Account created successfully.');
    }

    #[Test]
    public function it_validates_account_store_request()
    {
        $this->accountServiceMock->shouldReceive('createAccount')
            ->once()
            ->andThrow(new \Illuminate\Validation\ValidationException(
                validator([], ['name' => 'required', 'code' => 'required', 'type' => 'required'])
            ));

        $response = $this->actingAs($this->user)->post(route('admin.accounting.accounts.store'), [
            'name' => '',
            'code' => '',
            'type' => 'invalid_type',
        ]);

        $response->assertSessionHasErrors(['name', 'code', 'type']);
    }

    #[Test]
    public function it_can_display_the_accounts_edit_page()
    {
        $account = Account::factory()->create();

        $this->accountServiceMock->shouldReceive('getAllAccountsExcept')
            ->with($account->id)
            ->andReturn(collect([]));

        $response = $this->actingAs($this->user)->get(route('admin.accounting.accounts.edit', $account));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.accounts.edit');
        $response->assertViewHas('account', $account);
        $response->assertViewHas('accounts');
    }

    #[Test]
    public function it_can_update_an_account()
    {
        $account = Account::factory()->create(['name' => 'Old Name', 'code' => 'OLD1', 'type' => 'asset']);
        $tenantId = app('currentTenant')->id;
        $parentAccount = Account::where('code', '1000-' . $tenantId)->first();

        $this->accountServiceMock->shouldReceive('updateAccount')
            ->once()
            ->andReturn($account);

        $response = $this->actingAs($this->user)->put(route('admin.accounting.accounts.update', $account), [
            'name' => 'Updated Name',
            'code' => 'UPD1',
            'type' => 'liability',
            'parent_id' => $parentAccount->id,
            'description' => 'Updated description',
        ]);

        $response->assertRedirect(route('admin.accounting.accounts.index'));
        $response->assertSessionHas('success', 'Account updated successfully.');
    }

    #[Test]
    public function it_validates_account_update_request()
    {
        $account = Account::factory()->create();

        $this->accountServiceMock->shouldReceive('updateAccount')
            ->once()
            ->andThrow(new \Illuminate\Validation\ValidationException(
                validator([], ['name' => 'required', 'code' => 'required', 'type' => 'required'])
            ));

        $response = $this->actingAs($this->user)->put(route('admin.accounting.accounts.update', $account), [
            'name' => '',
            'code' => '',
            'type' => 'invalid_type',
        ]);

        $response->assertSessionHasErrors(['name', 'code', 'type']);
    }

    #[Test]
    public function it_can_delete_an_account()
    {
        $account = Account::factory()->create();

        $this->accountServiceMock->shouldReceive('deleteAccount')
            ->with(Mockery::on(function ($arg) use ($account) {
                return $arg->id === $account->id;
            }))
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Account deleted successfully.',
            ]);

        $response = $this->actingAs($this->user)->delete(route('admin.accounting.accounts.destroy', $account));

        $response->assertRedirect(route('admin.accounting.accounts.index'));
        $response->assertSessionHas('success', 'Account deleted successfully.');
    }

    #[Test]
    public function it_cannot_delete_account_with_transactions()
    {
        $account = Account::factory()->create();

        $this->accountServiceMock->shouldReceive('deleteAccount')
            ->with(Mockery::on(function ($arg) use ($account) {
                return $arg->id === $account->id;
            }))
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Account cannot be deleted because it has transactions.',
            ]);

        $response = $this->actingAs($this->user)->delete(route('admin.accounting.accounts.destroy', $account));

        $response->assertRedirect(route('admin.accounting.accounts.index'));
        $response->assertSessionHas('error', 'Account cannot be deleted because it has transactions.');
    }

    #[Test]
    public function it_cannot_delete_account_with_children()
    {
        $account = Account::factory()->create();

        $this->accountServiceMock->shouldReceive('deleteAccount')
            ->with(Mockery::on(function ($arg) use ($account) {
                return $arg->id === $account->id;
            }))
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Account cannot be deleted because it has child accounts.',
            ]);

        $response = $this->actingAs($this->user)->delete(route('admin.accounting.accounts.destroy', $account));

        $response->assertRedirect(route('admin.accounting.accounts.index'));
        $response->assertSessionHas('error', 'Account cannot be deleted because it has child accounts.');
    }

    #[Test]
    public function it_can_export_chart_of_accounts_to_pdf()
    {
        $this->accountServiceMock->shouldReceive('getAccountsWithChildren')
            ->andReturn(collect([]));
        
        $this->accountingExportServiceMock->shouldReceive('exportChartOfAccountsToPdf')
            ->andReturn(response('pdf content', 200, ['Content-Type' => 'application/pdf']));

        $response = $this->actingAs($this->user)->post(route('admin.accounting.accounts.export'), [
            'export_option' => 'pdf',
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function it_can_export_chart_of_accounts_to_csv()
    {
        $this->accountServiceMock->shouldReceive('getAccountsWithChildren')
            ->andReturn(collect([]));
        
        $this->accountingReportServiceMock->shouldReceive('flattenAccountHierarchy')
            ->andReturn([]);
        
        $this->accountingExportServiceMock->shouldReceive('exportChartOfAccountsToCsv')
            ->andReturn(response('csv content', 200, ['Content-Type' => 'text/csv']));

        $response = $this->actingAs($this->user)->post(route('admin.accounting.accounts.export'), [
            'export_option' => 'csv',
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function it_can_export_journal_to_pdf()
    {
        $this->generalLedgerServiceMock->shouldReceive('getJournalEntriesForExport')
            ->andReturn(collect([]));
        
        $this->accountingExportServiceMock->shouldReceive('exportJournalToPdf')
            ->andReturn(response('pdf content', 200, ['Content-Type' => 'application/pdf']));

        $response = $this->actingAs($this->user)->post(route('admin.accounting.journal.export'), [
            'export_option' => 'pdf',
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function it_can_export_journal_to_csv()
    {
        $this->generalLedgerServiceMock->shouldReceive('getJournalEntriesForExport')
            ->andReturn(collect([]));
        
        $this->accountingExportServiceMock->shouldReceive('exportJournalToCsv')
            ->andReturn(response('csv content', 200, ['Content-Type' => 'text/csv']));

        $response = $this->actingAs($this->user)->post(route('admin.accounting.journal.export'), [
            'export_option' => 'csv',
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function it_can_export_trial_balance_to_pdf()
    {
        $this->accountingReportServiceMock->shouldReceive('generateTrialBalance')
            ->andReturn([
                'report_data' => [],
                'total_debits' => 0,
                'total_credits' => 0,
                'end_date' => '2024-01-31',
            ]);
        
        $this->accountingExportServiceMock->shouldReceive('exportTrialBalanceToPdf')
            ->andReturn(response('pdf content', 200, ['Content-Type' => 'application/pdf']));

        $response = $this->actingAs($this->user)->post(route('admin.accounting.trial_balance.export'), [
            'export_option' => 'pdf',
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function it_can_export_trial_balance_to_csv()
    {
        $this->accountingReportServiceMock->shouldReceive('generateTrialBalance')
            ->andReturn([
                'report_data' => [],
                'total_debits' => 0,
                'total_credits' => 0,
                'end_date' => '2024-01-31',
            ]);
        
        $this->accountingExportServiceMock->shouldReceive('exportTrialBalanceToCsv')
            ->andReturn(response('csv content', 200, ['Content-Type' => 'text/csv']));

        $response = $this->actingAs($this->user)->post(route('admin.accounting.trial_balance.export'), [
            'export_option' => 'csv',
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function it_can_export_general_ledger_to_pdf()
    {
        $tenantId = app('currentTenant')->id;
        $account = Account::where('code', '1110-' . $tenantId)->first();

        $this->generalLedgerServiceMock->shouldReceive('getGeneralLedgerForExport')
            ->andReturn([
                'account' => $account,
                'transactions' => collect([]),
                'opening_balance' => 0,
                'closing_balance' => 0,
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
            ]);
        
        $this->accountingExportServiceMock->shouldReceive('exportGeneralLedgerToPdf')
            ->andReturn(response('pdf content', 200, ['Content-Type' => 'application/pdf']));

        $response = $this->actingAs($this->user)->post(route('admin.accounting.ledger.export'), [
            'export_option' => 'pdf',
            'account_id' => $account->id,
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function it_can_export_general_ledger_to_csv()
    {
        $tenantId = app('currentTenant')->id;
        $account = Account::where('code', '1110-' . $tenantId)->first();

        $this->generalLedgerServiceMock->shouldReceive('getGeneralLedgerForExport')
            ->andReturn([
                'account' => $account,
                'transactions' => collect([]),
                'opening_balance' => 0,
                'closing_balance' => 0,
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
            ]);
        
        $this->accountingExportServiceMock->shouldReceive('exportGeneralLedgerToCsv')
            ->andReturn(response('csv content', 200, ['Content-Type' => 'text/csv']));

        $response = $this->actingAs($this->user)->post(route('admin.accounting.ledger.export'), [
            'export_option' => 'csv',
            'account_id' => $account->id,
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function it_can_display_accounting_settings()
    {
        $this->chartOfAccountsServiceMock->shouldReceive('getAccountingSettings')
            ->andReturn([
                'accounts' => collect([]),
                'settings' => [],
            ]);

        $response = $this->actingAs($this->user)->get(route('admin.setting.accounting'));

        $response->assertOk();
        $response->assertViewIs('admin.accounting.accounting-setting');
    }

    #[Test]
    public function it_can_update_accounting_settings()
    {
        $this->chartOfAccountsServiceMock->shouldReceive('validateSettingsData')
            ->andReturn([
                'sales_revenue_account_id' => 'required|exists:accounts,id',
                'accounts_receivable_account_id' => 'required|exists:accounts,id',
                'cost_of_goods_sold_account_id' => 'required|exists:accounts,id',
                'inventory_account_id' => 'required|exists:accounts,id',
                'accounts_payable_account_id' => 'required|exists:accounts,id',
                'cash_account_id' => 'required|exists:accounts,id',
            ]);

        $this->chartOfAccountsServiceMock->shouldReceive('updateAccountingSettings')
            ->andReturn([
                'success' => true,
                'message' => 'Accounting settings updated successfully.',
            ]);

        $tenantId = app('currentTenant')->id;
        $cash = Account::where('code', '1110-' . $tenantId)->first();
        $ar = Account::where('code', '1130-' . $tenantId)->first();
        $inventory = Account::where('code', '1150-' . $tenantId)->first();
        $ap = Account::where('code', '2110-' . $tenantId)->first();
        $sales = Account::where('code', '4100-' . $tenantId)->first();
        $cogs = Account::where('code', '5110-' . $tenantId)->first();

        $response = $this->actingAs($this->user)->post(route('admin.setting.accounting.update'), [
            'sales_revenue_account_id' => $sales->id,
            'accounts_receivable_account_id' => $ar->id,
            'cost_of_goods_sold_account_id' => $cogs->id,
            'inventory_account_id' => $inventory->id,
            'accounts_payable_account_id' => $ap->id,
            'cash_account_id' => $cash->id,
        ]);

        $response->assertRedirect(route('admin.setting.accounting'));
        $response->assertSessionHas('success', 'Accounting settings updated successfully.');
    }
}
