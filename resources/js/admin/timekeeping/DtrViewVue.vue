<template>
  <div>
    <filter-dtr-vue
      :month="month"
      :year="year"
      @update-date="updateDate"
    />
    <table-dtr-vue
      :employee_id="employee_id"
      :month="month"
      :year="year"
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
      type: Number,
      required: true
    }
  },
  data() {
    return {
      month: new Date().getMonth() + 1,
      year: new Date().getFullYear()
    }
  },
  methods: {
    updateDate({ month, year }) {
      this.month = month;
      this.year = year;
    }
  }
}
</script>
