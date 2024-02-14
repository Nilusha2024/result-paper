<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_event';
    /**
     * The attributes that are mass assignable.
     *
     * @var arraylicense_no
     */
    protected $fillable = ['meeting_id', 'name', 'event_number', 'starttime', 'offtime', 'status', 'file_name_twodb', 'file_name_abeta'];

    public function meeting()
    {
        return $this->hasMany('App\Meeting', 'id', 'meeting_id');
    }
}
