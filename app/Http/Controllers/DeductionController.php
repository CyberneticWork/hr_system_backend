<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\deduction; // Assuming you have a Deduction model
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DeductionTemplateExport;
use App\Imports\DeductionImport;

class DeductionController extends Controller
{

    // Add your methods for handling deductions here
    // For example, you might have methods for creating, updating, deleting, and listing deductions

    public function index()
    {
        $deductions = deduction::with('department', 'company')->get();


        return response()->json($deductions);
    }

    public function store(Request $request)
    {
        //validate the request data
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'company_id' => 'required|exists:companies,id',  // Add this line
            'deduction_code' => 'required|string|max:255|unique:deductions,deduction_code',
            'deduction_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            // 'category' => 'required|in:EPF,ETF,other',
            'deduction_type' => 'required|in:fixed,variable',
            'startDate' => 'nullable|string|max:255',
            'endDate' => 'nullable|string|max:255',
        ]);
        // Logic to create a new deduction
        $deduction = deduction::create($request->all());
        return response()->json($deduction, 201);
    }

    public function show($id)
    {
        // Logic to show a specific deduction
        $deduction = deduction::find($id);
        if (!$deduction) {
            return response()->json(['message' => 'Deduction not found'], 404);
        }
        return response()->json($deduction);
    }

    public function update(Request $request, $id)
    {
        // Logic to update a specific deduction
        $validator = Validator::make($request->all(), [
            'department_id' => 'required|exists:departments,id',
            'company_id' => 'required|exists:companies,id',  // Add this line
            'deduction_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            // 'category' => 'required|in:EPF,ETF,other',
            'deduction_type' => 'required|in:fixed,variable',
            'startDate' => 'nullable|string|max:255',
            'endDate' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $deduction = deduction::find($id);
        if (!$deduction) {
            return response()->json(['message' => 'Deduction not found'], 404);
        }
        $deduction->update($validator->validated());
        return response()->json($deduction);
    }

    public function destroy($id)
    {
        // Logic to soft delete a specific deduction
        $deduction = deduction::find($id);
        if (!$deduction) {
            return response()->json(['message' => 'Deduction not found'], 404);
        }
        $deduction->delete();
        return response()->json(['message' => 'Deduction deleted successfully']);
    }
    /**
     * Download Excel template for deductions import
     */
    public function downloadTemplate()
    {
        return Excel::download(new DeductionTemplateExport(), 'deductions_template.xlsx');
    }

    /**
     * Import deductions from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        try {
            Excel::import(new DeductionImport(), $request->file('file'));
            return response()->json(['message' => 'Deductions imported successfully'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error importing file',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    public function getDeductionsByCompanyOrDepartment(Request $request)
    {
        $query = deduction::where('status', 'active');



        $allowances = $query->get();

        if ($allowances->isEmpty()) {
            return response()->json(['message' => 'No allowances found.'], 404);
        }

        return response()->json(['data' => $allowances], 200);
    }
}

