<template>
    <div
        class="notes-container"
        :style="{ left: notesPos.x + 'px', top: notesPos.y + 'px' }"
        :class="{ 'dark-mode': isDarkMode }"
    >
        <div class="notes-header text-uppercase" @mousedown="startDrag">
            <div class="d-flex align-items-center">
                <div
                    style="width: 30px; height: 30px"
                    class="bg-warning rounded-2 me-3 d-flex justify-content-center align-items-center"
                >
                    <i class="fa-solid fa-pencil"></i>
                </div>
                <div class="fw-bold text-warning text-uppercase">Notes</div>
                <button
                    class="btn-close"
                    @click="closeNotes"
                    aria-label="Close"
                ></button>
            </div>
        </div>
        <div v-if="!editingNote" class="notes-content">
            <div class="notes-list">
                <div
                    v-for="note in notes"
                    :key="note.id"
                    class="note-item"
                    @click="viewOrEditNote(note)"
                >
                    <small class="note-title text-clamp-1 text-uppercase">{{
                        note.title || "Untitled"
                    }}</small>
                    <span>
                        <i
                            v-if="note.hasPin"
                            class="fa-solid fa-lock ms-2 text-warning"
                        ></i>
                    </span>
                    <div class="note-actions">
                        <button
                            class="btn btn-sm btn-outline-primary"
                            @click.stop="viewOrEditNote(note)"
                        >
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button
                            class="btn btn-sm btn-outline-danger"
                            @click.stop="confirmDelete(note)"
                        >
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div
                    v-if="notes.length === 0"
                    class="btn text-muted text-center w-100"
                >
                    No notes yet. Click Add to start.
                </div>
            </div>
            <div class="notes-footer pb-2">
                <button
                    class="btn btn-primary btn-sm text-uppercase px-3"
                    @click="addNote"
                >
                    Add Note
                </button>
            </div>
        </div>
        <div v-else class="notes-content">
            <div class="note-editor">
                <input
                    v-model="currentNote.title"
                    class="form-control mb-2"
                    placeholder="Note Title"
                    :class="{ 'is-invalid': errors.title }"
                />
                <div v-if="errors.title" class="invalid-feedback">
                    {{ errors.title[0] }}
                </div>
                <textarea
                    v-model="currentNote.content"
                    class="form-control mb-2"
                    rows="6"
                    placeholder="Note Content"
                    :class="{ 'is-invalid': errors.content }"
                ></textarea>
                <div v-if="errors.content" class="invalid-feedback">
                    {{ errors.content[0] }}
                </div>
                <div class="form-check mb-2 mt-3">
                    <input
                        v-model="currentNote.hasPin"
                        class="form-check-input"
                        type="checkbox"
                        id="hasPin"
                    />
                    <label class="form-check-label" for="hasPin"
                        >Protect with PIN</label
                    >
                </div>
                <input
                    v-if="currentNote.hasPin"
                    v-model="currentNote.pin"
                    class="form-control mb-2"
                    type="password"
                    placeholder="4-digit PIN"
                    maxlength="4"
                    :class="{ 'is-invalid': errors.pin }"
                />
                <div v-if="errors.pin" class="invalid-feedback">
                    {{ errors.pin[0] }}
                </div>
                <div class="editor-actions mt-3">
                    <button
                        class="btn btn-dark px-3 text-uppercase fw-medium btn-sm"
                        @click="cancelEdit"
                    >
                        Cancel
                    </button>
                    <button
                        class="btn btn-primary px-3 text-uppercase fw-bold btn-sm"
                        @click="saveNote"
                    >
                        Save
                    </button>
                </div>
            </div>
        </div>

        <!-- PIN Modal -->
        <div v-if="showPinModal" class="modal-overlay" @click="closePinModal">
            <div class="modal-content" @click.stop>
                <h5>Enter PIN</h5>
                <input
                    v-model="enteredPin"
                    class="form-control mb-2"
                    type="password"
                    placeholder="4-digit PIN"
                    maxlength="4"
                    :class="{ 'is-invalid': pinError }"
                />
                <div v-if="pinError" class="invalid-feedback">
                    {{ pinError }}
                </div>
                <button class="btn btn-primary" @click="submitPin">
                    Submit
                </button>
                <button class="btn btn-secondary ms-2" @click="closePinModal">
                    Cancel
                </button>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="modal-overlay" @click="cancelDelete">
            <div class="modal-content" @click.stop>
                <h5>Confirm Delete</h5>
                <p>Are you sure you want to delete this note?</p>
                <button class="btn btn-danger" @click="proceedDelete">
                    Delete
                </button>
                <button class="btn btn-secondary ms-2" @click="cancelDelete">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

