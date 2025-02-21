<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sales;
use App\Models\User;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);
        $sales = Sales::with(['product', 'customer'])->paginate($entries);
        $totalinvoice = Sales::count();

        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');
        return view('admin.sales.index', compact('entries','sales','totalinvoice','shopname','address'));
    }
}