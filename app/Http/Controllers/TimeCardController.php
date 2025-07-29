<?php

namespace App\Http\Controllers;

use App\Models\over_time;
use App\Models\time_card;
use App\Models\employee;
use App\Models\roster;
use App\Models\shifts;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Models\absence;
// use Maatwebsite\Excel\Facades\Excel;

class TimeCardController extends Controller
{
    public function index(Request $request)
    {
        $cards = time_card::with(['employee.organizationAssignment.department'])
            ->get()
            ->map(function ($card) {
                return [
                    'id' => $card->id, // <-- Add this line
                    'empNo' => $card->employee->attendance_employee_no ?? null,
                    'name' => $card->employee->full_name ?? null,
                    'fingerprintClock' => null, // Update if you have this field
                    'time' => $card->time,
                    'date' => $card->date,
                    'entry' => $card->entry,
                    'inOut' => $card->entry == 1 ? 'IN' : ($card->entry == 2 ? 'OUT' : null),
                    'department' => $card->employee->organizationAssignment->department->name ?? null,
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

        // --- Roster/shift assignment check (same as attendance) ---
        $employee = employee::find($validated['employee_id']);
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }
        $org = $employee->organizationAssignment;
        if (!$org) {
            return response()->json(['message' => 'Organization assignment not found'], 404);
        }

        // Find roster for this employee for the given date (fallback logic)
        $roster = roster::where('employee_id', $employee->id)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($validated) {
                $q->whereNull('date_from')->orWhere('date_from', '<=', $validated['date']);
            })
            ->where(function ($q) use ($validated) {
                $q->whereNull('date_to')->orWhere('date_to', '>=', $validated['date']);
            })
            ->first();
        if (!$roster && $org->sub_department_id) {
            $roster = roster::where('sub_department_id', $org->sub_department_id)
                ->whereNull('employee_id')
                ->whereNull('deleted_at')
                ->where(function ($q) use ($validated) {
                    $q->where(function ($q2) use ($validated) {
                        $q2->whereNull('date_from')->orWhere('date_from', '<=', $validated['date']);
                    });
                    $q->where(function ($q2) use ($validated) {
                        $q2->whereNull('date_to')->orWhere('date_to', '>=', $validated['date']);
                    });
                })
                ->first();
        }
        if (!$roster && $org->department_id) {
            $roster = roster::where('department_id', $org->department_id)
                ->whereNull('employee_id')
                ->whereNull('sub_department_id')
                ->whereNull('deleted_at')
                ->where(function ($q) use ($validated) {
                    $q->where(function ($q2) use ($validated) {
                        $q2->whereNull('date_from')->orWhere('date_from', '<=', $validated['date']);
                    });
                    $q->where(function ($q2) use ($validated) {
                        $q2->whereNull('date_to')->orWhere('date_to', '>=', $validated['date']);
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
                ->where(function ($q) use ($validated) {
                    $q->where(function ($q2) use ($validated) {
                        $q2->whereNull('date_from')->orWhere('date_from', '<=', $validated['date']);
                    });
                    $q->where(function ($q2) use ($validated) {
                        $q2->whereNull('date_to')->orWhere('date_to', '>=', $validated['date']);
                    });
                })
                ->first();
        }

        if (!$roster) {
            return response()->json(['message' => 'No shift/roster assigned for this employee on this date'], 422);
        }
        // --- End roster/shift assignment check ---

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

        if ($validated['status'] == "OUT") {
            $shift_code = $employee->rosters[0]->shift_code ?? null;
            $shift = shifts::find($shift_code);
            $shift_duration = $shift ? Carbon::parse($shift->start_time)->diffInHours(Carbon::parse($shift->end_time)) : null;
            $ot_time = ($working_hours - $shift_duration) - 1 ;

            $over_time = over_time::create([
                'employee_id' => $validated['employee_id'],
                'shift_code' => $shift_code,
                'time_cards_id' => $timeCard->id,
                'ot_hours' => $ot_time,
            ]);
        }

        return response()->json($timeCard, 201);
    }

    public function attendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'empno' => 'required|integer',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i:s',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = employee::where('attendance_employee_no', $request->empno)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $org = $employee->organizationAssignment;
        if (!$org) {
            return response()->json(['message' => 'Organization assignment not found'], 404);
        }

        // Find roster for this employee for the given date (fallback logic as before)
        $roster = roster::where('employee_id', $employee->id)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($request) {
                $q->whereNull('date_from')->orWhere('date_from', '<=', $request->date);
            })
            ->where(function ($q) use ($request) {
                $q->whereNull('date_to')->orWhere('date_to', '>=', $request->date);
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
        }

        $shift = shifts::find($roster->shift_code);
        if (!$shift) {
            return response()->json(['message' => 'Shift not found'], 404);
        }

        $existingCards = time_card::where('employee_id', $employee->id)
            ->where('date', $request->date)
            ->orderBy('created_at')
            ->get();

        $inputTime = Carbon::createFromFormat('H:i:s', $request->time);
        $shiftStart = Carbon::parse($shift->start_time);
        $shiftEnd = Carbon::parse($shift->end_time);

        // IN logic: allow only if within start_time - 1min to start_time + 1min
        if ($existingCards->count() == 0) {
            $startWindowStart = $shiftStart->copy()->subMinutes(30);
            $startWindowEnd = $shiftStart->copy()->addMinutes(30);

            if ($inputTime->lt($startWindowStart) || $inputTime->gt($startWindowEnd)) {
                return response()->json(['message' => 'IN time must be within 30 minutes after shift start time'], 422);
            }

            $entryType = 1; // IN
            $working_hours = null;

            // When saving IN as shift start time:
            $storeTime = $shiftStart->format('H:i:s');
        }
        // OUT logic: allow only if not within IN window
        elseif ($existingCards->count() == 1) {
            // Prevent OUT within the IN window
            $startWindowStart = $shiftStart->copy()->subMinutes(30);
            $startWindowEnd = $shiftStart->copy()->addMinutes(30);

            if ($inputTime->gte($startWindowStart) && $inputTime->lte($startWindowEnd)) {
                return response()->json(['message' => 'OUT cannot be marked within IN window (shift start ±30 min)'], 422);
            }

            $inTime = Carbon::parse($existingCards->first()->time);
            $outTime = $inputTime;
            $working_hours = round($inTime->floatDiffInHours($outTime), 2);

            // Check if OUT time is less than shift end time
            if ($outTime->lt($shiftEnd)) {
                $entryType = 0; // Leave
                $status = 'Leave';
            } else {
                $entryType = 2; // OUT
                $status = 'OUT';
            }

            // When saving OUT:
            $storeTime = $inputTime->format('H:i:s');
        } else {
            return response()->json(['message' => 'Attendance already marked IN and OUT for today'], 409);
        }

        $timeCard = time_card::create([
            'employee_id' => $employee->id,
            'time' => $storeTime,
            'date' => $request->date,
            'working_hours' => $working_hours,
            'entry' => $entryType,
            'status' => $entryType == 1 ? 'IN' : $status,
        ]);

        return response()->json([
            'message' => 'Attendance marked as ' . ($entryType == 1 ? 'IN' : $status),
            'data' => $timeCard,
            'employee' => [
                'id' => $employee->id,
                'attendance_employee_no' => $employee->attendance_employee_no,
                'full_name' => $employee->full_name,
                'department' => $org->department_id,
                'sub_department' => $org->sub_department_id,
                'company' => $org->company_id,
            ],
            'shift' => [
                'id' => $shift->id,
                'shift_code' => $shift->shift_code,
                'shift_description' => $shift->shift_description,
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
            ],
            'roster' => $roster,
        ], 201);
    }
    public function searchByEmployee(Request $request)
    {
        $search = $request->query('q');
        if (!$search) {
            return response()->json(['message' => 'Search query is required'], 422);
        }

        // Find employee by NIC or EPF number
        $employee = employee::where('nic', $search)
            ->orWhere('attendance_employee_no', $search)
            ->first();

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        // Fetch time cards for this employee, ordered by date and time (latest first)
        $cards = time_card::with(['employee.organizationAssignment.department'])
            ->where('employee_id', $employee->id)
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get()
            ->map(function ($card) {
                return [
                    'id' => $card->id,
                    'empNo' => $card->employee->attendance_employee_no ?? null,
                    'name' => $card->employee->full_name ?? null,
                    'fingerprintClock' => null,
                    'time' => $card->time,
                    'date' => $card->date,
                    'entry' => $card->entry,
                    'inOut' => $card->entry == 1 ? 'IN' : ($card->entry == 2 ? 'OUT' : null),
                    'department' => $card->employee->organizationAssignment->department->name ?? null,
                    'status' => $card->status,
                ];
            });

        return response()->json($cards);
    }
    // public function markAbsentees(Request $request)
    // {
    //     $date = $request->input('date');
    //     if (!$date) {
    //         return response()->json(['message' => 'Date is required'], 422);
    //     }

    //     // Get all rosters active on this date
    //     $rosters = roster::whereNull('deleted_at')
    //         ->where(function ($q) use ($date) {
    //             $q->whereNull('date_from')->orWhere('date_from', '<=', $date);
    //         })
    //         ->where(function ($q) use ($date) {
    //             $q->whereNull('date_to')->orWhere('date_to', '>=', $date);
    //         })
    //         ->get();

    //     $absentRecords = [];

    //     foreach ($rosters as $roster) {
    //         // Find employees for this roster
    //         if ($roster->employee_id) {
    //             $employees = employee::where('id', $roster->employee_id)->where('is_active', 1)->get();
    //         } else {
    //             $query = employee::where('is_active', 1);
    //             if ($roster->sub_department_id) {
    //                 $query->whereHas('organizationAssignment', function ($q) use ($roster) {
    //                     $q->where('sub_department_id', $roster->sub_department_id);
    //                 });
    //             } elseif ($roster->department_id) {
    //                 $query->whereHas('organizationAssignment', function ($q) use ($roster) {
    //                     $q->where('department_id', $roster->department_id);
    //                 });
    //             } elseif ($roster->company_id) {
    //                 $query->whereHas('organizationAssignment', function ($q) use ($roster) {
    //                     $q->where('company_id', $roster->company_id);
    //                 });
    //             }
    //             $employees = $query->get();
    //         }

    //         foreach ($employees as $employee) {
    //             // Check if both IN and OUT exist for this date
    //             $hasIn = time_card::where('employee_id', $employee->id)
    //                 ->where('date', $date)
    //                 ->where('entry', 1)
    //                 ->exists();
    //             $hasOut = time_card::where('employee_id', $employee->id)
    //                 ->where('date', $date)
    //                 ->where('entry', 2)
    //                 ->exists();

    //             if (!($hasIn && $hasOut)) {
    //                 // Mark as absent if not already marked
    //                 $alreadyAbsent = time_card::where('employee_id', $employee->id)
    //                     ->where('date', $date)
    //                     ->where('status', 'Absent')
    //                     ->exists();
    //                 if (!$alreadyAbsent) {
    //                     $absent = time_card::create([
    //                         'employee_id' => $employee->id,
    //                         'date' => $date,
    //                         'entry' => 0,
    //                         'status' => 'Absent',
    //                     ]);
    //                     $absentRecords[] = $absent;
    //                 }
    //             }
    //         }
    //     }

    //     // Fetch all absent records for the date, with related details
    //     $absentees = time_card::with([
    //             'employee.organizationAssignment.department',
    //             'employee.organizationAssignment.subDepartment',
    //             'employee.organizationAssignment.company'
    //         ])
    //         ->where('date', $date)
    //         ->where('status', 'Absent')
    //         ->get()
    //         ->map(function ($card) {
    //             return [
    //                 'id' => $card->id, // <-- Add this line
    //                 'empNo' => $card->employee->attendance_employee_no ?? null,
    //                 'name' => $card->employee->full_name ?? null,
    //                 'department' => $card->employee->organizationAssignment->department->name ?? null,
    //                 'sub_department' => $card->employee->organizationAssignment->subDepartment->name ?? null,
    //                 'company' => $card->employee->organizationAssignment->company->name ?? null,
    //                 'date' => $card->date,
    //                 'entry' => $card->entry,
    //                 'status' => $card->status,
    //             };
    //         });

    //     return response()->json([
    //         'message' => 'Absentees marked for date ' . $date,
    //         'absentees' => $absentees,
    //     ]);
    // }

//     public function update(Request $request, $id)
//     {
//         $validated = $request->validate([
//             'date' => 'required|date',
//             'time' => 'required',
//             'entry' => 'required|in:0,1,2',
//             'status' => 'required|in:IN,OUT,Absent,Leave',
//         ]);

//         $timeCard = TimeCard::findOrFail($id);
//         $timeCard->date = $validated['date'];
//         $timeCard->time = $validated['time'];
//         $timeCard->entry = $validated['entry'];
//         $timeCard->status = $validated['status'];
//         $timeCard->save();

//         return response()->json(['message' => 'Time card updated successfully', 'data' => $timeCard]);
//     }

//     public function destroy($id)
//     {
//         $timeCard = TimeCard::findOrFail($id);
//         $timeCard->delete();

//         return response()->json(['message' => 'Time card deleted successfully']);
//     }

    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'company_id' => 'required|exists:companies,id',
            'date' => 'required|date',
        ]);

        $companyId = $request->input('company_id');
        $importDate = $request->input('date');

        $path = $request->file('file')->getRealPath();
        $rows = Excel::toArray([], $path)[0];

        $results = [
            'imported' => 0,
            'absent' => 0,
            'errors' => [],
        ];

        \DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // skip header row

                $nic = trim($row[0]);
                $date = $importDate ?: trim($row[1]);
                $rawTime = trim($row[2]);
                $entry = trim($row[3]);
                $status = trim($row[4]);
                $reason = isset($row[5]) ? trim($row[5]) : null;

                // Parse time from Excel
                try {
                    if (is_numeric($rawTime)) {
                        $seconds = round($rawTime * 24 * 60 * 60);
                        $hours = floor($seconds / 3600);
                        $minutes = floor(($seconds % 3600) / 60);
                        $seconds = $seconds % 60;
                        $time = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                    } else {
                        $carbonTime = Carbon::parse($rawTime);
                        $time = $carbonTime->format('H:i:s');
                    }
                } catch (\Exception $ex) {
                    $results['errors'][] = "Invalid time format";
                    continue;
                }

                // Find employee by NIC and company
                $employee = employee::where('nic', $nic)
                    ->whereHas('organizationAssignment', function ($q) use ($companyId) {
                        $q->where('company_id', $companyId);
                    })
                    ->first();

                if (!$employee) {
                    $results['errors'][] = "Employee not found for NIC in selected company";
                    continue;
                }

                // Check roster/shift assignment for this employee and date
                $org = $employee->organizationAssignment;
                $roster = roster::where('employee_id', $employee->id)
                    ->whereNull('deleted_at')
                    ->where(function ($q) use ($date) {
                        $q->whereNull('date_from')->orWhere('date_from', '<=', $date);
                    })
                    ->where(function ($q) use ($date) {
                        $q->whereNull('date_to')->orWhere('date_to', '>=', $date);
                    })
                    ->first();
                if (!$roster && $org) {
                    if ($org->sub_department_id) {
                        $roster = roster::where('sub_department_id', $org->sub_department_id)
                            ->whereNull('employee_id')
                            ->whereNull('deleted_at')
                            ->where(function ($q) use ($date) {
                                $q->whereNull('date_from')->orWhere('date_from', '<=', $date);
                            })
                            ->where(function ($q) use ($date) {
                                $q->whereNull('date_to')->orWhere('date_to', '>=', $date);
                            })
                            ->first();
                    }
                    if (!$roster && $org->department_id) {
                        $roster = roster::where('department_id', $org->department_id)
                            ->whereNull('employee_id')
                            ->whereNull('sub_department_id')
                            ->whereNull('deleted_at')
                            ->where(function ($q) use ($date) {
                                $q->whereNull('date_from')->orWhere('date_from', '<=', $date);
                            })
                            ->where(function ($q) use ($date) {
                                $q->whereNull('date_to')->orWhere('date_to', '>=', $date);
                            })
                            ->first();
                    }
                    if (!$roster && $org->company_id) {
                        $roster = roster::where('company_id', $org->company_id)
                            ->whereNull('employee_id')
                            ->whereNull('sub_department_id')
                            ->whereNull('department_id')
                            ->whereNull('deleted_at')
                            ->where(function ($q) use ($date) {
                                $q->whereNull('date_from')->orWhere('date_from', '<=', $date);
                            })
                            ->where(function ($q) use ($date) {
                                $q->whereNull('date_to')->orWhere('date_to', '>=', $date);
                            })
                            ->first();
                    }
                }
                if (!$roster) {
                    $results['errors'][] = "No shift/roster assigned for this employee on $date";
                    continue;
                }

                // Get shift for roster
                $shift = shifts::find($roster->shift_code);
                if (!$shift) {
                    $results['errors'][] = "Shift not found for roster";
                    continue;
                }

                // Attendance logic
                if (in_array(strtoupper($status), ['IN', 'OUT', 'LEAVE'])) {
                    $working_hours = null;
                    if (strtoupper($status) === 'OUT') {
                        // Find IN record for working hours calculation
                        $inCard = time_card::where('employee_id', $employee->id)
                            ->where('date', $date)
                            ->where('status', 'IN')
                            ->orderBy('time', 'asc')
                            ->first();
                        if ($inCard) {
                            $inTime = Carbon::parse($inCard->time);
                            $outTime = Carbon::parse($time);
                            $working_hours = round($inTime->floatDiffInHours($outTime), 2);
                        }
                    }

                    // Prevent duplicate time_card
                    $exists = time_card::where('employee_id', $employee->id)
                        ->where('date', $date)
                        ->where('time', $time)
                        ->where('entry', $entry)
                        ->where('status', strtoupper($status))
                        ->exists();

                    if (!$exists) {
                        time_card::create([
                            'employee_id' => $employee->id,
                            'time' => $time,
                            'date' => $date,
                            'working_hours' => $working_hours,
                            'entry' => $entry,
                            'status' => strtoupper($status),
                        ]);
                        $results['imported']++;
                    }
                } elseif (strtoupper($status) === 'ABSENT') {
                    // Prevent duplicate absence
                    $exists = absence::where('employee_id', $employee->id)
                        ->where('date', $date)
                        ->exists();

                    if (!$exists) {
                        absence::create([
                            'employee_id' => $employee->id,
                            'date' => $date,
                            'reason' => $reason ?: 'not mentioned',
                        ]);
                        $results['absent']++;
                    }
                } else {
                    $results['errors'][] = "Unknown status";
                }
            }
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'message' => 'Import error',
                'errors' => array_unique($results['errors']),
                'exception' => $e->getMessage()
            ], 500);
        }

        // Remove duplicate error messages before returning
        $results['errors'] = array_unique($results['errors']);

        return response()->json($results);
    }
    public function fetchAbsentees(Request $request)
    {
        $date = $request->query('date');
        $search = $request->query('search', '');

        $query = absence::with(['employee'])
            ->when($date, function ($q) use ($date) {
                $q->where('date', $date);
            })
            ->when($search, function ($q) use ($search) {
                $q->whereHas('employee', function ($q2) use ($search) {
                    $q2->where('nic', 'like', "%$search%")
                       ->orWhere('attendance_employee_no', 'like', "%$search%");
                });
            });

        $absentees = $query->get()->map(function ($abs) {
            return [
                'id' => $abs->id,
                'employee_name' => $abs->employee->full_name ?? null,
                'date' => $abs->date,
                'reason' => $abs->reason,
            ];
        });

        return response()->json($absentees);
    }
}
