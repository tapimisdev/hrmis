<template>
    <div
        class="widget-container"
        ref="notesEl"
        :style="isMobile ? { left: '50%', top: '50%', transform: 'translate(-50%, -50%)' } : { left: notesPos.x + 'px', top: notesPos.y + 'px' }"
        :class="{ 'dark-mode': isDarkMode }"
    >
        <div class="widget-header text-uppercase" @mousedown="startDrag">
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
        <!-- List Mode -->
        <div v-if="!editingNote && !viewingNote" class="widget-content">
            <div class="notes-list">
                <div
                    v-for="note in notes"
                    :key="note.id"
                    class="note-item"
                    @click="viewNote(note)"
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
                            @click.stop="viewNote(note)"
                        >
                            <i class="fa-solid fa-eye"></i>
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
                    class="btn text-muted text-center w-100 mt-5"
                >
                    No notes yet. Click Add to start.
                </div>
            </div>
            <div class="notes-footer px-5">
                <button
                    class="btn btn-primary btn-sm text-uppercase px-3 py-2"
                    @click="addNote"
                >
                    Add Note
                </button>
            </div>
        </div>
        <!-- View Mode -->
        <div v-else-if="viewingNote && !editingNote" class="widget-content">
            <div class="note-viewer">
               <div class="viewer-actions mb-4">
                    <button
                        class="btn btn-dark px-3 text-uppercase fw-medium btn-sm"
                        @click="backToList"
                    >
                        Back
                    </button>
                    <button
                        class="btn btn-primary px-3 text-uppercase fw-bold btn-sm"
                        @click="editFromView"
                    >
                        Edit
                    </button>
                    <button
                        class="btn btn-outline-danger px-3 text-uppercase fw-medium btn-sm"
                        @click="confirmDelete(currentNote)"
                    >
                        Delete
                    </button>
                </div>
                <h5 class="note-title-view">{{ currentNote.title || "Untitled" }}</h5>
                <p class="note-content-view">{{ currentNote.content }}</p>
                <div v-if="currentNote.hasPin" class="text-muted small mt-5">
                    <i class="fa-solid fa-lock me-2"></i> This note is protected with a PIN.
                </div>
            </div>
        </div>
        <!-- Edit/Create Mode -->
        <div v-else class="widget-content">
            <div class="note-editor">
                <input
                    v-model="currentNote.title"
                    class="form-control mb-2"
                    placeholder="Note Title"
                    :class="{ 'is-invalid': errors.title }"
                />
                <div v-if="errors.title" class="invalid-feedback mb-3">
                    {{ errors.title[0] }}
                </div>
                <textarea
                    v-model="currentNote.content"
                    class="form-control mb-2"
                    rows="6"
                    placeholder="Note Content"
                    :class="{ 'is-invalid': errors.content }"
                ></textarea>
                <div v-if="errors.content" class="invalid-feedback mb-3">
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
                    placeholder="4 or 6 digits PIN"
                    minlength="4"
                    maxlength="6"
                    :class="{ 'is-invalid': errors.pin }"
                />
                <div v-if="errors.pin" class="invalid-feedback mb-3">
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
                        :disabled="isSaving"
                    >
                        <span v-if="isSaving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        {{ isSaving ? 'Saving...' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- PIN Modal -->
        <div v-if="showPinModal" class="modal-overlay" @click="closePinModal">
            <div class="modal-content" @click.stop>
                <h5 class="text-uppercase fw-medium mb-3">Enter PIN</h5>
                <input
                    v-model="enteredPin"
                    class="form-control mb-2"
                    type="password"
                    placeholder="4 or 6 digits PIN"
                    minlength="4"
                    maxlength="6"
                    :class="{ 'is-invalid': pinError }"
                />
                <div v-if="pinError" class="invalid-feedback mb-3">
                    {{ pinError }}
                </div>
                <div class="mt-3 d-block w-100">
                    <button class="btn btn-primary w-100 mb-2" @click="submitPin" :disabled="isSubmittingPin">
                        <span v-if="isSubmittingPin" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        {{ isSubmittingPin ? 'Submitting...' : 'Submit' }}
                    </button>
                    <button class="btn btn-dark w-100" @click="closePinModal">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="modal-overlay" @click="cancelDelete">
            <div class="modal-content" @click.stop>
                <h5>Confirm Delete</h5>
                <p>Are you sure you want to delete this note?</p>
                <div class="mt-3 d-block w-100">
                  <button class="btn btn-danger w-100 mb-2" @click="proceedDelete" :disabled="isDeleting">
                      <span v-if="isDeleting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                      {{ isDeleting ? 'Deleting...' : 'Delete' }}
                  </button>
                  <button class="btn btn-secondary w-100" @click="cancelDelete">
                      Cancel
                  </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

