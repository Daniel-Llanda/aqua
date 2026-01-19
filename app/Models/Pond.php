<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pond extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'hectares',
        'fish_type',
        'hatching_date',
        'harvest_date',
    ];

}
