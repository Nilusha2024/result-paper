<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EngResultEventPrize extends Model
{
    protected $table = 'tbl_eng_result_event_prize';

    protected $fillable = [
        'event_id',
        'position',
        'amount',
    ];
}
