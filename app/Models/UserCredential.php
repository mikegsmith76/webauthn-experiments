<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCredential extends Model
{
    protected $fillable = [
        'credential_id',
        'public_key',
        'user_id',  
    ];
}
