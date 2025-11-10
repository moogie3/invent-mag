<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Services\UnitService;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    protected $unitService;

    public function __construct(UnitService $unitService)
    {
        $this->unitService = $unitService;
    }

    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);
        $data = $this->unitService->getUnitIndexData($entries);
        return view('admin.unit.index', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'symbol' => 'required',
        ]);

        $result = $this->unitService->createUnit($request->all());

        if (!$result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message'], 'errors' => ['name' => [$result['message']]]], 422);
            }
            return back()->withErrors(['name' => $result['message']])->withInput();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Unit created successfully.']);
        }
        return redirect()->route('admin.setting.unit')->with('success', 'Unit created');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'symbol' => 'required',
        ]);

        $unit = Unit::find($id);
        $result = $this->unitService->updateUnit($unit, $request->all());

        if (!$result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message'], 'errors' => ['name' => [$result['message']]]], 422);
            }
            return back()->withErrors(['name' => $result['message']])->withInput();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Unit updated successfully.']);
        }
        return redirect()->route('admin.setting.unit')->with('success', 'Unit updated');
    }

    public function destroy($id)
    {
        $unit = Unit::find($id);
        $this->unitService->deleteUnit($unit);

        return redirect()->route('admin.setting.unit')->with('success', 'Unit deleted');
    }
}
