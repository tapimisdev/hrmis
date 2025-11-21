<template>
    <div>
        <Skeleton v-if="loading" class="mt-5" :rows="1" :columns="8" />
        <div v-else>
            <ul v-if="tabs.length != 0" class="nav nav-tabs">
                <li class="nav-item" v-for="(tab, index) in tabs" :key="tab.id">
                    <a
                        class="nav-link"
                        :class="tab_name === tab.tab_slug ? 'active' : ''"
                        @click="switchTab(tab.tab_slug)"
                        aria-current="page"
                    >
                        <i :class="tab.tab_icon" class="me-2"> </i>
                        {{ tab.tab_name }}
                    </a>
                </li>
            </ul>

            <div v-else>
                <div
                    class="d-flex justify-content-center align-items-center p-4"
                >
                    <div class="text-center">
                        <h5 class="mb-3">No tabs available</h5>
                        <p class="mb-4">
                            There are currently no tabs to display. Please add
                            tabs to manage module settings effectively.
                        </p>
                        <button class="btn btn-primary" @click="openModal">
                            <i class="fa-solid fa-plus me-2"></i>
                            Add Tab
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <ModalVue
            ref="taxModal"
            :headerIcon="'fa-solid fa-plus'"
            :title="'Add tab to ' + tab_name"
            :size="'modal-md'"
            subtitle="Manage the settings and options for this specific module with ease."
            type="default"
        >
            <FormTab
                @formSubmitted="
                    getTabs();
                    closeModal();
                "
                :submit_url="store_url"
            />
        </ModalVue>
    </div>
</template>

<script setup>
import axios from "axios";
import { defineProps, ref, onMounted, defineAsyncComponent } from "vue";

const Skeleton = defineAsyncComponent(() =>
    import("../../components/FormSkeletonVue.vue")
);

const ModalVue = defineAsyncComponent(() =>
    import("../../components/ModalVue.vue")
);

const FormTab = defineAsyncComponent(() => import("./FormTab.vue"));

const props = defineProps({
    store_url: {
        type: String,
        required: true,
    },
    slug: String,
});

let tabs = ref([]);
const tab_name = ref("");
const taxModal = ref(null);
const loading = ref(Boolean(true));

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    tab_name.value = params.get("tab");
    const btn = document.getElementById("create-btn");
    if (btn) btn.addEventListener("click", openModal);

    getTabs();
});

function getTabs() {
    loading.value = true;
    axios
        .get(`/admin/modules/${props.slug}?tab=${tab_name.value}`)
        .then((response) => {
            tabs.value = response.data.tabs;
        })
        .catch((error) => {
            console.error("Error fetching tabs:", error);
        })
        .finally(() => {
            loading.value = false;
        });
}

function switchTab(selectedTab) {
    tab_name.value = selectedTab;
    const params = new URLSearchParams(window.location.search);
    params.set("tab", selectedTab);
    const newUrl = `${window.location.pathname}?${params.toString()}`;
    window.history.replaceState({}, "", newUrl);
}

function openModal() {
    taxModal.value.open();
}

function closeModal() {
    taxModal.value.close();
}
</script>

<style scoped lang="scss">
.nav-link {
    cursor: pointer;
}
</style>
