<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    // relationships

    public function events(): HasMany
    {
        return $this->hasMany(EngResultEvent::class, 'meeting_auto_id');
    }
}
