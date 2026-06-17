<template>
    <main class="registration-page">
        <section class="registration-hero">
            <div>
                <span class="eyebrow">Applicant profile</span>
                <h1>Build your candidate profile</h1>
                <p>Complete your basic information, work preferences, education, and supporting records in one guided flow.</p>
            </div>
            <a href="/careers" class="btn btn-outline-primary">Back to Jobs</a>
        </section>

        <form class="registration-layout" novalidate @submit.prevent="submit">
            <aside class="profile-rail">
                <div class="profile-photo">
                    <img v-if="profilePreview" :src="profilePreview" alt="Applicant preview">
                    <span v-else>{{ initials }}</span>
                </div>
                <h2>{{ displayName || "New Applicant" }}</h2>
                <p>{{ form.email || "Add your email to make this profile searchable." }}</p>

                <div class="progress-wrap" aria-label="Profile completion">
                    <div class="progress-label">
                        <span>Profile completion</span>
                        <strong>{{ completionPercent }}%</strong>
                    </div>
                    <div class="progress-track">
                        <div :style="{ width: `${completionPercent}%` }"></div>
                    </div>
                </div>

                <nav class="step-list" aria-label="Registration sections">
                    <a v-for="section in sections" :key="section.id" :href="`#${section.id}`">
                        <span>{{ section.number }}</span>
                        {{ section.label }}
                    </a>
                </nav>
            </aside>

            <div class="form-column">
                <section id="account" class="section-panel">
                    <div class="section-heading">
                        <div>
                            <span class="section-kicker">Step 1</span>
                            <h2>Account Access</h2>
                        </div>
                        <span class="section-note">Used for login and HR updates</span>
                    </div>
                    <div class="row g-3">
                        <Input label="Email Address" type="email" v-model="form.email" :error="error('email')" class="col-md-6"/>
                        <Input label="Contact Number" v-model="form.contact_number" :error="error('contact_number')" class="col-md-6"/>
                        <Input label="Password" type="password" v-model="form.password" :error="error('password')" class="col-md-6"/>
                        <Input label="Confirm Password" type="password" v-model="form.password_confirmation" class="col-md-6"/>
                    </div>
                </section>

                <section id="profile" class="section-panel">
                    <div class="section-heading">
                        <div>
                            <span class="section-kicker">Step 2</span>
                            <h2>Personal Information</h2>
                        </div>
                    </div>
                    <div class="row g-3">
                        <Input label="First Name" v-model="form.first_name" :error="error('first_name')" class="col-md-4"/>
                        <Input label="Middle Name" v-model="form.middle_name" class="col-md-4"/>
                        <Input label="Last Name" v-model="form.last_name" :error="error('last_name')" class="col-md-4"/>
                        <div class="col-md-4">
                            <label class="form-label">Sex</label>
                            <select v-model="form.sex" class="form-select">
                                <option value="">Select</option>
                                <option>Male</option>
                                <option>Female</option>
                                <option>Prefer not to say</option>
                            </select>
                            <Err :text="error('sex')"/>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Profile Image</label>
                            <input type="file" class="form-control" accept="image/*" @change="selectProfileImage">
                        </div>
                        <Input label="Address" v-model="form.address" :error="error('address')" class="col-12"/>
                    </div>
                </section>

                <section id="interests" class="section-panel">
                    <div class="section-heading align-items-start">
                        <div>
                            <span class="section-kicker">Step 3</span>
                            <h2>Work Interests</h2>
                            <p>Choose up to 12 areas that match the work you want to be considered for.</p>
                        </div>
                        <span class="counter-pill">{{ form.interests.length }}/12 selected</span>
                    </div>
                    <input v-model="interestSearch" type="search" class="form-control interest-search" placeholder="Search interests">
                    <div class="interest-grid">
                        <label
                            v-for="interest in filteredInterests"
                            :key="interest.id"
                            class="interest-chip"
                            :class="{ selected: form.interests.includes(interest.id), disabled: interestDisabled(interest.id) }"
                        >
                            <input
                                v-model="form.interests"
                                :value="interest.id"
                                type="checkbox"
                                :disabled="interestDisabled(interest.id)"
                            >
                            {{ interest.name }}
                        </label>
                    </div>
                    <p v-if="!filteredInterests.length" class="empty-note">No matching interests found.</p>
                    <Err :text="error('interests')"/>
                </section>

                <section id="education" class="section-panel">
                    <div class="section-heading">
                        <div>
                            <span class="section-kicker">Step 4</span>
                            <h2>Education</h2>
                        </div>
                    </div>
                    <div class="education-list">
                        <article v-for="(education,index) in form.education" :key="education.level" class="mini-panel">
                            <h3>{{ title(education.level) }}</h3>
                            <div class="row g-3">
                                <Input label="School name" v-model="education.school_name" :error="error(`education.${index}.school_name`)" class="col-md-5"/>
                                <Input label="Course" v-model="education.course" class="col-md-4"/>
                                <Input label="Year graduated" type="number" v-model="education.year_graduated" class="col-md-3"/>
                            </div>
                        </article>
                    </div>
                </section>

                <section id="experience" class="section-panel">
                    <div class="section-heading">
                        <div>
                            <span class="section-kicker">Step 5</span>
                            <h2>Work Experience</h2>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" @click="addWork">Add Experience</button>
                    </div>
                    <div v-if="!form.work_experiences.length" class="empty-panel">
                        Add previous work experience if applicable. You can skip this for first-time applicants.
                    </div>
                    <article v-for="(work,index) in form.work_experiences" :key="index" class="repeat-panel">
                        <div class="repeat-heading">
                            <strong>Experience {{ index + 1 }}</strong>
                            <button type="button" class="btn btn-link text-danger p-0" @click="removeWork(index)">Remove</button>
                        </div>
                        <div class="row g-3">
                            <Input label="Company" v-model="work.company_name" class="col-md-4"/>
                            <Input label="Position" v-model="work.position" class="col-md-4"/>
                            <Input label="Year started" type="number" v-model="work.year_started" class="col-md-2"/>
                            <Input label="Year ended" type="number" v-model="work.year_ended" class="col-md-2"/>
                        </div>
                    </article>
                </section>

                <section id="certificates" class="section-panel">
                    <div class="section-heading">
                        <div>
                            <span class="section-kicker">Step 6</span>
                            <h2>Certificates</h2>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" @click="addCertificate">Add Certificate</button>
                    </div>
                    <div v-if="!form.certificates.length" class="empty-panel">
                        Add certificates, training, or licenses that strengthen your application.
                    </div>
                    <article v-for="(certificate,index) in form.certificates" :key="index" class="repeat-panel">
                        <div class="repeat-heading">
                            <strong>Certificate {{ index + 1 }}</strong>
                            <button type="button" class="btn btn-link text-danger p-0" @click="removeCertificate(index)">Remove</button>
                        </div>
                        <div class="row g-3">
                            <Input label="Certificate name" v-model="certificate.name" class="col-md-4"/>
                            <Input label="Issuer" v-model="certificate.issuer" class="col-md-3"/>
                            <Input label="Issued at" type="date" v-model="certificate.issued_at" class="col-md-2"/>
                            <div class="col-md-3">
                                <label class="form-label">Attachment</label>
                                <input type="file" class="form-control" @change="certificate.file=$event.target.files[0]">
                            </div>
                        </div>
                    </article>
                </section>

                <div class="submit-panel">
                    <div>
                        <strong>Ready to submit?</strong>
                        <p>HR will use this profile when you apply for vacancies.</p>
                    </div>
                    <button class="btn btn-primary btn-lg" :disabled="saving">
                        {{ saving ? "Creating..." : "Create Applicant Account" }}
                    </button>
                </div>
            </div>
        </form>
    </main>
