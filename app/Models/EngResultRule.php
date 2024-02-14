<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EngResultRule extends Model
{
    protected $table = 'tbl_eng_result_rule';

    protected $fillable = [
        'event_id',
        'competitor_id',
        'rule_id',
        'type',
        'deduction',
        'runner_deduction',
    ];
}
