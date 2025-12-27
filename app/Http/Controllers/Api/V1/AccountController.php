<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAccountRequest;
use App\Http\Requests\Api\V1\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Http\Request;

/**
 * @group Accounts
 *
 * APIs for managing accounts
 */
class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view-accounts');
    }

    /**
     * Display a listing of the accounts.
     *
     * @group Accounts
     * @authenticated
     * @queryParam per_page int The number of accounts to return per page. Defaults to 15. Example: 25
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"name":"Cash Account","code":"1110",...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $accounts = Account::with('parent')->paginate($perPage);
        return AccountResource::collection($accounts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @group Accounts
     * @authenticated
     * @bodyParam parent_id int The ID of the parent account. Example: 1
     * @bodyParam name string required The name of the account. Example: Cash
     * @bodyParam code string required The account code. Must be unique. Example: 1110
     * @bodyParam type string required The type of the account. Example: asset
     * @bodyParam description string The description of the account. Example: Cash on hand
     * @bodyParam level integer required The level of the account in the hierarchy. Example: 2
     * @bodyParam is_active boolean required Whether the account is active. Example: true
     *
     * @response 201 scenario="Success" {"data":{"id":1,"name":"Cash Account","code":"1110",...}}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function store(StoreAccountRequest $request)
    {
        $validated = $request->validated();

        $level = 0;
        if (isset($validated['parent_id'])) {
            $parent = Account::find($validated['parent_id']);
            if ($parent) {
                $level = $parent->level + 1;
            }
        }

        $account = Account::create($validated + ['level' => $level]);

        return new AccountResource($account);
    }

    /**
     * Display the specified account.
     *
     * @group Accounts
     * @authenticated
     * @urlParam account required The ID of the account. Example: 1
     *
     * @response 200 scenario="Success" {"data":{"id":1,"name":"Cash Account","code":"1110",...}}
     * @response 404 scenario="Not Found" {"message": "Account not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function show(Account $account)
    {
        return new AccountResource($account->load('parent', 'children'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Accounts
     * @authenticated
     * @urlParam account required The ID of the account. Example: 1
     * @bodyParam parent_id int The ID of the parent account. Example: 1
     * @bodyParam name string required The name of the account. Example: Cash
     * @bodyParam code string required The account code. Must be unique. Example: 1110
     * @bodyParam type string required The type of the account. Example: asset
     * @bodyParam description string The description of the account. Example: Cash on hand
     * @bodyParam level integer required The level of the account in the hierarchy. Example: 2
     * @bodyParam is_active boolean required Whether the account is active. Example: true
     *
     * @response 200 scenario="Success" {"data":{"id":1,"name":"Cash Account (Updated)","code":"1110",...}}
     * @response 404 scenario="Not Found" {"message": "Account not found."}
     * @response 422 scenario="Validation Error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(UpdateAccountRequest $request, Account $account)
    {
        $validated = $request->validated();

        $level = 0;
        if (isset($validated['parent_id'])) {
            $parent = Account::find($validated['parent_id']);
            if ($parent) {
                $level = $parent->level + 1;
            }
        }

        $account->update($validated + ['level' => $level]);

        return new AccountResource($account);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @group Accounts
     * @authenticated
     * @urlParam account required The ID of the account. Example: 1
     *
     * @response 204 scenario="Success"
     * @response 404 scenario="Not Found" {"message": "Account not found."}
     * @response 422 scenario="Deletion Failed" {"message": "Account cannot be deleted because it has transactions or child accounts."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function destroy(Account $account)
    {
        if ($account->transactions()->exists() || $account->children()->exists()) {
            return response()->json(['message' => 'Account cannot be deleted because it has transactions or child accounts.'], 422);
        }

        $account->delete();

        return response()->noContent();
    }
}