<template>
  <div>
    <h5 class="mb-1 text-primary text-uppercase">Step 2: Suspensions & Holidays</h5>
    <p class="text-muted mb-4">
      Manage employee suspensions and holidays that may affect this payroll period.
    </p>
    <FullCalendar ref="calendarRef" :options="calendarOptions" />
  </div>
</template>

<script>
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";

export default {
  components: { FullCalendar },
  props: {
    cutoffType: {
      type: String, // 'first_cutoff' or 'second_cutoff'
      default: "first_cutoff",
    },
    year: {
      type: Number,
      default: 2025,
    },
    month: {
      type: Number,
      default: 10,
    },
  },
  data() {
    return {
      calendarOptions: {
        plugins: [dayGridPlugin, interactionPlugin],
        initialView: "dayGridMonth",
        headerToolbar: {
          title: '',
          right: ''
        },
        editable: false,
        selectable: true,
        selectMirror: false,
        events: [],
        validRange: {},
        dateClick: null,
      },
    };
  },
  mounted() {
    this.setCutoffRange();
  },
  methods: {
    setCutoffRange() {
      const year = this.year;
      const month = this.month.toString().padStart(2, "0");

      const daysInMonth = this.daysInMonth(this.month, this.year);

      //  compute start/end based on cutoff type
      const cutoff =
        this.cutoffType === "second_cutoff"
          ? {
              label: "First Cutoff (1–15)",
              start: `${year}-${month}-01`,
              end: `${year}-${month}-16`,
              color: "#d1e7dd",
            }
          : {
              label: `Second Cutoff (16–${daysInMonth})`,
              start: `${year}-${month}-16`,
              end: `${year}-${month}-${daysInMonth}`,
              color: "#f8d7da",
            };

      //  restrict valid range (inclusive of last day)
      this.calendarOptions.validRange = {
        start: cutoff.start,
        end: cutoff.end,
      };

      //  highlight cutoff range
      this.calendarOptions.events = [
        {
          start: cutoff.start,
          end: this.addOneDay(cutoff.end), // make sure last day is included in FullCalendar
          display: "background",
          color: cutoff.color,
        },
      ];

      //  allow clicks only within cutoff range
      this.calendarOptions.dateClick = (info) => {
        const date = info.dateStr;
        if (date >= cutoff.start && date <= cutoff.end) {
          this.handleDateClick(date);
        }
      };
    },

    daysInMonth(month, year) {
      return new Date(year, month, 0).getDate();
    },

    // FullCalendar excludes the end date, so add +1 day to show full highlight
    addOneDay(dateStr) {
      const date = new Date(dateStr);
      date.setDate(date.getDate() + 1);
      return date.toISOString().split("T")[0];
    },

    handleDateClick(date) {
      console.log('sadasd');
      swal
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
  cursor: not-allowed !important;
}

.fc-daygrid-day:hover {
  background-color: rgba(13, 110, 253, 0.07);
  transform: scale(1.02);
}
</style>
