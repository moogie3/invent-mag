<?php

namespace App\Services;

use App\Models\PurchaseReturn;
use Illuminate\Http\Request;
use App\Models\Purchase;
use Carbon\Carbon;
use App\Models\User;

class PurchaseReturnService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function getPurchaseReturnIndexData(array $filters, int $entries)
    {
        $query = PurchaseReturn::with(['purchase', 'user']);

        if (isset($filters['month']) && $filters['month']) {
            $query->whereMonth('return_date', $filters['month']);
        }

        if (isset($filters['year']) && $filters['year']) {
            $query->whereYear('return_date', $filters['year']);
        }

        $returns = $query->paginate($entries);

        return [
            'returns' => $returns,
            'total_returns' => $returns->total(),
            'total_amount' => PurchaseReturn::sum('total_amount'),
        ];
    }
}
