<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Loans;
use App\Models\employee;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(loans::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Map camelCase to snake_case if the client sends employeeId
        if ($request->filled('employeeId') && !$request->filled('employee_id')) {
            $request->merge(['employee_id' => $request->input('employeeId')]);
        }

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->whereNull('deleted_at'), // remove whereNull if you want to allow soft-deleted
            ],
            'loan_id' => 'required|string|unique:loans,loan_id',
            'loan_amount' => ['required', 'numeric', 'min:0'],
            'interest_rate_per_annum' => 'nullable|numeric|min:0',
            'installment_amount' => 'required|numeric|min:0',
            'start_from' => 'required|date',
            'with_interest' => 'required|boolean',
            'installment_count' => 'nullable|integer|min:1',
        ]);

        try {
            $loan = loans::create($validated);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }

        // $loan = loans::create($validated);

        // return response()->json($loan, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $loan = loans::findOrFail($id);
        return response()->json($loan);
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

    /**
     * Get employee details by employee number.
     */
    public function getEmployeeByNumber($number)
    {
        $employee = employee::where('attendance_employee_no', $number)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }
        return response()->json([
            'id' => $employee->id,
            'full_name' => $employee->full_name,
        ]);
    }
}
