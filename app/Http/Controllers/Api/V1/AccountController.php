<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
     * @responseField data object[] A list of accounts.
     * @responseField data[].id integer The ID of the account.
     * @responseField data[].parent_id integer The ID of the parent account.
     * @responseField data[].name string The name of the account.
     * @responseField data[].code string The account code.
     * @responseField data[].type string The type of the account.
     * @responseField data[].description string The description of the account.
     * @responseField data[].level integer The level of the account in the hierarchy.
     * @responseField data[].is_active boolean Whether the account is active.
     * @responseField data[].created_at string The date and time the account was created.
     * @responseField data[].updated_at string The date and time the account was last updated.
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
     * @responseField id integer The ID of the account.
     * @responseField parent_id integer The ID of the parent account.
     * @responseField name string The name of the account.
     * @responseField code string The account code.
     * @responseField type string The type of the account.
     * @responseField description string The description of the account.
     * @responseField level integer The level of the account in the hierarchy.
     * @responseField is_active boolean Whether the account is active.
     * @responseField created_at string The date and time the account was created.
     * @responseField updated_at string The date and time the account was last updated.
     */
    public function store(\App\Http\Requests\Api\V1\StoreAccountRequest $request)
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
     * @responseField id integer The ID of the account.
     * @responseField parent_id integer The ID of the parent account.
     * @responseField name string The name of the account.
     * @responseField code string The account code.
     * @responseField type string The type of the account.
     * @responseField description string The description of the account.
     * @responseField level integer The level of the account in the hierarchy.
     * @responseField is_active boolean Whether the account is active.
     * @responseField created_at string The date and time the account was created.
     * @responseField updated_at string The date and time the account was last updated.
     * @responseField parent object The parent account.
     * @responseField children object[] A list of child accounts.
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
     * @responseField id integer The ID of the account.
     * @responseField parent_id integer The ID of the parent account.
     * @responseField name string The name of the account.
     * @responseField code string The account code.
     * @responseField type string The type of the account.
     * @responseField description string The description of the account.
     * @responseField level integer The level of the account in the hierarchy.
     * @responseField is_active boolean Whether the account is active.
     * @responseField created_at string The date and time the account was created.
     * @responseField updated_at string The date and time the account was last updated.
     */
    public function update(\App\Http\Requests\Api\V1\UpdateAccountRequest $request, Account $account)
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
     * @response 422 scenario="Deletion Failed" {"message": "Account cannot be deleted because it has transactions or child accounts."}
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