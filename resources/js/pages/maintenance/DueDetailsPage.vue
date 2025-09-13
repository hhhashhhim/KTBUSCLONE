<template>
  <section class="section">
    <div class="section-body">
      <div class="row">
        <div class="col-12 col-md-12 col-lg-12">
          <div class="card card-primary">
            <div class="card-header d-flex justify-content-between">
              <h4>Parts-wise Maintenance Details</h4>
            </div>
            <div class="card-body">
              <div class="row">


                <!-- From Date -->
                <div class="col-md-3">
                  <label for="from_date" class="form-label">From Date</label>
                  <input type="date" v-model="filters.from" id="from_date" class="form-control">
                </div>

                <!-- To Date -->
                <div class="col-md-3">
                  <label for="to_date" class="form-label">To Date</label>
                  <input type="date" v-model="filters.to" id="to_date" class="form-control">
                </div>

                <!-- Status -->
                <div class="col-md-3">
                  <label for="status" class="form-label">Status</label>
                  <select v-model="filters.status" id="status" class="form-control">
                    <option value="">All</option>
                    <option value="Due">Due</option>
                    <option value="Up To Date">Up To Date</option>
                  </select>
                </div>

                <!-- Bus -->
                <div class="col-md-3">
                  <label for="bus_id" class="form-label">Bus</label>
                  <select ref="busFilterSelect" v-model="filters.bus" id="bus_id" class="form-control">
                    <option value="">All Buses</option>
                    <option v-for="bus in busDrop" :key="bus.id" :value="bus.id">
                      {{ bus.bus_number || 'Bus #' + bus.id }}
                    </option>
                  </select>
                </div>


                <div class="col-md-12 my-3">
                  <div class="row">
                    <div class="col-md-6">
                      <button type="submit" @click="applyFilters" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-md-6">
                      <button type="button" @click="resetFilters" class="btn btn-danger w-100">Reset</button>
                    </div>
                  </div>
                </div>


              </div>


              <!-- END Filters -->

              <!-- Table -->
              <div class="row">
                <div class="col-12">
                  <div class="card">
                    <div class="card-body">
                      <div class="table-responsive">
                        <table class="table " id="parts_table">
                          <thead>
                            <tr>
                              <th>Bus Number</th>
                              <th>Current Reading</th>
                              <th>Part Name</th>
                              <th class="w-25">Health %</th>
                              <th>Next Maintenance Date</th>
                              <th>Status</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="part in filteredParts" :key="part.part_id"
                              :class="part.status === 'Due' ? 'due-row' : ''">
                              <td>{{ part.bus_number }}</td>
                              <td>{{ part.current_reading }} (km)</td>
                              <td>{{ part.part_name }}</td>
                              <td>
                                <div class="progress" style="height: 20px;">
                                  <div class="progress-bar" role="progressbar"
                                    :class="getProgressClass(part.percentage)"
                                    :style="{ width: part.percentage + '%' }">
                                    {{ part.percentage }}%
                                  </div>
                                </div>
                              </td>
                              <td>{{ formatDate(part.maintenance_days_plus_date) }}</td>
                              <td>
                                <span
                                  :class="part.status === 'Due' ? 'badge bg-danger small-badge' : 'badge bg-success small-badge'">
                                  {{ part.status }}
                                </span>
                              </td>
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
import axios from "axios";

export default {
  name: "DueDetailsPage",
  data() {
    return {
      partsData: [],
      busDrop: [],
      dataTableInitialized: false,
      filters: {
        from: "",
        to: "",
        status: "",
        bus: "",
      },
      filteredParts: [], // filtered rows
    };
  },
  methods: {
    formatDate(date) {
      if (!date) return "-";
      const d = new Date(date);
      const day = d.getDate();
      const month = d.toLocaleString("en-GB", { month: "short" });
      const year = d.getFullYear();
      return `${day} / ${month} / ${year}`;
    },
    async fetchPartsData() {
      try {
        const res = await this.callApi("get", "fleet/maintenance/due/details");
        if (res.status === 200) {
          this.partsData = res.data.partsData;
          this.busDrop = res.data.busDrop;

          // Apply initial filters
          this.filteredParts = [...this.partsData];

          // Destroy previous DataTable if exists
          if ($.fn.DataTable.isDataTable("#parts_table")) {
            $("#parts_table").DataTable().destroy();
          }

          this.$nextTick(() => {
            // Re-init DataTable
            $("#parts_table").DataTable({
              ordering: false,
              pageLength: 10,
              responsive: true
            });
          });
        }
      } catch (err) {
        console.error("Error fetching parts data:", err);
      }
    },

    applyFilters() {
  this.filteredParts = this.partsData.filter((part) => {
    // ✅ Convert to Date objects for correct filtering
    const partDate = part.maintenance_days_plus_date
      ? new Date(part.maintenance_days_plus_date)
      : null;
    const fromDate = this.filters.from ? new Date(this.filters.from) : null;
    const toDate = this.filters.to ? new Date(this.filters.to) : null;

    const fromMatch = fromDate ? (partDate && partDate >= fromDate) : true;
    const toMatch = toDate ? (partDate && partDate <= toDate) : true;

    const statusMatch = this.filters.status
      ? part.status === this.filters.status
      : true;
    const busMatch = this.filters.bus
      ? part.bus_id == this.filters.bus
      : true;

    return fromMatch && toMatch && statusMatch && busMatch;
  });

  // ✅ Always sort after filtering
  this.filteredParts.sort((a, b) => a.percentage - b.percentage);

  // ✅ Reinitialize DataTable
  if ($.fn.DataTable.isDataTable("#parts_table")) {
    $("#parts_table").DataTable().destroy();
  }

  this.$nextTick(() => {
    $("#parts_table").DataTable({
      ordering: false,
      pageLength: 10,
      responsive: true,
    });
  });
},

   resetFilters() {
  this.filters = { from: "", to: "", status: "", bus: "" };

  // ✅ Ensure sorting works numerically
  this.filteredParts = [...this.partsData].sort(
    (a, b) => Number(a.percentage) - Number(b.percentage)
  );

  // ✅ Reinitialize DataTable
  if ($.fn.DataTable.isDataTable("#parts_table")) {
    $("#parts_table").DataTable().destroy();
  }
  this.$nextTick(() => {
    $("#parts_table").DataTable({
      ordering: false,
      pageLength: 10,
      responsive: true,
    });
  });
},

    getProgressClass(percentage) {
      return percentage > 20 ? "bg-success" : "bg-danger";
    }
  },

  mounted() {
    this.fetchPartsData();
  },
};

</script>

<style scoped>
/* Table font and spacing */
table,
table * {
  font-size: 14px !important;

}

/* Progress bar height and style */
.progress {
  height: 20px;
}

/* Badge styling */
.badge {
  padding: 5px 10px;
  font-size: 12px;
}

/* Card header spacing */
.card-header h4 {
  margin: 0;
}

/* Optional: highlight due rows */
tr.due-row td {
  background-color: #ffe5e5;
  /* light red for due parts */
  border-color: #ff4d4d;
    padding: 15px 5px !important;
}

.small-badge {
  font-size: 10px !important;
  /* smaller text */
  color: #fff !important;
  /* force white text */
}

/* Red border around all cells when status is Due */
.due-row td {
  border-top: 1px solid red !important;
  border-bottom: 1px solid red !important;
  background-color: transparent !important;
  padding: 10px 5px !important;
}

.due-row td:first-child {
  border-left: 1px solid red !important;
  background-color: transparent !important;
}

.due-row td:last-child {

  background-color: transparent !important;
  border-right: 1px solid red !important;
}
</style>