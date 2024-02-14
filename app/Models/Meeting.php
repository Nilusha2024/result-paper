<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_meeting';
    /**
     * The attributes that are mass assignable.
     *
     * @var arraylicense_no
     */
    protected $fillable = ['location', 'date', 'name', 'type', 'code', 'racetype'];
}
