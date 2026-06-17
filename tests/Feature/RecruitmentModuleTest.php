<?php

namespace Tests\Feature;

use App\Models\JobPosting;
use App\Models\ApplicantProfile;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\WorkInterest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecruitmentModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_public_can_view_published_job_postings(): void
    {
        JobPosting::create([
            'title' => 'Science Research Specialist',
            'salary_min' => 30000,
            'salary_max' => 45000,
            'description' => '<p>Conduct research.</p>',
            'employment_type' => 'regular',
            'work_setup' => 'hybrid',
            'status' => 'published',
        ]);

        $this->get(route('careers.jobs'))
            ->assertOk()
            ->assertSee('Science Research Specialist');
    }

    public function test_applicant_can_register_with_multiple_work_interests(): void
    {
        $interests = [
            WorkInterest::create(['name' => 'Recruitment Test Interest A'])->id,
            WorkInterest::create(['name' => 'Recruitment Test Interest B'])->id,
        ];

        $response = $this->post(route('applicant.register.store'), [
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'last_name' => 'Reyes',
            'sex' => 'Female',
            'address' => 'Quezon City',
            'contact_number' => '09171234567',
            'email' => 'recruitment-test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'interests' => $interests,
            'education' => [
                ['level' => 'elementary', 'school_name' => 'Elementary School', 'year_graduated' => 2010],
                ['level' => 'high_school', 'school_name' => 'High School', 'year_graduated' => 2014],
                ['level' => 'college', 'school_name' => 'State University', 'course' => 'BS Biology', 'year_graduated' => 2018],
            ],
        ]);

        $response->assertRedirect(route('applicant.dashboard'));
        $this->assertDatabaseHas('applicant_profiles', ['email' => 'recruitment-test@example.com']);
        $profileId = ApplicantProfile::where('email', 'recruitment-test@example.com')->value('id');
        $this->assertSame(2, DB::table('applicant_work_interest')->where('applicant_profile_id', $profileId)->count());
    }

    public function test_authorized_hr_user_can_open_recruitment_admin_pages(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'hr.recruitment.view', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'recruitment_test_hr', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('recruitment.jobs'))
            ->assertOk()
            ->assertSee('Job Posting');
    }

    public function test_hiring_an_applicant_creates_an_hris_employee_profile(): void
    {
        $manage = Permission::firstOrCreate(['name' => 'hr.recruitment.manage', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'recruitment_hiring_test_hr', 'guard_name' => 'web']);
        $role->givePermissionTo($manage);
        $admin = User::factory()->create();
        $admin->assignRole($role);

        $applicant = User::factory()->create();
        $profile = ApplicantProfile::create([
            'user_id' => $applicant->id,
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'sex' => 'Male',
            'address' => 'Manila',
            'contact_number' => '09170000000',
            'email' => $applicant->email,
        ]);
        $job = JobPosting::create([
            'title' => 'Project Assistant',
            'salary_min' => 25000,
            'salary_max' => 30000,
            'description' => '<p>Assist the project.</p>',
            'employment_type' => 'contractual',
            'work_setup' => 'onsite',
            'status' => 'published',
        ]);
        $application = JobApplication::create([
            'job_posting_id' => $job->id,
            'applicant_profile_id' => $profile->id,
            'resume_path' => 'recruitment/resumes/test.pdf',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('recruitment.applications.hire', $application))
            ->assertRedirect();

        $employeeNo = 'REC-'.now()->format('Y').'-'.str_pad((string) $profile->id, 5, '0', STR_PAD_LEFT);
        $this->assertDatabaseHas('employee_information', [
            'employee_no' => $employeeNo,
            'user_id' => $applicant->id,
            'account_status' => 'active',
        ]);
        $this->assertDatabaseHas('employee_personal', [
            'employee_no' => $employeeNo,
            'firstname' => 'Juan',
            'lastname' => 'Dela Cruz',
        ]);
        $this->assertDatabaseHas('job_applications', ['id' => $application->id, 'stage' => 'hired']);
    }
}
