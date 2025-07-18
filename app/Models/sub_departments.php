<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class sub_departments extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'department_id',
        'name'
    ];

    public function department()
    {
        return $this->belongsTo(departments::class, 'department_id');
    }

    // Relationship: sub_department → rosters
    public function rosters()
    {
        return $this->hasMany(roster::class);
    }
}
