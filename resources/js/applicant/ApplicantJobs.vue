<template>
    <div class="careers-page">
        <section class="careers-hero">
            <div class="hero-copy">
                <span class="eyebrow">DOST Careers</span>
                <h1>Find work that moves public service forward.</h1>
                <p>
                    Browse open roles, review the details, and submit your application online.
                </p>
            </div>
            <div class="hero-stats" aria-label="Career opening summary">
                <div>
                    <strong>{{ jobs.length }}</strong>
                    <span>Open role{{ jobs.length === 1 ? "" : "s" }}</span>
                </div>
                <div>
                    <strong>{{ workSetups.length || 1 }}</strong>
                    <span>Work setup{{ workSetups.length === 1 ? "" : "s" }}</span>
                </div>
            </div>
        </section>

        <section class="career-search" aria-label="Vacancy search and filters">
            <div class="search-field">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search by job title, setup, or description"
                    aria-label="Search job postings"
                >
            </div>

            <div class="filter-row">
                <button
                    type="button"
                    class="filter-chip"
                    :class="{ active: selectedSetup === 'all' }"
                    @click="selectedSetup = 'all'"
                >
                    All setups
                </button>
                <button
                    v-for="setup in workSetups"
                    :key="setup"
                    type="button"
                    class="filter-chip"
                    :class="{ active: selectedSetup === setup }"
                    @click="selectedSetup = setup"
                >
                    {{ title(setup) }}
                </button>
            </div>
        </section>

        <div v-if="message" class="alert alert-success career-alert">{{ message }}</div>

        <section class="jobs-layout">
            <div class="jobs-list" aria-label="Job postings">
                <div class="list-header">
                    <div>
                        <h2>Current Vacancies</h2>
                        <p>{{ filteredJobs.length }} matching role{{ filteredJobs.length === 1 ? "" : "s" }}</p>
                    </div>
                    <select v-model="sortBy" class="form-select form-select-sm sort-select" aria-label="Sort jobs">
                        <option value="latest">Latest first</option>
                        <option value="salary_high">Highest salary</option>
                        <option value="title">Title A-Z</option>
                    </select>
                </div>

                <button
                    v-for="job in sortedJobs"
                    :key="job.id"
                    type="button"
                    class="job-row"
                    :class="{ active: selectedJob?.id === job.id }"
                    @click="selectJob(job)"
                >
                    <div class="job-row-main">
                        <div class="job-icon">
                            <img v-if="job.banner_path" :src="storage(job.banner_path)" :alt="job.title">
                            <i v-else class="fa-solid fa-briefcase"></i>
                        </div>
                        <div>
                            <h3>{{ job.title }}</h3>
                            <p>{{ plainText(job.description) }}</p>
                            <div class="job-tags">
                                <span>{{ title(job.employment_type) }}</span>
                                <span>{{ title(job.work_setup) }}</span>
                                <span>{{ salaryRange(job) }}</span>
                            </div>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-right row-arrow"></i>
                </button>

                <div v-if="!sortedJobs.length" class="empty-state">
                    <i class="fa-regular fa-folder-open"></i>
                    <h3>No matching vacancies</h3>
                    <p>Try clearing filters or using a different search term.</p>
                    <button type="button" class="btn btn-outline-primary" @click="clearFilters">
                        Clear Search
                    </button>
                </div>
            </div>

            <aside v-if="selectedJob" class="job-detail" aria-label="Selected job details">
                <div class="detail-media">
                    <img v-if="selectedJob.banner_path" :src="storage(selectedJob.banner_path)" :alt="selectedJob.title">
                    <div v-else class="detail-placeholder">
                        <i class="fa-solid fa-building-columns"></i>
                        <span>DOST Opportunity</span>
                    </div>
                </div>

                <div class="detail-body">
                    <div class="detail-topline">
                        <span>{{ title(selectedJob.work_setup) }}</span>
                        <span>{{ title(selectedJob.employment_type) }}</span>
                    </div>
                    <h2>{{ selectedJob.title }}</h2>

                    <div class="detail-facts">
                        <div>
                            <small>Salary Range</small>
                            <strong>{{ salaryRange(selectedJob) }}</strong>
                        </div>
                        <div>
                            <small>Open Positions</small>
                            <strong>{{ selectedJob.applicants_needed || "Not specified" }}</strong>
                        </div>
                        <div>
                            <small>Posting Window</small>
                            <strong>{{ postingWindow(selectedJob) }}</strong>
                        </div>
                    </div>

                    <div class="description" v-html="selectedJob.description"></div>

                    <form v-if="authenticated" class="apply-box" @submit.prevent="apply(selectedJob)">
                        <div>
                            <label class="form-label">Resume</label>
                            <input
                                type="file"
                                class="form-control"
                                accept=".pdf,.doc,.docx"
                                @change="formFor(selectedJob).resume = $event.target.files[0]"
                            >
                            <Error :errors="formFor(selectedJob).errors" name="resume" />
                        </div>
                        <div>
                            <label class="form-label">CV <span>(optional)</span></label>
                            <input
                                type="file"
                                class="form-control"
                                accept=".pdf,.doc,.docx"
                                @change="formFor(selectedJob).cv = $event.target.files[0]"
                            >
                        </div>
                        <button class="btn btn-primary w-100" :disabled="formFor(selectedJob).saving">
                            {{ formFor(selectedJob).saving ? "Submitting..." : "Submit Application" }}
                        </button>
                    </form>

                    <a v-else :href="registerUrl" class="btn btn-primary w-100 apply-link">
                        Register to Apply
                    </a>
                </div>
            </aside>
        </section>
    </div>
