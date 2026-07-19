<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;
use App\Models\PublicHoliday;
use App\Models\LeaveSetting;

class LeaveTypeSeeder extends Seeder
{
    public function run()
    {
        // Leave Types
        $leaveTypes = [
            [
                'name' => 'Annual Leave',
                'code' => 'annual',
                'description' => '16 working days for first year, +1 day every 2 additional years (max 30)',
                'default_days' => 16,
                'is_paid' => true,
                'requires_medical_certificate' => false,
                'active' => true,
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'sick',
                'description' => 'Up to 6 months: 1st month 100% pay, next 2 months 50%, final 3 months unpaid',
                'default_days' => 130,
                'is_paid' => true,
                'requires_medical_certificate' => true,
                'active' => true,
            ],
            [
                'name' => 'Maternity Leave',
                'code' => 'maternity',
                'description' => '120 consecutive days: 30 days pre-natal, 90 days post-natal, fully paid',
                'default_days' => 120,
                'is_paid' => true,
                'requires_medical_certificate' => true,
                'active' => true,
            ],
            [
                'name' => 'Paternity Leave',
                'code' => 'paternity',
                'description' => '3 consecutive working days upon birth of child, fully paid',
                'default_days' => 3,
                'is_paid' => true,
                'requires_medical_certificate' => false,
                'active' => true,
            ],
            [
                'name' => 'Marriage Leave',
                'code' => 'marriage',
                'description' => '3 working days for employee wedding',
                'default_days' => 3,
                'is_paid' => true,
                'requires_medical_certificate' => false,
                'active' => true,
            ],
            [
                'name' => 'Bereavement Leave',
                'code' => 'bereavement',
                'description' => '3 working days for death of spouse, child, parent, or close relative',
                'default_days' => 3,
                'is_paid' => true,
                'requires_medical_certificate' => false,
                'active' => true,
            ],
            [
                'name' => 'Unpaid Leave',
                'code' => 'unpaid',
                'description' => 'Up to 5 consecutive days for exceptional family events (max 2x per year)',
                'default_days' => 5,
                'is_paid' => false,
                'requires_medical_certificate' => false,
                'active' => true,
            ],
        ];

        foreach ($leaveTypes as $type) {
            LeaveType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }

        $this->command->info('Leave types seeded: ' . count($leaveTypes) . ' types');

        // Ethiopian Public Holidays 2024/2025
        $holidays = [
            ['name' => 'Ethiopian New Year (Enkutatash)', 'date' => '2024-09-11', 'description' => 'Ethiopian New Year celebration'],
            ['name' => 'Meskel (Finding of the True Cross)', 'date' => '2024-09-27', 'description' => 'Religious holiday'],
            ['name' => 'Mawlid al-Nabi (Birth of Prophet Muhammad)', 'date' => '2024-09-16', 'description' => 'Islamic holiday'],
            ['name' => 'Ethiopian Christmas (Genna)', 'date' => '2025-01-07', 'description' => 'Orthodox Christmas'],
            ['name' => 'Timket (Epiphany)', 'date' => '2025-01-19', 'description' => 'Orthodox Epiphany'],
            ['name' => 'Adwa Victory Day', 'date' => '2025-03-02', 'description' => 'Commemorates Battle of Adwa'],
            ['name' => 'Ethiopian Good Friday (Siklet)', 'date' => '2025-04-18', 'description' => 'Orthodox Good Friday'],
            ['name' => 'Ethiopian Easter (Fasika)', 'date' => '2025-04-20', 'description' => 'Orthodox Easter'],
            ['name' => 'Labour Day', 'date' => '2025-05-01', 'description' => 'International Workers Day'],
            ['name' => 'Ethiopian Patriots Victory Day', 'date' => '2025-05-05', 'description' => 'Patriots Day'],
            ['name' => 'Derg Downfall Day', 'date' => '2025-05-28', 'description' => 'End of Derg regime'],
            ['name' => 'Eid al-Fitr', 'date' => '2025-03-31', 'description' => 'End of Ramadan'],
            ['name' => 'Eid al-Adha', 'date' => '2025-06-07', 'description' => 'Feast of Sacrifice'],
        ];

        foreach ($holidays as $holiday) {
            PublicHoliday::updateOrCreate(
                ['name' => $holiday['name'], 'date' => $holiday['date']],
                [
                    'calendar_type' => 'gregorian',
                    'recurring_yearly' => true,
                    'description' => $holiday['description'],
                ]
            );
        }

        $this->command->info('Public holidays seeded: ' . count($holidays) . ' holidays');

        // Leave Settings
        $settings = [
            ['key' => 'calendar_system', 'value' => 'gregorian', 'description' => 'Calendar system: gregorian or ethiopian'],
            ['key' => 'max_annual_leave_carry_forward', 'value' => '2', 'description' => 'Max years to carry forward unused leave'],
            ['key' => 'sick_leave_pay_month1', 'value' => '100', 'description' => 'Pay % for first month of sick leave'],
            ['key' => 'sick_leave_pay_month2_3', 'value' => '50', 'description' => 'Pay % for months 2-3 of sick leave'],
            ['key' => 'sick_leave_pay_month4_6', 'value' => '0', 'description' => 'Pay % for months 4-6 of sick leave'],
            ['key' => 'maternity_leave_days', 'value' => '120', 'description' => 'Total maternity leave days'],
            ['key' => 'paternity_leave_days', 'value' => '3', 'description' => 'Total paternity leave days'],
            ['key' => 'working_days_per_week', 'value' => '5', 'description' => 'Working days per week'],
        ];

        foreach ($settings as $setting) {
            LeaveSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Leave settings seeded: ' . count($settings) . ' settings');
        $this->command->info('✅ Leave management data seeded successfully!');
    }
}
