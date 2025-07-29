<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DeductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ['EPF', 'ETF', 'other'];
        $types = ['fixed', 'variable'];

        $deductions = [
            ['EPF Contribution', 'EPF'],
            ['ETF Contribution', 'ETF'],
            ['Tax Deduction', 'other'],
            ['Insurance Premium', 'other'],
            ['Loan Repayment', 'other'],
            ['Advance Salary Deduction', 'other'],
            ['Union Fees', 'other'],
        ];

        foreach ($deductions as $deduction) {
            $type = $types[array_rand($types)];
            $isFixed = $type === 'fixed';

            DB::table('deductions')->insert([
                'deduction_code' => 'DED-' . Str::upper(Str::random(6)),
                'deduction_name' => $deduction[0],
                'company_id' => rand(1, 4),
                'department_id' => rand(1, 10),
                'description' => 'Monthly ' . $deduction[0] . ' for employees',
                'amount' => rand(500, 5000) / 100, // Random amount between 5.00 and 50.00
                'status' => 'active',
                'category' => $deduction[1],
                'deduction_type' => $type,
                'startDate' => !$isFixed ? now()->subDays(rand(1, 30))->format('Y-m-d') : null,
                'endDate' => !$isFixed ? now()->addDays(rand(31, 60))->format('Y-m-d') : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
