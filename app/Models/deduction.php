<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
