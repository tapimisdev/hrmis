<template>
    <div class="container-fluid py-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div><h2 class="mb-0">Job Posting</h2><small class="text-muted">Create and publish vacancies.</small></div>
            <div class="d-flex gap-2">
                <input v-model="search" class="form-control" placeholder="Search job postings...">
                <button v-if="canManage" class="btn btn-primary text-nowrap" @click="openModal"><i class="fa-solid fa-plus me-1"></i> New Job</button>
            </div>
        </div>

        <div class="job-grid">
            <article v-for="job in filteredJobs" :key="job.id" class="job-card">
                <div class="job-card-image">
                    <img v-if="job.banner_path" :src="storageUrl(job.banner_path)" :alt="job.title">
                    <div v-else class="job-card-placeholder"><i class="fa-solid fa-briefcase"></i></div>
                    <span class="job-status" :class="statusClass(job.status)"><i class="fa-solid fa-circle"></i>{{ title(job.status) }}</span>
                </div>
                <div class="job-card-content">
                    <div class="job-tags"><span class="job-tag">{{ title(job.employment_type) }}</span><span class="job-tag job-tag-secondary">{{ title(job.work_setup) }}</span></div>
                    <h5 class="job-card-title">{{ job.title }}</h5>
                    <p class="job-card-description">{{ plainText(job.description) }}</p>
                    <div class="job-meta">
                        <div class="job-meta-item"><span class="job-meta-icon"><i class="fa-solid fa-peso-sign"></i></span><span><small>Salary range</small><strong>{{ money(job.salary_min) }} - {{ money(job.salary_max) }}</strong></span></div>
                        <div class="job-meta-item"><span class="job-meta-icon"><i class="fa-solid fa-users"></i></span><span><small>Applications</small><strong>{{ job.applications_count || 0 }} applicant{{ job.applications_count === 1 ? '' : 's' }}</strong></span></div>
                        <div class="job-meta-item job-meta-wide"><span class="job-meta-icon"><i class="fa-regular fa-calendar"></i></span><span><small>Posting period</small><strong>{{ postingDate(job) }}</strong></span></div>
                    </div>
                </div>
                <div class="job-card-footer">
                    <div class="applicant-target"><small>Open positions</small><strong>{{ job.applicants_needed || 'No limit' }}</strong></div>
                    <div class="job-actions">
                        <a :href="`${processUrl}?job=${job.id}`" class="btn btn-primary"><i class="fa-solid fa-users-viewfinder me-2"></i>View Applicants</a>
                        <button v-if="canManage" class="btn btn-outline-danger" title="Archive job posting" :disabled="archivingId === job.id" @click="archive(job)"><span v-if="archivingId === job.id" class="spinner-border spinner-border-sm"></span><i v-else class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
            </article>
            <div v-if="!filteredJobs.length" class="empty-jobs">No job postings found.</div>
        </div>

        <div ref="modalElement" class="modal fade" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" novalidate @submit.prevent="submit">
                    <div class="modal-header"><h5 class="modal-title">Create Job Posting</h5><button type="button" class="btn-close" @click="closeModal"></button></div>
                    <div class="modal-body"><div class="row g-3">
                        <Field label="Job Title" error-key="title" :errors="errors" class="col-12"><input v-model="form.title" class="form-control" :class="{ 'is-invalid': errors.title }"></Field>
                        <Field label="Minimum Salary" error-key="salary_min" :errors="errors" class="col-md-6"><input v-model="form.salary_min" type="number" min="0" step=".01" class="form-control" :class="{ 'is-invalid': errors.salary_min }"></Field>
                        <Field label="Maximum Salary" error-key="salary_max" :errors="errors" class="col-md-6"><input v-model="form.salary_max" type="number" min="0" step=".01" class="form-control" :class="{ 'is-invalid': errors.salary_max }"></Field>
                        <div class="col-12"><label class="form-label">Job Description</label><div class="editor-wrap"><div v-if="editorLoading" class="editor-loading"><div class="spinner-border text-primary"></div><span>Loading editor...</span></div><textarea id="job-description" ref="descriptionElement"></textarea></div><div v-if="errors.description" class="text-danger small mt-1">{{ errors.description[0] }}</div></div>
                        <Field label="Employment Type" error-key="employment_type" :errors="errors" class="col-md-4"><select v-model="form.employment_type" class="form-select"><option value="regular">Regular</option><option value="part_time">Part-Time</option><option value="contractual">Contractual</option><option value="job_order">Job Order</option><option value="project_based">Project-Based</option></select></Field>
                        <Field label="Work Setup" error-key="work_setup" :errors="errors" class="col-md-4"><select v-model="form.work_setup" class="form-select"><option value="onsite">Onsite</option><option value="hybrid">Hybrid</option><option value="work_from_home">Work From Home</option></select></Field>
                        <Field label="Applicants Needed" error-key="applicants_needed" :errors="errors" class="col-md-4"><input v-model="form.applicants_needed" type="number" min="1" class="form-control"></Field>
                        <Field label="Scheduled Posting Date" error-key="scheduled_at" :errors="errors" class="col-md-4"><input v-model="form.scheduled_at" type="datetime-local" class="form-control"></Field>
                        <Field label="Posted Until" error-key="posted_until" :errors="errors" class="col-md-4"><input v-model="form.posted_until" type="datetime-local" class="form-control"></Field>
                        <Field label="Image / Banner" error-key="banner" :errors="errors" class="col-12">
                            <input type="file" accept=".png,.jpg,.jpeg" class="form-control" @change="selectBanner">
                            <div v-if="bannerPreview" class="banner-preview mt-3">
                                <img :src="bannerPreview" alt="Selected job banner preview">
                            </div>
                        </Field>
                        <Field label="Attachments" error-key="attachments" :errors="errors" class="col-12">
                            <input type="file" accept=".png,.jpg,.jpeg,.pdf" multiple class="form-control" @change="selectAttachments">
                            <div v-if="form.attachments.length" class="selected-files mt-2">
                                <span v-for="file in form.attachments" :key="`${file.name}-${file.lastModified}`" class="badge text-bg-secondary">{{ file.name }}</span>
                            </div>
                        </Field>
                    </div></div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button><button class="btn btn-primary" :disabled="saving || editorLoading">{{ saving ? 'Saving...' : 'Save Job Posting' }}</button></div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, defineComponent, h, nextTick, onBeforeUnmount, reactive, ref } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';
