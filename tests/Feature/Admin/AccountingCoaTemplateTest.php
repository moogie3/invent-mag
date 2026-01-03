<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Pest\Faker\fake;
use PHPUnit\Framework\Attributes\Test;
use Tests\Traits\CreatesTenant;
use Database\Seeders\AccountSeeder;
use Database\Seeders\RoleSeeder;

class AccountingCoaTemplateTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(AccountSeeder::class); // Ensure initial accounts are present
        $this->user->assignRole('superuser');
        $this->actingAs($this->user);

        // Ensure template files exist for testing
        File::makeDirectory(database_path('data/coa_templates'), 0755, true, true);

        $universalCoaContent = '[
            {
                "name": "Assets",
                "code": "1000",
                "type": "asset",
                "children": [
                    {
                        "name": "Current Assets",
                        "code": "1100",
                        "type": "asset",
                        "children": [
                            {
                                "name": "Cash",
                                "code": "1101",
                                "type": "asset",
                                "children": []
                            },
                            {
                                "name": "Accounts Receivable",
                                "code": "1102",
                                "type": "asset",
                                "children": []
                            },
                            {
                                "name": "Inventory",
                                "code": "1103",
                                "type": "asset",
                                "children": []
                            },
                            {
                                "name": "Prepaid Expenses",
                                "code": "1104",
                                "type": "asset",
                                "children": []
                            }
                        ]
                    },
                    {
                        "name": "Fixed Assets",
                        "code": "1200",
                        "type": "asset",
                        "children": [
                            {
                                "name": "Land",
                                "code": "1201",
                                "type": "asset",
                                "children": []
                            },
                            {
                                "name": "Buildings",
                                "code": "1202",
                                "type": "asset",
                                "children": []
                            },
                            {
                                "name": "Vehicles",
                                "code": "1203",
                                "type": "asset",
                                "children": []
                            },
                            {
                                "name": "Accumulated Depreciation",
                                "code": "1209",
                                "type": "asset",
                                "children": []
                            }
                        ]
                    }
                ]
            },
            {
                "name": "Liabilities",
                "code": "2000",
                "type": "liability",
                "children": [
                    {
                        "name": "Current Liabilities",
                        "code": "2100",
                        "type": "liability",
                        "children": [
                            {
                                "name": "Accounts Payable",
                                "code": "2101",
                                "type": "liability",
                                "children": []
                            },
                            {
                                "name": "Accrued Expenses",
                                "code": "2102",
                                "type": "liability",
                                "children": []
                            },
                            {
                                "name": "Short-term Loans",
                                "code": "2103",
                                "type": "liability",
                                "children": []
                            }
                        ]
                    },
                    {
                        "name": "Long-term Liabilities",
                        "code": "2200",
                        "type": "liability",
                        "children": [
                            {
                                "name": "Long-term Bank Loans",
                                "code": "2201",
                                "type": "liability",
                                "children": []
                            }
                        ]
                    }
                ]
            },
            {
                "name": "Equity",
                "code": "3000",
                "type": "equity",
                "children": [
                    {
                        "name": "Owner\'s Capital",
                        "code": "3101",
                        "type": "equity",
                        "children": []
                    },
                    {
                        "name": "Retained Earnings",
                        "code": "3102",
                        "type": "equity",
                        "children": []
                    }
                ]
            },
            {
                "name": "Revenue",
                "code": "4000",
                "type": "revenue",
                "children": [
                    {
                        "name": "Sales Revenue",
                        "code": "4101",
                        "type": "revenue",
                        "children": []
                    },
                    {
                        "name": "Service Revenue",
                        "code": "4102",
                        "type": "revenue",
                        "children": []
                    }
                ]
            },
            {
                "name": "Cost of Goods Sold (Category)",
                "code": "5000",
                "type": "expense",
                "children": [
                    {
                        "name": "Cost of Goods Sold",
                        "code": "5101",
                        "type": "expense",
                        "children": []
                    }
                ]
            },
            {
                "name": "Expenses",
                "code": "6000",
                "type": "expense",
                "children": [
                    {
                        "name": "Operating Expenses",
                        "code": "6100",
                        "type": "expense",
                        "children": [
                            {
                                "name": "Salaries and Wages",
                                "code": "6101",
                                "type": "expense",
                                "children": []
                            },
                            {
                                "name": "Rent Expense",
                                "code": "6102",
                                "type": "expense",
                                "children": []
                            },
                            {
                                "name": "Utilities Expense",
                                "code": "6103",
                                "type": "expense",
                                "children": []
                            },
                            {
                                "name": "Marketing Expense",
                                "code": "6104",
                                "type": "expense",
                                "children": []
                            }
                        ]
                    }
                ]
            }
        ]';
        File::put(database_path('data/coa_templates/universal.json'), $universalCoaContent);

        $indonesianCoaContent = '[
            {
                "name": "Aset",
                "code": "1000",
                "type": "asset",
                "children": [
                    {
                        "name": "Aset Lancar",
                        "code": "1100",
                        "type": "asset",
                        "children": [
                            {
                                "name": "Kas dan Setara Kas",
                                "code": "1101",
                                "type": "asset",
                                "children": []
                            },
                            {
                                "name": "Piutang Usaha",
                                "code": "1102",
                                "type": "asset",
                                "children": []
                            },
                            {
                                "name": "Persediaan",
                                "code": "1103",
                                "type": "asset",
                                "children": []
                            },
                            {
                                "name": "Beban Dibayar di Muka",
                                "code": "1104",
                                "type": "asset",
                                "children": []
                            }
                        ]
                    },
                    {
                        "name": "Aset Tetap",
                        "code": "1200",
                        "type": "asset",
                        "children": [
                            {
                                "name": "Tanah",
                                "code": "1201",
                                "type": "asset",
                                "children": []
                            },
                            {
                                "name": "Bangunan",
                                "code": "1202",
                                "type": "asset",
                                "children": []
                            },
                            {
                                "name": "Kendaraan",
                                "code": "1203",
                                "type": "asset",
                                "children": []
                            },
                            {
                                "name": "Akumulasi Penyusutan",
                                "code": "1209",
                                "type": "asset",
                                "children": []
                            }
                        ]
                    }
                ]
            },
            {
                "name": "Liabilitas",
                "code": "2000",
                "type": "liability",
                "children": [
                    {
                        "name": "Liabilitas Jangka Pendek",
                        "code": "2100",
                        "type": "liability",
                        "children": [
                            {
                                "name": "Utang Usaha",
                                "code": "2101",
                                "type": "liability",
                                "children": []
                            },
                            {
                                "name": "Beban Akrual",
                                "code": "2102",
                                "type": "liability",
                                "children": []
                            },
                            {
                                "name": "Pinjaman Jangka Pendek",
                                "code": "2103",
                                "type": "liability",
                                "children": []
                            }
                        ]
                    },
                    {
                        "name": "Liabilitas Jangka Panjang",
                        "code": "2200",
                        "type": "liability",
                        "children": [
                            {
                                "name": "Utang Bank Jangka Panjang",
                                "code": "2201",
                                "type": "liability",
                                "children": []
                            }
                        ]
                    }
                ]
            },
            {
                "name": "Ekuitas",
                "code": "3000",
                "type": "equity",
                "children": [
                    {
                        "name": "Modal Pemilik",
                        "code": "3101",
                        "type": "equity",
                        "children": []
                    },
                    {
                        "name": "Laba Ditahan",
                        "code": "3102",
                        "type": "equity",
                        "children": []
                    }
                ]
            },
            {
                "name": "Pendapatan",
                "code": "4000",
                "type": "revenue",
                "children": [
                    {
                        "name": "Pendapatan Penjualan",
                        "code": "4101",
                        "type": "revenue",
                        "children": []
                    },
                    {
                        "name": "Pendapatan Jasa",
                        "code": "4102",
                        "type": "revenue",
                        "children": []
                    }
                ]
            },
            {
                "name": "Harga Pokok Penjualan (Kategori)",
                "code": "5000",
                "type": "expense",
                "children": [
                    {
                        "name": "Harga Pokok Penjualan",
                        "code": "5101",
                        "type": "expense",
                        "children": []
                    }
                ]
            },
            {
                "name": "Beban",
                "code": "6000",
                "type": "expense",
                "children": [
                    {
                        "name": "Beban Operasional",
                        "code": "6100",
                        "type": "expense",
                        "children": [
                            {
                                "name": "Beban Gaji dan Upah",
                                "code": "6101",
                                "type": "expense",
                                "children": []
                            },
                            {
                                "name": "Beban Sewa",
                                "code": "6102",
                                "type": "expense",
                                "children": []
                            },
                            {
                                "name": "Beban Utilitas",
                                "code": "6103",
                                "type": "expense",
                                "children": []
                            },
                            {
                                "name": "Beban Pemasaran",
                                "code": "6104",
                                "type": "expense",
                                "children": []
                            }
                        ]
                    }
                ]
            }
        ]';
        File::put(database_path('data/coa_templates/indonesian.json'), $indonesianCoaContent);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[Test]
    public function a_user_can_apply_the_universal_coa_template(){
        // AccountSeeder already creates accounts, which will be truncated
        $this->assertCount(44, Account::all());

        $response = $this->post(route('admin.setting.apply-coa-template'), [
            'template' => 'universal.json',
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk();
        $response->assertJsonFragment(['success' => true, 'message' => 'Chart of Accounts template applied successfully.']);

        $this->assertCount(32, Account::all()); // All accounts from the universal template should exist
        $this->assertDatabaseHas('accounts', ['name' => 'Assets', 'code' => '1000']);
        $this->assertDatabaseMissing('accounts', ['name' => 'Old Account']);
    }

    #[Test]
    public function a_user_can_apply_the_indonesian_coa_template()
    {
        // AccountSeeder already creates accounts, which will be truncated
        $this->assertCount(44, Account::all());

        $response = $this->post(route('admin.setting.apply-coa-template'), [
            'template' => 'indonesian.json',
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk();
        $response->assertJsonFragment(['success' => true, 'message' => 'Chart of Accounts template applied successfully.']);

        $this->assertCount(32, Account::all()); // All accounts from the indonesian template should exist
        $this->assertDatabaseHas('accounts', ['name' => 'Aset', 'code' => '1000']);
        $this->assertDatabaseMissing('accounts', ['name' => 'Another Old Account']);
    }

    #[Test]
    public function applying_a_non_existent_coa_template_shows_an_error()
    {
        $initialAccountCount = Account::count();

        $response = $this->post(route('admin.setting.apply-coa-template'), [
            'template' => 'non_existent.json',
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertNotFound(); // Should return 404 for non-existent template
        $response->assertJsonFragment(['success' => false, 'message' => 'Template file not found.']);

        // Assert that no accounts were created or deleted
        $this->assertCount($initialAccountCount, Account::all());
    }
}