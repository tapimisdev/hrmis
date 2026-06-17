<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_interests', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('salary_min', 12, 2);
            $table->decimal('salary_max', 12, 2);
            $table->longText('description');
            $table->enum('employment_type', ['regular', 'part_time', 'contractual', 'job_order', 'project_based']);
            $table->enum('work_setup', ['onsite', 'hybrid', 'work_from_home']);
            $table->string('banner_path')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('posted_until')->nullable();
            $table->unsignedInteger('applicants_needed')->nullable();
            $table->json('attachments')->nullable();
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('applicant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('sex');
            $table->text('address');
            $table->string('profile_image')->nullable();
            $table->string('contact_number');
            $table->string('email')->unique();
            $table->dateTime('hired_at')->nullable();
            $table->dateTime('close_account_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('applicant_work_interest', function (Blueprint $table) {
            $table->foreignId('applicant_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_interest_id')->constrained()->cascadeOnDelete();
            $table->primary(['applicant_profile_id', 'work_interest_id']);
        });

        Schema::create('applicant_education', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_profile_id')->constrained()->cascadeOnDelete();
            $table->enum('level', ['elementary', 'high_school', 'college']);
            $table->string('school_name');
            $table->string('course')->nullable();
            $table->year('year_graduated')->nullable();
            $table->timestamps();
        });

        Schema::create('applicant_work_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_profile_id')->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->year('year_started');
            $table->year('year_ended')->nullable();
            $table->string('position');
            $table->timestamps();
        });

        Schema::create('applicant_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_profile_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('issuer')->nullable();
            $table->date('issued_at')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('applicant_profile_id')->constrained()->cascadeOnDelete();
            $table->string('resume_path');
            $table->string('cv_path')->nullable();
            $table->enum('stage', [
                'initial_screening', 'interview_exams', 'finalist', 'job_offer',
                'offer_confirmation', 'requirements', 'onboarding', 'hired', 'rejected',
            ])->default('initial_screening');
            $table->text('admin_notes')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['job_posting_id', 'applicant_profile_id']);
        });

        Schema::create('application_stage_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->string('stage');
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('recruitment_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['interview', 'exam']);
            $table->string('title');
            $table->dateTime('scheduled_at')->nullable();
            $table->enum('meeting_type', ['online', 'physical'])->default('physical');
            $table->string('external_link')->nullable();
            $table->text('location')->nullable();
            $table->text('instructions')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
        });

        Schema::create('recruitment_assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recruitment_assessment_id');
            $table->foreign('recruitment_assessment_id', 'assessment_questions_assessment_fk')
                ->references('id')->on('recruitment_assessments')->cascadeOnDelete();
            $table->text('question');
            $table->enum('answer_type', ['text', 'long_text', 'choice', 'file'])->default('text');
            $table->json('options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('job_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->longText('body');
            $table->string('pdf_path')->nullable();
            $table->string('signed_copy_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('application_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->string('requirement_type');
            $table->string('label');
            $table->string('file_path')->nullable();
            $table->string('value')->nullable();
            $table->enum('status', ['pending', 'submitted', 'verified', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_requirements');
        Schema::dropIfExists('job_offers');
        Schema::dropIfExists('recruitment_assessment_questions');
        Schema::dropIfExists('recruitment_assessments');
        Schema::dropIfExists('application_stage_histories');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('applicant_certificates');
        Schema::dropIfExists('applicant_work_experiences');
        Schema::dropIfExists('applicant_education');
        Schema::dropIfExists('applicant_work_interest');
        Schema::dropIfExists('applicant_profiles');
        Schema::dropIfExists('job_postings');
        Schema::dropIfExists('work_interests');
    }
};
