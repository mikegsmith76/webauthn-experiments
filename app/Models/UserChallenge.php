<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserChallenge extends Model
{
    protected $fillable = [
        'user_id',
        'challenge_data',
    ];
}
