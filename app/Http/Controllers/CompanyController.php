<?php

namespace App\Http\Controllers;

use App\Models\company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        return response()->json(company::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:companies,name',
            'location' => 'nullable|string|max:255',
            'established' => 'nullable|digits:4|integer|min:1900|max:' . (date('Y')),
        ]);
        $company = company::create($validated);
        return response()->json($company, 201);
    }

    public function update(Request $request, $id)
    {
        $company = company::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|unique:companies,name,' . $company->id,
            'location' => 'nullable|string|max:255',
            'established' => 'nullable|digits:4|integer|min:1900|max:' . (date('Y')),
        ]);
        $company->update($validated);
        return response()->json($company);
    }

    public function destroy($id)
    {
        $company = company::findOrFail($id);
        $company->delete();
        return response()->json(['message' => 'Deleted'], 204);
    }
}
