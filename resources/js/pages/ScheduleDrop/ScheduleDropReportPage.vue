<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Schedule Drop Report</h4>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                         <form @submit.prevent="fetchHeadersData">
  <div class="row px-2 mb-4 align-items-end">

    <!-- Optional schedule dropdown -->
    <!-- <div class="col-md-3">
      <label for="scheduleFilter">Select Schedule</label>
      <select id="scheduleFilter" class="form-control" v-model="filterData.schedule_id">
        <option value="">All Schedules</option>
        <option v-for="schedule in schedules" :key="schedule.id" :value="schedule.id">
          {{ schedule.name }}
        </option>
      </select>
    </div> -->

    <!-- From Date -->
    <div class="col-md-4">
      <label for="fromDate">From Date</label>
      <input id="fromDate" type="date" class="form-control" v-model="filterData.from_date">
    </div>

    <!-- To Date -->
    <div class="col-md-4">
      <label for="toDate">To Date</label>
      <input id="toDate" type="date" class="form-control" v-model="filterData.to_date">
    </div>

    <!-- Buttons -->
    <div class="col-md-4 d-flex justify-content-center">
      <button type="submit" class="btn w-100 btn-primary mr-2">Filter</button>
      <button type="button" class="btn w-100 btn-danger" @click="resetFilters">Reset</button>
    </div>
  </div>
</form>

                            <div class="row">
                                <div class="col-12">
                                    <div class="card">

                                        <div class="card-body">
                                           <div class="table-responsive">
  <table class="table table-striped table-hover" id="schedule_drop">
  <thead>
    <tr>
      <th>Sr No.</th>
      <th>Schedule</th>
      <th>Schedule Date</th>
      <th>Route</th>
      <th>Drop By</th>
      <th>Drop Time</th>
    </tr>
  </thead>
  <tbody>
    <tr v-if="dropSchedules.length === 0">
      <td colspan="6" class="text-center">No data available</td>
    </tr>
    <tr v-for="(drop, i) in dropSchedules" :key="drop.id">
      <td>{{ i + 1 }}</td>
      <td>{{ drop.schedule.name }}</td>
      <td>{{ drop.schedule_date }}</td>
      <td>{{ drop.schedule.route.name }}</td>
      <td>{{ drop.added_by.name }}</td>
      <td>{{ drop.time }}</td>
    </tr>
  </tbody>
</table>


</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- END TABLE -->
                        </div>
                    </div>
                </div>
            </div>

            

        </div>
    </section>


</template>

<script>
export default {
  name: "ScheduleDropReportPage",
  data() {
    return {
      dropSchedules: [],
      schedules: [],
      filterData: {
        bus_number: '',
        schedule_id: '',
        from_date: new Date().toISOString().split('T')[0],
        to_date: new Date().toISOString().split('T')[0],
      }
    };
  },
  mounted() {
    this.fetchHeadersData();
  },
  methods: {
   async fetchHeadersData() {
    const res = await this.callApi("post", 'report/schedules/drop', this.filterData);

    if (res.status === 200) {
        this.dropSchedules = res.data.dropSchedules;

        // Wait for Vue to render table rows
        this.$nextTick(() => {
            // Destroy existing DataTable safely
            if ($.fn.DataTable.isDataTable('#schedule_drop')) {
                $('#schedule_drop').DataTable().destroy();
            }

            // Re-initialize DataTable
            $('#schedule_drop').DataTable({
                responsive: true,
                autoWidth: false,
                paging: true,
                searching: true,
                ordering: true,
                destroy: true, // ensures safe re-init
                language: {
                    emptyTable: "No data available"
                }
            });
        });
    }
},
   resetFilters() {
    this.filterData = {
        bus_number: '',
        schedule_id: '',
        from_date: new Date().toISOString().split('T')[0],
        to_date: new Date().toISOString().split('T')[0]
    };
    this.fetchHeadersData(); // Refresh table
}

  }
};
</script>
