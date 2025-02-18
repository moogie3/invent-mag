<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sales;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function index()
    {
        $sales = Sales::with(['product', 'customer'])->get();
        return view('admin.sales.index', ['sales' => $sales]);
    }
}