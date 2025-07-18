<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class documents extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'document_type',
        'document_name',
        'document_path',
    ];
}
