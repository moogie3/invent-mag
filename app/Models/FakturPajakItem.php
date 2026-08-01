<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FakturPajakItem extends Model
{
    use BelongsToTenant;

    protected $table = 'faktur_pajak_items';

    protected $guarded = ['id', 'tenant_id'];

    protected function casts(): array
    {
        return [
            'harga_satuan' => 'decimal:2',
            'jumlah_barang' => 'decimal:4',
            'harga_total' => 'decimal:2',
            'diskon' => 'decimal:2',
            'dpp' => 'decimal:2',
            'ppn' => 'decimal:2',
        ];
    }

    public function fakturPajak(): BelongsTo
    {
        return $this->belongsTo(FakturPajak::class);
    }
}
