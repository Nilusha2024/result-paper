<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EngResultRacebet extends Model
{
    protected $table = 'tbl_eng_result_racebet';

    protected $fillable = [
        'event_id',
        'racebet_id',
        'bet_type',
        'amount',
        'instance',
        'type',
    ];
}