const NOTES_POS_KEY = "notes_pos";
const API_BASE = "/api/employee/notes"; // Base URL for notes API

export default {
    name: "Notes",
    props: {
        isDarkMode: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        const token = localStorage.getItem("auth_token");
        return {
            token,
            notes: [],
            notesPos: { x: 100, y: 100 },
            dragging: false,
            dragStart: { x: 0, y: 0 },
            editingNote: false,
            currentNote: {
                id: null,
                title: "",
                content: "",
                hasPin: false,
                pin: "",
            },
            errors: { title: "", content: "", pin: "" }, // Validation errors
            showPinModal: false,
            enteredPin: "",
            pinError: "", // PIN-specific error
            pendingAction: null, // { type: 'view|edit|delete', note: {} }
            showDeleteModal: false,
            noteToDelete: null,
        };
    },
    mounted() {
        this.notesPos = JSON.parse(
            localStorage.getItem(NOTES_POS_KEY) || '{"x":100,"y":100}',
        );
        this.loadNotes();
    },
    beforeUnmount() {
        document.removeEventListener("mousemove", this.onDrag);
        document.removeEventListener("mouseup", this.stopDrag);
    },
    methods: {
        async loadNotes() {
            if (!this.token) return; // Skip if no token
            try {
                const response = await axios.get(API_BASE, {
                    headers: { Authorization: `Bearer ${this.token}` },
                });
                this.notes = response.data;
            } catch (error) {
                console.error("Error loading notes:", error);
            }
        },
        closeNotes() {
            this.$emit("close");
        },
        startDrag(e) {
            this.dragging = true;
            this.dragStart = {
                x: e.clientX - this.notesPos.x,
                y: e.clientY - this.notesPos.y,
            };
            document.addEventListener("mousemove", this.onDrag);
            document.addEventListener("mouseup", this.stopDrag);
        },
        onDrag(e) {
            if (!this.dragging) return;
            this.notesPos.x = e.clientX - this.dragStart.x;
            this.notesPos.y = e.clientY - this.dragStart.y;
        },
        stopDrag() {
            this.dragging = false;
            document.removeEventListener("mousemove", this.onDrag);
            document.removeEventListener("mouseup", this.stopDrag);
            localStorage.setItem(NOTES_POS_KEY, JSON.stringify(this.notesPos));
        },
        addNote() {
            this.currentNote = {
                id: null,
                title: "",
                content: "",
                hasPin: false,
                pin: "",
            };
            this.errors = { title: "", content: "", pin: "" }; // Clear errors
            this.editingNote = true;
        },
        viewOrEditNote(note) {
            if (note.hasPin) {
                this.pendingAction = { type: "view", note };
                this.showPinModal = true;
            } else {
                this.editNote(note);
            }
        },
        async editNote(note) {
            if (!this.token) return;
            try {
                const response = await axios.get(`${API_BASE}/${note.id}`, {
                    headers: { Authorization: `Bearer ${this.token}` },
                });
                this.currentNote = response.data;
                this.errors = { title: "", content: "", pin: "" }; // Clear errors
                this.editingNote = true;
            } catch (error) {
                console.error("Error loading note:", error);
            }
        },
        confirmDelete(note) {
            if (note.hasPin) {
                this.pendingAction = { type: "delete", note };
                this.showPinModal = true;
            } else {
                this.showDeleteModal = true;
                this.noteToDelete = note;
            }
        },
        async proceedDelete() {
            if (!this.token) return;
            try {
                await axios.delete(`${API_BASE}/${this.noteToDelete.id}`, {
                    headers: { Authorization: `Bearer ${this.token}` },
                });
                this.notes = this.notes.filter(
                    (n) => n.id !== this.noteToDelete.id,
                );
                this.showDeleteModal = false;
            } catch (error) {
                console.error("Error deleting note:", error);
            }
        },
        cancelDelete() {
            this.showDeleteModal = false;
            this.noteToDelete = null;
        },
        async saveNote() {
            if (!this.token) return;
            try {
                const data = {
                    title: this.currentNote.title,
                    content: this.currentNote.content,
                    hasPin: this.currentNote.hasPin,
                    pin: this.currentNote.hasPin ? this.currentNote.pin : null,
                };
                const config = {
                    headers: { Authorization: `Bearer ${this.token}` },
                };
                if (this.currentNote.id) {
                    await axios.put(
                        `${API_BASE}/${this.currentNote.id}`,
                        data,
                        config,
                    );
                } else {
                    await axios.post(API_BASE, data, config);
                }
                this.errors = { title: "", content: "", pin: "" }; // Clear errors on success
                this.loadNotes();
                this.editingNote = false;
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data.errors || {}; // Set validation errors
                } else {
                    console.error("Error saving note:", error);
                }
            }
        },
        cancelEdit() {
            this.editingNote = false;
            this.errors = { title: "", content: "", pin: "" }; // Clear errors
        },
        submitPin() {
            if (this.pendingAction) {
                const { type, note } = this.pendingAction;
                if (type === "view") {
                    this.editNoteWithPin(note, this.enteredPin);
                } else if (type === "delete") {
                    this.deleteWithPin(note, this.enteredPin);
                }
            }
            this.closePinModal();
        },
        async editNoteWithPin(note, pin) {
            if (!this.token) return;
            try {
                const response = await axios.get(
                    `${API_BASE}/${note.id}?pin=${pin}`,
                    {
                        headers: { Authorization: `Bearer ${this.token}` },
                    },
                );
                this.currentNote = response.data;
                this.pinError = ""; // Clear PIN error
                this.editingNote = true;
            } catch (error) {
                if (error.response && error.response.status === 403) {
                    this.pinError = "Invalid PIN"; // Set PIN error
                } else {
                    console.error("Error loading note:", error);
                }
            }
        },
        async deleteWithPin(note, pin) {
            if (!this.token) return;
            try {
                await axios.delete(`${API_BASE}/${note.id}?pin=${pin}`, {
                    headers: { Authorization: `Bearer ${this.token}` },
                });
                this.notes = this.notes.filter((n) => n.id !== note.id);
                this.pinError = ""; // Clear PIN error
            } catch (error) {
                if (error.response && error.response.status === 403) {
                    this.pinError = "Invalid PIN"; // Set PIN error
                } else {
                    console.error("Error deleting note:", error);
                }
            }
        },
        closePinModal() {
            this.showPinModal = false;
            this.enteredPin = "";
            this.pinError = ""; // Clear PIN error
            this.pendingAction = null;
        },
    },
};
</script>

