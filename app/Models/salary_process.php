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

    public function employee()
    {
        return $this->belongsTo(employee::class);
    }

    public function noPayRecord()
    {
        return $this->belongsTo(NoPayRecord::class, 'no_pay_records_id');
    }

    public function overTime()
    {
        return $this->belongsTo(over_time::class, 'over_times_id');
    }

    public function allowance()
    {
        return $this->belongsTo(allowances::class, 'allowances_id');
    }

    public function loan()
    {
        return $this->belongsTo(loans::class, 'loans_id');
    }

    public function deduction()
    {
        return $this->belongsTo(deduction::class, 'deductions_id');
    }


}
