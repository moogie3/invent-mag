<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\Request;

/**
 * @group Warehouses
 *
 * APIs for managing warehouses
 */
class WarehouseController extends Controller
{
    protected $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    /**
     * Display a listing of the warehouses.
     *
     * @queryParam per_page int The number of warehouses to return per page. Defaults to 15. Example: 25
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $warehouses = Warehouse::paginate($perPage);
        return WarehouseResource::collection($warehouses);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @bodyParam name string required The name of the warehouse. Example: Secondary Warehouse
     * @bodyParam address string The address of the warehouse. Example: 456 Storage Rd
     * @bodyParam description string A description of the warehouse. Example: Overflow storage.
     * @bodyParam is_main boolean Is this the main warehouse. Example: false
     *
     * @response 201 {
     *     "data": {
     *         "id": 1,
     *         "name": "Secondary Warehouse",
     *         "address": "456 Storage Rd",
     *         "description": "Overflow storage.",
     *         "is_main": false,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-26T12:00:00.000000Z"
     *     }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'is_main' => 'boolean',
        ]);

        $warehouse = $this->warehouseService->createWarehouse($validated);

        return new WarehouseResource($warehouse);
    }

    /**
     * Display the specified warehouse.
     *
     * @urlParam warehouse required The ID of the warehouse. Example: 1
     */
    public function show(Warehouse $warehouse)
    {
        return new WarehouseResource($warehouse);
    }

    /**
     * Update the specified resource in storage.
     *
     * @urlParam warehouse integer required The ID of the warehouse. Example: 1
     * @bodyParam name string required The name of the warehouse. Example: Main Warehouse
     * @bodyParam address string The address of the warehouse. Example: 123 Warehouse St
     * @bodyParam description string A description of the warehouse. Example: Primary storage facility.
     * @bodyParam is_main boolean Is this the main warehouse. Example: true
     *
     * @response 200 {
     *     "data": {
     *         "id": 1,
     *         "name": "Main Warehouse",
     *         "address": "123 Warehouse St",
     *         "description": "Primary storage facility.",
     *         "is_main": true,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-27T12:00:00.000000Z"
     *     }
     * }
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'is_main' => 'boolean',
        ]);

        $warehouse = $this->warehouseService->updateWarehouse($warehouse, $validated);

        return new WarehouseResource($warehouse);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @urlParam warehouse integer required The ID of the warehouse to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(Warehouse $warehouse)
    {
        $this->warehouseService->deleteWarehouse($warehouse);

        return response()->noContent();
    }

    /**
     * @group Warehouses
     * @title Set as Main Warehouse
     * @urlParam id integer required The ID of the warehouse to set as main. Example: 1
     *
     * @response {
     *  "success": true,
     *  "message": "Main warehouse updated successfully."
     * }
     */
    public function setMain(Request $request, $id)
    {
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Warehouse not found.'], 404);
        }

        $result = $this->warehouseService->setMainWarehouse($warehouse);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 500);
        }

        return response()->json(['success' => true, 'message' => 'Main warehouse updated successfully.']);
    }

    /**
     * @group Warehouses
     * @title Unset as Main Warehouse
     * @urlParam id integer required The ID of the warehouse to unset as main. Example: 1
     *
     * @response {
     *  "success": true,
     *  "message": "Main warehouse status removed."
     * }
     */
    public function unsetMain(Request $request, $id)
    {
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Warehouse not found.'], 404);
        }
        
        $result = $this->warehouseService->unsetMainWarehouse($warehouse);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 500);
        }

        return response()->json(['success' => true, 'message' => 'Main warehouse status removed.']);
    }
}
