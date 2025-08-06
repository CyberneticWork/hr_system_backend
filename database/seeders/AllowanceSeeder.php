<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AllowanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ['travel', 'bonus', 'performance', 'health', 'other'];
        $types = ['fixed', 'variable'];

        $allowances = [
            ['Travel Allowance', 'travel'],
            ['Performance Bonus', 'bonus'],
            ['Health Insurance', 'health'],
            ['Meal Allowance', 'other'],
            ['Transportation Allowance', 'travel'],
            ['Annual Bonus', 'bonus'],
            ['Overtime Pay', 'performance'],
            ['Housing Allowance', 'other'],
        ];

        foreach ($allowances as $allowance) {
            $type = $types[array_rand($types)];
            $isFixed = $type === 'fixed';

            DB::table('allowances')->insert([
                'allowance_code' => 'ALW-' . Str::upper(Str::random(6)),
                'allowance_name' => $allowance[0],
                'company_id' => rand(1, 4),
                'department_id' => rand(1, 10),
                'status' => 'active',
                'category' => $allowance[1],
                'allowance_type' => $type,
                'amount' => rand(1000, 10000) / 100, // Random amount between 10.00 and 100.00
                'fixed_date' => $isFixed ? now()->addDays(rand(1, 30))->format('Y-m-d') : null,
                'variable_from' => !$isFixed ? now()->subDays(rand(1, 30))->format('Y-m-d') : null,
                'variable_to' => !$isFixed ? now()->addDays(rand(31, 60))->format('Y-m-d') : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
