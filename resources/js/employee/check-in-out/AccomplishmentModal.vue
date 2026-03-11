<script setup>
import { ref, onMounted, watch } from "vue";
import { Modal } from "bootstrap";

const props = defineProps({
    show: Boolean
});

const emit = defineEmits(["close", "submit"]);

const modalRef = ref(null);
const error = ref(false);

let modalInstance = null;
let editorInstance = null;

onMounted(() => {
    modalInstance = new Modal(modalRef.value);

    modalRef.value.addEventListener("hidden.bs.modal", () => {
        emit("close");
    });
});

watch(() => props.show, async (value) => {
    if (!modalInstance) return;

    if (value) {
        error.value = false;
        modalInstance.show();

        const insertDefaultTable = (editor) => {
            if (editor.getData().trim()) return;

            editor.model.change(writer => {
                const root = editor.model.document.getRoot();

                for (let i = root.childCount - 1; i >= 0; i--) {
                    writer.remove(root.getChild(i));
                }

                const table = writer.createElement("table");

                const headers = [
                    "No.",
                    "Accomplishment / Activity",
                    "Details",
                    "Status / Remarks",
                    "MOV (Means of Verification)"
                ];

                const headerRow = writer.createElement("tableRow");

                headers.forEach(text => {
                    const cell = writer.createElement("tableCell");
                    const paragraph = writer.createElement("paragraph");
                    const textNode = writer.createText(text);

                    writer.setAttribute("bold", true, textNode);
                    writer.setAttribute("contentEditable", false, paragraph);

                    writer.append(textNode, paragraph);
                    writer.append(paragraph, cell);
                    writer.append(cell, headerRow);
                });

                writer.append(headerRow, table);

                for (let i = 0; i < 1; i++) {
                    const row = writer.createElement("tableRow");

                    for (let j = 0; j < headers.length; j++) {
                        const cell = writer.createElement("tableCell");
                        const paragraph = writer.createElement("paragraph");

                        if (j === 0) {
                            const textNode = writer.createText((i + 1 + '.').toString());
                            writer.append(textNode, paragraph);
                        }

                        writer.append(paragraph, cell);
                        writer.append(cell, row);
                    }

                    writer.append(row, table);
                }

                const position = editor.model.document.selection.getFirstPosition();
                if (position) {
                    editor.model.insertContent(table, position);
                } else {
                    editor.model.insertContent(table, root.getFirstPosition());
                }
            });
        };

        if (!editorInstance) {
            try {
                editorInstance = await ClassicEditor.create(
                    document.querySelector("#content"),
                    {
                        placeholder: "Enter accomplishment report",
                        toolbar: [
                            "bold",
                            "italic",
                            "underline",
                            "|",
                            "bulletedList",
                            "numberedList",
                            "|",
                            "link",
                        ],
                    }
                );

                insertDefaultTable(editorInstance);

                editorInstance.model.document.on("change:data", () => {
                    editorInstance.model.change(writer => {
                        const root = editorInstance.model.document.getRoot();
                        const table = root.getChild(0);

                        if (!table || table.name !== "table") {
                            insertDefaultTable(editorInstance);
                        } else {
                            const firstRow = table.getChild(0);
                            if (!firstRow || firstRow.name !== "tableRow") {
                                insertDefaultTable(editorInstance);
                            }
                        }

                        for (let i = 1; i < root.childCount; i++) {
                            writer.remove(root.getChild(i));
                        }
                    });
                });
            } catch (err) {
                console.error("CKEditor initialization error:", err);
            }
        } else {
            insertDefaultTable(editorInstance);
        }
    } else {
        modalInstance.hide();
    }
});

function addTableRow() {
    if (!editorInstance) return;

    editorInstance.model.change(writer => {
        const root = editorInstance.model.document.getRoot();
        const table = Array.from(root.getChildren()).find(el => el.name === "table");
        if (!table) return;

        const columnCount = table.getChild(0).childCount;
        const newRow = writer.createElement("tableRow");

        for (let i = 0; i < columnCount; i++) {
            const cell = writer.createElement("tableCell");
            const paragraph = writer.createElement("paragraph");

            writer.append(paragraph, cell);
            writer.append(cell, newRow);
        }

        writer.append(newRow, table);
        updateRowNumbers(writer, table);
    });
}

function removeTableRow() {
    if (!editorInstance) return;

    editorInstance.model.change(writer => {
        const root = editorInstance.model.document.getRoot();
        const table = Array.from(root.getChildren()).find(el => el.name === "table");
        if (!table || table.childCount <= 2) return; 

        const lastRow = table.getChild(table.childCount - 1);
        writer.remove(lastRow);

        updateRowNumbers(writer, table);
    });
}

function updateRowNumbers(writer, table) {
    for (let i = 1; i < table.childCount; i++) { 
        const row = table.getChild(i);
        const cell = row.getChild(0);
        const paragraph = cell.getChild(0);

        if (paragraph) {
            writer.remove(paragraph);
        }

        const newParagraph = writer.createElement("paragraph");
        const textNode = writer.createText(i.toString());
        writer.append(textNode, newParagraph);
        writer.append(newParagraph, cell);
    }
}

function close() {
    modalInstance.hide();
}

function submit() {
    const data = editorInstance ? editorInstance.getData() : "";

    if (!data.trim()) {
        error.value = true;
        return;
    }
    
    emit("submit", data);
    modalInstance.hide();
}
</script>

<template>
    <div class="modal fade" ref="modalRef" id="reportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-bold">Accomplishment Report</h5>
                    <button type="button" class="btn-close" @click="close" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="content"></div>
                    <div class="mt-3 d-flex justify-content-between">
                        <button type="button" class="btn btn-sm btn-danger" @click="removeTableRow">
                            <i class="fa-solid fa-minus"></i>
                        </button>
                         <button type="button" class="btn btn-sm btn-primary me-2" @click="addTableRow">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <div v-if="error" class="alert alert-danger mt-3">
                        Please fill in the report content.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="close">Cancel</button>
                    <button type="button" class="btn btn-primary" @click="submit">Submit</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style>

.ck-widget__type-around__button {
  display: none !important;
}

</style>