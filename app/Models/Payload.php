<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payload extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'pond_id',
        'payload',
    ];



    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pond()
    {
        return $this->belongsTo(Pond::class);
    }
}
