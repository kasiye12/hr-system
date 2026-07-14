<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\ApplicantPosition;
use App\Models\SelectionCriterion;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run()
    {
        // Create default organization
        $org = Organization::firstOrCreate(
            ['name' => 'TNT'],
            ['active' => true]
        );

        // Create default positions
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
            ApplicantPosition::firstOrCreate(
                [
                    'name' => $pos['name'],
                    'organization_id' => $org->id
                ],
                [
                    'active' => true,
                    'grade' => $pos['grade'],
                    'salary' => $pos['salary'],
                    'requirement_type' => $pos['requirement_type'],
                    'criteria' => $pos['criteria'],
                ]
            );
        }

        // Create default selection criteria
        $criteria = [
            ['Document Screening', 20, 100, 50, 1],
            ['Written Examination', 30, 100, 50, 2],
            ['Interview', 30, 100, 50, 3],
            ['Practical Test', 20, 100, 50, 4],
        ];

        foreach ($criteria as $item) {
            SelectionCriterion::firstOrCreate(
                [
                    'organization_id' => $org->id,
                    'criterion_name' => $item[0],
                    'position_id' => null,
                ],
                [
                    'weight' => $item[1],
                    'max_score' => $item[2],
                    'pass_mark' => $item[3],
                    'display_order' => $item[4],
                    'active' => true,
                ]
            );
        }
    }
}