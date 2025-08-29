<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    protected $fillable = [
        'resident_id',
        'waste_collector_id',
        'bank_name',
        'bank_account_number',
        'account_name',
        'amount',
        'status',
        'approved_by',
    ];

    public function resident() {
        return $this->belongsTo(Resident::class, 'resident_id');
    }

    public function picker() {
        return $this->belongsTo(WasteCollector::class, 'waste_collector_id');
    }

    public function supervisor() {
        return $this->belongsTo(Supervisor::class, 'approved_by');
    }
}
