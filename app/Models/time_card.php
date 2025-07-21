<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class time_card extends Model
{
    public function employee()
    {
        return $this->belongsTo(employee::class, 'employee_id');
    }
}
