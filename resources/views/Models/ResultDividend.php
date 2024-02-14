<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultDividend extends Model
{
    protected $table = 'tbl_result_dividend';
    protected $fillable = [
        'event_id',
        'event_type',
        'dividend_type',
        'instance',
        'dividend_amount',
        'jackpot_carried_over',
        'status',
        'runner_numbers',
    ];
}
