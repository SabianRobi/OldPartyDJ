<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TracksPlayedInParty extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_id',
        'added_by',
        'platform',
        'track_uri',
    ];
}