import tinymce from 'tinymce/tinymce';
import 'tinymce/icons/default'; import 'tinymce/models/dom'; import 'tinymce/themes/silver';
import 'tinymce/plugins/advlist'; import 'tinymce/plugins/autolink'; import 'tinymce/plugins/code'; import 'tinymce/plugins/image';
import 'tinymce/plugins/link'; import 'tinymce/plugins/lists'; import 'tinymce/plugins/table'; import 'tinymce/plugins/wordcount';
import 'tinymce/skins/ui/oxide-dark/skin.min.css';

const props = defineProps({ initialJobs: Array, storeUrl: String, processUrl: String, canManage: Boolean });
const Field = defineComponent({ props: ['label', 'errorKey', 'errors'], setup(p, { slots, attrs }) { return () => h('div', attrs, [h('label', { class: 'form-label' }, p.label), slots.default?.(), p.errors[p.errorKey] ? h('div', { class: 'text-danger small mt-1' }, p.errors[p.errorKey][0]) : null]); } });
const jobs = ref([...props.initialJobs]); const search = ref(''); const errors = ref({}); const saving = ref(false); const archivingId = ref(null); const editorLoading = ref(false); const bannerPreview = ref('');
const modalElement = ref(); const descriptionElement = ref(); let modal; let editor;
const emptyForm = () => ({ title: '', salary_min: '', salary_max: '', description: '', employment_type: 'regular', work_setup: 'onsite', applicants_needed: '', scheduled_at: '', posted_until: '', banner: null, attachments: [] });
const form = reactive(emptyForm());
const filteredJobs = computed(() => jobs.value.filter(j => `${j.title} ${j.description} ${j.status}`.toLowerCase().includes(search.value.toLowerCase())));
const title = v => String(v || '').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
const money = v => Number(v || 0).toLocaleString(); const storageUrl = p => `/storage/${p}`; const plainText = html => new DOMParser().parseFromString(html || '', 'text/html').body.textContent.slice(0, 150);
const statusClass = s => s === 'published' ? 'bg-success' : s === 'closed' ? 'bg-secondary' : 'bg-warning text-dark';
const postingDate = j => j.posted_until ? `Until ${new Date(j.posted_until).toLocaleDateString()}` : j.scheduled_at ? `Posts ${new Date(j.scheduled_at).toLocaleDateString()}` : 'No posting deadline';
async function openModal() { errors.value = {}; modal ||= new Modal(modalElement.value, { backdrop: 'static', keyboard: false }); modal.show(); await nextTick(); editorLoading.value = true; editor = (await tinymce.init({ target: descriptionElement.value, height: 500, skin: false, content_css: false, content_style: 'body{background:#222f3e;color:#f1f1f1;font-family:Arial,sans-serif}', menubar: 'file edit view insert format table tools help', plugins: 'advlist autolink code image link lists table wordcount', toolbar: 'undo redo | bold italic underline | bullist numlist | outdent indent | link image | table | code', setup: e => { editor = e; e.on('init', () => editorLoading.value = false); e.on('input change undo redo', () => { form.description = e.getContent(); if (form.description) delete errors.value.description; }); } }))[0]; }
function closeModal() { editor?.remove(); editor = null; modal?.hide(); editorLoading.value = false; }
function selectBanner(event) { if (bannerPreview.value) URL.revokeObjectURL(bannerPreview.value); form.banner = event.target.files[0] || null; bannerPreview.value = form.banner ? URL.createObjectURL(form.banner) : ''; }
function selectAttachments(event) { form.attachments = [...event.target.files]; }
function resetForm() { if (bannerPreview.value) URL.revokeObjectURL(bannerPreview.value); bannerPreview.value = ''; Object.assign(form, emptyForm()); }
async function submit() { saving.value = true; errors.value = {}; const currentEditor = editor || tinymce.get('job-description') || tinymce.activeEditor; form.description = currentEditor?.getContent() || form.description; const data = new FormData(); Object.entries(form).forEach(([k,v]) => { if (k === 'description') return; if (k === 'attachments') v.forEach(f => data.append('attachments[]', f)); else if (v !== null && v !== '') data.append(k, v); }); data.set('description', form.description || ''); try { const { data: res } = await axios.post(props.storeUrl, data, { headers: { Accept: 'application/json' } }); jobs.value.unshift(res.job); resetForm(); closeModal(); window.SuccesToast?.fire({ title: res.message }); } catch (e) { errors.value = e.response?.data?.errors || {}; } finally { saving.value = false; } }
async function archive(job) {
    const confirmation = await window.Swal.fire({
        title: 'Archive job posting?',
        text: `"${job.title}" will be removed from the active job postings.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, archive it',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    });

    if (!confirmation.isConfirmed) return;

    archivingId.value = job.id;
    try {
        const { data } = await axios.delete(`${props.storeUrl}/${job.id}`, { headers: { Accept: 'application/json' } });
        jobs.value = jobs.value.filter(item => item.id !== job.id);
        window.SuccesToast?.fire({ title: data.message });
    } catch (error) {
        window.ErrorToast?.fire({ title: error.response?.data?.message || 'Unable to archive job posting.' });
    } finally {
        archivingId.value = null;
    }
}
onBeforeUnmount(() => { editor?.remove(); if (bannerPreview.value) URL.revokeObjectURL(bannerPreview.value); });
</script>

<style scoped>
.job-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1.5rem}.job-card{display:flex;flex-direction:column;min-width:0;overflow:hidden;background:var(--bs-secondary-bg);border:1px solid var(--bs-border-color);border-radius:18px;box-shadow:0 4px 16px rgba(0,0,0,.08);transition:transform .2s ease,box-shadow .2s ease}.job-card:hover{transform:translateY(-3px);box-shadow:0 12px 28px rgba(0,0,0,.14)}.job-card-image{position:relative;height:210px;overflow:hidden;background:linear-gradient(135deg,var(--bs-primary),#6f42c1)}.job-card-image:after{content:"";position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,.08),rgba(0,0,0,.3))}.job-card-image img{width:100%;height:100%;object-fit:cover}.job-card-placeholder{display:grid;place-items:center;height:100%;color:#fff;font-size:3.5rem}.job-status{position:absolute;z-index:1;top:1rem;right:1rem;display:inline-flex;align-items:center;gap:.45rem;padding:.45rem .75rem;color:#fff;border-radius:999px;font-size:.75rem;font-weight:700;box-shadow:0 4px 12px rgba(0,0,0,.25)}.job-status i{font-size:.45rem}.job-card-content{flex:1;padding:1.4rem}.job-tags{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:.9rem}.job-tag{padding:.32rem .7rem;color:#fff;background:var(--bs-primary);border-radius:999px;font-size:.72rem;font-weight:600}.job-tag-secondary{background:var(--bs-info);color:var(--bs-dark)}.job-card-title{margin-bottom:.55rem;font-size:1.3rem;font-weight:700}.job-card-description{display:-webkit-box;min-height:3em;margin-bottom:1.25rem;overflow:hidden;color:var(--bs-secondary-color);line-height:1.5;-webkit-box-orient:vertical;-webkit-line-clamp:2}.job-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem}.job-meta-item{display:flex;align-items:center;gap:.7rem;min-width:0;padding:.75rem;background:var(--bs-tertiary-bg);border:1px solid var(--bs-border-color);border-radius:12px}.job-meta-wide{grid-column:1/-1}.job-meta-icon{display:grid;place-items:center;flex:0 0 34px;width:34px;height:34px;color:var(--bs-primary);background:color-mix(in srgb,var(--bs-primary) 12%,transparent);border-radius:10px}.job-meta-item span:last-child{display:flex;min-width:0;flex-direction:column}.job-meta-item small{color:var(--bs-secondary-color);font-size:.68rem;text-transform:uppercase;letter-spacing:.04em}.job-meta-item strong{overflow:hidden;font-size:.86rem;text-overflow:ellipsis;white-space:nowrap}.job-card-footer{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.4rem;background:var(--bs-tertiary-bg);border-top:1px solid var(--bs-border-color)}.applicant-target{display:flex;flex-direction:column}.applicant-target small{color:var(--bs-secondary-color);font-size:.7rem;text-transform:uppercase}.applicant-target strong{font-size:1rem}.job-actions{display:flex;gap:.5rem}.job-actions .btn{white-space:nowrap}.empty-jobs{grid-column:1/-1;padding:5rem;text-align:center}.editor-wrap{position:relative;min-height:500px}.editor-loading{position:absolute;inset:0;z-index:2;display:flex;gap:.75rem;align-items:center;justify-content:center;background:var(--bs-body-bg)}.banner-preview{overflow:hidden;border:1px solid var(--bs-border-color);border-radius:.5rem;background:var(--bs-secondary-bg)}.banner-preview img{display:block;width:100%;max-height:360px;object-fit:contain}.selected-files{display:flex;flex-wrap:wrap;gap:.5rem}@media(max-width:575.98px){.job-grid{grid-template-columns:1fr}.job-meta{grid-template-columns:1fr}.job-meta-wide{grid-column:auto}.job-card-footer{align-items:stretch;flex-direction:column}.job-actions .btn:first-child{flex:1}}
</style>
