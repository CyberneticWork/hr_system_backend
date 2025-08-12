<?php

namespace App\Http\Controllers;

use App\Models\salary_process;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $salary = salary_process::all();
        return response()->json($salary, 200);
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
        $validated = $request->validate([
            'data' => 'required|array',
            'data.*.emp_no' => 'required|integer',
            'data.*.full_name' => 'required|string',
            // Add other validation rules as needed
        ]);

        try {
            foreach ($request->data as $employeeData) {
                salary_process::create([
                    'employee_id' => $employeeData['id'],
                    'employee_no' => $employeeData['emp_no'],
                    'full_name' => $employeeData['full_name'],
                    'company_name' => $employeeData['company_name'],
                    'department_name' => $employeeData['department_name'],
                    'sub_department_name' => $employeeData['sub_department_name'] ?? null,
                    'basic_salary' => $employeeData['basic_salary'],
                    'increment_active' => $employeeData['increment_active'] ?? false,
                    'increment_value' => $employeeData['increment_value'] ?? null,
                    'increment_effected_date' => $employeeData['increment_effected_date'] ?? null,
                    'ot_morning' => $employeeData['ot_morning'] ?? false,
                    'ot_evening' => $employeeData['ot_evening'] ?? false,
                    'enable_epf_etf' => $employeeData['enable_epf_etf'] ?? false,
                    'br1' => $employeeData['br1'] ?? false,
                    'br2' => $employeeData['br2'] ?? false,
                    'br_status' => $employeeData['br_status'] ?? '',
                    'total_loan_amount' => $employeeData['total_loan_amount'] ?? 0,
                    'installment_count' => $employeeData['installment_count'] ?? null,
                    'installment_amount' => $employeeData['installment_amount'] ?? null,
                    'approved_no_pay_days' => $employeeData['approved_no_pay_days'] ?? 0,
                    'allowances' => $employeeData['allowances'] ?? null,
                    'deductions' => $employeeData['deductions'] ?? null,
                    'salary_breakdown' => $employeeData['salary_breakdown'] ?? null,
                    'month' => $request->month ?? date('m'),
                    'year' => $request->year ?? date('Y'),
                ]);
            }

            return response()->json(['message' => 'Salary data saved successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error saving salary data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $salary = salary_process::findOrFail($id);
        $salary->delete();
        return response()->json($salary, 200);
    }

    public function salaryCSV(Request $request)
    {
        // Get processed salaries
        $salaries = salary_process::with(['employee', 'compensation', 'contactDetails'])
            ->where('status', 'processed')
            ->get();

        if ($salaries->isEmpty()) {
            return response()->json(['message' => 'No processed salaries found'], 404);
        }
        // return response()->json($salaries, 200);
        // CSV filename with current date
        $filename = 'CEFT_Salary_Payments_' . now()->format('Y-m-d') . '.csv';

        // Open a file handle for writing
        $handle = fopen('php://temp', 'w');

        // Add CSV headers (matching the Excel template)
        fputcsv($handle, [
            'Record Identifier',
            'Value Date',
            'Payment Method Name',
            'Debit Account No.',
            'Payable Currency',
            'Payment Amount',
            'Beneficiary Code (Request)',
            'Beneficiary Name',
            'Beneficiary Account No',
            'Beneficiary Bank Code',
            'Beneficiary Bank Branch Code',
            'Corporate Ref No',
            'Payment Instructions 1',
            'Payment Instructions 2',
            'Remarks',
            'Remittance Code',
            'Beneficiary Advise Dispatch Mode',
            'Phone/Mobile No',
            'Email'
        ]);

        // Add data rows
        foreach ($salaries as $salary) {
            fputcsv($handle, [
                $salary->id, // Record Identifier (Debit)
                now()->format('d/m/Y'), // Value Date (current date)
                'CEFTS', // Payment Method Name
                '000123456789', // Debit Account No. (hardcoded or configurable)
                'LKR', // Payable Currency
                $salary->salary_breakdown['net_salary'], // Payment Amount
                '', // Beneficiary Code (empty)
                $salary->full_name, // Beneficiary Name
                $salary->compensation->bank_account_no, // Beneficiary Account No
                $salary->compensation->bank_code, // Beneficiary Bank Code
                $salary->compensation->branch_code, // Beneficiary Bank Branch Code
                $salary->employee->attendance_employee_no, // Corporate Ref No
                "Salary for {$salary->month}-{$salary->year}", // Payment Instructions 1
                '', // Payment Instructions 2 (empty)
                'Salary Payment', // Remarks
                '', // Remittance Code (empty)
                '', // Beneficiary Advise Dispatch Mode (empty)
                $salary->contactDetails->mobile_line, // Phone/Mobile No (empty)
                $salary->contactDetails->email  // Email (empty)
            ]);
        }

        // Reset file pointer
        rewind($handle);

        // Get CSV content
        $csv = stream_get_contents($handle);
        fclose($handle);

        // Return CSV as downloadable response
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }
}
