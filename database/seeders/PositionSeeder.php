<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'COS' => [
                ['code' => 'PL I', 'name' => 'Project Laborer I'],
                ['code' => 'PUA I', 'name' => 'Project Utility Aide I'],
                ['code' => 'PUW I', 'name' => 'Project Utility Worker I'],
                ['code' => 'PUA II', 'name' => 'Project Utility Aide II'],
                ['code' => 'PL II', 'name' => 'Project Laborer II'],
                ['code' => 'PA I', 'name' => 'Project Administrative I'],
                ['code' => 'PTA I', 'name' => 'Project Technical Aide I'],
                ['code' => 'PA II', 'name' => 'Project Administrative Aide II'],
                ['code' => 'PTA II', 'name' => 'Project Technical Aide II'],
                ['code' => 'PTA III', 'name' => 'Project Technical Aide III'],
                ['code' => 'PA III', 'name' => 'Project Administrative Aide III'],
                ['code' => 'PTA IV', 'name' => 'Project Technical Aide IV'],
                ['code' => 'PA IV', 'name' => 'Project Administrative Aide IV'],
                ['code' => 'PTA V', 'name' => 'Project Technical Aide V'],
                ['code' => 'PA V', 'name' => 'Project Administrative Aide V'],
                ['code' => 'PTA VI', 'name' => 'Project Technical Aide VI'],
                ['code' => 'PA VI', 'name' => 'Project Administrative Aide VI'],
                ['code' => 'PTAS I', 'name' => 'Project Technical Assistant I'],
                ['code' => 'PAAS I', 'name' => 'Project Administrative Assistant I'],
                ['code' => 'PTAS II', 'name' => 'Project Technical Assistant II'],
                ['code' => 'PAAS II', 'name' => 'Project Administrative Assistant II'],
                ['code' => 'PTAS III', 'name' => 'Project Technical Assistant III'],
                ['code' => 'PAAS III', 'name' => 'Project Administrative Assistant III'],
                ['code' => 'PTAS IV', 'name' => 'Project Technical Assistant IV'],
                ['code' => 'PAAS IV', 'name' => 'Project Administrative Assistant IV'],
                ['code' => 'PTAS V', 'name' => 'Project Technical Assistant V'],
                ['code' => 'PAAS V', 'name' => 'Project Administrative Assistant V'],
                ['code' => 'PTAS VI', 'name' => 'Project Technical Assistant VI'],
                ['code' => 'PTS I', 'name' => 'Project Technical Specialist I'],
                ['code' => 'PAO I', 'name' => 'Project Administrative Officer I'],
                ['code' => 'PTO I', 'name' => 'Project Technical Officer I'],
                ['code' => 'PTS II', 'name' => 'Project Technical Specialist II'],
                ['code' => 'PAO II', 'name' => 'Project Administrative Officer II'],
                ['code' => 'PTO II', 'name' => 'Project Technical Officer II'],
                ['code' => 'PTS III', 'name' => 'Project Technical Specialist III'],
                ['code' => 'PAO III', 'name' => 'Project Administrative Officer III'],
                ['code' => 'PTO III', 'name' => 'Project Technical Officer III'],
                ['code' => 'PTS IV', 'name' => 'Project Technical Specialist IV'],
                ['code' => 'PAO IV', 'name' => 'Project Administrative Officer IV'],
                ['code' => 'PTO IV', 'name' => 'Project Technical Officer IV'],
                ['code' => 'PTS V', 'name' => 'Project Technical Specialist V'],
                ['code' => 'PAO V', 'name' => 'Project Administrative Officer V'],
                ['code' => 'PTO V', 'name' => 'Project Technical Officer V'],
                ['code' => 'PTS VI', 'name' => 'Project Technical Specialist VI'],
                ['code' => 'PTO VI', 'name' => 'Project Technical Officer VI'],
                ['code' => 'PAO VI', 'name' => 'Project Administrative Officer VI'],
                ['code' => 'PSTS', 'name' => 'Project Senior Technical Specialist'],
                ['code' => 'PSAO I', 'name' => 'Project Senior Administrative Officer I'],
                ['code' => 'PSTO I', 'name' => 'Project Senior Technical Officer I'],
                ['code' => 'SPTS', 'name' => 'Project Supervising Technical Specialist'],
                ['code' => 'SPTO', 'name' => 'Project Supervising Technical Officer'],
                ['code' => 'SPAO', 'name' => 'Project Supervising Administrative Officer'],
                ['code' => 'STF I', 'name' => 'S&T Fellow I'],
                ['code' => 'PCTS', 'name' => 'Project Chief Technical Specialist'],
                ['code' => 'PCTO', 'name' => 'Project Chief Technical Officer'],
                ['code' => 'PCAO', 'name' => 'Project Chief Administrative Officer'],
                ['code' => 'STF II', 'name' => 'S&T Fellow II'],
                ['code' => 'STF III', 'name' => 'S&T Fellow III'],
                ['code' => 'STF IV', 'name' => 'S&T Fellow IV'],
                ['code' => 'STF V', 'name' => 'S&T Fellow V'],
            ]
        ];

        foreach ($data as $division => $positions) {
            $employmentType = DB::table('employment_types')->where('code', $division)->first();

            if (!$employmentType) {
                $this->command->warn("Employment type with code '$division' not found!");
                continue;
            }

            $employment_type_id = $employmentType->id;

            foreach ($positions as $position) {
                DB::table('positions')->updateOrInsert(
                    ['code' => $position['code']], 
                    [
                        'name' => $position['name'],
                        'employment_type_id' => $employment_type_id,
                    ]
                );
            }
        }
    }
}
