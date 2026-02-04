<template>
  <div class="mt-3">
    <filter-dtr-vue
      :employee_id="employee_id"
      :month="month"
      :year="year"
      :payload="payload"
      @update-date="updateDate"
    />
    <table-dtr-vue
      :employee_id="employee_id"
      :month="month"
      :year="year"
      @send-payload="handleSummary"
    />
  </div>
</template>

<script>
import FilterDtrVue from './components/FilterDtrVue.vue'
import TableDtrVue from './components/TableDtrVue.vue'

export default {
  components: { FilterDtrVue, TableDtrVue },

  props: {
    employee_id: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      month: new Date().getMonth() + 1,
      year: new Date().getFullYear(),
      payload: [],
    }
  },

  mounted() {
    this.initializeFromQuery()
  },

  methods: {
    initializeFromQuery() {
      const params = this.$route?.query || Object.fromEntries(new URLSearchParams(window.location.search))

      const month = params.month ? parseInt(params.month) : null
      const year = params.year ? parseInt(params.year) : null

      if (month) this.month = month
      if (year) this.year = year
    },

    updateDate({ month, year }) {
      this.month = month
      this.year = year
    },

    handleSummary(payload) {
      this.payload = payload
    },
  },
}
</script>
