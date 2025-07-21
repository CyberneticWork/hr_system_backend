<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class departments extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        // 'code',
    ];

    public function deductions(): HasMany
    {
        return $this->hasMany(deduction::class);
    }

    public function company()
    {
        return $this->belongsTo(company::class, 'company_id');
    }

    public function leaveCalendars(): HasMany
    {
        return $this->hasMany(leaveCalendar::class);
    }

    // Optional: If you have subdepartments
    public function subdepartments()
    {
        return $this->hasMany(sub_departments::class, 'department_id');
    }

    // Relationship: department → rosters
    public function rosters()
    {
        return $this->hasMany(roster::class);
    }
}