</template>

<script setup>
import { computed, defineComponent, h, reactive, ref, watch } from "vue";
import axios from "axios";

const props = defineProps({
    initialJobs: Array,
    authenticated: Boolean,
    applyBaseUrl: String,
    registerUrl: String,
});

const Error = defineComponent({
    props: ["errors", "name"],
    setup: (props) => () => props.errors[props.name]
        ? h("div", { class: "text-danger small mt-1" }, props.errors[props.name][0])
        : null,
});

const jobs = ref(props.initialJobs || []);
const message = ref("");
const search = ref("");
const selectedSetup = ref("all");
const sortBy = ref("latest");
const selectedJobId = ref(jobs.value[0]?.id || null);
const forms = reactive({});

const title = (value) => String(value || "").replaceAll("_", " ").replace(/\b\w/g, (char) => char.toUpperCase());
const money = (value) => Number(value || 0).toLocaleString();
const storage = (path) => `/storage/${path}`;
const salaryRange = (job) => `PHP ${money(job.salary_min)} - ${money(job.salary_max)}`;
const plainText = (html) => new DOMParser()
    .parseFromString(html || "", "text/html")
    .body
    .textContent
    .trim()
    .slice(0, 170);

const workSetups = computed(() => [...new Set(jobs.value.map((job) => job.work_setup).filter(Boolean))]);
const filteredJobs = computed(() => {
    const term = search.value.trim().toLowerCase();
    return jobs.value.filter((job) => {
        const matchesSetup = selectedSetup.value === "all" || job.work_setup === selectedSetup.value;
        const haystack = `${job.title} ${job.description} ${job.employment_type} ${job.work_setup}`.toLowerCase();
        return matchesSetup && (!term || haystack.includes(term));
    });
});
const sortedJobs = computed(() => {
    const list = [...filteredJobs.value];
    if (sortBy.value === "salary_high") {
        return list.sort((a, b) => Number(b.salary_max || 0) - Number(a.salary_max || 0));
    }
    if (sortBy.value === "title") {
        return list.sort((a, b) => String(a.title || "").localeCompare(String(b.title || "")));
    }
    return list.sort((a, b) => Number(b.id || 0) - Number(a.id || 0));
});
const selectedJob = computed(() => (
    sortedJobs.value.find((job) => job.id === selectedJobId.value)
    || sortedJobs.value[0]
    || null
));

