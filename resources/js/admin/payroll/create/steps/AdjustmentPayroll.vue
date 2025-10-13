<template>
  <div>
    <ModalVue ref="modal" :title="'Suspension - Holiday'">
      
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

export default {
  components: { FullCalendar, ModalVue },

  props: { modelValue: Object },

  data: () => ({
    showCalendar: false,
    calendarKey: 0,
    calendarOptions: null,
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
    async initCalendar() {
      this.setCutoffRange();
      await this.$nextTick();
      setTimeout(() => {
        this.showCalendar = true;
      }, 100);
    },

    async remountCalendar() {
      // Hide calendar
      this.showCalendar = false;
      
      // Wait for DOM cleanup
      await this.$nextTick();
      
      // Small delay to ensure cleanup
      await new Promise(resolve => setTimeout(resolve, 50));
      
      // Update options and increment key
      this.setCutoffRange();
      this.calendarKey++;
      
      // Wait and show calendar
      await this.$nextTick();
      setTimeout(() => {
        this.showCalendar = true;
      }, 50);
    },

    setCutoffRange() {
      if (!this.modelValue?.date) return;

      const date = new Date(this.modelValue.date);
      const month = date.getMonth() + 1;
      const year = date.getFullYear();
      const days = new Date(year, month, 0).getDate();
      const isFirst = this.modelValue.cutoff === "first_cutoff";

      // Format month with leading zero
      const monthStr = month.toString().padStart(2, "0");
      const startDay = isFirst ? "01" : "16";
      const endDay = isFirst ? "15" : days.toString().padStart(2, "0");

      const cutoff = {
        label: isFirst ? "First Cutoff (1–15)" : `Second Cutoff (16–${days})`,
        start: `${year}-${monthStr}-${startDay}`,
        end: `${year}-${monthStr}-${endDay}`,
        color: isFirst ? "#d1e7dd" : "#f8d7da",
      };

      // Create completely fresh options object
      this.calendarOptions = {
        plugins: [dayGridPlugin, interactionPlugin],
        initialView: "dayGridMonth",
        initialDate: cutoff.start,
        headerToolbar: { 
          left: "prev,next today",
          center: "title",
          right: "" 
        },
        editable: false,
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
        weekends: true,
        validRange: { 
          start: cutoff.start, 
          end: this.addOneDay(cutoff.end) 
        },
        events: [
          {
            start: cutoff.start,
            end: this.addOneDay(cutoff.end),
            display: "background",
            color: cutoff.color,
          },
        ],
        dateClick: (info) => {
          const dateStr = info.dateStr;
          if (dateStr >= cutoff.start && dateStr <= cutoff.end) {
            this.handleDateClick(dateStr);
          }
        },
      };
    },

    addOneDay(d) {
      const date = new Date(d);
      date.setDate(date.getDate() + 1);
      return date.toISOString().split("T")[0];
    },

    handleDateClick(date) {
      this.date = date;
      this.$refs.modal.open();
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