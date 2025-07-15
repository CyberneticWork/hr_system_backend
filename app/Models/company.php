<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class company extends Model
{
    use SoftDeletes;

    public function departments()
    {
        return $this->hasMany(departments::class);
    }

    // Relationship: company → organization_assignments
    public function organizationAssignments()
    {
        return $this->hasMany(\App\Models\organization_assignment::class, 'company_id');
    }

    // Employees through organization_assignments
    public function employees()
    {
        return $this->hasManyThrough(
            employee::class,
            organization_assignment::class,
            'company_id', // Foreign key on organization_assignments
            'organization_assignment_id', // Foreign key on employees
            'id', // Local key on companies
            'id' // Local key on organization_assignments
        );
    }
}
