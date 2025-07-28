<?php
namespace App\Imports;

use App\Models\Allowances;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AllowancesImport implements ToCollection, WithHeadingRow
{
    private $errors = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            // Skip empty rows
            if ($row->filter()->isEmpty()) {
                continue;
            }

            // Normalize and validate the row structure
            $normalizedRow = $this->normalizeRow($row);
            
            if (!isset($normalizedRow['allowance_type'])) {
                $this->errors[] = [
                    'row' => $index + 2,
                    'errors' => ['Missing required column: allowance_type']
                ];
                continue;
                 if (isset($normalized['variable_from']) && is_numeric($normalized['variable_from'])) {
        $normalized['variable_from'] = $this->convertExcelDate($normalized['variable_from']);
    }
    
    if (isset($normalized['variable_to']) && is_numeric($normalized['variable_to'])) {
        $normalized['variable_to'] = $this->convertExcelDate($normalized['variable_to']);
    }
    
    if (isset($normalized['fixed_date']) && is_numeric($normalized['fixed_date'])) {
        $normalized['fixed_date'] = $this->convertExcelDate($normalized['fixed_date']);
    }
    
    return $normalized;
            }

            $validator = Validator::make($normalizedRow, [
                'allowance_code' => 'required|unique:allowances,allowance_code',
                'allowance_name' => 'required|string|max:255',
                'status' => 'required|in:active,inactive',
                'category' => 'required|in:travel,bonus,performance,health,other',
                'allowance_type' => 'required|in:fixed,variable',
                'company_id' => 'required|exists:companies,id',
                'amount' => 'required|numeric|min:0',
                'department_id' => [
                    'nullable',
                    'exists:departments,id',
                    Rule::exists('departments', 'id')->where(function ($query) use ($normalizedRow) {
                        $query->where('company_id', $normalizedRow['company_id']);
                    })
                ],
                'fixed_date' => [
                    'nullable',
                    'date',
                    Rule::requiredIf(function () use ($normalizedRow) {
                        return $normalizedRow['allowance_type'] === 'fixed';
                    })
                ],
                'variable_from' => [
                    'nullable',
                    'date',
                    Rule::requiredIf(function () use ($normalizedRow) {
                        return $normalizedRow['allowance_type'] === 'variable';
                    }),
                    function ($attribute, $value, $fail) use ($normalizedRow) {
                        if ($normalizedRow['allowance_type'] === 'variable' && 
                            isset($normalizedRow['variable_to']) && 
                            $value > $normalizedRow['variable_to']) {
                            $fail('The from date must be before the to date.');
                        }
                    }
                ],
                'variable_to' => [
                    'nullable',
                    'date',
                    Rule::requiredIf(function () use ($normalizedRow) {
                        return $normalizedRow['allowance_type'] === 'variable';
                    }),
                    'after_or_equal:variable_from'
                ]
            ]);

            if ($validator->fails()) {
                $this->errors[] = [
                    'row' => $index + 2,
                    'errors' => $validator->errors()->all()
                ];
                continue;
            }

            $data = $validator->validated();

            // Prepare data based on allowance type
            $data = $this->prepareData($data);

            Allowances::create($data);
        }

        if (!empty($this->errors)) {
            $this->throwValidationException();
        }
    }

    private function normalizeRow($row)
    {
        $normalized = [];
        $mappings = [
            'allowance_code' => ['allowance_code', 'code', 'allowance code'],
            'allowance_name' => ['allowance_name', 'name', 'allowance name'],
            'status' => ['status'],
            'category' => ['category'],
            'allowance_type' => ['allowance_type', 'type', 'allowance type'],
            'company_id' => ['company_id', 'company', 'company id'],
            'department_id' => ['department_id', 'department', 'department id'],
            'amount' => ['amount'],
            'fixed_date' => ['fixed_date', 'fixed date'],
            'variable_from' => ['variable_from', 'from date', 'start date'],
            'variable_to' => ['variable_to', 'to date', 'end date']
        ];

        foreach ($mappings as $field => $possibleHeaders) {
            foreach ($possibleHeaders as $header) {
                $header = strtolower(str_replace(' ', '_', $header));
                if (isset($row[$header])) {
                    $normalized[$field] = $row[$header];
                    break;
                }
            }
        }

        return $normalized;
    }

    private function prepareData($data)
    {
        if ($data['allowance_type'] === 'fixed') {
            $data['variable_from'] = null;
            $data['variable_to'] = null;
        } else {
            $data['fixed_date'] = null;
        }

        return $data;
    }
private function convertExcelDate($excelDate)
{
    if (is_numeric($excelDate)) {
        // Convert Excel serial date to YYYY-MM-DD
        $unixDate = ($excelDate - 25569) * 86400;
        return gmdate("Y-m-d", $unixDate);
    }
    return $excelDate;
}
    private function throwValidationException()
    {
        $errorMessages = [];
        foreach ($this->errors as $error) {
            $errorMessages[] = "Row {$error['row']}: " . implode(', ', $error['errors']);
        }
        throw new \Exception(implode("\n", $errorMessages));
    }
}