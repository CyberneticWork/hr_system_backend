<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class contact_detail extends Model
{
    use SoftDeletes;

    public function employee()
    {
        return $this->belongsTo(employee::class);
    }
}
