<?php

namespace App\Imports;

use App\Models\employee_deductions;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class EmployeeDeductionsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        return new employee_deductions([
            'employee_id' => $row['id'] ?? $row['ID'], // Handles both cases
            'deduction_id' => $row['deduction_id'] ?? $row['deduction id'] ?? $row['Deduction ID'], // Multiple possible headers
            'custom_amount' => $row['deduction_amount_lkr'] ?? $row['deduction amount (lkr)'] ?? $row['Deduction Amount (LKR)'] ?? $row['Deduction Amount (LKR)'], // Multiple possible headers
            'is_active' => true
        ]);
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:employees,id',
            'deduction_id' => 'required|exists:deductions,id',
            'deduction_amount_lkr' => 'required|numeric|min:0'
        ];
    }

    public function prepareForValidation($data)
    {
        // Normalize all possible header variations
        $data['id'] = $data['ID'] ?? $data['id'] ?? null;
        $data['deduction_id'] = $data['Deduction ID'] ?? $data['deduction id'] ?? $data['deduction_id'] ?? $data['deduction_id'] ?? null;
        $data['deduction_amount_lkr'] = $data['Deduction Amount (LKR)'] ?? $data['Deduction Amount (LKR)'] ?? $data['deduction amount (lkr)'] ?? $data['deduction_amount_lkr'] ?? null;

        return $data;
    }
}
