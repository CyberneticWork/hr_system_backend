<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class allowances extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'allowance_code',
        'allowance_name',
        'status',
        'category',
        'allowance_type',
        'amount',
        'company_id',
        'department_id',
        'fixed_date',
        'variable_from',
        'variable_to',
        'from_date',
        'to_date'
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'fixed_date' => 'date',
        'variable_from' => 'date',
        'variable_to' => 'date'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function department()
    {
        return $this->belongsTo(departments::class);
    }
}
