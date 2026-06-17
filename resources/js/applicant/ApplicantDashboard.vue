<template>
    <main class="dashboard-page">
        <section class="dashboard-hero">
            <div class="identity-card">
                <div class="avatar">{{ initials }}</div>
                <div>
                    <span class="eyebrow">Applicant dashboard</span>
                    <h1>Welcome, {{ profile.first_name }}</h1>
                    <p>Track your applications, interviews, exams, job offers, and onboarding requirements.</p>
                </div>
            </div>
            <a :href="jobsUrl" class="btn btn-primary">Browse Jobs</a>
        </section>

        <div v-if="message" class="alert alert-success dashboard-alert">{{ message }}</div>

        <section v-if="profile.applications.length" class="dashboard-grid">
            <aside class="application-list" aria-label="Applications">
                <div class="list-heading">
                    <span>Applications</span>
                    <strong>{{ profile.applications.length }}</strong>
                </div>
                <button
                    v-for="application in profile.applications"
                    :key="application.id"
                    type="button"
                    class="application-tab"
                    :class="{ active: selectedApplication?.id === application.id }"
                    @click="selectedApplicationId = application.id"
                >
                    <span>{{ application.job_posting.title }}</span>
                    <small>{{ title(application.stage) }}</small>
                </button>
            </aside>

            <article v-if="selectedApplication" class="application-panel">
                <header class="application-header">
                    <div>
                        <span class="eyebrow">Current application</span>
                        <h2>{{ selectedApplication.job_posting.title }}</h2>
                    </div>
                    <span class="status-pill">{{ title(selectedApplication.stage) }}</span>
                </header>

                <div class="stage-track">
                    <div
                        v-for="(stage, index) in stages"
                        :key="stage.key"
                        class="stage-step"
                        :class="{ done: index <= stageIndex(selectedApplication.stage) }"
                    >
                        <span>{{ index + 1 }}</span>
                        <small>{{ stage.label }}</small>
                    </div>
                </div>

                <section v-if="selectedApplication.assessments?.length" class="content-block">
                    <div class="block-heading">
                        <h3>Interview / Exam</h3>
                        <span>{{ selectedApplication.assessments.length }} scheduled</span>
                    </div>
                    <div class="assessment-grid">
                        <article
                            v-for="assessment in selectedApplication.assessments"
                            :key="assessment.id"
                            class="assessment-card"
                        >
                            <div class="assessment-top">
                                <div>
                                    <strong>{{ assessment.title }}</strong>
                                    <p>{{ date(assessment.scheduled_at) || "To be arranged" }}</p>
                                </div>
                                <div class="badge-stack">
                                    <span>{{ title(assessment.type) }}</span>
                                    <span>{{ title(assessment.meeting_type) }}</span>
                                </div>
                            </div>
                            <a
                                v-if="assessment.external_link"
                                :href="assessment.external_link"
                                target="_blank"
                                class="btn btn-outline-primary btn-sm"
                            >
                                Open Meeting / Exam Link
                            </a>
                            <p v-else class="venue">{{ assessment.location || "Venue to be announced" }}</p>
                            <p v-if="assessment.instructions" class="instructions">{{ assessment.instructions }}</p>
                            <div v-if="assessment.questions?.length" class="questionnaire">
                                <strong>Questionnaire</strong>
                                <ol>
                                    <li v-for="question in assessment.questions" :key="question.id">
                                        <span>{{ question.question }}</span>
                                        <small>
                                            {{ title(question.answer_type) }}
                                            <template v-if="question.options?.length">
                                                - Options: {{ question.options.join(", ") }}
                                            </template>
                                        </small>
                                    </li>
                                </ol>
                            </div>
                        </article>
                    </div>
                </section>

                <section v-if="['onboarding', 'hired'].includes(selectedApplication.stage)" class="success-block">
                    <h3>Congratulations!</h3>
                    <p>Welcome to the organization. HR will contact you with next steps.</p>
                </section>

                <section v-if="selectedApplication.offer?.pdf_path" class="content-block">
                    <div class="block-heading">
                        <h3>Job Offer</h3>
                        <span v-if="selectedApplication.offer.confirmed_at">Signed copy submitted</span>
                    </div>
                    <div class="offer-row">
                        <a target="_blank" :href="storage(selectedApplication.offer.pdf_path)" class="btn btn-outline-primary">Review Job Offer</a>
                        <form v-if="!selectedApplication.offer.confirmed_at" class="offer-form" @submit.prevent="sign(selectedApplication)">
                            <input type="file" class="form-control" @change="signedFiles[selectedApplication.id] = $event.target.files[0]">
                            <button class="btn btn-success">Submit Signed Copy</button>
                        </form>
                    </div>
                </section>

                <section v-if="selectedApplication.requirements?.length" class="content-block">
                    <div class="block-heading">
                        <h3>Submission of Requirements</h3>
                        <span>{{ selectedApplication.requirements.length }} item(s)</span>
                    </div>
                    <div class="requirements-grid">
                        <article v-for="requirement in selectedApplication.requirements" :key="requirement.id" class="requirement-card">
                            <div class="requirement-title">
                                <strong>{{ requirement.label }}</strong>
                                <span>{{ title(requirement.status) }}</span>
                            </div>
                            <input
                                v-if="numberTypes.includes(requirement.requirement_type)"
                                v-model="requirement.value"
                                class="form-control"
                                placeholder="Enter details"
                            >
                            <input type="file" class="form-control" @change="requirementFiles[requirement.id] = $event.target.files[0]">
                            <button class="btn btn-sm btn-primary" @click="upload(selectedApplication, requirement)">Submit / Replace</button>
                        </article>
                    </div>
                </section>
            </article>
        </section>

        <section v-else class="empty-state">
            <h2>No applications yet</h2>
            <p>Browse current vacancies and submit your first application when you find a role that fits.</p>
            <a :href="jobsUrl" class="btn btn-primary">Browse Jobs</a>
        </section>
    </main>
