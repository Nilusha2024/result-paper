<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EngResultEvent extends Model
{
    protected $table = 'tbl_eng_result_event';

    protected $casts = [
        'time' => 'datetime',
    ];

    protected $fillable = [
        'meeting_auto_id',
        'meeting_code',
        'name',
        'event_type',
        'is_virtual',
        'event_id',
        'num',
        'date',
        'time',
        'places_expected',
        'each_way_places',
        'coverage_code',
        'course_type',
        'surface',
        'grade',
        'handicap',
        'status',
        'runners',
        'going',
        'distance',
        'offtime',
        'progress_code',
        'pmsg',
    ];

    public function competitors()
    {
        return $this->hasMany(EngResultCompetitor::class, 'event_id', 'event_id');
    }

    public function racebets()
    {
        return $this->hasMany(EngResultRacebet::class, 'event_id', 'event_id');
    }
}
