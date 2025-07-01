<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $entries = $request->input('entries', 10); // Pagination
        $wos = Warehouse::paginate($entries);
        $totalwarehouse = Warehouse::count();
        $mainWarehouse = Warehouse::where('is_main', true)->first();
        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');

        return view('admin.warehouse.index', compact('shopname', 'address', 'wos', 'entries', 'totalwarehouse', 'mainWarehouse'));
    }

    public function store(Request $request)
    {
        $data = $request->except("_token");
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'description' => 'required'
        ]);

        $isWOExist = Warehouse::where('name', $request->name)->exists();

        if ($isWOExist) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'This warehouse already exists.', 'errors' => ['name' => ['This warehouse already exists.']]], 422);
            }
            return back()
                ->withErrors([
                    'name' => 'This warehouse already exists'
                ])
                ->withInput();
        }

        // Check if this is marked as main warehouse
        if (isset($data['is_main']) && $data['is_main']) {
            // Check if there's already a main warehouse
            if (Warehouse::hasMainWarehouse()) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'There is already a main warehouse defined. Please unset the current main warehouse first.', 'errors' => ['is_main' => ['There is already a main warehouse defined. Please unset the current main warehouse first.']]], 422);
                }
                return back()
                    ->withErrors([
                        'is_main' => 'There is already a main warehouse defined. Please unset the current main warehouse first.'
                    ])
                    ->withInput();
            }
        }

        Warehouse::create($data);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Warehouse created successfully.']);
        }
        return redirect()->route('admin.warehouse')->with('success', 'Warehouse created');
    }

    public function update(Request $request, $id)
    {
        $data = $request->except("_token");
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'description' => 'required'
        ]);

        $wos = Warehouse::find($id);

        // Check if this is marked as main warehouse
        if (isset($data['is_main']) && $data['is_main']) {
            // Check if there's already a main warehouse (other than this one)
            if (Warehouse::hasMainWarehouse($id)) {
                return back()
                    ->withErrors([
                        'is_main' => 'There is already a main warehouse defined. Please unset the current main warehouse first.'
                    ])
                    ->withInput();
            }
        }

        $wos->update($data);
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Warehouse updated successfully.']);
        }
        return redirect()->route('admin.warehouse')->with('success', 'Warehouse updated');
    }

    public function destroy($id)
    {
        $warehouse = Warehouse::find($id);

        // Check if this is the main warehouse
        if ($warehouse->is_main) {
            return redirect()->route('admin.warehouse')
                ->with('error', 'Cannot delete the main warehouse. Please set another warehouse as main first.');
        }

        $warehouse->delete();

        return redirect()->route('admin.warehouse')->with('success', 'Warehouse deleted');
    }

    public function setMain($id)
    {
        // First, unset all warehouses as main
        Warehouse::where('is_main', true)->update(['is_main' => false]);

        // Then set the selected warehouse as main
        $warehouse = Warehouse::find($id);
        $warehouse->is_main = true;
        $warehouse->save();

        return redirect()->route('admin.warehouse')->with('success', 'Main warehouse updated successfully');
    }

    public function unsetMain($id)
    {
        $warehouse = Warehouse::find($id);
        if ($warehouse->is_main) {
            $warehouse->is_main = false;
            $warehouse->save();
            return redirect()->route('admin.warehouse')->with('success', 'Main warehouse status removed');
        }

        return redirect()->route('admin.warehouse')->with('error', 'This is not the main warehouse');
    }
}