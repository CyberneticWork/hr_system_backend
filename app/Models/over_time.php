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
        'morning_ot',
        'afternoon_ot',
        'status',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class);
    }
    public function shift()
    {
        return $this->belongsTo(shifts::class, 'shift_code', 'id');
    }
    public function timeCard()
    {
        return $this->belongsTo(time_card::class, 'time_cards_id', 'id');
    }
}
