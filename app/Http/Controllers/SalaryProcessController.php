<?php

namespace App\Http\Controllers;

use App\Models\salary_process;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\employee;


class SalaryProcessController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $salaryData = salary_process::with(['employee', 'noPayRecord', 'overTime', 'allowance', 'loan', 'deduction'])
            ->orderBy('process_date', 'desc')
            ->get();

        return response()->json($salaryData);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $salaryData = salary_process::create([
            'employee_id' => $request->employee_id,
            'process_date' => $request->process_date,
            'basic' => $request->basic,
            'basic_salary' => $request->basic_salary,
            'no_pay_records_id' => $request->no_pay_records_id,
            'over_times_id' => $request->over_times_id,
            'allowances_id' => $request->allowances_id,
            'loans_id' => $request->loans_id,
            'deductions_id' => $request->deductions_id,
            'gross_amount' => $request->gross_amount,
            'salary_advance' => $request->salary_advance,
            'net_salary' => $request->net_salary,
            'status' => 'Pending',
            'processed_by' => Auth::id(),
        ]);

        return response()->json($salaryData, 201);
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
    // public function getEmployeesByMonthAndCompany(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'month' => 'required|integer|between:1,12',
    //         'year' => 'required|integer',
    //         'company_id' => 'required|exists:companies,id',
    //         'department_id' => 'nullable|exists:departments,id'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'message' => 'Validation failed',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     $month = $request->month;
    //     $year = $request->year;
    //     $startDate = "$year-$month-01";
    //     $endDate = date('Y-m-t', strtotime($startDate));

    //     // Simplified roster date condition
    //     $rosterCondition = function ($query) use ($startDate, $endDate) {
    //         $query->where(function ($q) use ($startDate, $endDate) {
    //             $q->whereNull('date_from')
    //                 ->whereNull('date_to');
    //         })->orWhere(function ($q) use ($startDate, $endDate) {
    //             $q->where('date_from', '<=', $endDate)
    //                 ->where('date_to', '>=', $startDate);
    //         });
    //     };

    //     $query = Employee::with([
    //         'organizationAssignment.company',
    //         'organizationAssignment.department',
    //         'organizationAssignment.subDepartment',
    //         'organizationAssignment.designation',
    //         'compensation',
    //         'rosters' => function ($query) use ($rosterCondition) {
    //             $query->where($rosterCondition)
    //                 ->with('shift');
    //         }
    //     ])
    //         ->whereHas('organizationAssignment', function ($query) use ($request) {
    //             $query->where('company_id', $request->company_id);
    //             if ($request->has('department_id')) {
    //                 $query->where('department_id', $request->department_id);
    //             }
    //         })
    //         ->whereHas('rosters', $rosterCondition);

    //     $employees = $query->get();

    //     return response()->json([
    //         'data' => $employees,
    //         'month' => $month,
    //         'year' => $year,
    //         'company_id' => $request->company_id,
    //         'department_id' => $request->department_id ?? null,
    //     ]);
    // }

    public function getEmployeesByMonthAndCompany(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
            'company_id' => 'required|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $month = $request->month;
        $year = $request->year;
        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        // Simplified roster date condition
        $rosterCondition = function ($query) use ($startDate, $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereNull('date_from')
                    ->whereNull('date_to');
            })->orWhere(function ($q) use ($startDate, $endDate) {
                $q->where('date_from', '<=', $endDate)
                    ->where('date_to', '>=', $startDate);
            });
        };

        $employees = Employee::select([
            'employees.id',
            'employees.attendance_employee_no as emp_no',
            'employees.full_name',
            'employees.organization_assignment_id'
        ])
            ->with([
                'organizationAssignment.company:id,name',
                'organizationAssignment.department:id,name',
                'rosters' => function ($query) use ($rosterCondition) {
                    $query->where($rosterCondition);
                }
            ])
            ->whereHas('organizationAssignment', function ($query) use ($request) {
                $query->where('company_id', $request->company_id);
                if ($request->has('department_id')) {
                    $query->where('department_id', $request->department_id);
                }
            })
            ->whereHas('rosters', $rosterCondition)
            ->get()
            ->map(function ($employee) {
                return [
                    'emp_no' => $employee->emp_no,
                    'name' => $employee->full_name,
                    'company' => $employee->organizationAssignment->company->name ?? null,
                    'department' => $employee->organizationAssignment->department->name ?? null,
                    'has_roster' => $employee->rosters->isNotEmpty()
                ];
            });

        return response()->json([
            'data' => $employees,
            'meta' => [
                'month' => $month,
                'year' => $year,
                'company_id' => $request->company_id,
                'department_id' => $request->department_id ?? null,
                'count' => $employees->count()
            ]
        ]);
    }

}
