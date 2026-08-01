<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountService
{
    /**
     * Get all accounts with their children (hierarchical).
     */
    public function getAccountsWithChildren(): Collection
    {
        return Account::with('children')
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get();
    }

    /**
     * Get all accounts ordered by name.
     */
    public function getAllAccountsOrdered(): Collection
    {
        return Account::orderBy('name')->get();
    }

    /**
     * Get all accounts except the given one, ordered by name.
     */
    public function getAllAccountsExcept(int $excludeId): Collection
    {
        return Account::where('id', '!=', $excludeId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get accounts grouped by type.
     */
    public function getAccountsGroupedByType(): Collection
    {
        return Account::all()->groupBy('type');
    }

    /**
     * Create a new account.
     *
     * @throws ValidationException
     */
    public function createAccount(array $data): Account
    {
        $this->validateAccountData($data);

        $level = $this->calculateAccountLevel($data['parent_id'] ?? null);

        return Account::create(array_merge($data, ['level' => $level]));
    }

    /**
     * Update an existing account.
     *
     * @throws ValidationException
     */
    public function updateAccount(Account $account, array $data): Account
    {
        $this->validateAccountData($data, $account->id);

        $level = $this->calculateAccountLevel($data['parent_id'] ?? null);

        $account->update(array_merge($data, ['level' => $level]));

        return $account;
    }

    /**
     * Delete an account if it has no transactions or children.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteAccount(Account $account): array
    {
        if ($account->transactions()->exists()) {
            return [
                'success' => false,
                'message' => 'Account cannot be deleted because it has transactions.',
            ];
        }

        if ($account->children()->exists()) {
            return [
                'success' => false,
                'message' => 'Account cannot be deleted because it has child accounts.',
            ];
        }

        $account->delete();

        return [
            'success' => true,
            'message' => 'Account deleted successfully.',
        ];
    }

    /**
     * Calculate the level for an account based on its parent.
     */
    protected function calculateAccountLevel(?int $parentId): int
    {
        if (! $parentId) {
            return 0;
        }

        $parent = Account::find($parentId);

        return $parent ? $parent->level + 1 : 0;
    }

    /**
     * Validate account data.
     *
     * @throws ValidationException
     */
    protected function validateAccountData(array $data, ?int $ignoreId = null): void
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('accounts', 'name')->where('tenant_id', auth()->user()->tenant_id)->ignore($ignoreId),
            ],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('accounts', 'code')->where('tenant_id', auth()->user()->tenant_id)->ignore($ignoreId),
            ],
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id' => 'nullable|exists:accounts,id',
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Get account by ID.
     */
    public function findById(int $id): ?Account
    {
        return Account::find($id);
    }

    /**
     * Check if account can be deleted.
     */
    public function canDelete(Account $account): bool
    {
        return ! $account->transactions()->exists() && ! $account->children()->exists();
    }
}
