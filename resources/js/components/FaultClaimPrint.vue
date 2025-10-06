<template>
  <div>
    <!-- Printable Section -->
    <div id="fault-claim-print" class="p-4" v-if="fault">
      <!-- Header -->
      <div class="text-center mb-4">
        <h4 class="font-weight-bold text-dark">
          <i class="fas fa-tools mr-2"></i> Fault Claim Report
        </h4>
      </div>

      <!-- Bus & Driver Info -->
      <div class="card  border-0 mb-4">
        <div class="card-body">
          <h5 class="card-title mb-4">
            <span class="text-dark">Bus: </span>
            <strong>{{ fault.bus?.bus_number || 'N/A' }}</strong> |
            <span class="text-dark">Driver: </span>
            <strong>{{ fault.driver?.name || 'N/A' }}</strong>
          </h5>

          <!-- Dock Requests -->
          <div v-if="fault.dock_requests?.length">
            <div
              class="mb-3"
              v-for="(dock, i) in fault.dock_requests"
              :key="i"
            >
              <div class="card border-0">
                <div class="card-body py-2 px-3">
                  <div
                    class="d-flex justify-content-between align-items-center p-2 rounded mb-2 bg-primary text-white"
                  >
                    <h6 class="font-weight-bold mb-0">
                      Dock Request #{{ i + 1 }}
                    </h6>
                    <h6>
                      Dock Start Time:
                      {{ dock.dock_start_time
                        ? formatDateTime(dock.dock_start_time)
                        : "Not Assigned" }}
                    </h6>
                     <span :class="getStatusClass(dock.status)">{{ dock.status
                                                                }}</span>
                  </div>

                  <div class="row">
                    <div class="col-md-6 mt-2">
                      <strong class="text-dark">Dock Time:</strong>
                      {{ dock.dock_time || "N/A" }}
                    </div>
                    <div class="col-md-6 mt-2">
                      <strong class="text-dark">Priority:</strong>
                      {{ dock.periority || "N/A" }}
                    </div>
                    <div class="col-md-6 mt-2">
                      <strong class="text-dark">Approved By:</strong>
                      {{ dock.approved_by || "N/A" }}
                    </div>
                    <div class="col-md-6 mt-2">
                      <strong class="text-dark">Approved At:</strong>
                      {{ formatDateTime(dock.approved_at) || "N/A" }}
                    </div>
                    <div class="col-md-6 mt-2">
                      <strong class="text-dark">Request Type:</strong>
                      {{ dock.request_type || "N/A" }}
                    </div>
                    <div class="col-md-6 mt-2">
                      <strong class="text-dark">Approval Comments:</strong>
                      {{ dock.comments || "N/A" }}
                    </div>
                    <div class="col-md-12 mt-2">
                      <strong class="text-dark">Description:</strong>
                      {{ dock.description || "N/A" }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-center text-muted py-4">
            <i class="fas fa-info-circle fa-2x mb-2"></i>
            <p>No dock requests found for this fault.</p>
          </div>

          <!-- Inspection Info -->
          <div class="card border-0 mb-4" v-if="inspection">
            <div class="card-header bg-success text-white">
              <h5 class="mb-0">
                <i class="fas fa-clipboard-check mr-2"></i> Inspection Result
              </h5>
            </div>
            <div class="card-body">
              <!-- Bus & Driver -->
              <div class="row mb-3">
                <div class="col-md-6">
                  <strong>Bus Number:</strong>
                  {{ inspection.bus?.bus_number || "N/A" }}
                </div>
                <div class="col-md-6">
                  <strong>Driver Name:</strong>
                  {{ inspection.driver?.name || "N/A" }}
                </div>
              </div>

              <!-- Status / Repair / Entry -->
              <div class="bg-light rounded p-3 mb-3">
                <div class="row mb-3">
                  <div class="col-md-4">
                    <strong>Status: </strong>
                    <span class="badge p-2 badge-info text-uppercase">
                      {{ inspection.status || "N/A" }}
                    </span>
                  </div>
                  <div class="col-md-4">
                    <strong>Repair Type:</strong>
                    {{ inspection.repair_type
                      ? inspection.repair_type.replace("_", " ")
                      : "N/A" }}
                  </div>
                  <div class="col-md-4">
                    <strong>Entry Date:</strong>
                    {{ formatDateTime(inspection.created_at) || "N/A" }}
                  </div>
                </div>

                <div class="row mb-3">
                  <div class="col-md-4">
                    <strong>Mechanic Name:</strong>
                    {{ inspection.machanic_name || "N/A" }}
                  </div>
                  <div class="col-md-4">
                    <strong>Vendor:</strong>
                    {{ inspection.vendor?.name || "N/A" }}
                  </div>
                  <div class="col-md-4">
                    <strong>Bill Amount:</strong>
                    {{
                      inspection.amount
                        ? parseFloat(inspection.amount).toLocaleString()
                        : "N/A"
                    }}
                  </div>
                </div>

                <div class="row mb-0">
                  <div class="col-md-4">
                    <strong>Current Reading:</strong>
                    {{
                      inspection.current_reading
                        ? inspection.current_reading + " KM"
                        : "N/A"
                    }}
                  </div>
                  <div class="col-md-4">
                    <strong>Maintenance Date:</strong>
                    {{ inspection.maintenance_date || "N/A" }}
                  </div>
                </div>
              </div>

              <!-- Parts -->
              <div class="mb-3">
                <strong>Parts Used:</strong>
                <ul
                  class="list-group list-group-sm mt-2"
                  v-if="inspection.parts && inspection.parts.length"
                >
                  <li
                    v-for="part in inspection.parts"
                    :key="part.id"
                    class="list-group-item py-1 px-3"
                  >
                    {{ part.part?.name || "N/A" }}
                  </li>
                </ul>
                <div v-else class="text-muted">No parts used</div>
              </div>

              <!-- Last Dock Request -->
              <div class="px-3 py-2 mb-2 d-flex align-items-center">
               <i class="fas fa-tools mr-2"></i>
                <strong class="text-dark text-uppercase mb-0">
                  Last Dock Request
                </strong>
              </div>
              <div class="row mb-3" v-if="inspection.dock_request">
                <div class="col-md-4">
                  <strong>Dock Time:</strong>
                  {{
                    inspection.dock_request.dock_time
                      ? inspection.dock_request.dock_time.replace(":", "h ") +
                        "m"
                      : "N/A"
                  }}
                </div>
                <div class="col-md-4">
                  <strong>Priority:</strong>
                  {{ inspection.dock_request.periority || "N/A" }}
                </div>
                <div class="col-md-4">
                  <strong>Dock Description:</strong>
                  {{ inspection.dock_request.description || "N/A" }}
                </div>
              </div>

              <!-- Comments -->
              <div class="mt-3">
                <strong>Comments:</strong>
                <div class="border rounded p-2 bg-light">
                  {{ inspection.comments || "No comments provided." }}
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-muted"></div>
        </div>
      </div>
    </div>

    <div v-else class="text-center text-muted p-4">
      <i class="fas fa-info-circle"></i> No fault data to display.
    </div>
  </div>
</template>

<script>
export default {
  name: "FaultClaimPrint",
  props: {
    fault: { type: Object, required: false },
    inspection: { type: Object, required: false }
  },
  methods: {
    formatDateTime(dt) {
      if (!dt) return  'Not Assigned';
      return new Date(dt).toLocaleString();
    },
     getStatusClass(status) {
            switch (status) {
                case 'pending':
                    return 'p-2 rounded text-white badge badge-warning';
                case 'approved':
                    return 'p-2 rounded text-white badge badge-success';
                case 'rejected':
                    return 'p-2 rounded text-white badge badge-danger';
                default:
                    return 'p-2 rounded text-white badge badge-secondary';
            }
        },
  }
};
</script>
