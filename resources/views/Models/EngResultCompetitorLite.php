<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EngResultCompetitorLite extends Model
{
    protected $table = 'tbl_eng_result_competitor_lite';

    protected $fillable = [
        'event_id',
        'name',
        'competitor_id',
        'jockey',
        'jockey_allowance',
        'num',
        'trainer',
        'finish_position',
        'run_status',
        'fav_status'
    ];
}