const NOTES_POS_KEY = "notes_pos";
const API_BASE = "/api/employee/notes";

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
            isMobile: false,
            dragging: false,
            dragStart: { x: 0, y: 0 },
            editingNote: false,
            viewingNote: false,
            currentNote: {
                id: null,
                title: "",
                content: "",
                hasPin: false,
                pin: "",
            },
            errors: { title: "", content: "", pin: "" },
            showPinModal: false,
            enteredPin: "",
            pinError: "",
            pendingAction: null, 
            showDeleteModal: false,
            noteToDelete: null,
            isSaving: false, // Added for loading state
            isSubmittingPin: false, // Added for PIN submission loading
            isDeleting: false, // Added for delete loading state
        };
    },
    mounted() {
        this.isMobile = window.innerWidth <= 767;
        if (!this.isMobile) {
            this.notesPos = JSON.parse(
                localStorage.getItem(NOTES_POS_KEY) || '{"x":100,"y":100}',
            );
        }
        this.loadNotes();
        window.addEventListener('resize', this.updateIsMobile);
    },
    beforeUnmount() {
        document.removeEventListener("mousemove", this.onDrag);
        document.removeEventListener("mouseup", this.stopDrag);
        window.removeEventListener('resize', this.updateIsMobile);
    },
    methods: {
        updateIsMobile() {
            const wasMobile = this.isMobile;
            this.isMobile = window.innerWidth <= 767;
            if (wasMobile && !this.isMobile) {
                // Became desktop, load saved position
                this.notesPos = JSON.parse(
                    localStorage.getItem(NOTES_POS_KEY) || '{"x":100,"y":100}',
                );
            }
        },
        async loadNotes() {
            if (!this.token) return; 
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
            localStorage.setItem("show_notes", "false");
            this.$emit("close");
        },
        startDrag(e) {
            if (this.isMobile) return;
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

            const el = this.$refs.notesEl;
            if (!el) return;

            const rect = el.getBoundingClientRect();

            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;

            let newX = e.clientX - this.dragStart.x;
            let newY = e.clientY - this.dragStart.y;

            // Clamp X
            newX = Math.max(0, Math.min(newX, viewportWidth - rect.width));

            // Clamp Y
            newY = Math.max(0, Math.min(newY, viewportHeight - rect.height));

            this.notesPos.x = newX;
            this.notesPos.y = newY;
        },
        stopDrag() {
            this.dragging = false;
            document.removeEventListener("mousemove", this.onDrag);
            document.removeEventListener("mouseup", this.stopDrag);
            if (!this.isMobile) {
                localStorage.setItem(NOTES_POS_KEY, JSON.stringify(this.notesPos));
            }
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
            this.viewingNote = false;
        },
        viewNote(note) {
            if (note.hasPin) {
                this.pendingAction = { type: "view", note };
                this.showPinModal = true;
            } else {
                this.viewNoteDirect(note);
            }
        },
        async viewNoteDirect(note) {
            if (!this.token) return;
            try {
                const response = await axios.get(`${API_BASE}/${note.id}`, {
                    headers: { Authorization: `Bearer ${this.token}` },
                });
                this.currentNote = response.data;
                this.errors = { title: "", content: "", pin: "" }; // Clear errors
                this.viewingNote = true;
                this.editingNote = false;
            } catch (error) {
                console.error("Error loading note:", error);
            }
        },
        editFromView() {
            if (this.currentNote.hasPin) {
                this.currentNote.pin = "******";
            }
            this.editingNote = true;
            this.viewingNote = false;
        },
        backToList() {
            this.viewingNote = false;
            this.editingNote = false;
            this.currentNote = {
                id: null,
                title: "",
                content: "",
                hasPin: false,
                pin: "",
            };
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
            this.isDeleting = true; // Start loading
            try {
                await axios.delete(`${API_BASE}/${this.noteToDelete.id}`, {
                    headers: { Authorization: `Bearer ${this.token}` },
                });
                this.notes = this.notes.filter(
                    (n) => n.id !== this.noteToDelete.id,
                );
                this.showDeleteModal = false;
                this.backToList();
            } catch (error) {
                console.error("Error deleting note:", error);
            } finally {
                this.isDeleting = false; // Stop loading
            }
        },
        cancelDelete() {
            this.showDeleteModal = false;
            this.noteToDelete = null;
        },
        async saveNote() {
            if (!this.token) return;
            this.isSaving = true; // Start loading
            try {
                const data = {
                    title: this.currentNote.title,
                    content: this.currentNote.content,
                    hasPin: this.currentNote.hasPin,
                    pin: this.currentNote.hasPin && this.currentNote.pin !== "******" ? this.currentNote.pin : null,
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
                this.errors = { title: "", content: "", pin: "" }; 
                this.loadNotes();
                this.backToList(); 
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data.errors || {};
                } else {
                    console.error("Error saving note:", error);
                }
            } finally {
                this.isSaving = false; // Stop loading
            }
        },
        cancelEdit() {
            this.backToList();
        },
        submitPin() {
            if (this.pendingAction) {
                const { type, note } = this.pendingAction;
                if (type === "view") {
                    this.viewNoteWithPin(note, this.enteredPin);
                } else if (type === "delete") {
                    this.deleteWithPin(note, this.enteredPin);
                }
            }
        },
        async viewNoteWithPin(note, pin) {
            if (!this.token) return;
            this.isSubmittingPin = true; // Start loading
            try {
                const response = await axios.get(
                    `${API_BASE}/${note.id}?pin=${pin}`,
                    {
                        headers: { Authorization: `Bearer ${this.token}` },
                    },
                );
                this.currentNote = response.data;
                this.pinError = "";
                this.viewingNote = true;
                this.editingNote = false;
                this.closePinModal();
            } catch (error) {
                if (error.response && (error.response.status === 422 || error.response.status === 403)) {
                    this.pinError = "PIN is incorrect"; 
                } else {
                    console.error("Error loading note:", error);
                }
                // Modal stays open on error
            } finally {
                this.isSubmittingPin = false; // Stop loading
            }
        },
        async deleteWithPin(note, pin) {
            if (!this.token) return;
            this.isDeleting = true; // Start loading
            try {
                await axios.delete(`${API_BASE}/${note.id}?pin=${pin}`, {
                    headers: { Authorization: `Bearer ${this.token}` },
                });
                this.notes = this.notes.filter((n) => n.id !== note.id);
                this.pinError = ""; 
                this.backToList(); 
                this.closePinModal();
            } catch (error) {
                if (error.response && error.response.status === 403) {
                    this.pinError = "PIN is incorrect"; 
                } else {
                    console.error("Error deleting note:", error);
                }
            } finally {
                this.isDeleting = false; // Stop loading
            }
        },
        closePinModal() {
            this.showPinModal = false;
            this.enteredPin = "";
            this.pinError = ""; 
            this.pendingAction = null;
        },
    },
};
</script>

