<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SamuraiQuote extends Model
{

    protected $fillable = ['quote', 'embedding'];

    protected $casts = [
        'embedding' => 'array',
    ];
}
