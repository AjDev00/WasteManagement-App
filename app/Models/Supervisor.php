<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class Supervisor extends Model
{
    use HasFactory, Notifiable, HasApiTokens;
    
    protected $fillable = [
        'fullname',
        'phone_number',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];
}
