<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\roster;

class RosterController extends Controller
{
    // Controller methods for managing rosters will go here
    // For example, methods to create, update, delete, and view rosters

    public function index()
    {
        // Logic to list all rosters
        $rosters = roster::all(); // Assuming you have a Roster model
        return response()->json($rosters);
    }

    public function show($id)
    {
        $roster = roster::find($id);
        if (!$roster) {
            return response()->json(['message' => 'Roster not found'], 404);
        }
        return response()->json($roster);
    }

    public function store(Request $request)
    {
        // Logic to create a new roster
        $validator = Validator::make($request->all(), [
            'shift_code' => 'required|exists:shifts,id',
            'company_id' => 'nullable|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id',
            'sub_department_id' => 'nullable|exists:sub_departments,id',
            'employee_id' => 'nullable|exists:employees,id',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|string',
            'notes' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $roster = roster::create($validator->validated());
        return response()->json($roster, 201);
    }

    public function update(Request $request, $id)
    {
        // Logic to update an existing roster
        $roster = roster::find($id);
        if (!$roster) {
            return response()->json(['message' => 'Roster not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'shift_code' => 'required|exists:shifts,id',
            'company_id' => 'nullable|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id',
            'sub_department_id' => 'nullable|exists:sub_departments,id',
            'employee_id' => 'nullable|exists:employees,id',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|string',
            'notes' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $roster->update($validator->validated());
        return response()->json($roster);
    }

    public function destroy($id)
    {
        // Logic to delete a roster
        $roster = roster::find($id);
        if (!$roster) {
            return response()->json(['message' => 'Roster not found'], 404);
        }

        $roster->delete();
        return response()->json(['message' => 'Roster deleted successfully']);
    }
}
