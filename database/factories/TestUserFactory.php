<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestUserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $firstname = $this->faker->firstName();
        $lastname  = $this->faker->lastName();

        return [
            'name'           => "$firstname $lastname",
            'email'          => $this->faker->unique()->safeEmail(),
            'password'       => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function withEmployeeData(): static
    {
        return $this->afterCreating(function (User $user) {
            $employeeNo = fake()->unique()->bothify('emp###');
            
            $fullName = $user->getRawOriginal('name'); 

            $parts = explode(' ', $fullName, 2);
            $firstname = $parts[0];
            $lastname  = $parts[1] ?? '';

            DB::table('employee_information')->insert([
                'user_id'        => $user->id,
                'employee_no'    => $employeeNo,
                'biometrics_id'  => fake()->randomNumber(5, true),
                'account_status' => 'active',
                'date_hired'     => now()->format('Y-m-d'),
                'salary_method'  => fake()->randomElement(['cash', 'bank']),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            DB::table('employee_personal')->insert([
                'employee_no' => $employeeNo,
                'firstname'   => $firstname,
                'lastname'    => $lastname,  
                'age'         => fake()->numberBetween(20, 50),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::table('employee_organization')->insert([
                'employee_no'        => $employeeNo,
                'division_id'        => 5,
                'unit_id'            => fake()->numberBetween(1, 4),
                'employment_type_id' => fake()->numberBetween(1, 2),
                'position_id'        => fake()->numberBetween(1, 5),
                'effectivity_date'   => now(),
            ]);

            DB::table('employee_shift_work_schedule')->insert([
                'employee_no'      => $employeeNo,
                'shift_id'         => fake()->numberBetween(1, 2),
                'work_schedule_id' => fake()->numberBetween(1, 2),
                'effectivity_date' => now(),
            ]);

            DB::table('employee_salary')->insert([
                'employee_no'      => $employeeNo,
                'amount'           => 0,
                'effectivity_date' => now(),
            ]);
        });
    }
}
