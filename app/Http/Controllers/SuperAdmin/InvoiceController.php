<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionOrder;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = SubscriptionOrder::with('tenant', 'plan')
            ->where('status', 'paid');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('tenant', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->latest('paid_at')->paginate(20)->withQueryString();

        $stats = [
            'total_invoices' => SubscriptionOrder::where('status', 'paid')->count(),
            'total_revenue' => SubscriptionOrder::where('status', 'paid')->sum('amount'),
        ];

        return view('superadmin.invoices.index', compact('invoices', 'stats'));
    }

    public function show(SubscriptionOrder $order)
    {
        if ($order->status !== 'paid') {
            return redirect()->route('superadmin.invoices.index')
                ->with('error', 'Invoice not found for unpaid orders.');
        }

        $order->load('tenant', 'plan');

        return view('superadmin.invoices.show', compact('order'));
    }
}
