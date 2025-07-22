<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class time_card extends Model
{
    protected $fillable = [
        'employee_id', 'time', 'date', 'working_hours', 'entry', 'status'
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'employee_id');
    }
}
