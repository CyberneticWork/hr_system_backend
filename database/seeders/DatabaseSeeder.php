<?php

namespace Database\Seeders;

use App\Models\employment_type;
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

    }
}
