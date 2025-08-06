<?php

namespace App\Http\Controllers;

use App\Models\over_time;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $overtimes = over_time::with(['employee', 'shift', 'timeCard'])
            ->get()
            ->map(function ($overtime) {
                return [
                    'id' => $overtime->id,
                    'employee_id' => $overtime->employee->id ?? null,
                    'employee_name' => $overtime->employee->full_name ?? null,
                    'shift_code' => $overtime->shift ?? null,
                    'time_card_id' => $overtime->timeCard ?? null,
                    'ot_hours' => $overtime->ot_hours,
                    'morning_ot' => $overtime->morning_ot,
                    'evening_ot' => $overtime->afternoon_ot,
                    'status' => $overtime->status,
                    'created_at' => $overtime->created_at,
                ];
            });
        return response()->json($overtimes);
    }

    public function approve(Request $request, $id)
    {
        $overtime = over_time::findOrFail($id);
        if ($request->status == 'approved') {
            $overtime->status = 'approved';
        } else if ($request->status == 'rejected') {
            $overtime->status = 'rejected';
        } else {
            return response()->json(['message' => 'Invalid status'], 400);
        }
        $overtime->save();
        return response()->json(['message' => 'Overtime approved successfully']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
