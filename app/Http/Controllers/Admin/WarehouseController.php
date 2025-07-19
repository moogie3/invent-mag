<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    protected $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);
        $data = $this->warehouseService->getWarehouseIndexData($entries);

        return view('admin.warehouse.index', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'description' => 'required'
        ]);

        $result = $this->warehouseService->createWarehouse($request->all());

        if (!$result['success']) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $result['message'], 'errors' => ['name' => [$result['message']]]], 422);
            }
            return back()->withErrors(['name' => $result['message']])->withInput();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Warehouse created successfully.']);
        }
        return redirect()->route('admin.warehouse')->with('success', 'Warehouse created');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'description' => 'required'
        ]);

        $warehouse = Warehouse::find($id);
        $result = $this->warehouseService->updateWarehouse($warehouse, $request->all());

        if (!$result['success']) {
            return back()->withErrors(['is_main' => $result['message']])->withInput();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Warehouse updated successfully.']);
        }
        return redirect()->route('admin.warehouse')->with('success', 'Warehouse updated');
    }

    public function destroy($id)
    {
        $warehouse = Warehouse::find($id);
        $result = $this->warehouseService->deleteWarehouse($warehouse);

        if (!$result['success']) {
            return redirect()->route('admin.warehouse')->with('error', $result['message']);
        }

        return redirect()->route('admin.warehouse')->with('success', 'Warehouse deleted');
    }

    public function setMain($id)
    {
        $warehouse = Warehouse::find($id);
        $this->warehouseService->setMainWarehouse($warehouse);

        return redirect()->route('admin.warehouse')->with('success', 'Main warehouse updated successfully');
    }

    public function unsetMain($id)
    {
        $warehouse = Warehouse::find($id);
        $result = $this->warehouseService->unsetMainWarehouse($warehouse);

        if (!$result['success']) {
            return redirect()->route('admin.warehouse')->with('error', $result['message']);
        }

        return redirect()->route('admin.warehouse')->with('success', 'Main warehouse status removed');
    }
}