watch(selectedJob, (job) => {
    if (job) selectedJobId.value = job.id;
});

function formFor(job) {
    if (!forms[job.id]) {
        forms[job.id] = { resume: null, cv: null, saving: false, errors: {} };
    }
    return forms[job.id];
}

function selectJob(job) {
    selectedJobId.value = job.id;
}

function clearFilters() {
    search.value = "";
    selectedSetup.value = "all";
}

function postingWindow(job) {
    if (job.posted_until) return `Until ${new Date(job.posted_until).toLocaleDateString()}`;
    if (job.scheduled_at) return `Posted ${new Date(job.scheduled_at).toLocaleDateString()}`;
    return "Open now";
}

async function apply(job) {
    const form = formFor(job);
    form.saving = true;
    form.errors = {};

    const data = new FormData();
    if (form.resume) data.append("resume", form.resume);
    if (form.cv) data.append("cv", form.cv);

    try {
        const response = await axios.post(
            `${props.applyBaseUrl}/${job.id}/apply`,
            data,
            { headers: { Accept: "application/json" } },
        );
        message.value = response.data.message;
        form.resume = null;
        form.cv = null;
    } catch (error) {
        form.errors = error.response?.data?.errors || {};
    } finally {
        form.saving = false;
    }
}
</script>

<style scoped>
.careers-page {
    color: #172033;
    max-width: 1296px;
    margin: 0 auto;
    padding: 32px 20px 56px;
}

.careers-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 24px;
    align-items: end;
    padding: 32px 0 24px;
}

.eyebrow {
    color: #1264d8;
    font-size: 0.8rem;
    font-weight: 800;
    letter-spacing: 0;
    text-transform: uppercase;
}

.hero-copy h1 {
    max-width: 680px;
    margin: 8px 0 10px;
    font-size: 3.2rem;
    line-height: 1.05;
    font-weight: 850;
}

.hero-copy p {
    max-width: 620px;
    margin: 0;
    color: #5f6b7a;
    font-size: 1.05rem;
}

.hero-stats {
    display: flex;
    gap: 10px;
}

.hero-stats div {
    min-width: 118px;
    border: 1px solid #d8e0eb;
    border-radius: 8px;
    padding: 14px 16px;
    background: #fff;
}

.hero-stats strong {
    display: block;
    font-size: 1.5rem;
}

.hero-stats span {
    color: #627085;
    font-size: 0.86rem;
}

.career-search {
    display: grid;
    gap: 14px;
    padding: 18px;
    border: 1px solid #d8e0eb;
    border-radius: 8px;
    background: #fff;
}

.search-field {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 48px;
    padding: 0 14px;
    border: 1px solid #cbd6e5;
    border-radius: 8px;
    background: #f7f9fc;
}

.search-field i {
    color: #1264d8;
}

.search-field input {
    width: 100%;
    border: 0;
    outline: 0;
    background: transparent;
}

.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.filter-chip {
    border: 1px solid #cbd6e5;
    border-radius: 999px;
    padding: 8px 14px;
    background: #fff;
    color: #3f4c5f;
    font-weight: 700;
}

.filter-chip.active,
.filter-chip:hover {
    border-color: #1264d8;
    background: #eaf2ff;
    color: #1264d8;
}

.career-alert {
    margin: 18px 0 0;
}

.jobs-layout {
    display: grid;
    grid-template-columns: minmax(0, 0.9fr) minmax(360px, 0.8fr);
    gap: 20px;
    align-items: start;
    margin-top: 20px;
}

.jobs-list,
.job-detail {
    border: 1px solid #d8e0eb;
    border-radius: 8px;
    background: #fff;
}

