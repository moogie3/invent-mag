<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Services\UnitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $filters = $request->only(['search']);
        $data = $this->unitService->getUnitIndexData($filters, $entries);

        return view('admin.unit.index', $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'symbol' => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
            }

            return redirect()->route('admin.setting.unit')->withErrors($validator)->withInput();
        }

        $result = $this->unitService->createUnit($request->all());

        if (! $result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message'], 'errors' => ['name' => [$result['message']]]], 422);
            }

            return redirect()->route('admin.setting.unit')->withErrors(['name' => $result['message']])->withInput();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Unit created successfully.']);
        }

        return redirect()->route('admin.setting.unit')->with('success', 'Unit created');
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'symbol' => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
            }

            return redirect()->route('admin.setting.unit')->withErrors($validator)->withInput();
        }

        $unit = Unit::find($id);
        if (! $unit) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unit not found.'], 404);
            }

            return redirect()->route('admin.setting.unit')->with('error', 'Unit not found.');
        }

        $result = $this->unitService->updateUnit($unit, $request->all());

        if (! $result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message'], 'errors' => ['name' => [$result['message']]]], 422);
            }

            return redirect()->route('admin.setting.unit')->withErrors(['name' => $result['message']])->withInput();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Unit updated successfully.']);
        }

        return redirect()->route('admin.setting.unit')->with('success', 'Unit updated');
    }

    public function destroy(Request $request, $id)
    {
        $unit = Unit::find($id);
        $result = $this->unitService->deleteUnit($unit);

        if (! $result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message']], 500);
            }

            return redirect()->route('admin.setting.unit')->with('error', $result['message']);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Unit deleted successfully.']);
        }

        return redirect()->route('admin.setting.unit')->with('success', 'Unit deleted');
    }
}
