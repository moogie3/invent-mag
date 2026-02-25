<?php

namespace Tests\Unit\Helpers;

use App\Helpers\PurchaseReturnHelper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchaseReturnHelperTest extends TestCase
{
    #[Test]
    public function it_returns_correct_status_class_for_completed()
    {
        $this->assertEquals('bg-success-lt', PurchaseReturnHelper::getStatusClass('completed'));
        $this->assertEquals('bg-success-lt', PurchaseReturnHelper::getStatusClass('Completed'));
    }

    #[Test]
    public function it_returns_correct_status_class_for_pending()
    {
        $this->assertEquals('bg-warning-lt', PurchaseReturnHelper::getStatusClass('pending'));
        $this->assertEquals('bg-warning-lt', PurchaseReturnHelper::getStatusClass('Pending'));
    }

    #[Test]
    public function it_returns_correct_status_class_for_canceled()
    {
        $this->assertEquals('bg-danger-lt', PurchaseReturnHelper::getStatusClass('canceled'));
        $this->assertEquals('bg-danger-lt', PurchaseReturnHelper::getStatusClass('Canceled'));
    }

    #[Test]
    public function it_returns_default_status_class_for_unknown_status()
    {
        $this->assertEquals('bg-secondary-lt', PurchaseReturnHelper::getStatusClass('unknown'));
    }

    #[Test]
    public function it_returns_correct_status_text_for_completed()
    {
        $expected = '<span class="h4"><i class="ti ti-check me-1 fs-4"></i> ' . __('messages.pr_status_completed') . '</span>';
        $this->assertEquals($expected, PurchaseReturnHelper::getStatusText('completed'));
    }

    #[Test]
    public function it_returns_correct_status_text_for_pending()
    {
        $expected = '<span class="h4"><i class="ti ti-clock me-1 fs-4"></i> ' . __('messages.pr_status_pending') . '</span>';
        $this->assertEquals($expected, PurchaseReturnHelper::getStatusText('pending'));
    }

    #[Test]
    public function it_returns_correct_status_text_for_canceled()
    {
        $expected = '<span class="h4"><i class="ti ti-x me-1 fs-4"></i> ' . __('messages.pr_status_canceled') . '</span>';
        $this->assertEquals($expected, PurchaseReturnHelper::getStatusText('canceled'));
    }

    #[Test]
    public function it_returns_default_status_text_for_unknown_status()
    {
        $expected = '<span class="h4"><i class="ti ti-info-circle me-1 fs-4"></i> ' . ucfirst('unknown') . '</span>';
        $this->assertEquals($expected, PurchaseReturnHelper::getStatusText('unknown'));
    }
}
