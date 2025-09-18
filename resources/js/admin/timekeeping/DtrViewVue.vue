<template>
  <div>
    <filter-dtr-vue
      :employee_id="employee_id"
      :month="month"
      :year="year"
      :summary="summary"
      @update-date="updateDate"
    />
    <table-dtr-vue
      :employee_id="employee_id"
      :month="month"
      :year="year"
      @send-summary="handleSummary"
    />
  </div>
</template>

<script>
import FilterDtrVue from './components/FilterDtrVue.vue';
import TableDtrVue from './components/TableDtrVue.vue';

export default {
  components: { FilterDtrVue, TableDtrVue },
  props: {
    employee_id: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      month: new Date().getMonth() + 1,
      year: new Date().getFullYear(),
      summary: []
    }
  },
  methods: {
    updateDate({ month, year }) {
      this.month = month;
      this.year = year;
    },
    handleSummary(payload) {
      this.summary = payload;
    }
  }
}
</script>
