<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class WasteCollector extends Model
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'phone_number',
        'password',
        'verifying_id',
        'verify_type',
        'verifying_image',
        'bank_name',
        'bank_account_number',
    ];

    protected $hidden = [
        'password',
    ];

    public function collection() {
        return $this->hasMany(Collection::class);
    }
}
