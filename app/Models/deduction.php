<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class deduction extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'department_id',
        'deduction_code',
        'deduction_name',
        'description',
        'amount',
        'status',
        'category',
        'deduction_type',
        'startDate',
        'endDate',
        'created_at',
        'updated_at',
        'deleted_at'
    ];


    public function department(): BelongsTo
    {
        return $this->belongsTo(departments::class);
    }


    public function company(): HasOneThrough
    {
        // Get the company through the department relationship
        return $this->hasOneThrough(
            company::class,
            departments::class,
            'id', // Foreign key on departments table
            'id', // Foreign key on companies table
            'department_id', // Local key on deductions table
            'company_id' // Local key on departments table
        );
    }
}
