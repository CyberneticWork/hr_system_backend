<?php

namespace Database\Seeders;

use App\Models\allowances;
use App\Models\departments;
use App\Models\employment_type;
use App\Models\pay_deductions;
use App\Models\roles;
use App\Models\shifts;
use App\Models\sub_departments;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        User::factory()->create([
            'name' => 'Isuru Bandara',
            'email' => 'isuru@mail.com',
            'password' => '123456789',
            'role' => 'admin',
        ]);

        employment_type::insert([
            [
                'id' => 1,
                'name' => 'Permanent Basis',
            ],
            [
                'id' => 2,
                'name' => 'Training',
            ],
            [
                'id' => 3,
                'name' => 'Contract Basis',
            ],
            [
                'id' => 4,
                'name' => 'Daily Wages Salary',
            ],
        ]);



        $departments = [
            'IT',
            'Human Resources',
            'Finance',
            'Marketing',
            'Operations'
        ];

        foreach ($departments as $dept) {
            departments::create(['name' => $dept]);
        }

        $subDepartments = [
            'IT' => ['Software Development', 'Network Administration', 'Technical Support'],
            'Human Resources' => ['Recruitment', 'Employee Relations'],
            'Finance' => ['Accounts Payable', 'Accounts Receivable'],
            'Marketing' => ['Digital Marketing', 'Brand Management'],
            'Operations' => ['Logistics', 'Facilities']
        ];

        foreach ($subDepartments as $deptName => $subs) {
            $department = departments::where('name', $deptName)->first();

            if ($department) {
                foreach ($subs as $sub) {
                    sub_departments::create([
                        'name' => $sub,
                        'department_id' => $department->id
                    ]);
                }
            }
        }

        $shifts = [
            ['001', 'No OT - WD', '08:00:00', '17:00:00', '08:00:00', '17:00:00', '00:00:00', false, 4.5],
            ['002', 'Snr Mgt - FM', '08:15:00', '17:15:00', '08:15:00', '17:15:00', '00:00:00', false, 4.5],
            ['003', 'Snr Mgt - Purchasing', '07:45:00', '16:45:00', '07:45:00', '16:45:00', '00:00:00', false, 4.5],
            ['004', 'Office Executive - WD', '08:00:00', '17:00:00', '08:00:00', '17:00:00', '08:00:00', false, 5.0],
            ['005', 'Office Executive - WE', '08:00:00', '13:00:00', '08:00:00', '13:00:00', '08:00:00', false, 5.0],
            ['006', 'Production Mgr - WD', '08:00:00', '17:00:00', '07:30:00', '17:15:00', '08:00:00', false, 4.5],
            ['007', 'Production Mgr - WE', '08:00:00', '13:00:00', '07:30:00', '17:15:00', '08:00:00', false, 5.0],
            ['008', 'QC - WE', '08:00:00', '13:00:00', '08:00:00', '17:00:00', '08:00:00', false, 5.0],
            ['009', 'No OT with Late - WD', '08:00:00', '17:00:00', '08:00:00', '17:00:00', '08:00:00', false, 4.5],
            ['010', 'Production - WD', '08:00:00', '17:00:00', '07:30:00', '17:00:00', '08:00:00', false, 5.0],
            ['011', 'Production - WE', '08:00:00', '13:00:00', '07:30:00', '17:00:00', '08:00:00', false, 5.0],
            ['012', 'Production - Trainee', '08:00:00', '17:00:00', '08:00:00', '17:00:00', '08:00:00', false, 4.5],
            ['013', 'No OT with Late - WE', '12:00:00', '17:00:00', '12:00:00', '17:00:00', '12:00:00', false, 5.0],
            ['014', 'OT with Late - WE', '12:00:00', '17:00:00', '11:30:00', '17:00:00', '12:00:00', false, 5.0],
            ['015', 'Security - WD Morning', '07:00:00', '15:00:00', '07:00:00', '19:00:00', '07:00:00', false, 4.0],
            ['016', 'Security - WD Night', '19:00:00', '03:00:00', '07:00:00', '07:00:00', '07:00:00', true, 4.0],
            ['017', 'Security - WE Morning', '07:00:00', '15:00:00', '07:00:00', '19:00:00', '07:00:00', false, 5.0],
            ['018', 'Security - WE Night', '19:00:00', '00:00:00', '07:00:00', '07:00:00', '07:00:00', true, 5.0],
        ];

        foreach ($shifts as $shift) {
            shifts::create([
                'shift_code' => $shift[0],
                'shift_description' => $shift[1],
                'start_time' => $shift[2],
                'end_time' => $shift[3],
                'morning_ot_start' => $shift[4],
                'special_ot_start' => $shift[5],
                'late_deduction' => $shift[6],
                'midnight_roster' => $shift[7],
                'nopay_hour_halfday' => $shift[8],
            ]);
        }

        $allowances = [
            ['001', 'Traveling Allowance'],
            ['002', 'Special Allowance'],
            ['003', 'Attendance Allowance'],
            ['004', 'Production Incentive'],
            ['005', 'Medical Reimbursement'],
            ['006', 'Other Reimbursement'],
        ];

        foreach ($allowances as [$code, $name]) {
            allowances::create([
                'allowance_code' => $code,
                'allowance_name' => $name,
            ]);
        }

        pay_deductions::insert([
            [
                'pay_deduction_code' => '001',
                'pay_deduction_name' => 'Salary Advance',
                'pay_deduction_amount' => 0.00,
            ],
            [
                'pay_deduction_code' => '002',
                'pay_deduction_name' => 'Meal Deduction',
                'pay_deduction_amount' => 0.00,
            ],
            [
                'pay_deduction_code' => '003',
                'pay_deduction_name' => 'Other Deduction',
                'pay_deduction_amount' => 0.00,
            ],
            [
                'pay_deduction_code' => '004',
                'pay_deduction_name' => 'Bond Deduction',
                'pay_deduction_amount' => 0.00,
            ],
        ]);

    }
}
