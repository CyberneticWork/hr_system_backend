<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class over_time extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'shift_code',
        'time_cards_id',
        'ot_hours',
    ];
}
