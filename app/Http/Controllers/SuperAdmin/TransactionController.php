<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionOrder;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = SubscriptionOrder::with('tenant', 'plan');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('tenant', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => SubscriptionOrder::count(),
            'paid' => SubscriptionOrder::where('status', 'paid')->count(),
            'pending' => SubscriptionOrder::where('status', 'pending')->count(),
            'failed' => SubscriptionOrder::where('status', 'failed')->count(),
        ];

        return view('superadmin.transactions.index', compact('orders', 'stats'));
    }
}
