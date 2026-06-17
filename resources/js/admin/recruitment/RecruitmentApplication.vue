<template>
    <div class="container-fluid py-4">
        <div v-if="message" class="alert alert-success">{{ message }}</div>

        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <a :href="processUrl">← Hiring Process</a>
                <h2 class="mt-2">{{ fullName(app.applicant_profile) }}</h2>
                <p class="text-muted">{{ app.job_posting.title }}</p>
            </div>
            <span class="badge bg-primary fs-6">{{ title(app.stage) }}</span>
        </div>

        <div class="row g-4">
            <div class="col-xl-8">
                <section class="card shadow-sm mb-4">
                    <div class="card-header"><strong>Applicant Profile</strong></div>
                    <div class="card-body">
                        <p><strong>Email:</strong> {{ app.applicant_profile.email }}</p>
                        <p><strong>Contact:</strong> {{ app.applicant_profile.contact_number }}</p>
                        <p><strong>Address:</strong> {{ app.applicant_profile.address }}</p>
                        <a class="btn btn-outline-primary me-2" target="_blank" :href="storage(app.resume_path)">View Resume</a>
                        <a v-if="app.cv_path" class="btn btn-outline-primary" target="_blank" :href="storage(app.cv_path)">View CV</a>
                    </div>
                </section>

                <section class="card shadow-sm mb-4">
                    <div class="card-header"><strong>Education and Experience</strong></div>
                    <div class="card-body">
                        <h6>Education</h6>
                        <p v-for="education in app.applicant_profile.education" :key="education.id">
                            {{ title(education.level) }}:
                            {{ education.school_name }}
                            {{ education.course ? `- ${education.course}` : "" }}
                            ({{ education.year_graduated || "N/A" }})
                        </p>
                        <hr>
                        <h6>Work Experience</h6>
                        <p v-for="experience in app.applicant_profile.work_experiences" :key="experience.id">
                            {{ experience.position }} at {{ experience.company_name }}
                            ({{ experience.year_started }} - {{ experience.year_ended || "Present" }})
                        </p>
                    </div>
                </section>

                <section class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>Interview / Exam</strong>
                        <span class="badge text-bg-light border">{{ app.assessments?.length || 0 }} scheduled</span>
                    </div>
                    <div class="card-body">
                        <div
                            v-for="item in app.assessments"
                            :key="item.id"
                            class="border rounded p-3 mb-3"
                        >
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div>
                                    <strong>{{ item.title }}</strong>
                                    <span class="badge bg-info ms-2">{{ title(item.type) }}</span>
                                    <span class="badge bg-secondary ms-1">{{ title(item.meeting_type) }}</span>
                                </div>
                                <span class="text-muted small">{{ date(item.scheduled_at) || "To be arranged" }}</span>
                            </div>
                            <div class="mt-2">
                                <a
                                    v-if="item.external_link"
                                    :href="item.external_link"
                                    target="_blank"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Open Meeting / Exam Link
                                </a>
                                <span v-else class="text-muted">{{ item.location || "No venue set" }}</span>
                            </div>
                            <p v-if="item.instructions" class="mt-2 mb-0 text-muted">{{ item.instructions }}</p>
                            <div v-if="item.questions?.length" class="mt-3">
                                <div class="fw-semibold small text-uppercase text-muted mb-2">Questionnaire</div>
                                <ol class="mb-0">
                                    <li v-for="question in item.questions" :key="question.id" class="mb-2">
                                        <div>{{ question.question }}</div>
                                        <small class="text-muted">
                                            {{ title(question.answer_type) }}
                                            <template v-if="question.options?.length">
                                                · Options: {{ question.options.join(", ") }}
                                            </template>
                                        </small>
                                    </li>
                                </ol>
                            </div>
                        </div>

                        <form v-if="canManage" class="border-top pt-3 row g-3" @submit.prevent="schedule">
                            <div class="col-md-3">
                                <label class="form-label">Type</label>
                                <select v-model="assessment.type" class="form-select">
                                    <option value="interview">Interview</option>
                                    <option value="exam">Exam</option>
                                </select>
                            </div>
                            <div class="col-md-9">
                                <label class="form-label">Title</label>
                                <input v-model="assessment.title" class="form-control" placeholder="Initial interview, Technical exam, Panel interview">
                                <Error :errors="errors" name="title" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Schedule</label>
                                <input v-model="assessment.scheduled_at" type="datetime-local" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Mode</label>
                                <select v-model="assessment.meeting_type" class="form-select">
                                    <option value="online">Online</option>
                                    <option value="physical">Physical</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">{{ assessment.meeting_type === "online" ? "Meeting / Exam Link" : "Location" }}</label>
                                <input
                                    v-if="assessment.meeting_type === 'online'"
                                    v-model="assessment.external_link"
                                    class="form-control"
                                    placeholder="https://meet.google.com/... or exam link"
                                >
                                <input
                                    v-else
                                    v-model="assessment.location"
                                    class="form-control"
                                    placeholder="Office, room, or venue"
                                >
                                <Error :errors="errors" :name="assessment.meeting_type === 'online' ? 'external_link' : 'location'" />
                            </div>
                            <div class="col-12">
                                <label class="form-label">Instructions</label>
                                <textarea
                                    v-model="assessment.instructions"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Exam rules, interview reminders, required files, or preparation notes"
                                ></textarea>
                            </div>

                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Questionnaire</label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addQuestion">
                                        Add Question
                                    </button>
                                </div>
                                <div
                                    v-for="(question, index) in assessment.questions"
                                    :key="index"
                                    class="border rounded p-3 mb-2"
                                >
                                    <div class="row g-2">
                                        <div class="col-md-7">
                                            <input
                                                v-model="question.question"
                                                class="form-control"
                                                :placeholder="`Question ${index + 1}`"
                                            >
                                        </div>
                                        <div class="col-md-3">
                                            <select v-model="question.answer_type" class="form-select">
                                                <option value="text">Short Answer</option>
                                                <option value="long_text">Long Answer</option>
                                                <option value="choice">Multiple Choice</option>
                                                <option value="file">File Upload</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-grid">
                                            <button type="button" class="btn btn-outline-danger" @click="removeQuestion(index)">
                                                Remove
                                            </button>
                                        </div>
                                        <div v-if="question.answer_type === 'choice'" class="col-12">
                                            <input
                                                v-model="question.options"
                                                class="form-control"
                                                placeholder="Choices separated by comma, e.g. A, B, C"
                                            >
                                        </div>
                                    </div>
                                </div>
                                <div v-if="!assessment.questions.length" class="text-muted small">
                                    Add questions for online exams or pre-interview questionnaires.
                                </div>
                            </div>

                            <div class="col-12">
                                <button class="btn btn-primary">Schedule Interview / Exam</button>
                            </div>
                        </form>
                    </div>
                </section>

                <section v-if="app.requirements?.length" class="card shadow-sm">
                    <div class="card-header"><strong>Requirements</strong></div>
                    <div class="card-body">
                        <div v-for="requirement in app.requirements" :key="requirement.id" class="d-flex justify-content-between border-bottom py-2">
                            <div>
                                <strong>{{ requirement.label }}</strong>
                                <span class="badge bg-secondary">{{ title(requirement.status) }}</span>
                            </div>
                            <div v-if="requirement.status === 'submitted'">
                                <button class="btn btn-sm btn-success me-1" @click="verify(requirement, 'verified')">Verify</button>
                                <button class="btn btn-sm btn-danger" @click="verify(requirement, 'rejected')">Reject</button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-xl-4">
                <section class="card shadow-sm mb-4">
                    <div class="card-header"><strong>Advance Hiring Stage</strong></div>
                    <form class="card-body" @submit.prevent="updateStage">
                        <select v-model="stageForm.stage" class="form-select mb-2">
                            <option v-for="stage in stages" :key="stage" :value="stage">{{ title(stage) }}</option>
                        </select>
                        <textarea v-model="stageForm.notes" class="form-control mb-2" placeholder="Review notes"></textarea>
                        <button class="btn btn-primary w-100">Update Stage</button>
                    </form>
                </section>

                <section class="card shadow-sm mb-4">
                    <div class="card-header"><strong>Job Offer</strong></div>
                    <div class="card-body">
                        <form @submit.prevent="prepareOffer">
                            <input v-model="offer.subject" class="form-control mb-2">
                            <textarea v-model="offer.body" rows="8" class="form-control mb-2"></textarea>
                            <button class="btn btn-outline-primary w-100">Generate / Review PDF</button>
                        </form>
                        <a v-if="app.offer?.pdf_path" target="_blank" class="btn btn-outline-secondary w-100 mt-2" :href="storage(app.offer.pdf_path)">Preview Offer PDF</a>
                        <button v-if="app.offer?.pdf_path" class="btn btn-success w-100 mt-2" @click="sendOffer">Send Offer Email</button>
                    </div>
                </section>

                <button v-if="app.stage !== 'hired'" class="btn btn-dark w-100" @click="hire">Mark Hired and Create HRIS Profile</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineComponent, h, reactive, ref } from "vue";
