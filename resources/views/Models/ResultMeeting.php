<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultMeeting extends Model
{
    protected $table = 'tbl_result_meeting';

    protected $fillable = [
        'location',
        'name',
        'date',
        'type',
        'going',
        'code',
        'weather',
    ];
}
