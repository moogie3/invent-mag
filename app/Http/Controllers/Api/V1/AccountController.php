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
     * @queryParam per_page int The number of accounts to return per page. Defaults to 15. Example: 25
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $accounts = Account::with('parent')->paginate($perPage);
        return AccountResource::collection($accounts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified account.
     *
     * @urlParam account required The ID of the account. Example: 1
     */
    public function show(Account $account)
    {
        return new AccountResource($account->load('parent', 'children'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
