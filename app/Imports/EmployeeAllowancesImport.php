<?php

namespace App\Imports;

use App\Models\employee_allowances;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class EmployeeAllowancesImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        return new employee_allowances([
            'employee_id' => $row['id'] ?? $row['ID'], // Handles both cases
            'allowance_id' => $row['allowance_id'] ?? $row['allowance id'] ?? $row['Allowance ID'], // Multiple possible headers
            'custom_amount' => $row['amount_lkr'] ?? $row['amount (lkr)'] ?? $row['Amount (LKR)'], // Multiple possible headers
            'is_active' => true
        ]);
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:employees,id',
            'allowance_id' => 'required|exists:allowances,id',
            'amount_lkr' => 'required|numeric|min:0'
        ];
    }

    public function prepareForValidation($data)
    {
        // Normalize all possible header variations
        $data['id'] = $data['ID'] ?? $data['id'] ?? null;
        $data['allowance_id'] = $data['Allowance ID'] ?? $data['allowance id'] ?? $data['allowance_id'] ?? null;
        $data['amount_lkr'] = $data['Amount (LKR)'] ?? $data['amount (lkr)'] ?? $data['amount_lkr'] ?? null;

        return $data;
    }
}
