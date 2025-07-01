<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request){
        $entries = $request->input('entries', 10);//pagination
        $units = Unit::paginate($entries);
        $totalunit = Unit::count();
        return view('admin.unit.index', compact('units', 'entries','totalunit'));
    }

    public function store(Request $request){
        $data = $request->except("_token");
        $request->validate([
            'name' => 'required',
            'symbol' => 'required',
        ]);

        $isUnitExist = Unit::where('name', $request->name)->exists();

        if ($isUnitExist) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'This unit already exists.', 'errors' => ['name' => ['This unit already exists.']]], 422);
            }
            return back()
            ->withErrors([
                'name' => 'This unit already exist'
            ])

            ->withInput();
        }

        Unit::create($data);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Unit created successfully.']);
        }
        return redirect()->route('admin.setting.unit')->with('success','Unit created');
    }

    public function update(Request $request, $id){
        $data = $request->except("_token");
        $request->validate([
            'name' => 'required',
            'symbol' => 'required',
        ]);

        $units = Unit::find($id);
        $units->update($data);
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Unit updated successfully.']);
        }
        return redirect()->route('admin.setting.unit')->with('success', 'Unit updated');
    }

    public function destroy($id)
    {
        Unit::find($id)->delete();

        return redirect()->route('admin.setting.unit')->with('success', 'Unit deleted');
    }

}