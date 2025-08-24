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
        'pickup_on_time',
        'delivered_on',
        'accepted_by',
        'picture',
        'summary',
        'status',
        'completed_at',
    ];

    protected static function booted()
    {
        static::saving(function ($collection) {
            // If status is set to completed and completed_at is not already set
            if ($collection->status === 'completed' && is_null($collection->completed_at)) {
                $collection->completed_at = now();
            }
        });
    }

    public function wasteInvoices()
    {
        return $this->hasMany(WasteInvoice::class);
    }

    public function location(){
        return $this->belongsTo(Location::class, 'location_id');
    }
    
    public function resident() {
        return $this->belongsTo(Resident::class, 'resident_id');
    }

    public function picker() {
        return $this->belongsTo(WasteCollector::class, 'waste_collector_id');
    }
}
