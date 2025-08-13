<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $fillable = [
        'resident_id',
        'waste_collector_id',
        'location_id',
        'amount_total',
        'pickup_on',
        'delivered_on',
        'accepted_by',
        'picture',
        'summary',
        'status'
    ];
}
