<template>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div><h2>{{ heading }}</h2><p class="text-muted">{{ subtitle }}</p></div>
            <select v-if="page === 'process'" v-model="stage" class="form-select w-auto"><option value="">All stages</option><option v-for="item in stages" :key="item" :value="item">{{ title(item) }}</option></select>
        </div>

        <div v-if="page === 'assessments'" class="row g-3">
            <div v-for="assessment in items" :key="assessment.id" class="col-lg-6"><div class="card h-100 shadow-sm"><div class="card-body">
                <div class="d-flex justify-content-between"><h5>{{ assessment.title }}</h5><span class="badge bg-info">{{ title(assessment.type) }}</span></div>
                <p class="mb-1"><strong>Applicant:</strong> {{ fullName(assessment.job_application.applicant_profile) }}</p>
                <p class="mb-1"><strong>Job:</strong> {{ assessment.job_application.job_posting.title }}</p>
                <p class="mb-1"><strong>Schedule:</strong> {{ date(assessment.scheduled_at) || 'To be arranged' }}</p>
                <p class="mb-2"><strong>{{ assessment.meeting_type === 'online' ? 'Online Link' : 'Venue' }}:</strong> <a v-if="assessment.external_link" :href="assessment.external_link" target="_blank">{{ assessment.external_link }}</a><span v-else>{{ assessment.location || 'To be announced' }}</span></p>
                <div class="d-flex justify-content-between align-items-center">
                    <small>{{ assessment.questions?.length || 0 }} questionnaire item(s)</small>
                    <a class="btn btn-sm btn-outline-primary" :href="applicationUrl(assessment.job_application_id)">Review Application</a>
                </div>
            </div></div></div>
            <div v-if="!items.length" class="col-12"><div class="alert alert-light border">No interviews or exams scheduled.</div></div>
        </div>

        <div v-else class="card shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr v-if="page === 'applicants'"><th>Applicant</th><th>Contact</th><th>Work Interests</th><th>Applications</th></tr><tr v-else><th>Applicant</th><th>Job</th><th>Submitted</th><th>Stage</th><th></th></tr></thead>
            <tbody>
                <template v-if="page === 'applicants'"><tr v-for="applicant in items" :key="applicant.id"><td><strong>{{ fullName(applicant) }}</strong><div class="small">{{ applicant.sex }}</div></td><td>{{ applicant.email }}<br><small>{{ applicant.contact_number }}</small></td><td><span v-for="interest in applicant.interests" :key="interest.id" class="badge text-bg-light border me-1">{{ interest.name }}</span></td><td><a v-for="application in applicant.applications" :key="application.id" :href="applicationUrl(application.id)" class="badge text-bg-primary text-decoration-none me-1">{{ application.job_posting.title }}</a></td></tr></template>
                <template v-else><tr v-for="application in filteredItems" :key="application.id"><td>{{ fullName(application.applicant_profile) }}</td><td>{{ application.job_posting.title }}</td><td>{{ date(application.submitted_at) }}</td><td><span class="badge bg-primary">{{ title(application.stage) }}</span></td><td><a class="btn btn-sm btn-primary" :href="applicationUrl(application.id)">Review</a></td></tr></template>
                <tr v-if="!(page === 'process' ? filteredItems.length : items.length)"><td :colspan="page === 'applicants' ? 4 : 5" class="text-center py-5">No records found.</td></tr>
            </tbody>
        </table></div></div>
        <nav v-if="pagination?.links?.length" class="mt-3"><a v-for="link in pagination.links" :key="link.label" :href="link.url || '#'" class="btn btn-sm me-1" :class="link.active ? 'btn-primary' : 'btn-outline-secondary'" v-html="link.label"></a></nav>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
const props = defineProps({ page: String, items: Array, pagination: Object, stages: { type: Array, default: () => [] }, applicationBaseUrl: String });
const stage = ref('');
const headings = { applicants: ['Applicants', 'Registered applicant profiles and work interests.'], process: ['Hiring Process', 'Move applicants through the recruitment pipeline.'], assessments: ['Interview / Exams', 'Scheduled interviews, exams, meeting links, and questionnaires.'] };
const heading = computed(() => headings[props.page][0]); const subtitle = computed(() => headings[props.page][1]);
const filteredItems = computed(() => stage.value ? props.items.filter(i => i.stage === stage.value) : props.items);
const title = v => String(v || '').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
const fullName = p => [p.first_name, p.middle_name, p.last_name].filter(Boolean).join(' ');
const date = value => value ? new Date(value).toLocaleString() : '';
const applicationUrl = id => `${props.applicationBaseUrl}/${id}`;
</script>
