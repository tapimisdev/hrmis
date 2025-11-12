<template>
  <div>
    <ModalVue
      ref="modal"
      :size="'modal-lg'"
      :headerIcon="'fa-solid fa-gear text-body'"
      :title="'Suspension & Holiday'"
    >
      <!-- Select Adjustment Type -->
      <div class="px-3 pt-3 border-bottom pb-3">
        <label class="form-label text-body">
          Adjustment Type <span class="text-danger">*</span>
        </label>
        <select
          class="form-select"
          v-model="adjustment_type"
          :disabled="idEdit"
        >
          <option value="">Select Type</option>
          <option value="suspension">Suspension</option>
          <option value="holiday">Holiday</option>
        </select>
      </div>

      <!-- Dynamic Form -->
      <SuspensionForm
        ref="suspension_form"
        v-if="adjustment_type === 'suspension'"
        :isEdit="idEdit"
        :date="selectedDate"
        :suspension_id="suspension_id"
        :suspension_date_id="suspension_date_id"
        @submit="refreshCalendar"
      />

      <HolidayForm
        ref="holiday_form"
        v-else-if="adjustment_type === 'holiday'"
        :isEdit="idEdit"
        :date="selectedDate"
        :holiday_id="holiday_id"
        @submit="refreshCalendar"
      />

      <div v-else class="px-3">
        <div class="alert alert-info text-uppercase" role="alert">
          -- No selected adjustment type --
        </div>
      </div>
    </ModalVue>

    <h5 class="mb-1 text-primary text-uppercase">Step 2: Suspensions & Holidays</h5>
    <p class="text-muted mb-4">
      Manage employee suspensions and holidays that may affect this payroll period.
    </p>

    <div v-if="showCalendar">
      <FullCalendar :key="calendarKey" ref="calendarRef" :options="calendarOptions" />
    </div>
    <div v-else class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
  </div>
</template>

<script>
import ModalVue from "../../../../components/ModalVue.vue";
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import SuspensionForm from "./forms/SuspensionForm.vue";
import HolidayForm from "./forms/HolidayForm.vue";
import axios from "axios";

const token = localStorage.getItem("auth_token");

