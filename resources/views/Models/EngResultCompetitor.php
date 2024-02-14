<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EngResultCompetitor extends Model
{
    protected $table = 'tbl_eng_result_competitor';

    protected $fillable = [
        'event_id',
        'name',
        'competitor_id',
        'short_name',
        'jockey',
        'jockey_allowance',
        'short_jockey',
        'num',
        'age',
        'trainer',
        'owner',
        'dam',
        'sire',
        'damsire',
        'bred',
        'weight',
        'born_date',
        'color',
        'sex',
        'finish_position',
        'fav_status'
    ];
}
