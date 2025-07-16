<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class leaveCalendar extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'department_id',
        'company_id',
        'leave_type',
        'reason',
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function department()
    {
        return $this->belongsTo(departments::class);
    }

    public function company()
    {
        return $this->belongsTo(company::class);
    }
}
