<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WasteInvoice extends Model
{
    protected $fillable = [
        'collection_id',
        'type_id',
        'kg',
        'amount',
        'picture',
        'count',
        'description',
        'status',
        'created_by'
    ];

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }
}
