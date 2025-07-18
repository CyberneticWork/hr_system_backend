<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class shifts extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'shift_code',
        'shift_description',
        'start_time',
        'end_time',
        'morning_ot_start',
        'special_ot_start',
        'late_deduction',
        'midnight_roster',
        'nopay_hour_halfday',
        'break_time'
    ];

    protected $casts = [
        'midnight_roster' => 'boolean',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'morning_ot_start' => 'datetime:H:i',
        'special_ot_start' => 'datetime:H:i',
        'late_deduction' => 'datetime:H:i'
    ];
}