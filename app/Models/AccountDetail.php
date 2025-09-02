<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountDetail extends Model
{
    protected $fillable = [
        'resident_id',
        'waste_collector_id',
        'bank_name',
        'bank_account_number',
        'account_name'
    ];
}
