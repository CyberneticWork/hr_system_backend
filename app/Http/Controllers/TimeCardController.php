<?php

namespace App\Http\Controllers;

use App\Models\time_card;
use App\Models\employee;
use App\Models\roster;
use App\Models\shift;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TimeCardController extends Controller
{
    public function index(Request $request)
    {
        $cards = time_card::with(['employee.organizationAssignment.department'])
            ->get()
            ->map(function ($card) {
                return [
                    'empNo' => $card->employee->attendance_employee_no ?? null,
                    'name' => $card->employee->full_name ?? null,
                    'fingerprintClock' => null, // Update if you have this field
                    'time' => $card->time,
                    'date' => $card->date,
                    'entry' => $card->entry,
                    'inOut' => $card->entry == 1 ? 'IN' : ($card->entry == 2 ? 'OUT' : null),
                    // 'department' => $card->employee->organizationAssignment->department->name ?? null,
                    'status' => $card->status,
                ];
            });

        return response()->json($cards);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'time' => 'required',
            'date' => 'required|date',
            'entry' => 'required',
            'status' => 'required',
        ]);

        $working_hours = null;

        if (strtoupper($validated['status']) === 'OUT') {
            // Find the IN record for this employee and date
            $inCard = time_card::where('employee_id', $validated['employee_id'])
                ->where('date', $validated['date'])
                ->where('status', 'IN')
                ->orderBy('time', 'asc')
                ->first();

            if ($inCard) {
                $inTime = Carbon::parse($inCard->time);
                $outTime = Carbon::parse($validated['time']);
                $working_hours = round($inTime->floatDiffInHours($outTime), 2);
            }
        }

        $timeCard = time_card::create([
            'employee_id' => $validated['employee_id'],
            'time' => $validated['time'],
            'date' => $validated['date'],
            'working_hours' => $working_hours,
            'entry' => $validated['entry'],
            'status' => $validated['status'],
        ]);

        return response()->json($timeCard, 201);
    }
}
