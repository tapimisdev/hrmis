<template>
    <div ref="modalRef" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Accomplishment Report</h5>
                    <button
                        type="button"
                        class="btn-close"
                        @click="close"
                    ></button>
                </div>
                <div class="modal-body">
                    <div v-if="isLoading" class="loading-overlay">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 py-5">Loading editor...</p>
                    </div>
                    <textarea id="tinyEditor"></textarea>
                    <div v-if="error" class="text-danger mt-2">
                        Please enter a report.
                    </div>
                </div>
                <div class="modal-footer d-flex gap-2">
                    <button
                        class="btn btn-danger text-uppercase"
                        @click="close"
                    >
                        Close
                    </button>
                    <button
                        class="btn btn-primary text-uppercase fw-medium"
                        @click="submit"
                    >
                        Proceed
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from "vue";
import { Modal } from "bootstrap";

const props = defineProps({ show: Boolean });
const emit = defineEmits(["close", "submit"]);

const modalRef = ref(null);
const error = ref(false);
const isLoading = ref(false);
let modalInstance = null;
let editorInstance = null;

onMounted(() => {
    modalInstance = new Modal(modalRef.value);

    modalRef.value.addEventListener("hidden.bs.modal", () => {
        emit("close");
        error.value = false;
        if (editorInstance) {
            editorInstance.remove();
            editorInstance = null;
        }
    });
});

watch(
    () => props.show,
    async (value) => {
        if (!modalInstance) return;

        if (value) {
            modalInstance.show();
            await nextTick();

            if (!editorInstance) {
                isLoading.value = true;

                if (typeof tinymce === "undefined") {
                    await loadTinyMCE();
                }

                try {
                    editorInstance = await tinymce.init({
                        selector: "#tinyEditor",
                        height: 600,
                        skin: "oxide-dark",
                        content_css: "dark",
                        menubar:
                            "file edit view insert format table tools help",
                        plugins: [
                            "advlist autolink lists link image charmap print preview anchor",
                            "searchreplace visualblocks code fullscreen",
                            "insertdatetime media table paste code help wordcount",
                        ],
                        toolbar:
                            "undo redo | bold italic underline | bullist numlist | outdent indent | link image | table | code",
                        setup(editor) {
                            editor.on("init", () => {
                                isLoading.value = false;
                            });
                        },
                    });
                } catch (err) {
                    isLoading.value = false;
                }
            } else {
                isLoading.value = false;
            }
        } else {
            modalInstance.hide();
        }
    },
);

async function loadTinyMCE() {
    return new Promise((resolve, reject) => {
        if (window.tinymce) {
            resolve();
            return;
        }

        const script = document.createElement("script");
        script.src =
            "https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js";
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

function isEmpty(content) {
    return content.replace(/<[^>]*>?/gm, "").trim() === "";
}

function close() {
    modalInstance.hide();
}

function submit() {
    const editor = window.tinymce.get("tinyEditor");
    const content = editor ? editor.getContent() : "";

    if (isEmpty(content)) {
        error.value = true;
        return;
    }

    emit("submit", content);
    modalInstance.hide();
}
</script>

<style scoped>
.tox .tox-edit-area__iframe {
    height: 500px !important;
}

.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
</style>
