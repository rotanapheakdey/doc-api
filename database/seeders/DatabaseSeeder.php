<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\Document;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. CREATE CENTRAL CORE USERS
        $dg = User::create([
            'name' => 'Director General',
            'email' => 'dg@ministry.gov',
            'password' => Hash::make('password123'),
            'role' => 'dg',
            'department_id' => null,
        ]);

        $fileOfficer = User::create([
            'name' => 'File Department Officer',
            'email' => 'file@ministry.gov',
            'password' => Hash::make('password123'),
            'role' => 'file_dept',
            'department_id' => null,
        ]);

        // 2. DEFINE MINISTRY DEPARTMENTS
        $departmentsData = [
            ['name' => 'Administration', 'code' => 'ADM'],
            ['name' => 'Finance & Accounting', 'code' => 'FIN'],
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Information & Broadcasting', 'code' => 'GDIB'],
            ['name' => 'Digital Archives', 'code' => 'DDA'],
            ['name' => 'Internal Audit', 'code' => 'DIA'],
            ['name' => 'Personnel & Administration', 'code' => 'DPA'],
            ['name' => 'Media Management', 'code' => 'DMM'],
        ];

        // 3. CREATE DEPARTMENTS & MAKER/CHECKER ACCOUNTS
        $deptIds = [];
        foreach ($departmentsData as $data) {
            $dept = Department::create([
                'name' => $data['name'],
                'code' => $data['code']
            ]);

            $deptIds[$data['code']] = $dept->id;
            $lowerCode = strtolower($data['code']);

            // The Checker: VDG Account
            User::create([
                'name' => 'VDG (' . $data['code'] . ')',
                'email' => 'vdg.' . $lowerCode . '@ministry.gov',
                'password' => Hash::make('password123'),
                'role' => 'vdg',
                'department_id' => $dept->id,
            ]);

            // The Maker: Staff Account
            User::create([
                'name' => 'Staff (' . $data['code'] . ')',
                'email' => 'staff.' . $lowerCode . '@ministry.gov',
                'password' => Hash::make('password123'),
                'role' => 'staff',
                'department_id' => $dept->id,
            ]);
        }

        // 4. SEED THE 7-STEP PIPELINE

        $sampleDocs = [
            // ---------------------------------------------------------
            // PHASE 1: pending_dg_init (Sitting on DG's Urgent Feed)
            // ---------------------------------------------------------
            [
                'title' => 'Q3 National Budget Framework Request',
                'status' => 'pending_dg_init', 'assigned_department_id' => null,
                'comment' => 'Urgent clearance requested.',
            ],
            [
                'title' => 'Ministry Vehicle Fleet Registration Renewal',
                'status' => 'pending_dg_init', 'assigned_department_id' => null,
                'comment' => 'Includes updated insurance policies for 2026.',
            ],
            [
                'title' => 'International Press Credential Guidelines',
                'status' => 'pending_dg_init', 'assigned_department_id' => null,
                'comment' => 'New directive from the foreign affairs liaison.',
            ],

            // ---------------------------------------------------------
            // PHASE 2: pending_dispatch (File Dept needs to Dispatch)
            // ---------------------------------------------------------
            [
                'title' => 'Server Equipment Procurement Memo',
                'status' => 'pending_dispatch', 'assigned_department_id' => $deptIds['DDA'],
                'comment' => 'DG Note: Verify specs before sending to tech.',
            ],
            [
                'title' => 'Quarterly Payroll Adjustment Report',
                'status' => 'pending_dispatch', 'assigned_department_id' => $deptIds['FIN'],
                'comment' => 'DG Note: Please cross-check with HR allowances.',
            ],
            [
                'title' => 'New Hire Orientation Schedule',
                'status' => 'pending_dispatch', 'assigned_department_id' => $deptIds['HR'],
                'comment' => 'DG Note: Approved, prepare the orientation packets.',
            ],

            // ---------------------------------------------------------
            // PHASE 3: dg_directed (Staff needs to do the work)
            // ---------------------------------------------------------
            [
                'title' => 'Annual Broadcasting License Audits 2026',
                'status' => 'dg_directed', 'assigned_department_id' => $deptIds['FIN'],
                'comment' => 'Please generate the financial impact report.',
            ],
            [
                'title' => 'National Radio Frequency Spectrum Map',
                'status' => 'dg_directed', 'assigned_department_id' => $deptIds['GDIB'],
                'comment' => 'Update the mapping for the new 5G rollout areas.',
            ],
            [
                'title' => 'Internal Server Security Audit',
                'status' => 'dg_directed', 'assigned_department_id' => $deptIds['DIA'],
                'comment' => 'Audit the firewall logs for Q1 and report vulnerabilities.',
            ],

            // ---------------------------------------------------------
            // PHASE 4: pending_vdg_approval (VDG needs to check/sign)
            // ---------------------------------------------------------
            [
                'title' => 'Media Management Policy Directive Draft',
                'status' => 'pending_vdg_approval', 'assigned_department_id' => $deptIds['DMM'],
                'comment' => 'Staff uploaded the draft. Awaiting VDG review.',
            ],
            [
                'title' => 'Digital Archive Migration Strategy',
                'status' => 'pending_vdg_approval', 'assigned_department_id' => $deptIds['DDA'],
                'comment' => 'Cloud vendor comparisons attached for VDG approval.',
            ],
            [
                'title' => 'Staff Attendance & Leave Balance Q2',
                'status' => 'pending_vdg_approval', 'assigned_department_id' => $deptIds['DPA'],
                'comment' => 'Consolidated timesheets ready for VDG sign-off.',
            ],

            // ---------------------------------------------------------
            // PHASE 5: pending_dg_approval (DG needs to final sign)
            // ---------------------------------------------------------
            [
                'title' => 'HR Q1 Performance Review Consolidation',
                'status' => 'pending_dg_approval', 'assigned_department_id' => $deptIds['HR'],
                'comment' => 'VDG signed off. Ready for top office.',
            ],
            [
                'title' => 'Department Budget Reallocation Request',
                'status' => 'pending_dg_approval', 'assigned_department_id' => $deptIds['FIN'],
                'comment' => 'Cleared by Finance VDG. Awaiting DG executive order.',
            ],
            [
                'title' => 'Public Information Campaign Budget',
                'status' => 'pending_dg_approval', 'assigned_department_id' => $deptIds['GDIB'],
                'comment' => 'Campaign assets verified. Needs DG funding approval.',
            ],

            // ---------------------------------------------------------
            // PHASE 6: dg_signed (File Dept needs to Archive)
            // ---------------------------------------------------------
            [
                'title' => 'Official Ministry Restructuring Authorization',
                'status' => 'dg_signed', 'assigned_department_id' => $deptIds['ADM'],
                'comment' => 'Fully executed. Needs physical vaulting.',
            ],
            [
                'title' => 'Standard Operating Procedures 2026',
                'status' => 'dg_signed', 'assigned_department_id' => $deptIds['DPA'],
                'comment' => 'DG signature attached. Prepare for departmental distribution.',
            ],
            [
                'title' => 'Internal Audit Compliance Certificate',
                'status' => 'dg_signed', 'assigned_department_id' => $deptIds['DIA'],
                'comment' => 'Passed inspection. File original in Cabinet C.',
            ],

            // ---------------------------------------------------------
            // PHASE 7: completed_archive (Dead & Buried)
            // ---------------------------------------------------------
            [
                'title' => '2025 Historical Tax Exemption Receipts',
                'status' => 'completed_archive', 'assigned_department_id' => $deptIds['FIN'],
                'comment' => 'Permanently filed.',
            ],
            [
                'title' => '2024 End of Year Gala Expense Report',
                'status' => 'completed_archive', 'assigned_department_id' => $deptIds['ADM'],
                'comment' => 'Archived and locked.',
            ],
            [
                'title' => 'Decommissioned Server Hardware Log',
                'status' => 'completed_archive', 'assigned_department_id' => $deptIds['DDA'],
                'comment' => 'Hardware recycled. File closed.',
            ],
        ];

        foreach ($sampleDocs as $index => $doc) {
            $hasReport = in_array($doc['status'], ['pending_vdg_approval', 'pending_dg_approval', 'dg_signed', 'completed_archive']);
            Document::create([
                'uploaded_by_user_id' => $fileOfficer->id,
                'assigned_department_id' => $doc['assigned_department_id'],
                'control_no' => 'DOC-20260608-' . Str::upper(Str::random(4)) . ($index + 1),
                'title' => $doc['title'],
                'file_path' => 'documents/seed_test_file_' . ($index + 1) . '.pdf',
                'report_path' => $hasReport ? 'reports/seed_report_file_' . ($index + 1) . '.pdf' : null,
                'file_dept_comment' => $doc['comment'],
                'status' => $doc['status'],
                'created_at' => now()->subHours(10 - $index),
                'updated_at' => now()->subHours(10 - $index),
            ]);
        }
    }
}
