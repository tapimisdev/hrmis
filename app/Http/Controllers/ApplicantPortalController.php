<?php

namespace App\Http\Controllers;

use App\Models\ApplicantProfile;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use App\Models\WorkInterest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class ApplicantPortalController extends Controller
{
    public function jobs()
    {
        $jobs = JobPosting::where('status', 'published')
            ->where(fn ($query) => $query->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('posted_until')->orWhere('posted_until', '>=', now()))
            ->latest()->paginate(12);

        return view('applicant.jobs', compact('jobs'));
    }

    public function register()
    {
        return view('applicant.register', ['interests' => WorkInterest::orderBy('name')->get()]);
    }

    public function storeRegistration(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'sex' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'profile_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:5120'],
            'contact_number' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:applicant_profiles,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'interests' => ['required', 'array', 'min:1', 'max:12'],
            'interests.*' => ['integer', 'exists:work_interests,id'],
            'education' => ['required', 'array', 'min:3'],
            'education.*.level' => ['required', 'in:elementary,high_school,college'],
            'education.*.school_name' => ['required', 'string', 'max:255'],
            'education.*.course' => ['nullable', 'string', 'max:255'],
            'education.*.year_graduated' => ['nullable', 'integer', 'digits:4'],
            'work_experiences' => ['nullable', 'array'],
            'work_experiences.*.company_name' => ['required', 'string', 'max:255'],
            'work_experiences.*.year_started' => ['required', 'integer', 'digits:4'],
            'work_experiences.*.year_ended' => ['nullable', 'integer', 'digits:4'],
            'work_experiences.*.position' => ['required', 'string', 'max:255'],
            'certificates' => ['nullable', 'array'],
            'certificates.*.name' => ['required', 'string', 'max:255'],
            'certificates.*.issuer' => ['nullable', 'string', 'max:255'],
            'certificates.*.issued_at' => ['nullable', 'date'],
            'certificates.*.file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,pdf', 'max:10240'],
        ]);

        $user = DB::transaction(function () use ($request, $data) {
            $user = User::create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
            $user->assignRole(Role::firstOrCreate(['name' => 'applicant', 'guard_name' => 'web']));

            $profile = ApplicantProfile::create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'sex' => $data['sex'],
                'address' => $data['address'],
                'profile_image' => $request->file('profile_image')?->store('recruitment/applicant-profiles', 'public'),
                'contact_number' => $data['contact_number'],
                'email' => $data['email'],
            ]);
            $profile->interests()->sync($data['interests']);
            foreach ($data['education'] as $education) {
                $profile->education()->create($education);
            }
            foreach ($data['work_experiences'] ?? [] as $experience) {
                $profile->workExperiences()->create($experience);
            }
            foreach ($data['certificates'] ?? [] as $index => $certificate) {
                unset($certificate['file']);
                $certificate['file_path'] = $request->file("certificates.$index.file")
                    ?->store('recruitment/certificates', 'public');
                $profile->certificates()->create($certificate);
            }

            return $user;
        });

        Auth::login($user);
        return $request->expectsJson()
            ? response()->json(['message' => 'Applicant account created.', 'redirect' => route('applicant.dashboard')], 201)
            : redirect()->route('applicant.dashboard')->with('success', 'Applicant account created.');
    }

    public function dashboard()
    {
        $profile = $this->profile();
        return view('applicant.dashboard', [
            'profile' => $profile->load([
                'applications.jobPosting',
                'applications.offer',
                'applications.requirements',
                'applications.assessments.questions',
            ]),
        ]);
    }

    public function apply(Request $request, JobPosting $job)
    {
        abort_unless($job->status === 'published', 404);
        $data = $request->validate([
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $profile = $this->profile();
        if ($profile->applications()->where('job_posting_id', $job->id)->exists()) {
            return $request->expectsJson()
                ? response()->json(['errors' => ['resume' => ['You have already applied for this job posting.']]], 422)
                : back()->withErrors(['resume' => 'You have already applied for this job posting.']);
        }

        $application = JobApplication::create([
            'job_posting_id' => $job->id,
            'applicant_profile_id' => $profile->id,
            'resume_path' => $request->file('resume')->store('recruitment/resumes', 'public'),
            'cv_path' => $request->file('cv')?->store('recruitment/cvs', 'public'),
            'submitted_at' => now(),
        ]);
        DB::table('application_stage_histories')->insert([
            'job_application_id' => $application->id,
            'stage' => 'initial_screening',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $request->expectsJson()
            ? response()->json(['message' => 'Application submitted.', 'application' => $application], 201)
            : back()->with('success', 'Application submitted.');
    }

    public function uploadSignedOffer(Request $request, JobApplication $application)
    {
        $this->authorizeApplication($application);
        $request->validate(['signed_offer' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240']]);
        $path = $request->file('signed_offer')->store('recruitment/signed-offers', 'public');
        $application->offer()->update(['signed_copy_path' => $path, 'confirmed_at' => now()]);
        $application->update(['stage' => 'requirements']);
        if (!$application->requirements()->exists()) {
            foreach ($this->defaultRequirements() as $type => $label) {
                $application->requirements()->create(['requirement_type' => $type, 'label' => $label]);
            }
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Signed job offer submitted.', 'application' => $application->fresh()->load(['offer', 'requirements'])])
            : back()->with('success', 'Signed job offer submitted.');
    }

    public function uploadRequirement(Request $request, JobApplication $application, $requirement)
    {
        $this->authorizeApplication($application);
        $record = $application->requirements()->findOrFail($requirement);
        $request->validate([
            'file' => ['nullable', 'required_without:value', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
            'value' => ['nullable', 'required_without:file', 'string', 'max:255'],
        ]);
        if ($request->hasFile('file')) {
            Storage::disk('public')->delete($record->file_path);
        }
        $record->update([
            'file_path' => $request->file('file')?->store('recruitment/requirements', 'public') ?? $record->file_path,
            'value' => $request->input('value'),
            'status' => 'submitted',
        ]);

        return $request->expectsJson()
            ? response()->json(['message' => 'Requirement submitted.', 'requirement' => $record->fresh()])
            : back()->with('success', 'Requirement submitted.');
    }

    private function profile(): ApplicantProfile
    {
        return ApplicantProfile::where('user_id', Auth::id())->firstOrFail();
    }

    private function authorizeApplication(JobApplication $application): void
    {
        abort_unless($application->applicantProfile->user_id === Auth::id(), 403);
    }

    private function defaultRequirements(): array
    {
        return [
            'medical_clearance' => 'Medical Clearance',
            'valid_ids' => 'Valid IDs',
            'nbi_police_clearance' => 'NBI or Police Clearance',
            'sss' => 'SSS Number',
            'philhealth' => 'PhilHealth Number',
            'pagibig' => 'Pag-IBIG Number',
            'tin' => 'TIN',
            'bank_account' => 'Bank Account Details',
            'other' => 'Other Company or Agency Requirements',
        ];
    }
}
