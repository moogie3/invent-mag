<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;

class POSController extends Controller
{
    public function index()
    {
        $pos = Purchase::all();
        $products = Product::all();
        $customers = Customer::all();
        return view('admin.pos.index', compact('pos', 'customers', 'products'));
    }
}