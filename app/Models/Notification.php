<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'resident_id',
        'waste_collector_id',
        'title',
        'message',
        'message_type',
        'is_read'
    ];

    public function resident() {
        return $this->belongsTo(Resident::class, 'resident_id');
    }

    public function picker() {
        return $this->belongsTo(WasteCollector::class, 'waste_collector_id');
    }
}
