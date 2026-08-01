<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'user_name',
        'user_role',
        'module',
        'action',
        'entity_type',
        'entity_id',
        'entity_label',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Action constants
    public const ACTION_CREATE = 'create';

    public const ACTION_UPDATE = 'update';

    public const ACTION_DELETE = 'delete';

    public const ACTION_LOGIN = 'login';

    public const ACTION_LOGOUT = 'logout';

    public const ACTION_EXPORT = 'export';

    public const ACTION_IMPORT = 'import';

    public const ACTION_APPROVE = 'approve';

    public const ACTION_REJECT = 'reject';

    public const ACTION_TRANSFER = 'transfer';

    public const ACTION_ADJUST = 'adjust';

    public const ACTION_PAYMENT = 'payment';

    public const ACTION_POST = 'post';

    public const ACTION_VOID = 'void';

    public const ACTION_REVERSE = 'reverse';

    // Module constants — one per navigation area
    public const MODULE_DASHBOARD = 'Dashboard';

    public const MODULE_PRODUCT = 'Product';

    public const MODULE_SALES = 'Sales';

    public const MODULE_SALES_RETURN = 'Sales Return';

    public const MODULE_PURCHASE_ORDER = 'Purchase Order';

    public const MODULE_PURCHASE_RETURN = 'Purchase Return';

    public const MODULE_POS = 'POS';

    public const MODULE_CUSTOMER = 'Customer';

    public const MODULE_SUPPLIER = 'Supplier';

    public const MODULE_WAREHOUSE = 'Warehouse';

    public const MODULE_STOCK_ADJUSTMENT = 'Stock Adjustment';

    public const MODULE_STOCK_TRANSFER = 'Stock Transfer';

    public const MODULE_STOCK_OPNAME = 'Stock Opname';

    public const MODULE_CATEGORY = 'Category';

    public const MODULE_UNIT = 'Unit';

    public const MODULE_TAX = 'Tax';

    public const MODULE_CURRENCY = 'Currency';

    public const MODULE_USER = 'User';

    public const MODULE_SETTINGS = 'Settings';

    public const MODULE_ACCOUNTING = 'Accounting';

    public const MODULE_BANK_RECONCILIATION = 'Bank Reconciliation';

    public const MODULE_JOURNAL_ENTRY = 'Journal Entry';

    public const MODULE_SALES_PIPELINE = 'Sales Pipeline';

    public const MODULE_CRM = 'CRM';

    public const MODULE_SRM = 'Supplier Management';

    public const MODULE_EMPLOYEE = 'Employee';

    public const MODULE_ATTENDANCE = 'Attendance';

    public const MODULE_PAYROLL = 'Payroll';

    public const MODULE_REPORT = 'Report';

    public const MODULE_PAYMENT = 'Payment';

    /**
     * Relationship to user (may be null if user was deleted).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Human-friendly badge color for each action.
     */
    public function getActionBadgeClassAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_CREATE => 'bg-success text-white',
            self::ACTION_UPDATE => 'bg-blue text-white',
            self::ACTION_DELETE => 'bg-danger text-white',
            self::ACTION_LOGIN => 'bg-teal text-white',
            self::ACTION_LOGOUT => 'bg-secondary text-white',
            self::ACTION_EXPORT => 'bg-purple text-white',
            self::ACTION_IMPORT => 'bg-indigo text-white',
            self::ACTION_PAYMENT => 'bg-yellow text-dark',
            self::ACTION_ADJUST, self::ACTION_TRANSFER => 'bg-orange text-white',
            self::ACTION_POST, self::ACTION_APPROVE => 'bg-cyan text-white',
            self::ACTION_VOID, self::ACTION_REJECT => 'bg-pink text-white',
            self::ACTION_REVERSE => 'bg-lime text-dark',
            default => 'bg-secondary text-white',
        };
    }
}
