<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create the Departments
        $adminDept = Department::create(['name' => 'Administration', 'code' => 'ADM']);
        $financeDept = Department::create(['name' => 'Finance & Accounting', 'code' => 'FIN']);
        $hrDept = Department::create(['name' => 'Human Resources', 'code' => 'HR']);

        // 2. Create the 4 strict User Roles

        // The Top Boss (Can see everything)
        User::create([
            'name' => 'Director General',
            'email' => 'dg@ministry.gov',
            'password' => Hash::make('password123'),
            'role' => 'dg',
            'department_id' => null,
        ]);

        // The Air Traffic Control (Handles scanning and archiving)
        User::create([
            'name' => 'File Department Officer',
            'email' => 'file@ministry.gov',
            'password' => Hash::make('password123'),
            'role' => 'file_dept',
            'department_id' => null,
        ]);

        // The Branch Manager (Approves Finance work)
        User::create([
            'name' => 'Vice Director General (Finance)',
            'email' => 'vdg.finance@ministry.gov',
            'password' => Hash::make('password123'),
            'role' => 'vdg',
            'department_id' => $financeDept->id,
        ]);

        // The Ground Worker (Does the actual Finance work)
        User::create([
            'name' => 'Finance Staff Member',
            'email' => 'staff.finance@ministry.gov',
            'password' => Hash::make('password123'),
            'role' => 'staff',
            'department_id' => $financeDept->id,
        ]);
    }
}
