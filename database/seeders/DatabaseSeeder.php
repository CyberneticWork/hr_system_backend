<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\roles;
use App\Models\shifts;
use App\Models\spouse;
use App\Models\company;
use App\Models\children;
use App\Models\employee;
use App\Models\allowances;
use App\Models\departments;
use App\Models\designation;
use App\Models\contact_detail;
use App\Models\pay_deductions;
use App\Models\employment_type;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\sub_departments;
use Illuminate\Database\Seeder;
use App\Models\organization_assignment;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::create([
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

        designation::insert([
            ['name' => 'Software Engineer', 'description' => 'Responsible for software development'],
            ['name' => 'HR Manager', 'description' => 'Manages HR operations'],
            ['name' => 'Finance Analyst', 'description' => 'Analyzes financial data'],
            ['name' => 'Marketing Specialist', 'description' => 'Handles marketing campaigns'],
            ['name' => 'Operations Manager', 'description' => 'Oversees daily operations'],
        ]);

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

        company::insert([
            [
                'id' => 1,
                'name' => 'ABC Pvt Ltd'
            ],
            [
                'id' => 2,
                'name' => 'XYZ Pvt Ltd'
            ],
        ]);

        organization_assignment::create([
            'id' => 1,
            'company_id' => 1,
            'current_supervisor' => 1,
            'date_of_joining' => '2020-01-01',
            'department_id' => 3,
            'sub_department_id' => 7,
            'designation_id' => 1,
            'day_off' => 'Sunday',
            'confirmation_date' => '2020-01-01',
        ]);

        spouse::create([
            'id' => 1,
            'type' => 'wife',
            'title' => 'Mrs',
            'name' => 'Tharushi Bandara',
            'nic' => '123456789102',
            'age' => 28,
            'dob' => '1995-01-01',
        ]);

        employee::create([
            'title' => 'Mr',
            'attendance_employee_no' => 1,
            'epf' => 1,
            'nic' => '123456789101',
            'dob' => '1995-01-01',
            'gender' => 'male',
            'name_with_initials' => 'Isuru Bandara',
            'full_name' => 'Isuru Bandara',
            'display_name' => 'razorisuru',
            'is_active' => 1,
            'employment_type_id' => 1,
            'organization_assignment_id' => 1,
            'marital_status' => 'married',
            'spouse_id' => 1,
        ]);

        children::insert([
            [
                'name' => 'Athendi Themuni',
                'nic' => '123456789103',
                'age' => 5,
                'dob' => '2018-01-01',
                'employee_id' => 1,
            ],
            [
                'name' => 'Methendi Weluni',
                'nic' => '12345678910',
                'age' => 5,
                'dob' => '2018-01-01',
                'employee_id' => 1,
            ]
        ]);

        contact_detail::create([
            'employee_id' => 1,
            'permanent_address' => '123 Main St, Colombo',
            'mobile_line' => '0712345678',
            'email' => 'test@mil.com',
        ]);


    }
}