.list-header {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    padding: 18px;
    border-bottom: 1px solid #e3e9f2;
}

.list-header h2 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 850;
}

.list-header p {
    margin: 2px 0 0;
    color: #64748b;
}

.sort-select {
    max-width: 156px;
}

.job-row {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 18px;
    border: 0;
    border-bottom: 1px solid #eef2f7;
    background: #fff;
    color: inherit;
    text-align: left;
}

.job-row:hover,
.job-row.active {
    background: #f7fbff;
}

.job-row.active {
    box-shadow: inset 3px 0 0 #1264d8;
}

.job-row-main {
    display: flex;
    gap: 14px;
    min-width: 0;
}

.job-icon {
    width: 52px;
    height: 52px;
    flex: 0 0 52px;
    display: grid;
    place-items: center;
    overflow: hidden;
    border: 1px solid #d8e0eb;
    border-radius: 8px;
    background: #edf5ff;
    color: #1264d8;
}

.job-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.job-row h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 850;
}

.job-row p {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin: 4px 0 8px;
    color: #64748b;
}

.job-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.job-tags span,
.detail-topline span {
    display: inline-flex;
    align-items: center;
    min-height: 26px;
    padding: 4px 9px;
    border: 1px solid #d8e0eb;
    border-radius: 999px;
    color: #435066;
    font-size: 0.8rem;
    font-weight: 750;
}

.row-arrow {
    color: #8fa0b8;
}

.empty-state {
    padding: 48px 20px;
    text-align: center;
}

.empty-state i {
    color: #1264d8;
    font-size: 2rem;
}

.empty-state h3 {
    margin: 12px 0 4px;
    font-size: 1.1rem;
}

.empty-state p {
    color: #64748b;
}

.job-detail {
    position: sticky;
    top: 86px;
    overflow: hidden;
}

.detail-media {
    height: 190px;
    border-bottom: 1px solid #e3e9f2;
    background: #eef4fb;
}

.detail-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.detail-placeholder {
    height: 100%;
    display: grid;
    place-items: center;
    align-content: center;
    gap: 8px;
    color: #1264d8;
    font-weight: 800;
}

.detail-placeholder i {
    font-size: 2rem;
}

.detail-body {
    padding: 20px;
}

.detail-topline {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.detail-body h2 {
    margin: 0 0 16px;
    font-size: 1.55rem;
    font-weight: 850;
}

.detail-facts {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    margin-bottom: 18px;
}

.detail-facts div {
    min-width: 0;
    border: 1px solid #d8e0eb;
    border-radius: 8px;
    padding: 10px;
    background: #fbfcfe;
}

.detail-facts small {
    display: block;
    color: #64748b;
    font-weight: 700;
}

.detail-facts strong {
    display: block;
    margin-top: 4px;
    font-size: 0.9rem;
}

.description {
    color: #344256;
}

.description :deep(*) {
    max-width: 100%;
}

.apply-box {
    display: grid;
    gap: 12px;
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid #e3e9f2;
}

.apply-box label {
    color: #2d394b;
    font-weight: 800;
}

.apply-box label span {
    color: #64748b;
    font-weight: 600;
}

.apply-link {
    margin-top: 16px;
}

@media (max-width: 991.98px) {
    .careers-hero,
    .jobs-layout {
        grid-template-columns: 1fr;
    }

    .hero-stats {
        width: 100%;
    }

    .hero-stats div {
        flex: 1;
    }

    .job-detail {
        position: static;
    }
}

@media (max-width: 575.98px) {
    .careers-page {
        padding: 22px 14px 40px;
    }

    .hero-copy h1 {
        font-size: 2.2rem;
    }

    .list-header,
    .job-row {
        align-items: stretch;
    }

    .list-header {
        flex-direction: column;
    }

    .sort-select {
        max-width: none;
    }

    .detail-facts {
        grid-template-columns: 1fr;
    }

    .row-arrow {
        display: none;
    }
}
</style>
