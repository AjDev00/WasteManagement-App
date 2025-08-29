<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'resident_id',
        'waste_collector_id',
        'title',
        'description',
        'amount',
        'status',
    ];
}
