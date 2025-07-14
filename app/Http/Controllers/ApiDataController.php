<?php

namespace App\Http\Controllers;

use App\Models\company;
use App\Models\departments;
use App\Models\designation;
use Illuminate\Http\Request;
use App\Models\sub_departments;

class ApiDataController extends Controller
{
    public function companies()
    {
        $companies = \App\Models\company::withCount('employees')->get()->map(function ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'code' => null,
                'location' => null,
                'employees' => $company->employees_count,
                'established' => null,
            ];
        });
        return response()->json($companies, 200);
    }

    public function departments()
    {
        // Assuming you have a Department model
        $departments = departments::all();
        return response()->json($departments, 200);
    }

    public function subDepartments()
    {
        // Assuming you have a Department model
        $subDepartments = sub_departments::all();
        return response()->json($subDepartments, 200);
    }

    public function designations()
    {
        // Assuming you have a Designation model
        $designations = designation::all();
        return response()->json($designations, 200);
    }
}
