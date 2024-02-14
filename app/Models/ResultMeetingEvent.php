<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultMeetingEvent extends Model
{
    protected $table = 'tbl_result_meeting_event';

    protected $fillable = [
        'meeting_id',
        'runners',
        'distance',
        'name',
        'number',
        'start_time',
        'status',
    ];
}
