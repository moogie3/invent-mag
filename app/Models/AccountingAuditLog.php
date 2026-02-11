<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class AccountingAuditLog extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'description',
        'user_id',
        'user_name',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_POST = 'post';
    public const ACTION_UNPOST = 'unpost';
    public const ACTION_VOID = 'void';
    public const ACTION_REVERSE = 'reverse';
    public const ACTION_APPROVE = 'approve';
    public const ACTION_REJECT = 'reject';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getEntityAttribute()
    {
        $entityType = $this->entity_type;
        if (class_exists($entityType)) {
            return $entityType::find($this->entity_id);
        }
        return null;
    }
}
