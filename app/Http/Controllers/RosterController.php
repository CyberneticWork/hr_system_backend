<?php

namespace App\Http\Controllers;

use App\Models\roster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
        // Check if the request contains JSON array data
        if ($this->isJsonArray($request)) {
            return $this->storeBulk($request);
        }

        // Original single entry logic
        $validator = Validator::make($request->all(), [
            'roster_id' => 'required|integer',
            'shift_code' => 'required|exists:shifts,id',
            'company_id' => 'nullable|exists:companies,id',
            'department_id' => 'nullable',
            'sub_department_id' => 'nullable',
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

        $data = $validator->validated();

        // If roster_id not provided, generate one
        if (!isset($data['roster_id'])) {
            $data['roster_id'] = roster::max('roster_id') + 1;
        }

        $roster = roster::create($data);
        return response()->json($roster, 201);
    }

    protected function isJsonArray($request)
    {
        $content = $request->getContent();
        if (empty($content)) {
            return false;
        }

        $data = json_decode($content, true);
        return is_array($data) && array_keys($data) === range(0, count($data) - 1);
    }

    public function storeBulk(Request $request)
    {
        $entries = json_decode($request->getContent(), true);

        if (!is_array($entries)) {
            return response()->json(['error' => 'Invalid bulk data format. Expected JSON array.'], 400);
        }

        // Validate all entries
        $validatedEntries = [];
        $errors = [];
        $rosterId = null;

        foreach ($entries as $index => $entry) {
            $validator = Validator::make($entry, [
                'roster_id' => 'required|integer',
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
                $errors[$index] = $validator->errors();
                continue;
            }

            $validated = $validator->validated();

            // Ensure all entries have the same roster_id
            if ($rosterId === null) {
                $rosterId = $validated['roster_id'];
            } elseif ($validated['roster_id'] !== $rosterId) {
                $errors[$index] = ['roster_id' => 'All entries in a bulk request must have the same roster_id'];
                continue;
            }

            $validatedEntries[] = $validated;
        }

        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        // Insert all valid entries in a transaction
        DB::beginTransaction();
        try {
            roster::insert($validatedEntries);
            DB::commit();

            return response()->json([
                'message' => 'Bulk roster entries created successfully',
                'roster_id' => $rosterId,
                'count' => count($validatedEntries)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to create bulk roster entries',
                'message' => $e->getMessage()
            ], 500);
        }
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

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'company_id' => 'nullable|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id',
            'sub_department_id' => 'nullable|exists:sub_departments,id',
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = roster::with(['company', 'department', 'subDepartment', 'employee']);

        // Apply date filters if provided
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->where(function ($q) use ($request) {
                $dateFrom = $request->date_from;
                $dateTo = $request->date_to ?? $request->date_from;

                if ($dateFrom && $dateTo) {
                    $q->whereBetween('date_from', [$dateFrom, $dateTo])
                        ->orWhereBetween('date_to', [$dateFrom, $dateTo])
                        ->orWhere(function ($query) use ($dateFrom, $dateTo) {
                            $query->where('date_from', '<=', $dateFrom)
                                ->where('date_to', '>=', $dateTo);
                        });
                } else if ($dateFrom) {
                    $q->where('date_from', '>=', $dateFrom);
                }
            });
        }

        // Apply organization filters if provided
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('sub_department_id')) {
            $query->where('sub_department_id', $request->sub_department_id);
        }

        // Apply employee filter if provided
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        try {
            $rosters = $query->get()->map(function ($roster) {
                return [
                    'roster_details' => [
                        'id' => $roster->id,
                        'roster_id' => $roster->roster_id,
                        'shift_code' => $roster->shift_code,
                        'is_recurring' => $roster->is_recurring,
                        'recurrence_pattern' => $roster->recurrence_pattern,
                        'notes' => $roster->notes,
                        'date_from' => $roster->date_from,
                        'date_to' => $roster->date_to
                    ],
                    'organization_details' => [
                        'company' => $roster->company ? [
                            'id' => $roster->company->id,
                            'name' => $roster->company->name
                        ] : null,
                        'department' => $roster->department ? [
                            'id' => $roster->department->id,
                            'name' => $roster->department->name
                        ] : null,
                        'sub_department' => $roster->subDepartment ? [
                            'id' => $roster->subDepartment->id,
                            'name' => $roster->subDepartment->name
                        ] : null
                    ],
                    'employee_details' => $roster->employee ? [
                        'id' => $roster->employee->id,
                        'name' => $roster->employee->name_with_initials,
                        'full_name' => $roster->employee->full_name,
                        'epf' => $roster->employee->epf,
                        'attendance_no' => $roster->employee->attendance_employee_no
                    ] : null
                ];
            });

            return response()->json([
                'status' => 'success',
                'count' => $rosters->count(),
                'data' => $rosters
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error retrieving roster data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
