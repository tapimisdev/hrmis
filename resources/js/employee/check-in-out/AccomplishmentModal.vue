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
                    <textarea id="tinyEditor"></textarea>
                    <div v-if="error" class="text-danger mt-2">
                        Please enter a report.
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="close">
                        Close
                    </button>
                    <button class="btn btn-primary" @click="submit">
                        Submit
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
let modalInstance = null;

onMounted(() => {
    modalInstance = new Modal(modalRef.value);

    modalRef.value.addEventListener("hidden.bs.modal", () => {
        emit("close");
        error.value = false;
        if (window.tinymce.get("tinyEditor")) {
            window.tinymce.get("tinyEditor").setContent("");
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

            if (!window.tinymce.get("tinyEditor")) {
                tinymce.init({
                    selector: "#tinyEditor",
                    height: 600,
                    skin: "oxide-dark", // Dark skin
                    content_css: "dark", // Dark content
                    menubar: "file edit view insert format table tools help",
                    plugins: [
                        "advlist autolink lists link image charmap print preview anchor",
                        "searchreplace visualblocks code fullscreen",
                        "insertdatetime media table paste code help wordcount",
                    ],
                    toolbar:
                        "undo redo | bold italic underline | bullist numlist | outdent indent | link image | table | code",
                    setup(editor) {
                        editor.on("init", () => {
                            console.log("TinyMCE dark mode initialized");
                        });
                    },
                });
            }
        } else {
            modalInstance.hide();
        }
    },
);

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
</style>
