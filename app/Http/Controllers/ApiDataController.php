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
        $companies = company::all();
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

    public function companiesById($id)
    {
        $company = company::find($id);
        return response()->json($company, 200);
    }

    public function departmentsById($id)
    {
        $department = departments::where('company_id', $id)->get();
        return response()->json($department, 200);
    }

    public function subDepartmentsById($id)
    {
        $subDepartment = sub_departments::where('department_id', $id)->get();
        return response()->json($subDepartment, 200);
    }

}
