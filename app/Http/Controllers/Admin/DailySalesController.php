<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailySales;
use Illuminate\Http\Request;

class DailySalesController extends Controller
{
    public function index(Request $request){
        $entries = $request->input('entries', 10);//pagination
        $dss = DailySales::paginate($entries);
        $totaldss = DailySales::count();

        $totalDailySales = DailySales::all()->sum('total');

        return view('admin.ds.index', compact('dss', 'entries','totaldss','totalDailySales'));
    }

    public function create(){
        $dss = DailySales::all();
        return view('admin.ds.ds-create', compact('dss'));
    }

    public function store(Request $request){
        $data = $request->except("_token");
        $request->validate([
            'date' => 'required|date',
            'total' => 'required',
        ]);

        DailySales::create($data);

        return redirect()->route('admin.ds')->with('success','Daily Sales created');
    }

    public function destroy($id)
    {
        DailySales::find($id)->delete();

        return redirect()->route('admin.ds')->with('success', 'Daily Sales deleted');
    }
}