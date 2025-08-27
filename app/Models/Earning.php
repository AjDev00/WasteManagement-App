<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Earning extends Model
{
    protected $fillable = [
        'resident_id',
        'waste_collector_id',
        'collection_id',
        'earning',
        'total_earning',
        'reference_no',
        'authorized_by',
        'total_kg',
    ];
}
