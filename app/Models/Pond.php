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
    ];

    protected function casts(): array
    {
        return [
            'fish_type' => 'array',
        ];
    }

    public function cycles()
    {
        return $this->hasMany(PondCycle::class);
    }

}
