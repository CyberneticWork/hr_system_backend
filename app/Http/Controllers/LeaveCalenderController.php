<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\leaveCalendar;
use Illuminate\Support\Facades\Validator;

class LeaveCalenderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $leaveCalendars = leaveCalendar::all();
        return response()->json($leaveCalendars);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department_id' => 'sometimes|nullable|exists:departments,id',
            'company_id' => 'required|exists:companies,id',
            'leave_type' => 'required|string|max:255',
            'reason' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $leaveCalendar = leaveCalendar::create($request->all());
        return response()->json($leaveCalendar, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

        $leaveCalendar = leaveCalendar::find($id);
        if (!$leaveCalendar) {
            return response()->json(['message' => 'Leave Calendar not found'], 404);
        }
        return response()->json($leaveCalendar);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $leaveCalendar = leaveCalendar::find($id);
        if (!$leaveCalendar) {
            return response()->json(['message' => 'Leave Calendar not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'department_id' => 'sometimes|nullable|exists:departments,id',
            'company_id' => 'required|exists:companies,id',
            'leave_type' => 'required|string|max:255',
            'reason' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $leaveCalendar->update($request->all());
        return response()->json($leaveCalendar);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $leaveCalendar = leaveCalendar::find($id);
        if (!$leaveCalendar) {
            return response()->json(['message' => 'Leave Calendar not found'], 404);
        }

        $leaveCalendar->delete();
        return response()->json(['message' => 'Leave Calendar deleted successfully']);
    }
}
