<template>
  <div>
    <p>This is the filter</p>
       <div class="d-flex gap-2">
            <!-- Month Dropdown -->
            <select class="form-select" id="monthSelect">
                <option value="">Select Month</option>
                <option value="1">January</option>
                <option value="2">February</option>
                <option value="3">March</option>
                <option value="4">April</option>
                <option value="5">May</option>
                <option value="6">June</option>
                <option value="7">July</option>
                <option value="8">August</option>
                <option value="9">September</option>
                <option value="10">October</option>
                <option value="11">November</option>
                <option value="12">December</option>
            </select>

            <!-- Year Dropdown -->
            <select class="form-select" id="yearSelect">
                <option value="">Select Year</option>
            </select>
        </div>

  </div>
</template>

<script>
export default {

}
</script>

<style>

</style>