<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NoPayRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'date',
        'no_pay_count',
        'description',
        'status',
        'processed_by'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}

