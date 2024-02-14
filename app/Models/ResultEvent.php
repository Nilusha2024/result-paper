<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultEvent extends Model
{
    protected $table = 'tbl_result_event';

    protected $fillable = [
        'event_id',
        'meeting_id',
        'event_name',
        'race_num',
        'event_type',
        'description',
        'start_datetime',
        'utc_start_datetime',
        'end_datetime',
        'going',
        'status',
        'length',
        'country_name',
        'country_code',
        'location_code',
        'mtp',
        'closed_time',
    ];

    public function competitors()
    {
        return $this->hasMany(ResultCompetitor::class, 'event_id', 'event_id');
    }

    public function dividends()
    {
        return $this->hasMany(ResultDividend::class, 'event_id', 'event_id');
    }

    public function pools()
    {
        return $this->hasMany(ResultPool::class, 'event_id', 'event_id');
    }

}
