<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ReturnTaxHelper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReturnTaxHelperTest extends TestCase
{
    #[Test]
    public function ratio_is_return_over_original_base()
    {
        $this->assertEqualsWithDelta(0.5, ReturnTaxHelper::ratio(100.0, 200.0), 0.0001);
    }

    #[Test]
    public function ratio_is_zero_when_either_side_is_missing()
    {
        // A zero base would divide by zero; a zero return means nothing to
        // reverse. Both must yield 0 so every derived amount is 0 and the
        // caller posts nothing.
        $this->assertEquals(0.0, ReturnTaxHelper::ratio(100.0, 0.0));
        $this->assertEquals(0.0, ReturnTaxHelper::ratio(0.0, 200.0));
        $this->assertEquals(0.0, ReturnTaxHelper::ratio(0.0, 0.0));
    }

    #[Test]
    public function sales_reversal_scales_the_snapshot_by_the_net_base_ratio()
    {
        $reversal = ReturnTaxHelper::salesReversal(
            returnTotal: 100.0,
            originalRevenueBase: 200.0,
            snapshotPpnAmount: 22.0,
            isPpn: true,
            snapshotPphAmount: 10.0,
            isPph: true,
        );

        $this->assertEqualsWithDelta(0.5, $reversal['ratio'], 0.0001);
        $this->assertEqualsWithDelta(11.0, $reversal['ppn'], 0.0001);
        $this->assertEqualsWithDelta(5.0, $reversal['pph'], 0.0001);
    }

    #[Test]
    public function sales_reversal_is_zero_when_the_tax_flags_or_snapshots_are_absent()
    {
        $notTaxed = ReturnTaxHelper::salesReversal(100.0, 200.0, 22.0, false, 10.0, false);
        $this->assertEquals(0.0, $notTaxed['ppn']);
        $this->assertEquals(0.0, $notTaxed['pph']);

        $noSnapshot = ReturnTaxHelper::salesReversal(100.0, 200.0, 0.0, true, 0.0, true);
        $this->assertEquals(0.0, $noSnapshot['ppn']);
        $this->assertEquals(0.0, $noSnapshot['pph']);
    }

    #[Test]
    public function sales_reversal_is_zero_when_the_base_is_missing()
    {
        $reversal = ReturnTaxHelper::salesReversal(100.0, 0.0, 22.0, true, 10.0, true);

        $this->assertEquals(0.0, $reversal['ratio']);
        $this->assertEquals(0.0, $reversal['ppn']);
        $this->assertEquals(0.0, $reversal['pph']);
    }

    #[Test]
    public function purchase_reversal_scales_what_the_original_journal_posted()
    {
        $reversal = ReturnTaxHelper::purchaseReversal(
            returnTotal: 50.0,
            originalPurchaseBase: 200.0,
            originalPostedPpn: 22.0,
            originalPostedPph: 10.0,
        );

        $this->assertEqualsWithDelta(0.25, $reversal['ratio'], 0.0001);
        $this->assertEqualsWithDelta(5.5, $reversal['ppn'], 0.0001);
        $this->assertEqualsWithDelta(2.5, $reversal['pph'], 0.0001);
    }

    #[Test]
    public function purchase_reversal_is_zero_when_the_original_posted_no_tax()
    {
        // Legacy or fallback postings may carry no tax legs; reversing a
        // figure the original never booked would invent one.
        $reversal = ReturnTaxHelper::purchaseReversal(50.0, 200.0, 0.0, 0.0);

        $this->assertEquals(0.0, $reversal['ppn']);
        $this->assertEquals(0.0, $reversal['pph']);
    }
}
