<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class employee_allowances extends Model
{

    protected $fillable = [
        'employee_id',
        'allowance_id',
        'custom_amount',
        'is_active',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class);
    }

    public function allowance()
    {
        return $this->belongsTo(allowances::class);
    }
}
