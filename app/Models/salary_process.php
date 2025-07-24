<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class salary_process extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'process_date',
        'basic',
        'basic_salary',
        'no_pay_records_id',
        'over_times_id',
        'allowances_id',
        'loans_id',
        'deductions_id',
        'gross_amount',
        'salary_advance',
        'net_salary',
        'status',
        'processed_by'
    ];
}
