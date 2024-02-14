<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultPool extends Model
{
    protected $table = 'tbl_result_pool';
    protected $fillable = [
        'event_id',
        'pool_id',
        'pool_type',
        'jackpot',
        'leg_number',
        'status',
        'pool_total',
        'substitute',
        'closed_time',
    ];
}