export default {
  components: { FullCalendar, ModalVue, SuspensionForm, HolidayForm },
  props: { modelValue: Object },

  data: () => ({
    showCalendar: false,
    calendarKey: 0,
    calendarOptions: null,
    selectedDate: null,
    errors: [],
    adjustment_type: "",
    start_date: "",
    end_date: "",
    idEdit: false,
    holiday_id: null,
    suspension_date_id: null,
    suspension_id: null,
  }),

  mounted() {
    this.initCalendar();
  },

  watch: {
    "modelValue.date": {
      handler: "remountCalendar",
      deep: true,
    },
    "modelValue.cutoff": {
      handler: "remountCalendar",
      deep: true,
    },
  },

  methods: {
    //  Initialize the calendar on mount
    async initCalendar() {
      this.setCutoffRange();
      await this.$nextTick();
      setTimeout(() => (this.showCalendar = true), 100);
    },

    //  Recreate calendar if cutoff/date changed
    async remountCalendar() {
      // Properly destroy the calendar before remounting
      const calendarApi = this.$refs.calendarRef?.getApi?.();
      if (calendarApi) {
        calendarApi.destroy();
      }
      
      this.showCalendar = false;
      await this.$nextTick();
      await new Promise((r) => setTimeout(r, 100));
      
      this.setCutoffRange();
      this.calendarKey++;
      
      await this.$nextTick();
      await new Promise((r) => setTimeout(r, 100));
      this.showCalendar = true;
    },

    //  Fetch adjustments from backend
    async fetchAdjustments() {
      try {
        const response = await axios.post(
          "/api/payroll/adjustments",
          {
            start_date: this.start_date,
            end_date: this.end_date,
          },
          {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
              "Content-Type": "application/json",
            },
          }
        );
        return response.data;
      } catch (error) {
        console.error("Failed to fetch adjustments:", error.response?.data || error.message);
        return [];
      }
    },

    //  Compute cutoff & setup events
    async setCutoffRange() {
      if (!this.modelValue?.date) return;

      const date = new Date(this.modelValue.date);
      const month = date.getMonth() + 1;
      const year = date.getFullYear();
      const days = new Date(year, month, 0).getDate();
      const isFirst = this.modelValue.cutoff === "first_cutoff";

      const monthStr = month.toString().padStart(2, "0");
      const startDay = isFirst ? "01" : "16";
      const endDay = isFirst ? "15" : days.toString().padStart(2, "0");

      const cutoff = {
        label: isFirst ? "First Cutoff (1–15)" : `Second Cutoff (16–${days})`,
        start: `${year}-${monthStr}-${startDay}`,
        end: `${year}-${monthStr}-${endDay}`,
        color: isFirst ? "#d1e7dd" : "#f8d7da",
      };

      this.start_date = cutoff.start;
      this.end_date = cutoff.end;

      const adjustments = await this.fetchAdjustments();

      this.calendarOptions = {
        plugins: [dayGridPlugin, interactionPlugin],
        initialView: "dayGridMonth",
        initialDate: cutoff.start,
        headerToolbar: {
          left: "prev,next today",
          center: "title",
          right: "",
        },
        editable: false,
        selectable: true,
        dayMaxEvents: true,
        weekends: true,
        validRange: {
          start: cutoff.start,
          end: this.addOneDay(cutoff.end),
        },
        events: [
          {
            start: cutoff.start,
            end: this.addOneDay(cutoff.end),
            display: "background",
            color: cutoff.color,
          },
          ...adjustments,
        ],
        dateClick: (info) => this.handleDateClick(info, cutoff),
        eventClick: (info) => this.handleEventClick(info),
      };
    },

    //  Add 1 day utility
    addOneDay(d) {
      const date = new Date(d);
      date.setDate(date.getDate() + 1);
      return date.toISOString().split("T")[0];
    },

    //  Handle clicking empty date cell
    handleDateClick(info, cutoff) {
      const dateStr = info.dateStr;
      const calendar = info.view.calendar;

      const existingEvents = calendar.getEvents().filter((event) => {
        return event.startStr === dateStr && event.display !== "background";
      });

      if (existingEvents.length > 0) {
        return;
      }

      if (dateStr < cutoff.start || dateStr > cutoff.end) return;

      this.selectedDate = dateStr;
      this.idEdit = false;
      this.holiday_id = null;
      this.suspension_id = null;
      this.suspension_date_id = null;
      this.adjustment_type = "";

      if (this.$refs.suspension_form) this.$refs.suspension_form.resetForm();
      if (this.$refs.holiday_form) this.$refs.holiday_form.resetForm();

      this.$refs.modal.open();
    },

    //  Handle clicking existing event
    async handleEventClick(info) {
      const event = info.event;
      const props = event.extendedProps || {};

      if (props.category === "holiday") {
        this.adjustment_type = "holiday";
        this.selectedDate = event.startStr;
        this.idEdit = true;
        this.holiday_id = props.id;
        this.suspension_id = null;
        this.suspension_date_id = null;
        
        this.$refs.modal.open();
        await this.$nextTick();
        await this.loadHolidayData(props.id);
      } else if (props.category === "suspension") {
        this.adjustment_type = "suspension";
        this.selectedDate = event.startStr;
        this.idEdit = true;
        this.suspension_date_id = props.id;
        this.suspension_id = props.suspension_id;
        this.holiday_id = null;
        
        this.$refs.modal.open();
        await this.$nextTick();
        await this.loadSuspensionData(props.suspension_id, props.id);
      }
    },

    //  Load specific holiday for editing
    async loadHolidayData(id) {
      try {
        const response = await axios.get(`/admin/maintenance/holiday/${id}/edit`, {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        });
        const holiday = response.data;

        if (this.$refs.holiday_form) {
          Object.assign(this.$refs.holiday_form.form, {
            name: holiday.name || "",
            date: holiday.date || "",
            type: holiday.type || "",
            is_repeating: !!holiday.is_repeating,
            no_work_rate: holiday.no_work_rate || 0,
            work_rate: holiday.work_rate || 0,
            overtime_rate: holiday.overtime_rate || 0,
          });
        }
      } catch (error) {
        console.error("Failed to load holiday:", error.response?.data || error.message);
        alert("Failed to load holiday data. Please try again.");
      }
    },

    //  Load specific suspension for editing
    async loadSuspensionData(suspensionId, suspensionDateId) {
      try {
        const response = await axios.get(`/admin/service/suspensions/${suspensionId}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        });
        const suspension = response.data;

        if (this.$refs.suspension_form) {
          // Find the specific suspension date entry
          const suspensionDate = suspension.suspension_dates?.find(
            (sd) => sd.id === suspensionDateId
          );

          if (suspensionDate) {
            Object.assign(this.$refs.suspension_form.form, {
              name: suspension.name || "",
              reason: suspension.reason || "",
              description: suspension.description || "",
              date: suspensionDate.date || "",
              type: suspensionDate.type || "",
              suspensions: [
                {
                  date: suspensionDate.date || "",
                  type: suspensionDate.type || "",
                  shift: suspensionDate.shift || "",
                },
              ],
            });
          } else {
            // Fallback if suspension_dates structure is different
            Object.assign(this.$refs.suspension_form.form, {
              name: suspension.name || "",
              date: suspension.date || this.selectedDate,
              reason: suspension.reason || "",
              type: suspension.type || "",
              description: suspension.description || "",
              suspensions: [
                {
                  date: suspension.date || this.selectedDate,
                  type: suspension.type || "",
                  shift: suspension.shift || "",
                },
              ],
            });
          }
        }
      } catch (error) {
        console.error("Failed to load suspension:", error.response?.data || error.message);
        alert("Failed to load suspension data. Please try again.");
      }
    },

    //  Refresh calendar events after submitting forms
    async refreshCalendar() {
      this.$refs.modal.close();
      
      // Reset form state
      this.adjustment_type = "";
      this.idEdit = false;
      this.holiday_id = null;
      this.suspension_id = null;
      this.suspension_date_id = null;
      this.selectedDate = null;

      // Fetch updated adjustments
      const newAdjustments = await this.fetchAdjustments();
      const calendarApi = this.$refs.calendarRef?.getApi?.();

      if (calendarApi) {
        // Remove only non-background events
        const allEvents = calendarApi.getEvents();
        allEvents.forEach((event) => {
          if (event.display !== "background") {
            event.remove();
          }
        });

        // Add new events
        newAdjustments.forEach((event) => {
          calendarApi.addEvent(event);
        });
      }
    },
  },
};
</script>

<style lang="scss" scoped>
.fc {
  border-radius: 10px;
}

.fc-daygrid-day {
  cursor: pointer;
  transition: 0.2s;
}

.fc-day-today {
  background-color: rgba(13, 110, 253, 0.15) !important;
}

.fc-day-disabled {
  background-color: #f8f9fa !important;
  color: #adb5bd !important;
  pointer-events: none;
}

.fc-daygrid-day:hover {
  background-color: rgba(13, 110, 253, 0.07);
  transform: scale(1.02);
}
</style>