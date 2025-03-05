<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailySales;
use Illuminate\Http\Request;

class DailySalesController extends Controller
{
    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);

        $query = DailySales::query();

        // apply filters
        if ($request->has('month') && $request->month) {
            $query->whereMonth('date', $request->month);
        }
        if ($request->has('year') && $request->year) {
            $query->whereYear('date', $request->year);
        }

        $dss = $query->paginate($entries);
        $totaldss = $query->count();
        $totalDailySales = $query->sum('total');

        return view('admin.ds.index', compact('dss', 'entries', 'totaldss', 'totalDailySales'));
    }

    public function create()
    {
        $dss = DailySales::all();
        return view('admin.ds.ds-create', compact('dss'));
    }

    public function store(Request $request)
    {
        $data = $request->except('_token');
        $request->validate([
            'date' => 'required|date',
            'total' => 'required',
        ]);

        DailySales::create($data);

        return redirect()->route('admin.ds')->with('success', 'Daily Sales created');
    }

    public function destroy($id)
    {
        DailySales::find($id)->delete();

        return redirect()->route('admin.ds')->with('success', 'Daily Sales deleted');
    }
}