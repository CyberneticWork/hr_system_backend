<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class employee extends Model
{
    use SoftDeletes;

    public function employmentType()
    {
        return $this->belongsTo(employment_type::class);
    }

    public function organizationAssignment()
    {
        return $this->belongsTo(organization_assignment::class);
    }

    public function spouse()
    {
        return $this->belongsTo(Spouse::class);
    }

    public function childrens()
    {
        return $this->hasMany(Children::class);
    }

    public function contactDetail()
    {
        return $this->hasOne(contact_detail::class);
    }


}
