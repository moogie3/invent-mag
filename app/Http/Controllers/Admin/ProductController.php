<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(){
        $products = Product::all();
        return view ('admin.product', ['products' => $products]);
    }

    public function create() {
        return view('admin.product-create');
    }
}