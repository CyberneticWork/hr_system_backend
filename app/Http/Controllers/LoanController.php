<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\loans;

class LoanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(\App\Models\loans::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'loan_id' => 'required|string|unique:loans,loan_id',
            'employee_id' => 'required|exists:employees,id',
            'loan_amount' => 'required|numeric|min:0',
            'interest_rate_per_annum' => 'nullable|numeric|min:0',
            'installment_amount' => 'required|numeric|min:0',
            'start_from' => 'required|date',
            'with_interest' => 'required|boolean',
            'installment_count' => 'nullable|integer|min:1',
        ]);

        $loan = loans::create($validated);

        return response()->json($loan, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $loan = \App\Models\loans::findOrFail($id);
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
}
