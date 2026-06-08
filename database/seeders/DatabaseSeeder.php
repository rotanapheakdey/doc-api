<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\Document; // 💡 Fixed: Imported Document model
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB; // 💡 Fixed: Imported DB facade
use Illuminate\Support\Str;        // 💡 Fixed: Imported Str helper

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create the Initial Departments
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
        // 💡 Fixed: Assigned to a variable ($fileOfficer) so the document loop can reference its ID
        $fileOfficer = User::create([
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

        // More Ministry Departments Seeding
        // --------------------------------------------------------
        $departments = [
            ['name' => 'General Department of Information & Broadcasting', 'code' => 'GDIB'],
            ['name' => 'Department of Digital Archives', 'code' => 'DDA'],
            ['name' => 'Department of Internal Audit', 'code' => 'DIA'],
            ['name' => 'Department of Personnel & Administration', 'code' => 'DPA'],
            ['name' => 'Department of Media Management', 'code' => 'DMM'], // 💡 Fixed: Stripped extra "=" typo
        ];

        $deptIds = [];
        foreach ($departments as $dept) {
            $id = DB::table('departments')->insertGetId([
                'name' => $dept['name'],
                'code' => $dept['code'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $deptIds[] = $id;
        };

        // 3. SEED 5 DOCUMENTS
        // --------------------------------------------------------
        $sampleDocs = [
            [
                'title' => 'Q3 National Budget Framework Request',
                'status' => 'pending_dg_init',
                'assigned_department_id' => null,
                'file_dept_comment' => 'Urgent clearance requested for broadcasting systems equipment.',
            ],
            [
                'title' => 'Digital Archive Server Expansion Proposal',
                'status' => 'dg_directed',
                'assigned_department_id' => $deptIds[1], // Assigned to DDA
                'file_dept_comment' => 'Awaiting physical review from technical department.',
            ],
            [
                'title' => 'Annual Broadcasting License Audits 2026',
                'status' => 'processing_dept',
                'assigned_department_id' => $deptIds[2], // Assigned to DIA
                'file_dept_comment' => 'Internal checking phase.',
            ],
            [
                'title' => 'Media Management Policy Directive Draft',
                'status' => 'pending_vdg',
                'assigned_department_id' => $deptIds[4], // Assigned to DMM
                'file_dept_comment' => 'Forwarded upwards for VDG screening.',
            ],
            [
                'title' => 'Official Ministry Restructuring Authorization',
                'status' => 'completed_archive',
                'assigned_department_id' => $deptIds[3], // Assigned to DPA
                'file_dept_comment' => 'Fully signed off and locked down in permanent records.',
            ],
        ];

        foreach ($sampleDocs as $index => $doc) {
            Document::create([
                'uploaded_by_user_id' => $fileOfficer->id, // 💡 Fixed: Uses the valid file officer ID
                'assigned_department_id' => $doc['assigned_department_id'],
                'control_no' => 'DOC-20260608-' . Str::upper(Str::random(4)) . ($index + 1),
                'title' => $doc['title'],
                'file_path' => 'documents/seed_test_file_' . ($index + 1) . '.pdf',
                'file_dept_comment' => $doc['file_dept_comment'],
                'status' => $doc['status'],
                'created_at' => now()->subHours(5 - $index),
                'updated_at' => now()->subHours(5 - $index),
            ]);
        }
    }
}
