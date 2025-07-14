<?php

namespace App\Http\Controllers;

use App\Models\Allowances;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class AllowancesController extends Controller
{
    public function index()
    {
        $allowances = Allowances::with('company:id,name')->get();
        return response()->json(['data' => $allowances]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'allowance_code' => 'required|unique:allowances,allowance_code',
            'allowance_name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive', 
            'category' => 'required|in:travel,bonus,performance,health,other',
            'allowance_type' => 'required|in:fixed,variable',
            'company_id' => 'required|exists:companies,id',
            'from_date' => 'nullable|date|required_if:allowance_type,Variable',
            'to_date' => 'nullable|date|after_or_equal:from_date|required_if:allowance_type,Variable'
        ]);

        $allowance = Allowances::create($validated);
        return response()->json(['data' => $allowance], Response::HTTP_CREATED);
    }

    public function show(Allowances $allowance)
    {
        return response()->json(['data' => $allowance->load('company:id,name')]);
    }

    public function update(Request $request, Allowances $allowance)
    {
        $validated = $request->validate([
            'allowance_code' => ['required', Rule::unique('allowances')->ignore($allowance->id)],
            'allowance_name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive', 
            'category' => 'required|in:travel,bonus,performance,health,other',
            'allowance_type' => 'required|in:fixed,variable',
            'company_id' => 'required|exists:companies,id',
            'from_date' => 'nullable|date|required_if:allowance_type,Variable',
            'to_date' => 'nullable|date|after_or_equal:from_date|required_if:allowance_type,Variable'
        ]);

        $allowance->update($validated);
        return response()->json(['data' => $allowance]);
    }

    public function destroy(Allowances $allowance)
    {
        $allowance->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}