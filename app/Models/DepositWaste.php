<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepositWaste extends Model
{
    protected $fillable = [
        'waste_collector_id',
        'recycler_company_id',
        'deposited_at',
        'type_id',
        'kg',
        'picture'
    ];
}
