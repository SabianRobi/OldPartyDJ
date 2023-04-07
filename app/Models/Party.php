<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Party extends Model
{
    use HasFactory;

    protected $hidden = [
        'password',
    ];

    public function tracksInQueue()
    {
        return $this->hasMany(TrackInQueue::class);
    }

    public function participants()
    {
        return $this->hasMany(User::class);
    }
}
