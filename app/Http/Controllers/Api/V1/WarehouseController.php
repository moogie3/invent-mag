<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreWarehouseRequest;
use App\Http\Requests\Api\V1\UpdateWarehouseRequest;
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
     * @group Warehouses
     * @authenticated
     * @queryParam per_page int The number of warehouses to return per page. Defaults to 15. Example: 25
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"name":"Main Warehouse",...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(Request $request)
    {
        $entries = $request->input('per_page', 15);
        $data = $this->warehouseService->getWarehouseIndexData($entries);
        return WarehouseResource::collection($data['wos']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @group Warehouses
     * @authenticated
     * @bodyParam name string required The name of the warehouse. Example: Secondary Warehouse
     * @bodyParam address string The address of the warehouse. Example: 456 Storage Rd
     * @bodyParam description string A description of the warehouse. Example: Overflow storage.
     * @bodyParam is_main boolean Is this the main warehouse. Example: false
     *
     * @response 201 scenario="Success" {"data":{"id":1,"name":"Secondary Warehouse",...}}
     * @response 422 scenario="Validation Error" {"success":false,"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function store(StoreWarehouseRequest $request)
    {
        $result = $this->warehouseService->createWarehouse($request->validated());
        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }
        return new WarehouseResource($result['warehouse']);
    }

    /**
     * Display the specified warehouse.
     *
     * @group Warehouses
     * @authenticated
     * @urlParam warehouse required The ID of the warehouse. Example: 1
     *
     * @response 200 scenario="Success" {"data":{"id":1,"name":"Main Warehouse",...}}
     * @response 404 scenario="Not Found" {"message": "Warehouse not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function show(Warehouse $warehouse)
    {
        return new WarehouseResource($warehouse);
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Warehouses
     * @authenticated
     * @urlParam warehouse integer required The ID of the warehouse. Example: 1
     * @bodyParam name string required The name of the warehouse. Example: Main Warehouse
     * @bodyParam address string The address of the warehouse. Example: 123 Warehouse St
     * @bodyParam description string A description of the warehouse. Example: Primary storage facility.
     * @bodyParam is_main boolean Is this the main warehouse. Example: true
     *
     * @response 200 scenario="Success" {"data":{"id":1,"name":"Main Warehouse (Updated)",...}}
     * @response 404 scenario="Not Found" {"message": "Warehouse not found."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     * @response 500 scenario="Error Updating Warehouse" {"success":false,"message":"Failed to update warehouse: Internal server error."}
     */
    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        $result = $this->warehouseService->updateWarehouse($warehouse, $request->validated());
        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }
        return new WarehouseResource($result['warehouse']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @group Warehouses
     * @authenticated
     * @urlParam warehouse integer required The ID of the warehouse to delete. Example: 1
     *
     * @response 204 scenario="Success"
     * @response 404 scenario="Not Found" {"message": "Warehouse not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     * @response 500 scenario="Error Deleting Warehouse" {"success":false,"message":"Failed to delete warehouse: Internal server error."}
     */
    public function destroy(Warehouse $warehouse)
    {
        $result = $this->warehouseService->deleteWarehouse($warehouse);
        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 500);
        }
        return response()->noContent();
    }

    /**
     * Set as Main Warehouse
     *
     * @group Warehouses
     * @authenticated
     * @urlParam id integer required The ID of the warehouse to set as main. Example: 1
     *
     * @response 200 scenario="Success" {"success":true,"message":"Main warehouse updated successfully."}
     * @response 404 scenario="Warehouse Not Found" {"success": false, "message": "Warehouse not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     * @response 500 scenario="Error Setting Main Warehouse" {"success":false,"message":"Failed to set main warehouse: Internal server error."}
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
     * Unset as Main Warehouse
     *
     * @group Warehouses
     * @authenticated
     * @urlParam id integer required The ID of the warehouse to unset as main. Example: 1
     *
     * @response 200 scenario="Success" {"success":true,"message":"Main warehouse status removed."}
     * @response 404 scenario="Warehouse Not Found" {"success": false, "message": "Warehouse not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     * @response 500 scenario="Error Unsetting Main Warehouse" {"success":false,"message":"Failed to unset main warehouse: Internal server error."}
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
