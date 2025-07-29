<?php

namespace App\Http\Controllers;

use App\Models\employee;
use App\Models\employee_allowances;
use App\Models\employee_deductions;
use Illuminate\Http\Request;
use App\Models\salary_process;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


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

        $employees = Employee::select([
            'employees.id',
            'employees.attendance_employee_no as emp_no',
            'employees.full_name',
            'companies.name as company_name',
            'departments.name as department_name',
            'sub_departments.name as sub_department_name',
            'compensation.basic_salary',
            'compensation.increment_active',
            DB::raw("CASE WHEN compensation.increment_active = 1 THEN compensation.increment_value ELSE NULL END AS increment_value"),
            DB::raw("CASE WHEN compensation.increment_active = 1 THEN compensation.increment_effected_date ELSE NULL END AS increment_effected_date"),
            'compensation.ot_morning',
            'compensation.ot_evening',
            'compensation.enable_epf_etf',
            DB::raw("CASE
                WHEN compensation.br1 = 1 AND compensation.br2 = 1 THEN 'Both BR1 and BR2'
                WHEN compensation.br1 = 1 THEN 'BR1 Only'
                WHEN compensation.br2 = 1 THEN 'BR2 Only'
                ELSE 'None'
            END AS br_status"),
            DB::raw("COALESCE(SUM(loans.loan_amount), 0) as total_loan_amount"),
            DB::raw("COALESCE(COUNT(npr.id), 0) as approved_no_pay_days"),
            DB::raw("CONCAT('[',
                GROUP_CONCAT(DISTINCT
                    JSON_OBJECT(
                        'id', allowances.id,
                        'allowance_name', allowances.allowance_name,
                        'amount', COALESCE(ea.custom_amount, allowances.amount),
                        'is_custom', CASE WHEN ea.id IS NOT NULL THEN 1 ELSE 0 END,
                        'allowance_code', allowances.allowance_code,
                        'category', allowances.category
                    )
                ),
            ']') AS allowances"),
            DB::raw("CONCAT('[',
                GROUP_CONCAT(DISTINCT
                    JSON_OBJECT(
                        'id', deductions.id,
                        'deduction_name', deductions.deduction_name,
                        'amount', COALESCE(ed.custom_amount, deductions.amount),
                        'is_custom', CASE WHEN ed.id IS NOT NULL THEN 1 ELSE 0 END,
                        'deduction_code', deductions.deduction_code,
                        'category', deductions.category
                    )
                ),
            ']') AS deductions")
        ])
            ->join('organization_assignments as oa', 'employees.organization_assignment_id', '=', 'oa.id')
            ->join('companies', 'oa.company_id', '=', 'companies.id')
            ->leftJoin('departments', 'oa.department_id', '=', 'departments.id')
            ->leftJoin('sub_departments', 'oa.sub_department_id', '=', 'sub_departments.id')
            ->leftJoin('compensation', 'employees.id', '=', 'compensation.employee_id')
            ->leftJoin('loans', 'employees.id', '=', 'loans.employee_id')
            ->leftJoin('no_pay_records as npr', function ($join) use ($startDate, $endDate) {
                $join->on('employees.id', '=', 'npr.employee_id')
                    ->where('npr.status', 'Approved')
                    ->whereBetween('npr.date', [$startDate, $endDate]);
            })
            ->leftJoin('allowances', function ($join) {
                $join->on('companies.id', '=', 'allowances.company_id')
                    ->where('allowances.status', 'active')
                    ->where(function ($query) {
                        $query->whereNull('allowances.department_id')
                            ->orWhereColumn('allowances.department_id', '=', 'oa.department_id');
                    });
            })
            ->leftJoin('employee_allowances as ea', function ($join) {
                $join->on('allowances.id', '=', 'ea.allowance_id')
                    ->on('employees.id', '=', 'ea.employee_id');
            })
            ->leftJoin('deductions', function ($join) {
                $join->on('companies.id', '=', 'deductions.company_id')
                    ->where('deductions.status', 'active')
                    ->where(function ($query) {
                        $query->whereNull('deductions.department_id')
                            ->orWhereColumn('deductions.department_id', '=', 'oa.department_id');
                    });
            })
            ->leftJoin('employee_deductions as ed', function ($join) {
                $join->on('deductions.id', '=', 'ed.deduction_id')
                    ->on('employees.id', '=', 'ed.employee_id');
            })
            ->where('oa.company_id', $request->company_id)
            ->when($request->filled('department_id'), function ($query) use ($request) {
                $query->where('oa.department_id', $request->department_id);
            })
            ->whereExists(function ($query) use ($startDate, $endDate) {
                $query->select(DB::raw(1))
                    ->from('rosters')
                    ->whereColumn('rosters.employee_id', 'employees.id')
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->where(function ($sub) {
                            $sub->whereNull('rosters.date_from')
                                ->whereNull('rosters.date_to');
                        })
                            ->orWhere(function ($sub) use ($startDate, $endDate) {
                                $sub->where('rosters.date_from', '<=', $endDate)
                                    ->where('rosters.date_to', '>=', $startDate);
                            })
                            ->orWhereBetween('rosters.date_from', [$startDate, $endDate])
                            ->orWhereBetween('rosters.date_to', [$startDate, $endDate]);
                    });
            })
            ->groupBy([
                'employees.id',
                'employees.attendance_employee_no',
                'employees.full_name',
                'companies.name',
                'departments.name',
                'sub_departments.name',
                'compensation.basic_salary',
                'compensation.increment_active',
                'compensation.increment_value',
                'compensation.increment_effected_date',
                'compensation.ot_morning',
                'compensation.ot_evening',
                'compensation.enable_epf_etf',
                'compensation.br1',
                'compensation.br2'
            ])
            ->get();

        // Process the JSON strings into actual arrays
        $employees->transform(function ($employee) {
            $employee->allowances = json_decode($employee->allowances ?? '[]', true);
            $employee->deductions = json_decode($employee->deductions ?? '[]', true);
            return $employee;
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

    public function updateEmployeesAllowances(Request $request)
    {
        $employeeIDs = $request->selectedEmployees; // This is an array of IDs
        $type = $request->bulkActionType;
        $amount = $request->bulkActionAmount || null; // Changed from 'amount' to match JSON

        if (!is_array($employeeIDs) || empty($employeeIDs)) {
            return response()->json(['error' => 'No employees selected'], 400);
        }

        $typeId = $request->bulkActionId;

        if ($type == "allowance") {
            $records = [];
            foreach ($employeeIDs as $employeeId) {
                $records[] = [
                    'employee_id' => $employeeId,
                    'allowance_id' => $typeId,
                    'custom_amount' => $amount,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            employee_allowances::insert($records);
        }
        if ($type == "deduction") {
            $records = [];
            foreach ($employeeIDs as $employeeId) {
                $records[] = [
                    'employee_id' => $employeeId,
                    'deduction_id' => $typeId,
                    'custom_amount' => $amount,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            employee_deductions::insert($records);
        }

        return response()->json([
            'message' => 'Bulk update successful',
            'affected_employees' => count($employeeIDs),
            'type' => $type,
            'amount' => $amount
        ], 200);
    }


}
