<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class absence extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'date',
        'reason',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'employee_id');
    }
}