<style lang="scss" scoped>

@media (max-width: 767.98px) {
    .widget-container {
        min-width: 280px !important;
        max-width: 90vw !important;
    }
}

.widget-container {
    position: fixed;
    min-width: 420px;
    max-width: 40px;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 999;
    overflow: hidden;

    &.dark-mode {
      
        background-color: #343a40;
        border-color: #495057;
        color: #fff;

        .note-item {
            border-bottom-color: #495057;
        }

        .btn-link {
            color: #6c757d !important;
        }
    }
}

.widget-header {
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

.widget-content {
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
    padding: 8px 18px 8px 18px !important;
    border-bottom: 1px solid #eee;
    cursor: pointer;

    span {
      margin-right: 12px;
    }

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
    position: sticky;
    bottom: 0;
    text-align: center;
    border-top: 1px solid #0505050a;
    background-color: #f7f7f7;
    width: 100%;
    padding: 15px;
    z-index: 1;
}

[data-bs-theme="dark"] .notes-footer {
    position: sticky;
    bottom: 0;
    text-align: center;
    border-top: 1px solid #ffffff31;
    background-color: #3d4349;
    width: 100%;
    padding: 15px;
    z-index: 1;
}

.note-viewer {
    padding: 15px;
    .note-title-view {
        font-weight: bold;
        margin-bottom: 10px;
    }
    .note-content-view {
        white-space: pre-wrap;
        margin-bottom: 10px;
    }

    .viewer-actions {
        display: flex;
        justify-content: flex-start;
        gap: 10px;
    }
}

.note-editor {
    padding: 15px;
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
    background: #f1f1f1;
    padding: 30px;
    border-radius: 8px;
    max-width: 380px;
    width: 100%;

    .dark-mode & {
        background-color: #25282b;
        color: #fff;
    }
}

.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: -0.25rem;
    font-size: 0.875em;
    color: #dc3545;
}
</style>