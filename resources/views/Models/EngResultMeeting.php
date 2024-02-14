<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EngResultMeeting extends Model
{
    protected $table = 'tbl_eng_result_meeting';

    protected $fillable = [
        'name',
        'code',
        'country',
        'category',
        'sportcode',
        'date',
        'events',
        'status',
        'coverage_code',
        'start_time',
        'end_time',
        'going',
    ];
}
