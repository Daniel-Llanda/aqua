<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PondCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'pond_id',
        'user_id',
        'cycle_number',
        'status',
        'hatching_started_at',
        'harvest_date',
        'species_data',
        'harvest_data',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'hatching_started_at' => 'date',
            'harvest_date' => 'date',
            'completed_at' => 'date',
            'species_data' => 'array',
            'harvest_data' => 'array',
        ];
    }

    public function pond()
    {
        return $this->belongsTo(Pond::class);
    }
}
