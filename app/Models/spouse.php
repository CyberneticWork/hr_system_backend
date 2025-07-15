<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class spouse extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'title',
        'name',
        'age',
        'dob',
        'nic',

    ];

    public function employees()
    {
        return $this->hasMany(employee::class);
    }
}
