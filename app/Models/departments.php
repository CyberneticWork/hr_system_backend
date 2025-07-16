<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class departments extends Model
{
    use SoftDeletes;
    public function deductions(): HasMany
    {
        return $this->hasMany(deduction::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

}
