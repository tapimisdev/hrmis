<?php

namespace Database\Seeders;

use Database\Factories\TestUserFactory;
use Illuminate\Database\Seeder;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TestUserFactory::new()
            ->count(50)
            ->withEmployeeData()
            ->create()
            ->each(fn ($user) => $user->assignRole('employee'));
    }
}
