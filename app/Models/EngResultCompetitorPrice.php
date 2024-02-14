<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EngResultCompetitorPrice extends Model
{
    protected $table = 'tbl_eng_result_competitor_price';

    protected $fillable = [
        'competitor_id',
        'price_id',
        'time',
        'fract',
        'dec',
        'mktnum',
        'mkttype',
        'timestamp',
    ];
}
