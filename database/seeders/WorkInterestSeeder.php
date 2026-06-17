<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkInterestSeeder extends Seeder
{
    public function run(): void
    {
        $interests = [
            'Accounting and Finance', 'Administration', 'Agriculture', 'Architecture', 'Arts and Design',
            'Audit and Compliance', 'Communications', 'Community Development', 'Customer Service',
            'Data Analytics', 'Economics', 'Education and Training', 'Engineering', 'Environmental Science',
            'Executive and Management', 'Facilities and Maintenance', 'Geographic Information Systems',
            'Healthcare', 'Human Resources', 'Information Technology', 'Legal Services', 'Logistics',
            'Marketing', 'Media and Public Relations', 'Procurement', 'Project Management', 'Research and Development',
            'Sales', 'Science and Laboratory', 'Security', 'Skilled Trades', 'Social Services',
            'Statistics', 'Supply Chain', 'Technical Support', 'Tourism and Hospitality', 'Writing and Editing',
        ];

        foreach ($interests as $interest) {
            DB::table('work_interests')->updateOrInsert(
                ['name' => $interest],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