</template>

<script setup>
import { computed, defineComponent, h, reactive, ref } from "vue";
import axios from "axios";

const props = defineProps({
    interests: Array,
    storeUrl: String,
});

const sections = [
    { id: "account", label: "Account", number: "01" },
    { id: "profile", label: "Profile", number: "02" },
    { id: "interests", label: "Interests", number: "03" },
    { id: "education", label: "Education", number: "04" },
    { id: "experience", label: "Experience", number: "05" },
    { id: "certificates", label: "Certificates", number: "06" },
];

const errors = ref({});
const saving = ref(false);
const interestSearch = ref("");
const profilePreview = ref("");
const form = reactive({
    first_name: "",
    middle_name: "",
    last_name: "",
    sex: "",
    address: "",
    profile_image: null,
    contact_number: "",
    email: "",
    password: "",
    password_confirmation: "",
    interests: [],
    education: [
        { level: "elementary", school_name: "", course: "", year_graduated: "" },
        { level: "high_school", school_name: "", course: "", year_graduated: "" },
        { level: "college", school_name: "", course: "", year_graduated: "" },
    ],
    work_experiences: [],
    certificates: [],
});

const Err = defineComponent({
    props: ["text"],
    setup: (props) => () => props.text ? h("div", { class: "text-danger small mt-1" }, props.text) : null,
});

