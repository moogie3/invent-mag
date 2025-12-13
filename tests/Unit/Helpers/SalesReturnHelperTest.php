<?php

namespace Tests\Unit\Helpers;

use App\Helpers\SalesReturnHelper;
use Tests\TestCase;

class SalesReturnHelperTest extends TestCase
{
    /**
     * @dataProvider statusClassProvider
     */
    public function test_get_status_class($status, $expectedClass)
    {
        $this->assertEquals($expectedClass, SalesReturnHelper::getStatusClass($status));
    }

    public static function statusClassProvider()
    {
        return [
            'completed' => ['completed', 'bg-success-lt'],
            'pending' => ['pending', 'bg-warning-lt'],
            'canceled' => ['canceled', 'bg-danger-lt'],
            'unknown' => ['unknown', 'bg-secondary-lt'],
            'Completed' => ['Completed', 'bg-success-lt'], // Test case insensitivity
        ];
    }

    /**
     * @dataProvider statusTextProvider
     */
    public function test_get_status_text($status, $expectedText)
    {
        // Mock the __ function if it doesn't exist, as it's used in the helper
        // This is a simple mock for testing purposes; a more robust solution
        // might involve Laravel's translation service testing utilities if available.
        if (!function_exists('__')) {
            function __($key) {
                $translations = [
                    'messages.sr_status_completed' => 'completed',
                    'messages.sr_status_pending' => 'pending',
                    'messages.sr_status_canceled' => 'canceled',
                ];
                return $translations[$key] ?? ucfirst(str_replace('messages.sr_status_', '', $key));
            }
        }
        $this->assertEquals($expectedText, SalesReturnHelper::getStatusText($status));
    }

    public static function statusTextProvider()
    {
        return [
            'completed' => ['completed', '<span class="h4"><i class="ti ti-check me-1 fs-4"></i> completed</span>'],
            'pending' => ['pending', '<span class="h4"><i class="ti ti-clock me-1 fs-4"></i> pending</span>'],
            'canceled' => ['canceled', '<span class="h4"><i class="ti ti-x me-1 fs-4"></i> canceled</span>'],
            'unknown' => ['unknown', '<span class="h4"><i class="ti ti-info-circle me-1 fs-4"></i> Unknown</span>'], // Note: 'Unknown' is ucfirst from mock
            'Completed' => ['Completed', '<span class="h4"><i class="ti ti-check me-1 fs-4"></i> completed</span>'], // Test case insensitivity
        ];
    }
}