</template>

<script setup>
import { computed, reactive, ref } from "vue";
import axios from "axios";

const props = defineProps({
    initialProfile: Object,
    jobsUrl: String,
    signedOfferBaseUrl: String,
    requirementsBaseUrl: String,
});

const stages = [
    { key: "initial_screening", label: "Screening" },
    { key: "interview_exams", label: "Interview / Exam" },
    { key: "finalist", label: "Finalist" },
    { key: "job_offer", label: "Offer" },
    { key: "requirements", label: "Requirements" },
    { key: "onboarding", label: "Onboarding" },
    { key: "hired", label: "Hired" },
];

const profile = reactive(structuredClone(props.initialProfile));
const message = ref("");
const selectedApplicationId = ref(profile.applications?.[0]?.id || null);
const signedFiles = reactive({});
const requirementFiles = reactive({});
const numberTypes = ["sss", "philhealth", "pagibig", "tin", "bank_account"];

const selectedApplication = computed(() => profile.applications.find((application) => application.id === selectedApplicationId.value) || profile.applications[0]);
const initials = computed(() => [profile.first_name, profile.last_name].filter(Boolean).map((name) => name[0]).join("").toUpperCase() || "A");

const title = (value) => String(value || "").replaceAll("_", " ").replace(/\b\w/g, (char) => char.toUpperCase());
const storage = (path) => `/storage/${path}`;
const date = (value) => value ? new Date(value).toLocaleString() : "";
const stageIndex = (stage) => Math.max(0, stages.findIndex((item) => item.key === stage));

async function sign(application) {
    const data = new FormData();
    if (signedFiles[application.id]) data.append("signed_offer", signedFiles[application.id]);
    const response = await axios.post(
        `${props.signedOfferBaseUrl}/${application.id}/signed-offer`,
        data,
        { headers: { Accept: "application/json" } },
    );
    Object.assign(application, response.data.application);
    message.value = response.data.message;
}

async function upload(application, requirement) {
    const data = new FormData();
    if (requirementFiles[requirement.id]) data.append("file", requirementFiles[requirement.id]);
    if (requirement.value) data.append("value", requirement.value);
    const response = await axios.post(
        `${props.requirementsBaseUrl}/${application.id}/requirements/${requirement.id}`,
        data,
        { headers: { Accept: "application/json" } },
    );
    Object.assign(requirement, response.data.requirement);
    message.value = response.data.message;
}
</script>

<style scoped>
.dashboard-page {
    max-width: 1280px;
    margin: 0 auto;
    padding: 36px 20px 72px;
    color: #1f2937;
}

.dashboard-hero,
.dashboard-grid {
    display: grid;
    gap: 20px;
}

.dashboard-hero {
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    margin-bottom: 18px;
}

.identity-card,
.application-list,
.application-panel,
.content-block,
.empty-state,
.success-block {
    border: 1px solid #d9e2ef;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
}

.identity-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 18px;
}

.avatar {
    width: 64px;
    height: 64px;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    border-radius: 8px;
    background: #eef4ff;
    color: #0a66c2;
    font-size: 1.4rem;
    font-weight: 800;
}

.eyebrow {
    color: #0a66c2;
    font-size: .78rem;
    font-weight: 800;
    text-transform: uppercase;
}

.dashboard-hero h1,
.application-header h2,
.empty-state h2 {
    margin: 4px 0;
    font-weight: 800;
}

