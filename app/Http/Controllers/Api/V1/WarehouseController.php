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
     * @group Warehouses
     * @authenticated
     * @queryParam per_page int The number of warehouses to return per page. Defaults to 15. Example: 25
     *
     * @responseField data object[] A list of warehouses.
     * @responseField data[].id integer The ID of the warehouse.
     * @responseField data[].name string The name of the warehouse.
     * @responseField data[].address string The address of the warehouse.
     * @responseField data[].description string A description of the warehouse.
     * @responseField data[].is_main boolean Whether this is the main warehouse.
     * @responseField data[].created_at string The date and time the warehouse was created.
     * @responseField data[].updated_at string The date and time the warehouse was last updated.
     * @responseField links object Links for pagination.
     * @responseField links.first string The URL of the first page.
     * @responseField links.last string The URL of the last page.
     * @responseField links.prev string The URL of the previous page.
     * @responseField links.next string The URL of the next page.
     * @responseField meta object Metadata for pagination.
     * @responseField meta.current_page integer The current page number.
     * @responseField meta.from integer The starting number of the results on the current page.
     * @responseField meta.last_page integer The last page number.
     * @responseField meta.path string The URL path.
     * @responseField meta.per_page integer The number of results per page.
     * @responseField meta.to integer The ending number of the results on the current page.
     * @responseField meta.total integer The total number of results.
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
     * @responseField id integer The ID of the warehouse.
     * @responseField name string The name of the warehouse.
     * @responseField address string The address of the warehouse.
     * @responseField description string A description of the warehouse.
     * @responseField is_main boolean Whether this is the main warehouse.
     * @responseField created_at string The date and time the warehouse was created.
     * @responseField updated_at string The date and time the warehouse was last updated.
     * @response 422 scenario="Creation Failed" {"success": false, "message": "Failed to create warehouse."}
     */
    public function store(\App\Http\Requests\Api\V1\StoreWarehouseRequest $request)
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
     * @responseField id integer The ID of the warehouse.
     * @responseField name string The name of the warehouse.
     * @responseField address string The address of the warehouse.
     * @responseField description string A description of the warehouse.
     * @responseField is_main boolean Whether this is the main warehouse.
     * @responseField created_at string The date and time the warehouse was created.
     * @responseField updated_at string The date and time the warehouse was last updated.
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
     * @responseField id integer The ID of the warehouse.
     * @responseField name string The name of the warehouse.
     * @responseField address string The address of the warehouse.
     * @responseField description string A description of the warehouse.
     * @responseField is_main boolean Whether this is the main warehouse.
     * @responseField created_at string The date and time the warehouse was created.
     * @responseField updated_at string The date and time the warehouse was last updated.
     * @response 422 scenario="Update Failed" {"success": false, "message": "Failed to update warehouse."}
     */
    public function update(\App\Http\Requests\Api\V1\UpdateWarehouseRequest $request, Warehouse $warehouse)
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
     * @response 500 scenario="Deletion Failed" {"success": false, "message": "Failed to delete warehouse."}
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
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     * @response 404 scenario="Warehouse Not Found" {"success": false, "message": "Warehouse not found."}
     * @response 500 scenario="Failed to set main warehouse" {"success": false, "message": "Failed to set main warehouse: <error message>"}
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
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     * @response 404 scenario="Warehouse Not Found" {"success": false, "message": "Warehouse not found."}
     * @response 500 scenario="Failed to unset main warehouse" {"success": false, "message": "Failed to unset main warehouse: <error message>"}
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
