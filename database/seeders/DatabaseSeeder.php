<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================
        // 1. USERS
        // ============================================
        DB::table('users')->truncate();
        
        $users = [
            ['username' => 'admin', 'password' => 'Admin@123', 'role' => 'admin'],
            ['username' => 'hr_editor', 'password' => 'HR@123', 'role' => 'editor'],
            ['username' => 'viewer', 'password' => 'View@123', 'role' => 'viewer'],
        ];

        foreach ($users as $user) {
            DB::table('users')->insert([
                'username' => $user['username'],
                'password_hash' => Hash::make($user['password']),
                'role' => $user['role'],
                'active' => 1,
                'must_change' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ============================================
        // 2. ORGANIZATIONS
        // ============================================
        DB::table('organizations')->truncate();
        
        $orgId = DB::table('organizations')->insertGetId([
            'name' => 'TNT',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ============================================
        // 3. APPLICANT POSITIONS
        // ============================================
        DB::table('applicant_positions')->truncate();
        
        $positions = [
            [
                'name' => 'Project Manager',
                'grade' => 'Grade I',
                'salary' => '50,000 - 70,000',
                'requirement_type' => 'Full-time',
                'criteria' => "Bachelor's degree with 5+ years experience in project management"
            ],
            [
                'name' => 'Senior Engineer',
                'grade' => 'Grade II',
                'salary' => '40,000 - 55,000',
                'requirement_type' => 'Full-time',
                'criteria' => "Bachelor's degree with 3+ years experience in engineering"
            ],
            [
                'name' => 'Junior Engineer',
                'grade' => 'Grade III',
                'salary' => '25,000 - 35,000',
                'requirement_type' => 'Full-time',
                'criteria' => "Bachelor's degree in Engineering, fresh graduates welcome"
            ],
            [
                'name' => 'HR Officer',
                'grade' => 'Grade II',
                'salary' => '30,000 - 45,000',
                'requirement_type' => 'Full-time',
                'criteria' => "Bachelor's degree with 2+ years HR experience"
            ],
            [
                'name' => 'Accountant',
                'grade' => 'Grade II',
                'salary' => '35,000 - 50,000',
                'requirement_type' => 'Full-time',
                'criteria' => "Bachelor's degree with 3+ years accounting experience"
            ],
            [
                'name' => 'Office Engineer',
                'grade' => 'Grade II',
                'salary' => '35,000 - 50,000',
                'requirement_type' => 'Full-time',
                'criteria' => "Bachelor's degree with 3+ years experience"
            ],
            [
                'name' => 'Site Engineer',
                'grade' => 'Grade II',
                'salary' => '35,000 - 50,000',
                'requirement_type' => 'Full-time',
                'criteria' => "Bachelor's degree with 3+ years site experience"
            ],
        ];

        foreach ($positions as $pos) {
            DB::table('applicant_positions')->insert([
                'organization_id' => $orgId,
                'name' => $pos['name'],
                'active' => 1,
                'grade' => $pos['grade'],
                'salary' => $pos['salary'],
                'requirement_type' => $pos['requirement_type'],
                'criteria' => $pos['criteria'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ============================================
        // 4. SELECTION CRITERIA
        // ============================================
        DB::table('selection_criteria')->truncate();
        
        $criteria = [
            ['Document Screening', 20, 100, 50, 1],
            ['Written Examination', 30, 100, 50, 2],
            ['Interview', 30, 100, 50, 3],
            ['Practical Test', 20, 100, 50, 4],
        ];

        foreach ($criteria as $item) {
            DB::table('selection_criteria')->insert([
                'organization_id' => $orgId,
                'position_id' => null,
                'criterion_name' => $item[0],
                'weight' => $item[1],
                'max_score' => $item[2],
                'pass_mark' => $item[3],
                'display_order' => $item[4],
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ============================================
        // 5. PROJECTS (Optional - for Manpower)
        // ============================================
        DB::table('projects')->truncate();
        
        $projects = [
            ['name' => 'Main Office', 'slot' => 1, 'status' => 'Active'],
            ['name' => 'Branch A', 'slot' => 2, 'status' => 'Active'],
            ['name' => 'Branch B', 'slot' => 3, 'status' => 'Inactive'],
        ];

        foreach ($projects as $project) {
            DB::table('projects')->insert([
                'name' => $project['name'],
                'slot' => $project['slot'],
                'status' => $project['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ============================================
        // 6. CATEGORIES (Job Titles)
        // ============================================
        DB::table('categories')->truncate();
        
        $categories = [
            ['code' => 'MGT', 'name' => 'Management', 'department' => 'Administration', 'employment_type' => 'Permanent', 'row_order' => 1],
            ['code' => 'HR', 'name' => 'Human Resources', 'department' => 'Administration', 'employment_type' => 'Permanent', 'row_order' => 2],
            ['code' => 'ACC', 'name' => 'Accountant', 'department' => 'Finance', 'employment_type' => 'Permanent', 'row_order' => 3],
            ['code' => 'ENG', 'name' => 'Engineer', 'department' => 'Operations', 'employment_type' => 'Contract', 'row_order' => 4],
            ['code' => 'TECH', 'name' => 'Technician', 'department' => 'Operations', 'employment_type' => 'Contract', 'row_order' => 5],
            ['code' => 'LAB', 'name' => 'Laborer', 'department' => 'Operations', 'employment_type' => 'Daily', 'row_order' => 6],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert([
                'code' => $category['code'],
                'name' => $category['name'],
                'department' => $category['department'],
                'employment_type' => $category['employment_type'],
                'row_order' => $category['row_order'],
                'is_reconciliation' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}