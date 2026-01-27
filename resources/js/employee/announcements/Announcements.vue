<template>
    <div class="position-relative tem">
        <!-- Search Bar -->
        <div
            class="mb-4 search-bar d-flex justify-content-center justify-content-md-end"
        >
            <div class="input-group" style="max-width: 320px">
                <input
                    v-model="searchQuery"
                    type="text"
                    class="form-control"
                    placeholder="Search announcements..."
                    @input="handleSearch"
                />
                <button
                    class="btn btn-outline-secondary"
                    type="button"
                    @click="clearSearch"
                    v-if="searchQuery"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
                <button
                    class="btn btn-primary"
                    type="button"
                    @click="performSearch"
                >
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </div>

        <!-- Announcements Grid -->
        <div v-if="loading" class="announcements-grid">
            <SkeletonCard
                v-for="n in 6"
                :key="n"
            />
        </div>
        <div v-else class="announcements-grid">
            <AnnouncementCard
                v-for="announcement in announcements.data"
                :key="announcement.id"
                :announcement="announcement"
            />
        </div>

        <!-- No Results Message -->
        <div v-if="!loading && announcements.data.length === 0" class="text-center py-5">
            <p class="text-muted">No announcements found.</p>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-end">
            <nav aria-label="Announcements Pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <!-- Previous Button -->
                    <li
                        :class="[
                            'page-item',
                            { disabled: !announcements.prev_page_url },
                        ]"
                    >
                        <button
                            class="page-link"
                            @click="goToPage(announcements.current_page - 1)"
                            :disabled="!announcements.prev_page_url"
                        >
                            Previous
                        </button>
                    </li>

                    <!-- Page Numbers -->
                    <li
                        v-for="page in totalPages"
                        :key="page"
                        :class="[
                            'page-item',
                            { active: page === announcements.current_page },
                        ]"
                    >
                        <button class="page-link" @click="goToPage(page)">
                            {{ page }}
                        </button>
                    </li>

                    <!-- Next Button -->
                    <li
                        :class="[
                            'page-item',
                            { disabled: !announcements.next_page_url },
                        ]"
                    >
                        <button
                            class="page-link"
                            @click="goToPage(announcements.current_page + 1)"
                            :disabled="!announcements.next_page_url"
                        >
                            Next
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</template>

<script>
import AnnouncementCard from "./AnnouncementCard.vue";
import SkeletonCard from "./Skeleton/SkeletonCard.vue";
import axios from "axios";

export default {
    name: "Announcements",
    components: { AnnouncementCard, SkeletonCard },
    data() {
        return {
            announcements: {
                data: [],
                current_page: 1,
                last_page: 1,
                prev_page_url: null,
                next_page_url: null,
            },
            searchQuery: "",
            searchTimeout: null,
            loading: false, // Added loading state
        };
    },
    computed: {
        totalPages() {
            return Array.from(
                { length: this.announcements.last_page },
                (_, i) => i + 1
            );
        },
    },
    mounted() {
        // Read the page number and search query from URL
        const params = new URLSearchParams(window.location.search);
        const page = parseInt(params.get("page")) || 1;
        const search = params.get("search") || "";

        this.searchQuery = search;
        this.fetchAnnouncements(
            `/employee/announcements?page=${page}${
                search ? `&search=${search}` : ""
            }`
        );
    },
    methods: {
        async fetchAnnouncements(url = "/employee/announcements") {
            try {
                this.loading = true; // Set loading before fetch
                const response = await axios.get(url, {
                    headers: { Authorization: `Bearer ${this.token}` },
                });

                // Assign the full response to keep pagination info
                this.announcements = response.data.data;

                // Update URL without reloading the page
                const params = new URLSearchParams();
                params.set("page", this.announcements.current_page);
                if (this.searchQuery) {
                    params.set("search", this.searchQuery);
                }
                const newUrl = `${
                    window.location.pathname
                }?${params.toString()}`;
                window.history.pushState({}, "", newUrl);
            } catch (error) {
                console.error("Error fetching announcements:", error);
            } finally {
                this.loading = false; // Always reset loading
            }
        },
        goToPage(page) {
            if (page >= 1 && page <= this.announcements.last_page) {
                this.loading = true; // Set loading before fetch
                const url = `/employee/announcements?page=${page}${
                    this.searchQuery ? `&search=${this.searchQuery}` : ""
                }`;
                this.fetchAnnouncements(url);
            }
        },
        handleSearch() {
            // Debounce search - wait 500ms after user stops typing
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.performSearch();
            }, 500);
        },
        performSearch() {
            this.loading = true; // Set loading before fetch
            // Reset to page 1 when searching
            const url = `/employee/announcements?page=1${
                this.searchQuery ? `&search=${this.searchQuery}` : ""
            }`;
            this.fetchAnnouncements(url);
        },
        clearSearch() {
            this.searchQuery = "";
            this.performSearch();
        },
    },
    beforeUnmount() {
        // Clear timeout when component is destroyed
        clearTimeout(this.searchTimeout);
    },
};
</script>

<style lang="scss" scoped>
@import "./../../../sass/variables";

@media (max-width: 898px) {
    .tem {
        margin-top: 140px;
    }
}

.search-bar {
    position: absolute;
    top: -78px;
    right: 0;

    @media (max-width: 768px) {
        position: relative;
        top: 0; // reset
        right: auto;
        margin-top: -60px;

        .announcements-grid {
            position: relative;
            margin-top: 200px; // use spacing, not top
        }
    }
}

.announcements-section {
    margin-bottom: 2rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    padding: 0 0.25rem;

    .section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--bs-body-color);
        margin: 0 0 0.25rem 0;
    }

    .section-subtitle {
        font-size: 0.85rem;
        color: var(--bs-tertiary-color);
        margin: 0;
    }

    .view-all-link {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--bs-primary);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.2s ease;

        &:hover {
            gap: 0.5rem;
            color: var(--bs-primary);
        }

        i {
            font-size: 0.75rem;
        }
    }
}

.announcements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.25rem;
}

// Responsive
@media (max-width: 1200px) {
    .announcements-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
}

@media (max-width: 768px) {
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .announcements-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 1rem;
    }
}

@media (max-width: 576px) {
    .announcements-grid {
        grid-template-columns: 1fr;
    }
}
</style>