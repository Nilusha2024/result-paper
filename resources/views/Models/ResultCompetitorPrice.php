<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultCompetitorPrice extends Model
{
    protected $table = 'tbl_result_competitor_price';
    protected $fillable = [
        'competitor_id',
        'odds',
        'price_type',
    ];

    public $timestamps = true;
}