const Input = defineComponent({
    props: ["modelValue", "label", "type", "placeholder", "error"],
    emits: ["update:modelValue"],
    setup(props, { emit, attrs }) {
        return () => h("div", attrs, [
            props.label ? h("label", { class: "form-label" }, props.label) : null,
            h("input", {
                class: "form-control",
                type: props.type || "text",
                placeholder: props.placeholder,
                value: props.modelValue,
                onInput: (event) => emit("update:modelValue", event.target.value),
            }),
            props.error ? h("div", { class: "text-danger small mt-1" }, props.error) : null,
        ]);
    },
});

const displayName = computed(() => [form.first_name, form.last_name].filter(Boolean).join(" "));
const initials = computed(() => {
    const name = displayName.value || form.email || "A";
    return name.split(" ").filter(Boolean).slice(0, 2).map((part) => part[0]).join("").toUpperCase();
});
const filteredInterests = computed(() => {
    const keyword = interestSearch.value.trim().toLowerCase();
    if (!keyword) return props.interests || [];
    return (props.interests || []).filter((interest) => interest.name.toLowerCase().includes(keyword));
});
const completionPercent = computed(() => {
    const checks = [
        form.first_name,
        form.last_name,
        form.email,
        form.contact_number,
        form.password,
        form.password_confirmation,
        form.sex,
        form.address,
        form.interests.length,
        form.education.some((row) => row.school_name),
    ];
    return Math.round((checks.filter(Boolean).length / checks.length) * 100);
});

const error = (key) => errors.value[key]?.[0];
const title = (value) => value.replaceAll("_", " ").replace(/\b\w/g, (char) => char.toUpperCase());
const interestDisabled = (id) => form.interests.length >= 12 && !form.interests.includes(id);

function selectProfileImage(event) {
    const file = event.target.files[0];
    form.profile_image = file;
    profilePreview.value = file ? URL.createObjectURL(file) : "";
}

function addWork() {
    form.work_experiences.push({ company_name: "", year_started: "", year_ended: "", position: "" });
}

function removeWork(index) {
    form.work_experiences.splice(index, 1);
}

function addCertificate() {
    form.certificates.push({ name: "", issuer: "", issued_at: "", file: null });
}

function removeCertificate(index) {
    form.certificates.splice(index, 1);
}

async function submit() {
    saving.value = true;
    errors.value = {};
    const data = new FormData();
    const append = (key, value) => {
        if (value !== null && value !== "") data.append(key, value);
    };
    Object.entries(form).forEach(([key, value]) => {
        if (["education", "work_experiences", "certificates"].includes(key)) {
            value.forEach((row, index) => Object.entries(row).forEach(([rowKey, rowValue]) => append(`${key}[${index}][${rowKey}]`, rowValue)));
        } else if (key === "interests") {
            value.forEach((id) => data.append("interests[]", id));
        } else {
            append(key, value);
        }
    });

    try {
        const response = await axios.post(props.storeUrl, data, { headers: { Accept: "application/json" } });
        window.location.assign(response.data.redirect);
    } catch (event) {
        errors.value = event.response?.data?.errors || {};
    } finally {
        saving.value = false;
    }
}
</script>

<style scoped>
.registration-page {
    max-width: 1280px;
    margin: 0 auto;
    padding: 40px 20px 72px;
    color: #1f2937;
}

.registration-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 24px;
    margin-bottom: 24px;
}

.eyebrow,
.section-kicker {
    display: block;
    color: #0a66c2;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: 0;
    text-transform: uppercase;
}

.registration-hero h1 {
    margin: 8px 0;
    font-size: 2.5rem;
    font-weight: 800;
}

.registration-hero p {
    max-width: 660px;
    margin: 0;
    color: #637083;
    font-size: 1rem;
}

.registration-layout {
    display: grid;
    grid-template-columns: 310px minmax(0, 1fr);
    gap: 20px;
    align-items: start;
}

.profile-rail,
.section-panel,
.submit-panel {
    background: #fff;
    border: 1px solid #d9e2ef;
    border-radius: 8px;
    box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
}

