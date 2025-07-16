<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\sub_departments;
use App\Models\departments;

class SubDepartmentsController extends Controller
{
    public function index()
    {
        // Eager load department and company
        $subDepartments = sub_departments::with(['department.company'])->get();
        return response()->json($subDepartments);
    }

    public function show($id)
    {
        $subDepartment = sub_departments::with(['department.company'])->findOrFail($id);
        return response()->json($subDepartment);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
        ]);

        $subDepartment = sub_departments::create([
            'department_id' => $validated['department_id'],
            'name' => $validated['name'],
        ]);

        return response()->json($subDepartment, 201);
    }

    public function update(Request $request, $id)
    {
        $subDepartment = sub_departments::findOrFail($id);

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
        ]);

        $subDepartment->update($validated);

        return response()->json($subDepartment);
    }

    public function destroy($id)
    {
        $subDepartment = sub_departments::findOrFail($id);
        $subDepartment->delete();

        return response()->json(['message' => 'Sub Department deleted']);
    }
}
