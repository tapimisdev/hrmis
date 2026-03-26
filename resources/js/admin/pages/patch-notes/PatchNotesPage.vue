<template>
    <div class="shell">
        <div class="topbar">
            <a class="brand" href="/hris-home">
                <div class="brand-mark">
                    <i class="fa-solid fa-code-branch"></i>
                </div>
                <div>
                    <div class="fw-bold">Patch Notes</div>
                    <div style="color: var(--text-muted); font-size: 0.92rem;">Standalone release summary</div>
                </div>
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="/" class="btn btn-sm rounded-pill px-3 py-2 border-0 shadow-sm"
                   style="background: var(--bs-body-bg); color: var(--bs-body-color);">
                    <i class="fa-solid fa-house me-1"></i>
                    Return Home
                </a>
            </div>
        </div>

        <section class="hero">
            <div class="hero-meta">
                <span class="pill"><i class="fa-solid fa-clipboard-list"></i> Release log</span>
                <span class="pill"><i class="fa-regular fa-calendar"></i> Updated {{ releasedAt }}</span>
                <span class="pill"><i class="fa-solid fa-shield-heart"></i> Production-ready changes</span>
            </div>
            <h1 class="mt-3">Recent platform patches, sorted from latest to oldest.</h1>
            <p class="hero-copy mb-0">
                This page is intentionally standalone. It reads from <code>public/patches/patches.csv</code>,
                presents the entries in reverse chronological order, and keeps the language clean for a release note format.
            </p>
        </section>

        <section class="filter-panel">
            <form method="GET" :action="pageUrl" class="row g-3 align-items-end">
                <div class="col-lg-3">
                    <div class="filter-label">Search</div>
                    <input
                        type="text"
                        name="q"
                        v-model="form.q"
                        class="form-control"
                        placeholder="Search issue key, summary, assignee..."
                    >
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="filter-label">Type</div>
                    <select name="type" v-model="form.type" class="form-select">
                        <option value="">All types</option>
                        <option v-for="option in filterOptions.types" :key="option" :value="option">
                            {{ option }}
                        </option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="filter-label">Status</div>
                    <select name="status" v-model="form.status" class="form-select">
                        <option value="">All status</option>
                        <option v-for="option in filterOptions.statuses" :key="option" :value="option">
                            {{ option }}
                        </option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="filter-label">Priority</div>
                    <select name="priority" v-model="form.priority" class="form-select">
                        <option value="">All priorities</option>
                        <option v-for="option in filterOptions.priorities" :key="option" :value="option">
                            {{ option }}
                        </option>
                    </select>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="filter-label">Assignee</div>
                    <select name="assignee" v-model="form.assignee" class="form-select">
                        <option value="">All assignees</option>
                        <option v-for="option in filterOptions.assignees" :key="option" :value="option">
                            {{ option }}
                        </option>
                    </select>
                </div>
                <div class="col-12 col-lg-2">
                    <div class="filter-actions">
                        <a :href="pageUrl" class="btn btn-outline-secondary w-100">Reset</a>
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
        </section>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Notes</div>
                <div class="stat-value">{{ stats.notes }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Done</div>
                <div class="stat-value">{{ stats.done }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Testing</div>
                <div class="stat-value">{{ stats.testing }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">In Progress</div>
                <div class="stat-value">{{ stats.in_progress }}</div>
            </div>
        </section>

        <section class="notes-list">
            <article
                v-for="note in patchNotes"
                :key="note.ticket"
                class="note-card"
                :style="{ '--accent': accentColor(note.tone) }"
            >
                <div class="note-header">
                    <div>
                        <div class="note-list-meta mb-2">
                            <span class="tag">{{ note.ticket }}</span>
                            <span class="tag">{{ note.type }}</span>
                            <span class="tag badge-status">{{ note.status }}</span>
                            <span class="tag">{{ note.priority }}</span>
                        </div>
                        <div class="note-title note-title--upper">{{ note.title }}</div>
                        <p class="note-summary">{{ note.summary }}</p>
                    </div>
                </div>

                <div class="tag-row">
                    <span class="tag"><i class="fa-regular fa-user"></i> {{ note.assignee }}</span>
                    <span class="tag"><i class="fa-regular fa-address-card"></i> {{ note.reporter }}</span>
                </div>

                <div class="note-foot">
                    <div>Created {{ note.created_label }}</div>
                    <div>Updated {{ note.updated_label }}</div>
                </div>
            </article>
        </section>

        <div v-if="!patchNotes.length" class="note-card mt-3">
            <div class="text-center py-4">
                <div class="fw-semibold mb-2">No patch notes matched your filters.</div>
                <div style="color: var(--text-muted);">Try a different keyword or clear the filters.</div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "PatchNotesPage",
    props: {
        patchNotes: {
            type: Array,
            default: () => [],
        },
        stats: {
            type: Object,
            default: () => ({ notes: 0, done: 0, testing: 0, in_progress: 0 }),
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
        filterOptions: {
            type: Object,
            default: () => ({ types: [], statuses: [], priorities: [], assignees: [] }),
        },
        releasedAt: {
            type: String,
            default: "",
        },
        pageUrl: {
            type: String,
            default: "/patch-notes",
        },
    },
    data() {
        return {
            form: {
                q: this.filters.q ?? "",
                type: this.filters.type ?? "",
                status: this.filters.status ?? "",
                priority: this.filters.priority ?? "",
                assignee: this.filters.assignee ?? "",
            },
        };
    },
    methods: {
        accentColor(tone) {
            return {
                emerald: "#10b981",
                amber: "#f59e0b",
                sky: "#0ea5e9",
                rose: "#f43f5e",
            }[tone] ?? "#6366f1";
        },
    },
};
</script>
