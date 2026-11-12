<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CancelFakturPajakJob;
use App\Models\Account;
use App\Models\FakturPajak;
use App\Models\JournalEntry;
use App\Models\NsfpPool;
use App\Models\Sales;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

/**
 * CancelFakturPajakJob: the API-cancel half of e-Faktur Phase 2
 * (roadmap: PjapInterface::cancel() had no UI and no caller).
 *
 * The contract this job must honour: local state changes ONLY after DJP
 * answers with a confirmed void. A refusal or a transport error leaves the
 * faktur exactly as it was, because DJP may still hold it.
 *
 * Http::fake() MERGES stubs and the first matching stub wins for the
 * whole test, so each test registers exactly one fake.
 */
class CancelFakturPajakJobTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
    }

    private function makeFaktur(array $attributes = []): FakturPajak
    {
        static $serial = 0;
        $serial++;

        $sale = Sales::factory()->create();

        return FakturPajak::create(array_merge([
            'sales_id' => $sale->id,
            'nsfp' => sprintf('010.000-26.%08d', $serial),
            'tanggal_faktur' => '2026-11-04',
            'masa_pajak' => 10,
            'tahun_pajak' => 2026,
            'npwp_penjual' => '01.234.567.8-901.000',
            'nama_penjual' => 'Test Tenant',
            'alamat_penjual' => 'Jakarta',
            'npwp_pembeli' => '02.111.222.3-444.555',
            'nama_pembeli' => 'Customer A',
            'alamat_pembeli' => 'Bandung',
            'dpp' => 100000,
            'ppn' => 11000,
            'ppnbm' => 0,
            'ppn_rate' => 11.00,
            'status' => 'submitted',
            'pjap_submission_id' => 'PJAP-1',
        ], $attributes));
    }

    #[Test]
    public function it_voids_at_djp_then_mirrors_the_cancel_locally(): void
    {
        Http::fake(['*' => Http::response(['code' => '04'], 200)]);
        $faktur = $this->makeFaktur();

        (new CancelFakturPajakJob($faktur))->handle();

        $faktur->refresh();
        $this->assertSame('cancelled', $faktur->status);
        $this->assertSame('cancelled', $faktur->pjap_status);
        // Cleared so a later retry cannot re-void a faktur DJP forgot.
        $this->assertNull($faktur->pjap_submission_id);

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT'
            && str_contains($r->url(), '/v1/efaktur/out/PJAP-1/cancel'));
    }

    #[Test]
    public function it_releases_the_nsfp_when_the_void_is_confirmed(): void
    {
        Http::fake(['*' => Http::response(['code' => '04'], 200)]);
        $faktur = $this->makeFaktur();

        NsfpPool::updateOrCreate(
            ['nsfp' => $faktur->nsfp],
            ['status' => 'used', 'tenant_id' => $this->tenant->id]
        );

        (new CancelFakturPajakJob($faktur))->handle();

        // DJP numbers are never reusable once allocated, so the pool entry
        // must be marked cancelled rather than returned to 'available'.
        $this->assertSame('cancelled', NsfpPool::where('nsfp', $faktur->nsfp)->value('status'));
    }

    #[Test]
    public function it_reverses_a_legacy_ppn_journal_entry_attached_to_the_faktur(): void
    {
        Http::fake(['*' => Http::response(['code' => '04'], 200)]);
        $faktur = $this->makeFaktur();

        // reverseJournalEntry() validates debits == credits, so the legacy
        // entry needs a real balanced pair, not an empty shell.
        $entry = JournalEntry::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'posted',
            'sourceable_type' => FakturPajak::class,
            'sourceable_id' => $faktur->id,
        ]);

        $receivable = Account::where('code', '1100'.$this->tenant->id)->firstOrFail();
        $revenue = Account::where('code', '4000'.$this->tenant->id)->firstOrFail();

        Transaction::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $receivable->id,
            'type' => 'debit',
            'amount' => 11000,
        ]);
        Transaction::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $revenue->id,
            'type' => 'credit',
            'amount' => 11000,
        ]);

        (new CancelFakturPajakJob($faktur))->handle();

        // The original stays 'posted' and gains a reversal pointing at it;
        // that link is what makes a second cancel a no-op.
        $this->assertDatabaseHas('journal_entries', [
            'reversed_entry_id' => $entry->id,
            'status' => 'posted',
        ]);
        $this->assertSame('posted', $entry->refresh()->status);

        // And the void itself still landed.
        $this->assertSame('cancelled', $faktur->refresh()->status);
    }

    #[Test]
    public function it_leaves_local_state_alone_when_djp_refuses_the_void(): void
    {
        // HTTP 200 but a code that is not 00/04: DJP answered, and said no.
        Http::fake(['*' => Http::response(['code' => '1106', 'message' => 'not found'], 200)]);
        $faktur = $this->makeFaktur();

        (new CancelFakturPajakJob($faktur))->handle();

        // No throw: a refusal is a verdict, not a transport fault, so the
        // retry budget is not burned on an answer that will not change.
        $faktur->refresh();
        $this->assertSame('submitted', $faktur->status);
        $this->assertSame('PJAP-1', $faktur->pjap_submission_id);
        $this->assertNotSame('cancelled', $faktur->pjap_status);
    }

    #[Test]
    public function it_leaves_local_state_alone_when_djp_never_answers(): void
    {
        Http::fake(['*' => Http::response(['message' => 'boom'], 500)]);
        $faktur = $this->makeFaktur();

        $caught = null;
        try {
            (new CancelFakturPajakJob($faktur))->handle();
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        // Re-thrown so the queue can retry up to $tries times.
        $this->assertNotNull($caught, 'The job must re-throw on a transport failure');

        $faktur->refresh();
        $this->assertSame('submitted', $faktur->status);
        $this->assertSame('PJAP-1', $faktur->pjap_submission_id);
    }

    #[Test]
    public function it_does_nothing_when_the_faktur_never_reached_djp(): void
    {
        Http::fake();
        $faktur = $this->makeFaktur(['pjap_submission_id' => null]);

        (new CancelFakturPajakJob($faktur))->handle();

        Http::assertNothingSent();
        $this->assertSame('submitted', $faktur->refresh()->status);
    }

    #[Test]
    public function it_does_nothing_when_the_faktur_is_already_cancelled(): void
    {
        Http::fake();
        $faktur = $this->makeFaktur(['status' => 'cancelled']);

        (new CancelFakturPajakJob($faktur))->handle();

        Http::assertNothingSent();
        $this->assertSame('cancelled', $faktur->refresh()->status);
    }

    #[Test]
    public function failed_keeps_local_state_unchanged(): void
    {
        Http::fake();
        $faktur = $this->makeFaktur();

        (new CancelFakturPajakJob($faktur))->failed(new \RuntimeException('retries exhausted'));

        // DJP may or may not have voided it; rewriting local state here
        // could claim a void that never happened.
        $faktur->refresh();
        $this->assertSame('submitted', $faktur->status);
        $this->assertSame('PJAP-1', $faktur->pjap_submission_id);
        $this->assertNotSame('cancelled', $faktur->status);
    }
}
