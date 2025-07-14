<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Allowances extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'allowance_code',
        'allowance_name',
        'status',
        'category',
        'allowance_type',
        'company_id',
        'from_date',
        'to_date'
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}