.profile-rail {
    position: sticky;
    top: 88px;
    padding: 22px;
}

.profile-photo {
    width: 84px;
    height: 84px;
    display: grid;
    place-items: center;
    margin-bottom: 16px;
    overflow: hidden;
    border-radius: 8px;
    background: #eef4ff;
    color: #0a66c2;
    font-size: 1.7rem;
    font-weight: 800;
}

.profile-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-rail h2 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 800;
}

.profile-rail p {
    margin: 6px 0 18px;
    color: #64748b;
    font-size: .92rem;
    overflow-wrap: anywhere;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    color: #475569;
    font-size: .85rem;
}

.progress-track {
    height: 8px;
    overflow: hidden;
    border-radius: 999px;
    background: #e5edf7;
}

.progress-track div {
    height: 100%;
    background: #0a66c2;
    transition: width .2s ease;
}

.step-list {
    display: grid;
    gap: 8px;
    margin-top: 22px;
}

.step-list a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 8px;
    color: #334155;
    font-weight: 700;
    text-decoration: none;
}

.step-list a:hover {
    background: #eef4ff;
    color: #0a66c2;
}

.step-list span {
    color: #0a66c2;
    font-size: .78rem;
}

.form-column {
    display: grid;
    gap: 18px;
}

.section-panel {
    padding: 24px;
    scroll-margin-top: 96px;
}

.section-heading,
.submit-panel {
    display: flex;
    justify-content: space-between;
    gap: 16px;
}

.section-heading {
    align-items: center;
    margin-bottom: 18px;
}

.section-heading h2 {
    margin: 4px 0 0;
    font-size: 1.35rem;
    font-weight: 800;
}

.section-heading p,
.submit-panel p {
    margin: 4px 0 0;
    color: #64748b;
}

.section-note,
.counter-pill {
    color: #64748b;
    font-size: .88rem;
}

.counter-pill {
    padding: 6px 10px;
    border: 1px solid #c8d6e8;
    border-radius: 999px;
    background: #f8fbff;
    color: #0a66c2;
    font-weight: 800;
    white-space: nowrap;
}

.interest-search {
    margin-bottom: 14px;
}

.interest-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}

.interest-chip {
    min-height: 44px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border: 1px solid #d6e1ef;
    border-radius: 8px;
    background: #fff;
    color: #334155;
    cursor: pointer;
    font-weight: 700;
}

.interest-chip.selected {
    border-color: #0a66c2;
    background: #eef6ff;
    color: #0a66c2;
}

.interest-chip.disabled {
    cursor: not-allowed;
    opacity: .55;
}

.mini-panel,
.repeat-panel,
.empty-panel {
    border: 1px solid #d9e2ef;
    border-radius: 8px;
    background: #fbfdff;
}

.education-list,
.repeat-panel + .repeat-panel {
    display: grid;
    gap: 12px;
}

.mini-panel,
.repeat-panel {
    padding: 16px;
}

.mini-panel h3 {
    margin: 0 0 12px;
    font-size: 1rem;
    font-weight: 800;
}

.repeat-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.empty-panel {
    padding: 18px;
    color: #64748b;
}

.empty-note {
    margin: 10px 0 0;
    color: #64748b;
}

.submit-panel {
    align-items: center;
    padding: 18px 20px;
}

.form-label {
    color: #334155;
    font-size: .86rem;
    font-weight: 800;
}

.form-control,
.form-select {
    min-height: 46px;
    border-color: #cbd7e6;
    border-radius: 8px;
}

.form-control:focus,
.form-select:focus {
    border-color: #0a66c2;
    box-shadow: 0 0 0 .2rem rgba(10, 102, 194, .12);
}

.btn {
    border-radius: 8px;
    font-weight: 700;
}

.btn-primary {
    background: #0a66c2;
    border-color: #0a66c2;
}

@media (max-width: 991.98px) {
    .registration-layout {
        grid-template-columns: 1fr;
    }

    .profile-rail {
        position: static;
    }

    .interest-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767.98px) {
    .registration-page {
        padding: 24px 14px 52px;
    }

    .registration-hero,
    .section-heading,
    .submit-panel {
        flex-direction: column;
        align-items: stretch;
    }

    .registration-hero h1 {
        font-size: 2rem;
    }

    .interest-grid {
        grid-template-columns: 1fr;
    }
}
</style>
