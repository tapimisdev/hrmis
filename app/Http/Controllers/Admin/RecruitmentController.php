<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicantProfile;
use App\Models\ApplicationRequirement;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\JobPosting;
use App\Models\RecruitmentAssessment;
use App\Models\RecruitmentAssessmentQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use TCPDF;

class RecruitmentController extends Controller
{
    private const STAGES = [
        'initial_screening', 'interview_exams', 'finalist', 'job_offer',
        'offer_confirmation', 'requirements', 'onboarding', 'hired', 'rejected',
    ];

    public function __construct()
    {
        $this->middleware('permission:hr.recruitment.view')->only([
            'jobs', 'applicants', 'process', 'showApplication', 'assessments',
        ]);
        $this->middleware('permission:hr.recruitment.manage')->except([
            'jobs', 'applicants', 'process', 'showApplication', 'assessments',
        ]);
    }

    public function jobs(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $query = JobPosting::withCount('applications')->latest();

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('employment_type', 'like', "%{$search}%")
                    ->orWhere('work_setup', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $jobs = $query->paginate(12)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'title' => 'Job Posting',
                'jobs' => $jobs,
            ]);
        }

        return view('admin.pages.recruitment.jobs', [
            'jobs' => $jobs,
            'search' => $search,
        ]);
    }

    public function storeJob(Request $request)
    {
        $data = $this->validateJob($request);
        unset($data['banner'], $data['attachments']);

        $data['created_by'] = Auth::id();
        $data['banner_path'] = $request->file('banner')?->store('recruitment/job-banners', 'public');
        $data['attachments'] = $this->storeFiles($request->file('attachments', []), 'recruitment/job-attachments');
        $data['status'] = 'published';
        $job = JobPosting::create($data)->loadCount('applications');

        return $request->expectsJson()
            ? response()->json(['message' => 'Job posting created.', 'job' => $job], 201)
            : back()->with('success', 'Job posting created.');
    }

    public function updateJob(Request $request, JobPosting $job)
    {
        $data = $this->validateJob($request);
        unset($data['banner'], $data['attachments']);

        if ($request->hasFile('banner')) {
            Storage::disk('public')->delete($job->banner_path);
            $data['banner_path'] = $request->file('banner')->store('recruitment/job-banners', 'public');
        }
        if ($request->hasFile('attachments')) {
            $data['attachments'] = array_merge(
                $job->attachments ?? [],
                $this->storeFiles($request->file('attachments'), 'recruitment/job-attachments')
            );
        }
        $data['status'] = $request->boolean('publish') ? 'published' : $request->input('status', $job->status);
        $job->update($data);

        return $request->expectsJson()
            ? response()->json(['message' => 'Job posting updated.', 'job' => $job->fresh()->loadCount('applications')])
            : back()->with('success', 'Job posting updated.');
    }

    public function destroyJob(JobPosting $job)
    {
        $job->delete();
        return request()->expectsJson()
            ? response()->json(['message' => 'Job posting archived.', 'id' => $job->id])
            : back()->with('success', 'Job posting archived.');
    }

    public function applicants()
    {
        return view('admin.pages.recruitment.applicants', [
            'applicants' => ApplicantProfile::with(['interests', 'applications.jobPosting'])->latest()->paginate(20),
        ]);
    }

    public function process(Request $request)
    {
        $query = JobApplication::with(['jobPosting', 'applicantProfile'])->latest('submitted_at');
        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }
        if ($request->filled('job')) {
            $query->where('job_posting_id', $request->integer('job'));
        }

        return view('admin.pages.recruitment.process', [
            'applications' => $query->paginate(20)->withQueryString(),
            'stages' => self::STAGES,
            'selectedJob' => $request->filled('job')
                ? JobPosting::find($request->integer('job'))
                : null,
        ]);
    }

    public function assessments()
    {
        return view('admin.pages.recruitment.assessments', [
            'assessments' => RecruitmentAssessment::with('questions')
                ->with(['jobApplication.applicantProfile', 'jobApplication.jobPosting'])
                ->latest('scheduled_at')->paginate(20),
        ]);
    }

    public function showApplication(JobApplication $application)
    {
        $application->load([
            'jobPosting', 'applicantProfile.interests', 'applicantProfile.education',
            'applicantProfile.workExperiences', 'applicantProfile.certificates',
            'assessments.questions', 'offer', 'requirements',
        ]);

        return view('admin.pages.recruitment.application', [
            'application' => $application,
            'stages' => self::STAGES,
        ]);
    }

    public function updateStage(Request $request, JobApplication $application)
    {
        $data = $request->validate([
            'stage' => ['required', Rule::in(self::STAGES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($application, $data) {
            $application->update(['stage' => $data['stage'], 'admin_notes' => $data['notes'] ?? $application->admin_notes]);
            DB::table('application_stage_histories')->insert([
                'job_application_id' => $application->id,
                'stage' => $data['stage'],
                'notes' => $data['notes'] ?? null,
                'changed_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($data['stage'] === 'requirements' && !$application->requirements()->exists()) {
                foreach ($this->defaultRequirements() as $type => $label) {
                    $application->requirements()->create(['requirement_type' => $type, 'label' => $label]);
                }
            }
        });

        $application->refresh()->load(['requirements', 'assessments.questions', 'offer']);

        return $request->expectsJson()
            ? response()->json(['message' => 'Hiring stage updated.', 'application' => $application])
            : back()->with('success', 'Hiring stage updated.');
    }

    public function storeAssessment(Request $request, JobApplication $application)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['interview', 'exam'])],
            'title' => ['required', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'meeting_type' => ['required', Rule::in(['online', 'physical'])],
            'external_link' => ['nullable', 'url', 'required_if:meeting_type,online'],
            'location' => ['nullable', 'string', 'required_if:meeting_type,physical'],
            'instructions' => ['nullable', 'string'],
            'questions' => ['nullable', 'array'],
            'questions.*.question' => ['required_with:questions', 'string'],
            'questions.*.answer_type' => ['required_with:questions', Rule::in(['text', 'long_text', 'choice', 'file'])],
            'questions.*.options' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($application, $data) {
            $questions = $data['questions'] ?? [];
            unset($data['questions']);
            $assessment = $application->assessments()->create($data);
            foreach ($questions as $index => $question) {
                $assessment->questions()->create([
                    'question' => $question['question'],
                    'answer_type' => $question['answer_type'],
                    'options' => $question['answer_type'] === 'choice'
                        ? array_values(array_filter(array_map('trim', explode(',', $question['options'] ?? ''))))
                        : null,
                    'sort_order' => $index,
                ]);
            }
            $application->update(['stage' => 'interview_exams']);
        });

        $application->refresh()->load(['assessments.questions']);

        return $request->expectsJson()
            ? response()->json(['message' => 'Interview or exam scheduled.', 'application' => $application])
            : back()->with('success', 'Interview or exam scheduled.');
    }

    public function prepareOffer(Request $request, JobApplication $application)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $profile = $application->applicantProfile;
        $pdf = new TCPDF();
        $pdf->SetCreator('DOST HRIS');
        $pdf->SetTitle($data['subject']);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 11);
        $pdf->writeHTML('<h2>'.e($data['subject']).'</h2><p>Dear '.e($profile->first_name).',</p>'.$data['body']);
        $path = 'recruitment/job-offers/offer-'.$application->id.'-'.now()->format('YmdHis').'.pdf';
        Storage::disk('public')->put($path, $pdf->Output('offer.pdf', 'S'));

        JobOffer::updateOrCreate(
            ['job_application_id' => $application->id],
            ['subject' => $data['subject'], 'body' => $data['body'], 'pdf_path' => $path]
        );
        $application->update(['stage' => 'job_offer']);

        $application->refresh()->load('offer');

        return $request->expectsJson()
            ? response()->json(['message' => 'Job offer PDF generated for review.', 'application' => $application])
            : back()->with('success', 'Job offer PDF generated for review.');
    }

    public function sendOffer(Request $request, JobApplication $application)
    {
        $offer = $application->offer()->firstOrFail();
        $request->validate(['email_body' => ['nullable', 'string']]);
        $body = $request->input('email_body', strip_tags($offer->body));
        $absolutePath = Storage::disk('public')->path($offer->pdf_path);

        Mail::raw($body, function ($message) use ($application, $offer, $absolutePath) {
            $message->to($application->applicantProfile->email)
                ->subject($offer->subject)
                ->attach($absolutePath);
        });

        $offer->update(['sent_at' => now()]);
        $application->update(['stage' => 'offer_confirmation']);

        $application->refresh()->load('offer');

        return $request->expectsJson()
            ? response()->json(['message' => 'Job offer sent to the applicant.', 'application' => $application])
            : back()->with('success', 'Job offer sent to the applicant.');
    }

    public function verifyRequirement(Request $request, ApplicationRequirement $requirement)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['verified', 'rejected'])]]);
        $requirement->update($data);
        return $request->expectsJson()
            ? response()->json(['message' => 'Requirement status updated.', 'requirement' => $requirement->fresh()])
            : back()->with('success', 'Requirement status updated.');
    }

    public function hire(JobApplication $application)
    {
        DB::transaction(function () use ($application) {
            $profile = $application->applicantProfile()->lockForUpdate()->first();
            if ($profile->hired_at) {
                return;
            }

            $employeeNo = 'REC-'.now()->format('Y').'-'.str_pad((string) $profile->id, 5, '0', STR_PAD_LEFT);
            DB::table('employee_information')->insert([
                'employee_no' => $employeeNo,
                'user_id' => $profile->user_id,
                'account_status' => 'active',
                'date_hired_organization' => now()->toDateString(),
                'date_hired_company' => now()->toDateString(),
                'isDeleted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('employee_personal')->insert([
                'employee_no' => $employeeNo,
                'profile' => $profile->profile_image,
                'firstname' => $profile->first_name,
                'middlename' => $profile->middle_name,
                'lastname' => $profile->last_name,
                'sex' => $profile->sex,
                'present_street' => $profile->address,
                'mobile_number' => $profile->contact_number,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($profile->education as $education) {
                DB::table('employee_education')->insert([
                    'employee_no' => $employeeNo,
                    'level' => $education->level,
                    'school_name' => $education->school_name,
                    'course' => $education->course,
                    'year_graduated' => $education->year_graduated,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            foreach ($profile->workExperiences as $experience) {
                DB::table('employee_work_experience')->insert([
                    'employee_no' => $employeeNo,
                    'from_year' => $experience->year_started,
                    'to_year' => $experience->year_ended,
                    'position' => $experience->position,
                    'department' => $experience->company_name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $user = $profile->user;
            $user->syncRoles([Role::firstOrCreate(['name' => 'emp_contractual', 'guard_name' => 'web'])]);
            $profile->update(['hired_at' => now(), 'close_account_at' => now()->addMonth()]);
            $application->update(['stage' => 'hired']);
        });

        return request()->expectsJson()
            ? response()->json(['message' => 'Applicant hired and HRIS employee profile created.', 'application' => $application->fresh()])
            : back()->with('success', 'Applicant hired and HRIS employee profile created.');
    }

    private function validateJob(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'salary_min' => ['required', 'numeric', 'min:0'],
            'salary_max' => ['required', 'numeric', 'gte:salary_min'],
            'description' => ['required', 'string'],
            'employment_type' => ['required', Rule::in(['regular', 'part_time', 'contractual', 'job_order', 'project_based'])],
            'work_setup' => ['required', Rule::in(['onsite', 'hybrid', 'work_from_home'])],
            'banner' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:10240'],
            'scheduled_at' => ['nullable', 'date'],
            'posted_until' => ['nullable', 'date', 'after_or_equal:scheduled_at'],
            'applicants_needed' => ['nullable', 'integer', 'min:1'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:png,jpg,jpeg,pdf', 'max:10240'],
        ]);
    }

    private function storeFiles(array $files, string $directory): array
    {
        return collect($files)->map(fn ($file) => [
            'name' => $file->getClientOriginalName(),
            'path' => $file->store($directory, 'public'),
        ])->values()->all();
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