.dashboard-hero h1 {
    font-size: 1.8rem;
}

.dashboard-hero p,
.empty-state p,
.assessment-top p,
.instructions,
.venue {
    margin: 0;
    color: #64748b;
}

.dashboard-alert {
    border-radius: 8px;
}

.dashboard-grid {
    grid-template-columns: 310px minmax(0, 1fr);
    align-items: start;
}

.application-list {
    position: sticky;
    top: 88px;
    padding: 14px;
}

.list-heading,
.application-header,
.block-heading,
.requirement-title,
.assessment-top,
.offer-row {
    display: flex;
    justify-content: space-between;
    gap: 14px;
}

.list-heading {
    align-items: center;
    padding: 6px 6px 12px;
    color: #475569;
    font-weight: 800;
}

.application-tab {
    width: 100%;
    display: grid;
    gap: 4px;
    padding: 12px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: #334155;
    text-align: left;
}

.application-tab:hover,
.application-tab.active {
    background: #eef4ff;
}

.application-tab span {
    font-weight: 800;
}

.application-tab small {
    color: #64748b;
}

.application-panel {
    padding: 24px;
}

.application-header {
    align-items: flex-start;
    margin-bottom: 22px;
}

.application-header h2 {
    font-size: 1.55rem;
}

.status-pill,
.block-heading span,
.badge-stack span,
.requirement-title span {
    border-radius: 999px;
    font-size: .78rem;
    font-weight: 800;
}

.status-pill,
.block-heading span {
    padding: 6px 10px;
    background: #eef6ff;
    color: #0a66c2;
}

.stage-track {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 8px;
    margin-bottom: 22px;
}

.stage-step {
    display: grid;
    gap: 6px;
    justify-items: center;
    color: #94a3b8;
    text-align: center;
    font-weight: 800;
}

.stage-step span {
    width: 32px;
    height: 32px;
    display: grid;
    place-items: center;
    border-radius: 999px;
    background: #e7edf5;
}

.stage-step.done {
    color: #0a66c2;
}

.stage-step.done span {
    background: #0a66c2;
    color: #fff;
}

.content-block,
.success-block {
    padding: 18px;
    margin-top: 16px;
}

.success-block {
    border-color: #bbf7d0;
    background: #f0fdf4;
}

.success-block h3,
.block-heading h3 {
    margin: 0;
    font-size: 1.12rem;
    font-weight: 800;
}

.block-heading {
    align-items: center;
    margin-bottom: 14px;
}

.assessment-grid,
.requirements-grid {
    display: grid;
    gap: 12px;
}

.assessment-card,
.requirement-card {
    border: 1px solid #d9e2ef;
    border-radius: 8px;
    background: #fbfdff;
    padding: 16px;
}

.assessment-top {
    align-items: flex-start;
    margin-bottom: 12px;
}

.badge-stack {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 6px;
}

.badge-stack span {
    padding: 5px 8px;
    background: #e8eef7;
    color: #475569;
}

.instructions,
.venue {
    margin-top: 10px;
}

.questionnaire {
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid #d9e2ef;
}

.questionnaire ol {
    margin: 8px 0 0;
    padding-left: 20px;
}

.questionnaire li {
    margin-bottom: 8px;
}

.questionnaire small {
    display: block;
    color: #64748b;
}

.offer-row {
    align-items: center;
}

.offer-form {
    display: flex;
    gap: 10px;
    flex: 1;
    justify-content: flex-end;
}

.requirements-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.requirement-card {
    display: grid;
    gap: 10px;
}

.requirement-title {
    align-items: center;
}

.requirement-title span {
    padding: 5px 8px;
    background: #e8eef7;
    color: #475569;
}

.empty-state {
    padding: 48px 24px;
    text-align: center;
}

.btn,
.form-control {
    border-radius: 8px;
}

.btn {
    font-weight: 700;
}

.btn-primary {
    background: #0a66c2;
    border-color: #0a66c2;
}

.form-control {
    min-height: 42px;
    border-color: #cbd7e6;
}

@media (max-width: 991.98px) {
    .dashboard-hero,
    .dashboard-grid {
        grid-template-columns: 1fr;
    }

    .application-list {
        position: static;
    }

    .stage-track,
    .requirements-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .dashboard-page {
        padding: 24px 14px 52px;
    }

    .identity-card,
    .application-header,
    .block-heading,
    .assessment-top,
    .offer-row,
    .offer-form {
        flex-direction: column;
        align-items: stretch;
    }

    .dashboard-hero h1 {
        font-size: 1.5rem;
    }

    .stage-track,
    .requirements-grid {
        grid-template-columns: 1fr;
    }
}
</style>
