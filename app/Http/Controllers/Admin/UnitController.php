<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(){
        $units = Unit::all();
        return view ('admin.unit.index', ['units' => $units]);
    }

    public function create(){
        $units = Unit::all();
        return view('admin.unit.unit-create', compact('units'));
    }

    public function edit($id)
    {
        $units = Unit::find($id);
        return view('admin.unit.unit-edit', ['units' => $units]);
    }

    public function store(Request $request){
        $data = $request->except("_token");
        $request->validate([
            'name' => 'required',
            'symbol' => 'required',
        ]);

        $isUnitExist = Unit::where('name', $request->name)->exists();

        if ($isUnitExist) {
            return back()
            ->withErrors([
                'name' => 'This unit already exist'
            ])

            ->withInput();
        }

        Unit::create($data);

        return redirect()->route('admin.unit')->with('success','Unit created');
    }

    public function update(Request $request, $id){
        $data = $request->except("_token");
        $request->validate([
            'name' => 'required',
            'symbol' => 'required',
        ]);

        $units = Unit::find($id);
        $units->update($data);
        return redirect()->route('admin.unit')->with('success', 'Unit updated');
    }

    public function destroy($id)
    {
        Unit::find($id)->delete();

        return redirect()->route('admin.unit')->with('success', 'Unit deleted');
    }
}