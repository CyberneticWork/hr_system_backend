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
                    'created_at' => $overtime->created_at,
                ];
            });
        return response()->json($overtimes);
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
