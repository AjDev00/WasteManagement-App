<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Resident extends Model
{
    use HasFactory, Notifiable, HasApiTokens;
    
    protected $fillable = [
        'fullname',
        'phone_number',
        'address',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];
}