import axios from "axios";

const props = defineProps({
    initialApplication: Object,
    stages: Array,
    routes: Object,
    processUrl: String,
    canManage: Boolean,
});

const Error = defineComponent({
    props: ["errors", "name"],
    setup: (props) => () => props.errors[props.name]
        ? h("div", { class: "text-danger small" }, props.errors[props.name][0])
        : null,
});

const emptyAssessment = () => ({
    type: "interview",
    title: "",
    scheduled_at: "",
    meeting_type: "online",
    location: "",
    external_link: "",
    instructions: "",
    questions: [],
});

const app = ref(structuredClone(props.initialApplication));
const message = ref("");
const errors = ref({});
const stageForm = reactive({
    stage: app.value.stage,
    notes: app.value.admin_notes || "",
});
const assessment = reactive(emptyAssessment());
const offer = reactive({
    subject: app.value.offer?.subject || `Job Offer - ${app.value.job_posting.title}`,
    body: app.value.offer?.body || `<p>We are pleased to offer you the position of <strong>${app.value.job_posting.title}</strong>.</p>`,
});

const title = (value) => String(value || "").replaceAll("_", " ").replace(/\b\w/g, (char) => char.toUpperCase());
const fullName = (profile) => [profile.first_name, profile.middle_name, profile.last_name].filter(Boolean).join(" ");
const storage = (path) => `/storage/${path}`;
const date = (value) => value ? new Date(value).toLocaleString() : "";

