<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class contact_detail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'permanent_address',
        'temporary_address',
        'email',
        'land_line',
        'mobile_line',
        'gn_division',
        'police_station',
        'district',
        'province',
        'electoral_division',
        'emg_relationship',
        'emg_name',
        'emg_address',
        'emg_tel'
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class);
    }
}