<style lang="scss" scoped>
@media (max-width: 767.98px) {
    .notes-container {
        min-width: 280px !important;
        max-width: 90vw !important;
    }
}

.notes-container {
    position: fixed;
    min-width: 420px;
    max-width: 400px;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 10000;
    overflow: hidden;

    &.dark-mode {
        background-color: #343a40;
        border-color: #495057;
        color: #fff;

        .note-item {
            border-bottom-color: #495057;
        }

        .btn-outline-primary {
            border-color: #007bff;
            color: #007bff;
        }

        .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
        }

        .btn-link {
            color: #6c757d !important;
        }
    }
}

.notes-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    background-color: inherit !important;
    border-bottom: 1px solid #ddd;
    cursor: move;
    font-weight: 600;
    position: relative;

    .dark-mode & {
        background-color: inherit !important;
        border-bottom-color: #6c757d;
        color: #fff;
    }

    .btn-close {
        position: absolute;
        right: 14px;
        font-size: 0.8rem;
    }
}

.notes-content {
    padding: 15px;
    max-height: 400px;
    overflow-y: auto;
}

.notes-list {
    margin-bottom: 15px;
}

.note-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    cursor: pointer;

    &:hover {
        background-color: #f8f9fa;

        .dark-mode & {
            background-color: #495057;
        }
    }

    .note-title {
        flex: 1;
        font-weight: 500;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .note-actions {
        display: flex;
        gap: 5px;
    }
}

.notes-footer {
    text-align: center;
}

.note-editor {
    .form-control {
        border-radius: 4px;
        border: 1px solid #ddd;

        .dark-mode & {
            background-color: #495057;
            border-color: #6c757d;
            color: #fff;
        }
    }

    .editor-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10001;
}

.modal-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    max-width: 300px;
    width: 100%;
}

.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: -0.25rem;
    font-size: 0.875em;
    color: #dc3545;
}
</style>