function addQuestion() {
    assessment.questions.push({ question: "", answer_type: "text", options: "" });
}

function removeQuestion(index) {
    assessment.questions.splice(index, 1);
}

function resetAssessment() {
    Object.assign(assessment, emptyAssessment());
}

async function post(url, data = {}, method = "post") {
    errors.value = {};
    try {
        const res = await axios({ url, method, data, headers: { Accept: "application/json" } });
        message.value = res.data.message;
        if (res.data.application) app.value = { ...app.value, ...res.data.application };
        return res.data;
    } catch (error) {
        errors.value = error.response?.data?.errors || {};
    }
}

const updateStage = async () => {
    await post(props.routes.stage, stageForm, "patch");
    stageForm.stage = app.value.stage;
};

const schedule = async () => {
    const payload = {
        ...assessment,
        questions: assessment.questions.filter((question) => question.question.trim()),
    };
    if (payload.meeting_type === "online") payload.location = "";
    if (payload.meeting_type === "physical") payload.external_link = "";

    const data = await post(props.routes.assessments, payload);
    if (data?.application) resetAssessment();
};

const prepareOffer = () => post(props.routes.offer, offer);
const sendOffer = () => post(props.routes.sendOffer, { email_body: offer.body });
const verify = async (requirement, status) => {
    const data = await post(`${props.routes.requirements}/${requirement.id}`, { status }, "patch");
    if (data?.requirement) Object.assign(requirement, data.requirement);
};
const hire = async () => {
    if (confirm("Create this applicant as an HRIS employee?")) await post(props.routes.hire);
};
</script>
