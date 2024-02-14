<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultCompetitor extends Model
{
    protected $table = 'tbl_result_competitor';

    protected $primaryKey = 'id';

    protected $fillable = [
        'competitor_id',
        'event_id',
        'event_type',
        'name',
        'description',
        'competitor_no',
        'competitor_type',
        'post_no',
        'finish_position',
        'form',
        'weight',
        'jockey',
        'trainer',
        'status',
        'fav_status',
    ];

    // Automatically manage timestamps
    public $timestamps = true;
    
    public function prices()
    {
        return $this->hasMany(ResultCompetitorPrice::class, 'competitor_id', 'competitor_id');
    }

    public function dividend()
    {
        return $this->belongsTo(ResultDividend::class, 'event_id', 'event_id');
    }
    public function runner()
    {
        return $this->belongsTo(ResultDividend::class, 'competitor_no', 'runner_numbers');
    }
}
