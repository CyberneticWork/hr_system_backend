<?php

namespace App\Http\Controllers;

use App\Models\time_card;
use App\Models\employee;
use App\Models\roster;
use App\Models\shifts;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    public function attendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'empno' => 'required|integer',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 1. Find employee by attendance_employee_no
        $employee = employee::where('attendance_employee_no', $request->empno)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        // 2. Get organization assignment
        $org = $employee->organizationAssignment;
        if (!$org) {
            return response()->json(['message' => 'Organization assignment not found'], 404);
        }

        // 3. Find roster for this employee for the given date, fallback to sub_department, department, company
        $roster = roster::where('employee_id', $employee->id)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($request) {
                $q->where(function ($q2) use ($request) {
                    $q2->whereNull('date_from')->orWhere('date_from', '<=', $request->date);
                });
                $q->where(function ($q2) use ($request) {
                    $q2->whereNull('date_to')->orWhere('date_to', '>=', $request->date);
                });
            })
            ->first();

        if (!$roster && $org->sub_department_id) {
            $roster = roster::where('sub_department_id', $org->sub_department_id)
                ->whereNull('employee_id')
                ->whereNull('deleted_at')
                ->where(function ($q) use ($request) {
                    $q->where(function ($q2) use ($request) {
                        $q2->whereNull('date_from')->orWhere('date_from', '<=', $request->date);
                    });
                    $q->where(function ($q2) use ($request) {
                        $q2->whereNull('date_to')->orWhere('date_to', '>=', $request->date);
                    });
                })
                ->first();
        }
        if (!$roster && $org->department_id) {
            $roster = roster::where('department_id', $org->department_id)
                ->whereNull('employee_id')
                ->whereNull('sub_department_id')
                ->whereNull('deleted_at')
                ->where(function ($q) use ($request) {
                    $q->where(function ($q2) use ($request) {
                        $q2->whereNull('date_from')->orWhere('date_from', '<=', $request->date);
                    });
                    $q->where(function ($q2) use ($request) {
                        $q2->whereNull('date_to')->orWhere('date_to', '>=', $request->date);
                    });
                })
                ->first();
        }
        if (!$roster && $org->company_id) {
            $roster = roster::where('company_id', $org->company_id)
                ->whereNull('employee_id')
                ->whereNull('sub_department_id')
                ->whereNull('department_id')
                ->whereNull('deleted_at')
                ->where(function ($q) use ($request) {
                    $q->where(function ($q2) use ($request) {
                        $q2->whereNull('date_from')->orWhere('date_from', '<=', $request->date);
                    });
                    $q->where(function ($q2) use ($request) {
                        $q2->whereNull('date_to')->orWhere('date_to', '>=', $request->date);
                    });
                })
                ->first();
        }

        if (!$roster) {
            return response()->json(['message' => 'No roster assigned for this employee on this date'], 422);
        } else {
            // 4. Get shift details (shift_code is shifts.id)
            $shift = shifts::find($roster->shift_code);
            if (!$shift) {
                $shift = null; // Still allow attendance if shift not found
            }
        }

        // 5. Check if already has IN/OUT for this date
        $existingCards = time_card::where('employee_id', $employee->id)
            ->where('date', $request->date)
            ->orderBy('created_at')
            ->get();

        if ($existingCards->count() == 0) {
            $entryType = 1; // IN
            $working_hours = null;
        } elseif ($existingCards->count() == 1) {
            $entryType = 2; // OUT
            $inTime = Carbon::parse($existingCards->first()->time);
            $outTime = Carbon::parse($request->time);
            $working_hours = round($inTime->floatDiffInHours($outTime), 2);
        } else {
            return response()->json(['message' => 'Attendance already marked IN and OUT for today'], 409);
        }

        $timeCard = time_card::create([
            'employee_id' => $employee->id,
            'time' => $request->time,
            'date' => $request->date,
            'working_hours' => $working_hours,
            'entry' => $entryType,
            'status' => $entryType == 1 ? 'IN' : 'OUT',
        ]);

        return response()->json([
            'message' => 'Attendance marked as ' . ($entryType == 1 ? 'IN' : 'OUT'),
            'data' => $timeCard,
            'employee' => [
                'id' => $employee->id,
                'attendance_employee_no' => $employee->attendance_employee_no,
                'full_name' => $employee->full_name,
                'department' => $org->department_id,
                'sub_department' => $org->sub_department_id,
                'company' => $org->company_id,
            ],
            'shift' => $shift ? [
                'id' => $shift->id,
                'shift_code' => $shift->shift_code,
                'shift_description' => $shift->shift_description,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
            ] : null,
            'roster' => $roster,
        ], 201);
    }
}
