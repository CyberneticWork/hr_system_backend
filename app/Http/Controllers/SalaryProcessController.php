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
        $month = $request->query('month');
        $year = $request->query('year');
        $company_id = $request->query('company_id');
        $department_id = $request->query('department_id');

        // Build the date range strings for the query
        $startDate = "{$year}-{$month}-01";
        $lastDay = date('t', strtotime($startDate));
        $endDate = "{$year}-{$month}-{$lastDay}";
        $workingDaysInMonth = $lastDay; // Assuming all days are working days for simplicity

        // Build the query with all the data consolidated in one SQL statement
        $query = "
        SELECT
            e.id,
            e.attendance_employee_no AS emp_no,
            e.full_name,
            c.name AS company_name,
            d.name AS department_name,
            sd.name AS sub_department_name,
            comp.basic_salary,

            -- Compensation flags
            comp.increment_active,
            CASE
                WHEN comp.increment_active = 1 THEN comp.increment_value
                ELSE NULL
            END AS increment_value,
            CASE
                WHEN comp.increment_active = 1 THEN comp.increment_effected_date
                ELSE NULL
            END AS increment_effected_date,
            comp.ot_morning,
            comp.ot_evening,
            comp.enable_epf_etf,

            -- BR status
            CASE
                WHEN comp.br1 = 1 AND comp.br2 = 1 THEN 'Both BR1 and BR2'
                WHEN comp.br1 = 1 THEN 'BR1 Only'
                WHEN comp.br2 = 1 THEN 'BR2 Only'
                ELSE 'None'
            END AS br_status,

            -- Loans
            COALESCE(SUM(lo.loan_amount), 0) AS total_loan_amount,
            lo.installment_count,
            lo.installment_amount,

            -- No pay records
            COALESCE(COUNT(npr.id), 0) AS approved_no_pay_days,

            -- Consolidated Allowances (as JSON-like string)
            (
                SELECT CONCAT('[',
                       GROUP_CONCAT(
                           CONCAT(
                               '{\"id\":', a.id,
                               ',\"name\":\"', a.allowance_name,
                               '\",\"amount\":', COALESCE(ea.custom_amount, a.amount),
                               ',\"is_custom\":', CASE WHEN ea.id IS NOT NULL THEN 1 ELSE 0 END,
                               ',\"code\":\"', a.allowance_code,
                               '\",\"category\":\"', a.category, '\"}'
                           )
                       ),
                       ']')
                FROM allowances a
                LEFT JOIN employee_allowances ea ON a.id = ea.allowance_id AND ea.employee_id = e.id
                WHERE a.company_id = c.id
                AND (a.department_id IS NULL OR a.department_id = oa.department_id)
                AND a.status = 'active'
            ) AS allowances,

            -- Consolidated Deductions (as JSON-like string)
            (
                SELECT CONCAT('[',
                       GROUP_CONCAT(
                           CONCAT(
                               '{\"id\":', dd.id,
                               ',\"name\":\"', dd.deduction_name,
                               '\",\"amount\":', COALESCE(ed.custom_amount, dd.amount),
                               ',\"is_custom\":', CASE WHEN ed.id IS NOT NULL THEN 1 ELSE 0 END,
                               ',\"code\":\"', dd.deduction_code,
                               '\",\"category\":\"', dd.category, '\"}'
                           )
                       ),
                       ']')
                FROM deductions dd
                LEFT JOIN employee_deductions ed ON dd.id = ed.deduction_id AND ed.employee_id = e.id
                WHERE dd.company_id = c.id
                AND (dd.department_id IS NULL OR dd.department_id = oa.department_id)
                AND dd.status = 'active'
            ) AS deductions
        FROM
            employees e
        JOIN
            organization_assignments oa ON e.organization_assignment_id = oa.id
        JOIN
            companies c ON oa.company_id = c.id
        LEFT JOIN
            departments d ON oa.department_id = d.id
        LEFT JOIN
            sub_departments sd ON oa.sub_department_id = sd.id
        LEFT JOIN
            compensation comp ON e.id = comp.employee_id
        LEFT JOIN
            loans lo ON e.id = lo.employee_id AND lo.status = 'active'
        LEFT JOIN
            no_pay_records npr ON e.id = npr.employee_id
            AND npr.status = 'Approved'
            AND npr.date BETWEEN ? AND ?
        WHERE
            oa.company_id = ?
    ";

        // Add department filter if specified
        if ($department_id) {
            $query .= " AND oa.department_id = ?";
        }

        $query .= "
        AND EXISTS (
            SELECT 1 FROM rosters r
            WHERE r.employee_id = e.id
            AND (
                (r.date_from <= ? AND r.date_to >= ?) OR
                (r.date_from BETWEEN ? AND ?) OR
                (r.date_to BETWEEN ? AND ?) OR
                (r.date_from IS NULL AND r.date_to IS NULL)
            )
        )
        GROUP BY
            e.id, e.attendance_employee_no, e.full_name,
            c.name, d.name, sd.name,
            comp.basic_salary, comp.br1, comp.br2,
            comp.increment_active, comp.increment_value,
            comp.increment_effected_date, comp.ot_morning,
            comp.ot_evening, comp.enable_epf_etf,
            lo.installment_count, lo.installment_amount,
            c.id, oa.department_id
    ";

        // Prepare parameters
        $params = [$startDate, $endDate, $company_id];
        if ($department_id) {
            $params[] = $department_id;
        }
        $params = array_merge($params, [$endDate, $startDate, $startDate, $endDate, $startDate, $endDate]);

        // Execute the query
        $results = DB::select($query, $params);

        // Process results and calculate salaries
        $data = [];
        foreach ($results as $result) {
            // Parse JSON fields
            $allowances = json_decode($result->allowances ?? '[]', true) ?: [];
            $deductions = json_decode($result->deductions ?? '[]', true) ?: [];

            // Convert to array
            $employeeData = (array) $result;
            $employeeData['allowances'] = $allowances;
            $employeeData['deductions'] = $deductions;

            // Calculate salary components
            $basicSalary = (float) $employeeData['basic_salary'];
            $approvedNoPayDays = (int) $employeeData['approved_no_pay_days'];
            $installmentAmount = (float) ($employeeData['installment_amount'] ?? 0);

            // 1. Handle Increment (if applicable)
            if (
                $employeeData['increment_active'] &&
                $employeeData['increment_effected_date'] &&
                strtotime($employeeData['increment_effected_date']) <= strtotime($endDate)
            ) {
                $incrementPercent = (float) rtrim($employeeData['increment_value'], '%');
                $basicSalary = $basicSalary * (1 + ($incrementPercent / 100));
            }

            // 2. Calculate No-Pay Deduction
            $perDaySalary = $basicSalary / $workingDaysInMonth;
            $noPayDeduction = $approvedNoPayDays * $perDaySalary;
            $adjustedBasic = $basicSalary - $noPayDeduction;

            // 3. Sum Allowances
            $totalAllowances = array_reduce($allowances, function ($carry, $item) {
                return $carry + (float) $item['amount'];
            }, 0);

            // 4. Sum Fixed Deductions (excluding loans)
            $totalFixedDeductions = array_reduce($deductions, function ($carry, $item) {
                return $carry + (float) $item['amount'];
            }, 0);

            // 5. Calculate EPF/ETF if enabled
            $epfDeduction = 0;
            if ($employeeData['enable_epf_etf']) {
                $epfDeduction = $basicSalary * 0.08; // 8% EPF deduction
            }

            // 6. Calculate Gross and Net Salary
            $grossSalary = $adjustedBasic + $totalAllowances;
            $totalDeductions = $totalFixedDeductions + $installmentAmount + $epfDeduction;
            $netSalary = $grossSalary - $totalDeductions;

            // Add calculated fields to response
            $employeeData['salary_breakdown'] = [
                'basic_salary' => $basicSalary,
                'adjusted_basic' => $adjustedBasic,
                'per_day_salary' => $perDaySalary,
                'no_pay_deduction' => $noPayDeduction,
                'total_allowances' => $totalAllowances,
                'total_fixed_deductions' => $totalFixedDeductions,
                'loan_installment' => $installmentAmount,
                'epf_deduction' => $epfDeduction,
                'gross_salary' => $grossSalary,
                'total_deductions' => $totalDeductions,
                'net_salary' => $netSalary
            ];

            $data[] = $employeeData;
        }

        return response()->json([
            'data' => $data,
            'meta' => [
                'month' => $month,
                'year' => $year,
                'company_id' => $company_id,
                'department_id' => $department_id,
                'count' => count($data),
                'working_days_in_month' => $workingDaysInMonth
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
