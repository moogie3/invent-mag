<?php

namespace Tests\Feature\Admin;

use App\Helpers\CurrencyHelper;
use App\Mail\SalesInvoiceMail;
use App\Models\Account;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use App\Services\PosService;
use Carbon\Carbon;
use Database\Factories\SalesFactory;
use Database\Seeders\AccountSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class SalesControllerTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected Customer $customer;

    protected Product $product;

    protected Warehouse $warehouse; // Added

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->seed(AccountSeeder::class);
        $this->user->assignRole('superuser');

        // Ensure the current tenant has the necessary accounting settings
        $tenantId = app('currentTenant')->id;
        $this->tenant->accounting_settings = [
            'cash_account_id' => Account::where('code', '1110-'.$tenantId)->first()->id,
            'accounts_receivable_account_id' => Account::where('code', '1130-'.$tenantId)->first()->id,
            'sales_revenue_account_id' => Account::where('code', '4100-'.$tenantId)->first()->id,
            'cost_of_goods_sold_account_id' => Account::where('code', '5200-'.$tenantId)->first()->id,
            'inventory_account_id' => Account::where('code', '1140-'.$tenantId)->first()->id,
        ];
        $this->tenant->save();
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::factory()->create(['is_main' => true]); // Added
        $this->customer = Customer::factory()->create();
        $this->product = Product::factory()->create();

        // Ensure product has stock in the warehouse
        ProductWarehouse::updateOrCreate(
            [
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
                'tenant_id' => $tenantId,
            ],
            ['quantity' => 100]
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_can_display_the_sales_index_page()
    {
        SalesFactory::new()->count(5)->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('admin.sales'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales.index');
        $response->assertViewHas('sales');
    }

    public function test_it_can_display_the_sales_create_page()
    {
        $response = $this->get(route('admin.sales.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales.sales-create');
        $response->assertViewHasAll(['sales', 'customers', 'products', 'items', 'tax']);
    }

    public function test_it_can_store_a_new_sale()
    {
        $salesData = [
            'invoice' => 'INV-'.rand(10000, 99999),
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id, // Added
            'order_date' => Carbon::now()->format('Y-m-d'),
            'due_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'discount_total' => 10,
            'discount_total_type' => 'fixed',
            'products' => json_encode([
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'customer_price' => $this->product->selling_price, // Changed from 'price'
                    'total' => $this->product->selling_price * 2,
                ],
            ]),
        ];

        $response = $this->post(route('admin.sales.store'), $salesData);

        $response->assertRedirect(route('admin.sales'));
        $response->assertSessionHas('success', 'Sale created successfully.');

        $this->assertDatabaseHas('sales', [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id, // Added
            'invoice' => $salesData['invoice'],
        ]);

        $this->assertDatabaseHas('sales_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);
    }

    /**
     * A line discount only ever shrinks what the line is worth, so it can
     * never exceed the line itself. Nothing read the lines inside the
     * `products` payload, so a fixed 1000 off a 100 price was stored as a
     * -1800 line: the item rows, the subtotal and every total derived from
     * them went negative while the stock still left the warehouse.
     */
    public function test_it_rejects_a_line_discount_larger_than_the_line()
    {
        $payloadFor = function (string $invoice, float $discount, string $type) {
            return [
                'invoice' => $invoice,
                'customer_id' => $this->customer->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => Carbon::now()->format('Y-m-d'),
                'due_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'products' => json_encode([
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                        'customer_price' => 100,
                        'discount' => $discount,
                        'discountType' => $type,
                    ],
                ]),
            ];
        };

        // A fixed discount above the line price, a percentage above 100 and
        // a negative discount all turn a line into a credit the customer
        // never earned.
        foreach ([[1000, 'fixed'], [150, 'percentage'], [-5, 'fixed']] as [$discount, $type]) {
            $response = $this->post(
                route('admin.sales.store'),
                $payloadFor('INV-'.rand(10000, 99999), $discount, $type)
            );

            $response->assertSessionHasErrors('products');
        }

        $this->assertSame(
            0,
            Sales::count(),
            'A rejected discount must not leave a sale behind.'
        );
    }

    public function test_it_can_display_the_sales_edit_page()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);

        $response = $this->get(route('admin.sales.edit', $sale->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales.sales-edit');
        $response->assertViewHas('sales', $sale);
    }

    public function test_editing_a_pos_sale_shows_the_edit_form()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => Sales::TYPE_POS,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $response = $this->get(route('admin.sales.edit', $sale->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales.sales-edit');
        $response->assertViewHas('sales', $sale);
    }

    public function test_editing_a_walk_in_pos_sale_shows_the_edit_form_without_a_customer()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => null,
            'user_id' => $this->user->id,
            'type' => Sales::TYPE_POS,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $response = $this->get(route('admin.sales.edit', $sale->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales.sales-edit');
    }

    /**
     * A POS sale's ledger shape is the invoice plus a cash receipt. Editing
     * it must reverse both old entries and post both again against the new
     * totals, or the books keep the money the way it was first rung up: no
     * cash left in the till, receivables standing where cash was received,
     * revenue on the old total. A second edit on top proves the reversal
     * guard: each entry may only ever be reversed once.
     */
    public function test_updating_a_pos_sale_reposts_the_invoice_and_the_cash_receipt()
    {
        $sale = (new PosService(app(AccountingService::class)))->createSale([
            'transaction_date' => Carbon::now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'products' => json_encode([
                ['id' => $this->product->id, 'quantity' => 2, 'price' => $this->product->selling_price, 'total' => $this->product->selling_price * 2],
            ]),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'grand_total' => $this->product->selling_price * 2,
            'payment_method' => 'Cash',
            'amount_received' => $this->product->selling_price * 2,
            'change_amount' => 0,
        ]);

        $unitPrice = (float) $this->product->selling_price;
        $unitCost = (float) $this->product->price;

        // Creation booked two entries against the sale: invoice and cash receipt.
        $this->assertSame(
            2,
            JournalEntry::where('sourceable_type', Sales::class)->where('sourceable_id', $sale->id)->count()
        );

        // Ring the sale down from two units to one.
        $response = $this->put(route('admin.sales.update', $sale->id), $this->posEditPayload($sale, 1));

        $response->assertRedirect(route('admin.sales.show', $sale->id));
        $response->assertSessionHas('success', 'Sale updated successfully.');

        $sale->refresh();
        $this->assertEqualsWithDelta($unitPrice, (float) $sale->total, 0.005, 'The edited total must follow the new quantity.');
        $this->assertSame('Paid', $sale->status);
        $this->assertEqualsWithDelta(0.0, $sale->balance, 0.005, 'A POS sale settles at the counter, so it stays settled after an edit.');
        $this->assertEqualsWithDelta($unitPrice, (float) $sale->payments()->first()->amount, 0.005, 'The recorded payment follows the edited total.');
        $this->assertEqualsWithDelta($unitPrice * 2, (float) $sale->amount_received, 0.005, 'What the customer handed over does not change.');
        $this->assertEqualsWithDelta($unitPrice, (float) $sale->change_amount, 0.005, 'Change owed is what was handed over minus the new total.');
        $this->assertSame('Cash', $sale->payment_type, 'The edit form does not post payment_type for a paid sale; it must not blank the method.');
        $this->assertDatabaseHas('product_warehouse', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 99,
        ]);

        // Old invoice and old cash receipt reversed exactly once, the new
        // shape posted once: four entries sourced to the sale, two reversals.
        $this->assertSame(
            4,
            JournalEntry::where('sourceable_type', Sales::class)->where('sourceable_id', $sale->id)->count()
        );
        $this->assertSame(2, JournalEntry::whereNotNull('reversed_entry_id')->count());

        $this->assertLedgerForSale($sale, [
            '1130' => 0.0,            // AR: invoiced then settled in cash, both shapes reversed
            '1110' => $unitPrice,     // Cash: the one unit still rung up
            '4100' => -$unitPrice,    // Revenue is a credit balance, so debit-positive signing shows it negative
            '5200' => $unitCost,      // COGS for the single unit
            '1140' => -$unitCost,     // Inventory credited out with it
        ], 'after editing a POS sale from 2 units to 1');

        // Second edit, back up to three units: the first repost must be
        // reversed exactly once too, and the ledger must land on the new
        // shape rather than on the sum of every edit ever made.
        $response = $this->put(route('admin.sales.update', $sale->id), $this->posEditPayload($sale, 3));

        $response->assertRedirect(route('admin.sales.show', $sale->id));
        $response->assertSessionHas('success', 'Sale updated successfully.');

        $sale->refresh();
        $this->assertEqualsWithDelta($unitPrice * 3, (float) $sale->total, 0.005, 'The second edit must reprice the sale again.');
        $this->assertSame('Paid', $sale->status);
        $this->assertEqualsWithDelta(0.0, $sale->balance, 0.005, 'Growing the sale at the counter means collecting the difference, so it stays settled.');
        $this->assertEqualsWithDelta($unitPrice * 3, (float) $sale->payments()->first()->amount, 0.005, 'The recorded payment follows the grown total.');
        $this->assertEqualsWithDelta($unitPrice * 3, (float) $sale->amount_received, 0.005, 'The extra cash collected at the counter raises what was handed over.');
        $this->assertEqualsWithDelta(0.0, (float) $sale->change_amount, 0.005, 'Nothing is left to hand back once received matches the total.');
        $this->assertDatabaseHas('product_warehouse', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 97,
        ]);

        $this->assertSame(
            6,
            JournalEntry::where('sourceable_type', Sales::class)->where('sourceable_id', $sale->id)->count()
        );
        $this->assertSame(4, JournalEntry::whereNotNull('reversed_entry_id')->count());

        $this->assertLedgerForSale($sale, [
            '1130' => 0.0,
            '1110' => $unitPrice * 3,
            '4100' => -$unitPrice * 3,
            '5200' => $unitCost * 3,
            '1140' => -$unitCost * 3,
        ], 'after a second edit of the same POS sale');
    }

    /**
     * A walk-in POS sale has no customer, and the edit form posts the empty
     * customer field it renders for one. The edit must go through without
     * inventing a customer, and marking it paid must record the cash the
     * till actually took under the sale's own payment method.
     */
    public function test_updating_a_walk_in_pos_sale_keeps_it_customer_less()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => null,
            'user_id' => $this->user->id,
            'type' => Sales::TYPE_POS,
            'warehouse_id' => $this->warehouse->id,
            'total' => 100,
            'payment_type' => 'Cash',
            'status' => 'Paid',
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id, 'quantity' => 2]);

        $response = $this->put(route('admin.sales.update', $sale->id), [
            'customer_id' => '',
            'order_date' => Carbon::now()->format('Y-m-d'),
            'due_date' => Carbon::now()->addDays(14)->format('Y-m-d'),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'status' => 'Paid',
            'products' => json_encode([
                ['product_id' => $this->product->id, 'quantity' => 2, 'customer_price' => 50],
            ]),
        ]);

        $response->assertRedirect(route('admin.sales.show', $sale->id));
        $response->assertSessionHas('success', 'Sale updated successfully.');

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'customer_id' => null, 'total' => 100]);
        $this->assertSame('Paid', $sale->fresh()->status);

        $payment = $sale->payments()->first();
        $this->assertNotNull($payment, 'Marking the edited walk-in POS sale paid must record the cash it took.');
        $this->assertEqualsWithDelta(100.0, (float) $payment->amount, 0.005);
        $this->assertSame('Cash', $payment->payment_method, 'The payment must keep the sale\'s own method, not a generic default.');
    }

    private function posEditPayload(Sales $sale, int $quantity): array
    {
        return [
            'customer_id' => $this->customer->id,
            'order_date' => Carbon::now()->format('Y-m-d'),
            'due_date' => Carbon::now()->addDays(14)->format('Y-m-d'),
            'discount_total' => 0,
            'discount_total_type' => 'fixed',
            'status' => 'Paid',
            'products' => json_encode([
                ['product_id' => $this->product->id, 'quantity' => $quantity, 'customer_price' => $this->product->selling_price],
            ]),
        ];
    }

    /**
     * Net effect one sale has on the ledger: every entry sourced to it, plus
     * the reversal of each of those, signed debit positive. Reversals carry
     * the opposite debits and credits already, so they are added, never
     * subtracted.
     *
     * @return array<string, float> account code => signed net
     */
    private function ledgerNetFor(Sales $sale): array
    {
        $codes = Account::pluck('code', 'id')->all();
        $net = [];

        $entries = JournalEntry::where('sourceable_type', Sales::class)
            ->where('sourceable_id', $sale->id)
            ->get();

        $this->assertNotEmpty($entries, 'The sale posted no journal entry at all.');

        foreach ($entries as $entry) {
            $reversals = JournalEntry::where('reversed_entry_id', $entry->id)->get();

            $this->assertLessThanOrEqual(
                1,
                $reversals->count(),
                "Entry #{$entry->id} ({$entry->description}) was reversed {$reversals->count()} times. An entry may only ever be reversed once."
            );

            foreach ($entry->transactions as $transaction) {
                $code = $codes[$transaction->account_id] ?? "unknown:{$transaction->account_id}";
                $sign = $transaction->type === 'debit' ? 1 : -1;
                $net[$code] = ($net[$code] ?? 0.0) + $sign * (float) $transaction->amount;
            }

            foreach ($reversals as $reversal) {
                foreach ($reversal->transactions as $transaction) {
                    $code = $codes[$transaction->account_id] ?? "unknown:{$transaction->account_id}";
                    $sign = $transaction->type === 'debit' ? 1 : -1;
                    $net[$code] = ($net[$code] ?? 0.0) + $sign * (float) $transaction->amount;
                }
            }
        }

        return $net;
    }

    /**
     * @param  array<string, float>  $expected  account code => signed net, keyed by the bare SAK code
     */
    private function assertLedgerForSale(Sales $sale, array $expected, string $stage): void
    {
        $net = $this->ledgerNetFor($sale);
        $tenantId = app('currentTenant')->id;

        foreach ($expected as $code => $amount) {
            $resolved = $code.'-'.$tenantId;

            $this->assertEqualsWithDelta(
                $amount,
                $net[$resolved] ?? 0.0,
                0.005,
                sprintf(
                    '%s: account %s net was %.2f, expected %.2f (whole ledger: %s)',
                    $stage,
                    $resolved,
                    $net[$resolved] ?? 0.0,
                    $amount,
                    json_encode(array_map(fn ($v) => round($v, 2), $net))
                )
            );
        }
    }

    public function test_it_can_update_a_sale()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);

        $newProduct = Product::factory()->create();
        // Ensure new product has stock
        ProductWarehouse::updateOrCreate(
            [
                'product_id' => $newProduct->id,
                'warehouse_id' => $this->warehouse->id,
                'tenant_id' => $sale->tenant_id,
            ],
            ['quantity' => 100]
        );

        $updateData = [
            'invoice' => $sale->invoice,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id, // Added
            'order_date' => Carbon::now()->format('Y-m-d'),
            'due_date' => Carbon::now()->addDays(14)->format('Y-m-d'),
            'discount_total' => 5,
            'discount_total_type' => 'percentage',
            'products' => json_encode([
                [
                    'product_id' => $newProduct->id,
                    'quantity' => 3,
                    'customer_price' => $newProduct->selling_price,
                    'total' => $newProduct->selling_price * 3,
                ],
            ]),
        ];

        $response = $this->put(route('admin.sales.update', $sale->id), $updateData);

        $response->assertRedirect(route('admin.sales.show', $sale->id));
        $response->assertSessionHas('success', 'Sale updated successfully.');

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'order_discount' => 5, // Changed from 'discount_total'
            'order_discount_type' => 'percentage', // Changed from 'discount_total_type'
        ]);

        $this->assertDatabaseHas('sales_items', [
            'sales_id' => $sale->id,
            'product_id' => $newProduct->id,
            'quantity' => 3,
        ]);
    }

    public function test_it_can_delete_a_sale()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);

        $response = $this->delete(route('admin.sales.destroy', $sale->id));

        $response->assertRedirect(route('admin.sales'));
        $response->assertSessionHas('success', 'Sales order deleted successfully');

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('sales_items', ['sales_id' => $sale->id]);
    }

    public function test_it_can_display_the_sales_view_page()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => Sales::TYPE_REGULAR,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);

        $response = $this->get(route('admin.sales.show', $sale->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.sales.show');
        $response->assertViewHas('sales', $sale);
    }

    public function test_it_can_add_payment_to_a_sale()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'total' => 100,
            'status' => 'Unpaid',
        ]);

        $paymentData = [
            'amount' => 50,
            'payment_date' => Carbon::now()->format('Y-m-d'),
            'payment_method' => 'cash',
            'notes' => 'Partial payment',
        ];

        $response = $this->post(route('admin.sales.add-payment', $sale->id), $paymentData);

        $response->assertRedirect(route('admin.sales.show', $sale->id));
        $response->assertSessionHas('success', 'Payment added successfully.');

        $this->assertDatabaseHas('payments', [
            'paymentable_id' => $sale->id,
            'paymentable_type' => Sales::class,
            'amount' => 50,
        ]);
    }

    public function test_it_can_email_an_invoice_to_the_customer()
    {
        Mail::fake();

        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);

        $response = $this->post(route('admin.sales.send-invoice', $sale->id));

        $response->assertRedirect(route('admin.sales.show', $sale->id));
        $response->assertSessionHas('success', __('messages.invoice_email_sent', ['email' => $this->customer->email]));

        Mail::assertSent(SalesInvoiceMail::class, function (SalesInvoiceMail $mail) use ($sale) {
            return $mail->hasTo($this->customer->email) && $mail->sale->is($sale);
        });
    }

    public function test_emailing_an_invoice_without_a_customer_email_reports_an_error()
    {
        Mail::fake();

        $customer = Customer::factory()->create(['email' => null]);
        $sale = SalesFactory::new()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->post(route('admin.sales.send-invoice', $sale->id));

        $response->assertRedirect(route('admin.sales.show', $sale->id));
        $response->assertSessionHas('error', __('messages.invoice_email_missing'));
        Mail::assertNothingSent();
    }

    public function test_emailing_an_invoice_surfaces_a_transport_failure_as_an_error_flash()
    {
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('SMTP down'));

        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->post(route('admin.sales.send-invoice', $sale->id));

        $response->assertRedirect(route('admin.sales.show', $sale->id));
        $response->assertSessionHas('error', __('messages.invoice_email_failed'));
    }

    public function test_a_php_error_in_the_send_path_is_not_masked_as_a_mail_failure()
    {
        // The catch is scoped to \Exception, not \Throwable: a transport
        // failure is a mail problem and gets the friendly flash, but a PHP
        // Error is a bug in the code and must surface as a 500 rather than
        // be reported to the user as "the email failed".
        Mail::shouldReceive('to')->once()->andThrow(new \Error('bug in the mail path'));

        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->post(route('admin.sales.send-invoice', $sale->id));

        $response->assertStatus(500);
        $response->assertSessionMissing('error');
    }

    public function test_emailing_an_invoice_requires_edit_permission()
    {
        Mail::fake();

        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);
        $userWithoutRole = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($userWithoutRole)
            ->post(route('admin.sales.send-invoice', $sale->id))
            ->assertStatus(403);

        Mail::assertNothingSent();
    }

    public function test_the_invoice_view_renders_the_send_email_button()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'type' => Sales::TYPE_REGULAR,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);

        $this->get(route('admin.sales.show', $sale->id))
            ->assertStatus(200)
            ->assertSee(route('admin.sales.send-invoice', $sale->id), false)
            ->assertSee(__('messages.send_invoice_email'), false)
            ->assertSee('data-invoice-email-send', false);
    }

    public function test_the_send_button_is_wired_to_its_loading_state_script()
    {
        // The button's pending state has to live in a bundled script: the
        // project runs strict CSP (inline handlers were extracted to
        // legacy-inline-scripts.js on purpose). Three source lines must stay
        // in step - the hook on the form, the @vite entry, and the selector
        // the script binds to. Clicking it is not possible in this
        // environment, so the wiring is asserted where it lives.
        $header = file_get_contents(
            resource_path('views/admin/layouts/partials/sales/view/header.blade.php')
        );
        $this->assertStringContainsString('data-invoice-email-send', $header);

        $script = file_get_contents(resource_path('views/admin/layouts/script.blade.php'));
        $this->assertStringContainsString('resources/js/admin/sales/invoice-email.js', $script);

        $js = file_get_contents(resource_path('js/admin/sales/invoice-email.js'));
        $this->assertStringContainsString('form[data-invoice-email-send]', $js);
        $this->assertStringContainsString('turbo:submit-end', $js);
        $this->assertStringContainsString('aria-busy', $js);
    }

    public function test_the_invoice_email_carries_the_invoice_details_and_pdf_attachment()
    {
        // A distinctive name: the factory draws a random faker word, which can
        // collide with ordinary text in the rendered HTML and make the
        // occurrence count below meaningless.
        $product = Product::factory()->create(['name' => 'ZQX Invoice Marker 4471']);

        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $product->id]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $product->id]);

        $mailable = new SalesInvoiceMail($sale);

        $mailable->assertHasSubject(__('messages.invoice_email_subject', [
            'invoice' => $sale->invoice,
            'company' => config('app.name'),
        ]));

        $html = $mailable->render();
        $this->assertStringContainsString($sale->invoice, $html);
        $this->assertStringContainsString($product->name, $html);
        $this->assertSame(2, substr_count($html, $product->name));

        // The intro is written with the invoice number, and no placeholder is
        // ever handed to the customer as text.
        $this->assertStringNotContainsString(':invoice', $html);
        $this->assertStringNotContainsString(':balance', $html);

        // The three figures every invoice email states, in the amount summary.
        $this->assertStringContainsString(__('messages.grand_total'), $html);
        $this->assertStringContainsString(__('messages.total_paid'), $html);
        $this->assertStringContainsString(__('messages.balance'), $html);

        $attachments = $mailable->attachments();
        $this->assertCount(1, $attachments);
        $this->assertSame('invoice-'.$sale->invoice.'.pdf', $attachments[0]->as);
    }

    public function test_an_unmapped_status_is_shown_as_its_own_word_not_a_raw_translation_key()
    {
        // The status cell builds a translation key from the stored status. If
        // the word has no key, the customer must see the status itself, never
        // the raw "messages.something" lookup string.
        $sale = $this->saleWithKnownTotals(['status' => 'Awaiting Payment']);

        $html = (new SalesInvoiceMail($sale))->render();

        $this->assertStringContainsString('Awaiting Payment', $html);
        $this->assertStringNotContainsString('messages.awaiting payment', $html);

        // And a mapped status still reads as the translated label.
        $mapped = $this->saleWithKnownTotals(['status' => 'Unpaid']);
        $mappedHtml = (new SalesInvoiceMail($mapped))->render();
        $this->assertStringContainsString(__('messages.unpaid'), $mappedHtml);
    }

    /**
     * A sale whose stored total matches its line items, so the email's
     * summary closes: gross - paid = balance.
     */
    private function saleWithKnownTotals(array $overrides = []): Sales
    {
        $product = Product::factory()->create(['selling_price' => 500]);

        $sale = SalesFactory::new()->create(array_merge([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'order_discount' => 0,
            'order_discount_type' => 'fixed',
            'tax_rate' => 0,
            'tax_type' => 'none',
            'total_tax' => 0,
            'status' => 'Unpaid',
        ], $overrides));

        SalesItem::factory()->create([
            'sales_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'customer_price' => 500,
            'total' => 1000,
            'discount' => 0,
            'discount_type' => 'fixed',
        ]);

        $sale->refresh();
        $sale->load('salesItems');
        $sale->update(['total' => $sale->grand_total]);
        $sale->refresh();

        return $sale;
    }

    public function test_an_unpaid_invoice_email_states_the_balance_still_due()
    {
        $sale = $this->saleWithKnownTotals();

        $html = (new SalesInvoiceMail($sale))->render();

        $this->assertSame(1000.0, (float) $sale->balance);
        $this->assertStringContainsString('<table class="panel"', $html);
        $this->assertStringContainsString(__('messages.invoice_email_unpaid_note', [
            'balance' => CurrencyHelper::format($sale->balance),
        ]), $html);
        $this->assertStringNotContainsString(__('messages.invoice_email_partial_note', [
            'paid' => CurrencyHelper::format($sale->total_paid),
            'amount' => CurrencyHelper::format($sale->total),
            'balance' => CurrencyHelper::format($sale->balance),
        ]), $html);
    }

    public function test_a_partially_paid_invoice_email_states_what_is_left()
    {
        $sale = $this->saleWithKnownTotals(['status' => 'Partial']);
        Payment::factory()->create([
            'paymentable_id' => $sale->id,
            'paymentable_type' => Sales::class,
            'amount' => 400,
            'payment_date' => now(),
        ]);
        $sale->refresh();

        $html = (new SalesInvoiceMail($sale))->render();

        $this->assertSame(600.0, (float) $sale->balance);
        $this->assertStringContainsString('<table class="panel"', $html);
        $this->assertStringContainsString(__('messages.invoice_email_partial_note', [
            'paid' => CurrencyHelper::format($sale->total_paid),
            'amount' => CurrencyHelper::format($sale->total),
            'balance' => CurrencyHelper::format($sale->balance),
        ]), $html);
        // The amounts themselves are in the summary, not only in the note.
        $this->assertStringContainsString(CurrencyHelper::format($sale->balance), $html);
    }

    public function test_a_settled_invoice_email_asks_for_nothing()
    {
        $sale = $this->saleWithKnownTotals(['status' => 'Paid']);
        Payment::factory()->create([
            'paymentable_id' => $sale->id,
            'paymentable_type' => Sales::class,
            'amount' => 1000,
            'payment_date' => now(),
        ]);
        $sale->refresh();

        $html = (new SalesInvoiceMail($sale))->render();

        $this->assertSame(0.0, (float) $sale->balance);
        // No due-amount callout: the summary already shows it settled.
        $this->assertStringNotContainsString('<table class="panel"', $html);
        $this->assertStringContainsString(CurrencyHelper::format(0), $html);
    }

    public function test_it_can_print_the_invoice_as_a_pdf()
    {
        $sale = SalesFactory::new()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);
        SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);

        $response = $this->get(route('admin.sales.print', $sale->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_it_can_bulk_delete_sales()
    {
        $sales = SalesFactory::new()->count(3)->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
        ]);
        foreach ($sales as $sale) {
            SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $this->product->id]);
        }

        $idsToDelete = $sales->pluck('id')->toArray();

        $response = $this->postJson(route('sales.bulk-delete'), ['ids' => $idsToDelete]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Successfully deleted sales order(s)',
            ]);

        foreach ($idsToDelete as $id) {
            $this->assertDatabaseMissing('sales', ['id' => $id]);
            $this->assertDatabaseMissing('sales_items', ['sales_id' => $id]);
        }
    }

    public function test_it_can_bulk_mark_paid_sales()
    {
        $product = Product::factory()->create();
        $sales = SalesFactory::new()->count(3)->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'status' => 'Unpaid',
            'total' => 100,
            'amount_received' => 0,
        ]);

        foreach ($sales as $sale) {
            SalesItem::factory()->create(['sales_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 1, 'total' => 100]);
        }

        $idsToMarkPaid = $sales->pluck('id')->toArray();

        $response = $this->postJson(route('sales.bulk-mark-paid'), ['ids' => $idsToMarkPaid]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Successfully marked 3 sales order(s) as paid.',
                'updated_count' => 3,
            ]);

        foreach ($idsToMarkPaid as $id) {
            $this->assertDatabaseHas('sales', [
                'id' => $id,
                'status' => 'Paid',
            ]);
        }
    }

    public function test_it_can_get_customer_price_for_product()
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        // Create a sales record for this customer and product
        $sale = SalesFactory::new()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
        ]);
        SalesItem::factory()->create([
            'sales_id' => $sale->id,
            'product_id' => $product->id,
            'customer_price' => 123.45, // Specific price to assert
        ]);

        $response = $this->get(route('admin.sales.get-customer-price', ['customer' => $customer->id, 'product' => $product->id]));

        $response->assertOk()
            ->assertJson(['past_price' => 123.45]);
    }
}
