<template>
  <section class="section">
    <div class="section-body">
      <div class="row">
        <div class="col-12 mt-4">
          <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
              <a class="nav-link" :class="{ active: activeTab === 'prn' }" href="#" @click.prevent="activeTab = 'prn'">
                <b>Purchase Requisition Note - PRNs</b>
              </a>
            </li>
            <li class="nav-item ml-2">
              <a class="nav-link" :class="{ active: activeTab === 'mr' }" href="#" @click.prevent="activeTab = 'mr'">
                <b>Material Requests - MRs <span class="badge badge-primary">{{ mrRequests }}</span></b>
              </a>
            </li>
          </ul>
        </div>
        <!-- MR Table -->
        <div class="col-12" v-if="activeTab === 'mr'">
          <div class="card card-primary">
            <div class="card-header">
              <h4>Material Requests - MRs <span class="badge badge-primary">{{ mrRequests }}</span></h4>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-hover dataTable1">
                  <thead>
                    <tr>
                      <th>Sr No.</th>
                      <th>MR #</th>
                      <th>Date</th>
                      <th>Request By</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(mr, index) in mrs" :key="mr.id">
                      <td>{{ index + 1 }}</td>
                      <td>MR - {{ mr.id }}</td>
                      <td>{{ new Date(mr.created_at).toLocaleString() }}</td>
                      <td>{{ mr.requested_by_user?.name }}</td>
                      <td>
                        <button class="btn btn-info btn-sm" @click="viewMR(mr)">
                          <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-danger btn-sm ml-1" :disabled="rejectingId === mr.id" title="Reject Material Request" @click="rejectMaterialRequest(mr)">
                          <span v-if="rejectingId === mr.id" class="spinner-border spinner-border-sm"></span>
                          <i v-else class="fas fa-times"></i> Reject
                        </button>
                      </td>
                    </tr>
                    <tr v-if="mrs.length === 0">
                      <td colspan="6" class="text-center">No Material Requests Found</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <!-- PRN Table -->
        <div class="col-12" v-if="activeTab === 'prn'">
          <div class="card card-primary">
            <div class="card-header">
              <h4>Purchase Requisition Note - PRNs </h4>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-hover dataTable1">
                  <thead>
                    <tr>
                      <th>Sr No.</th>
                      <th>MR #</th>
                      <th>PRN #</th>
                      <th>Date</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>

                    <tr v-for="(prn, index) in prns" :key="prn.id">
                      <td>{{ index + 1 }}</td>
                      <td>MR - {{ prn.mr_id }}</td>
                      <td>PRN - {{ prn.id }}</td>
                      <td>{{ new Date(prn.created_at).toLocaleString() }}</td>
                      <td>
  <span class="badge text-white" :class="prnStatusClass(prn)">{{ prnStatusLabel(prn) }}</span>
</td>

                      <td>
                        <button class="btn btn-info btn-sm" @click="viewPRN(prn)">
                          <i class="fas fa-eye"></i> View
                        </button>
                        <!-- Pass mr.id instead of full mr object -->
                        <button class="btn btn-dark btn-sm mx-1" @click="submitPRNPdf(prn.id)">
                          <i class="fas fa-print"></i>
                        </button>
                      </td>
                    </tr>

                    <tr v-if="prns.length === 0">
                      <td colspan="6" class="text-center">No Purchase Requisition Note Found</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- MR Details Modal -->
      <div class="modal fade" id="viewMRModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">MR Details - MR-{{ selectedMR?.mr_id }}</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" v-if="selectedMR">
              <table class="table table-bordered dataTable">
                <thead class="table-light bg-light border-top">
                  <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Requested Qty</th>
                    <th>Available Stock</th>
                    <th>PRN Qty</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in selectedMR.details" :key="index">
                    <td>{{ index + 1 }}</td>
                    <td>{{ item.product?.name }}</td>
                    <td>{{ item.qty }}</td>
                    <td>{{ item.product?.qty }}</td>
                    <td>
                      <input type="number" class="form-control form-control-sm" :max="item.qty" :min="0"
                        v-model.number="item.prnQty" placeholder="Enter PRN Qty" />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="modal-footer table-light bg-light border-top">
              <!-- <button class="btn btn-success" @click="issueSelectedItems(selectedMR)">Issue Selected Items</button> -->
              <button class="btn btn-primary" :disabled="loading" @click="submitInlinePRN">
                <span v-if="loading" class="spinner-border spinner-border-sm mr-1"></span>
                {{ loading ? 'Submitting...' : 'Submit PRN' }}
              </button>
              <button class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>

      <!-- PRN Detail Modal -->
      <div class="modal fade" id="viewPRNModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content shadow rounded-3">
            <div class="modal-header border-bottom">
              <h5 class="modal-title">PRN Details - <span class="fw-semibold">PRN-{{ selectedPRN?.id }}</span></h5>
              <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close"> &times; </button>
            </div>
            <div class="modal-body" v-if="selectedPRN">
              <div class="row mb-3">
                <div class="col-md-6">
                  <p class="mb-1"><strong>Requested By:</strong> {{ selectedPRN.mr.requested_by_user?.name }}</p>
                  <p class="mb-1"><strong>MR ID:</strong> MR-{{ selectedPRN.mr.id }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                  <p class="mb-1"><strong>Created At:</strong> {{ new Date(selectedPRN.created_at).toLocaleString() }}
                  </p>
                </div>
              </div>

              <h6 class="mb-3 fw-bold">Products</h6>
              <div class="table-responsive">
                <table class="table table-striped table-hover align-middle dataTable">
                  <thead class="table-light bg-light border-top">
                    <tr>
                      <th scope="col" class="text-center">#</th>
                      <th scope="col">Product</th>
                      <th scope="col" class="text-center">Quantity</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(detail, index) in selectedPRN.details" :key="detail.id">
                      <td class="text-center">{{ index + 1 }}</td>
                      <td>{{ detail.product?.name }}</td>
                      <td class="text-center">{{ detail.qty }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer bg-light border-top">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <form ref="printPRNPdfForm" :action="`${$store.state.api_url}api/web/v1/prn/pdf`" method="POST" target="_blank">
      <input type="hidden" name="token" :value="$store.state.token" />
      <input type="hidden" name="prn_id" />
    </form>
  </section>
</template>

<script>
export default {
  name: "PRNManager",
  data() {
    return {
      stockList: [
        { product: 'Mouse', available: 10 },
        { product: 'Keyboard', available: 5 },
      ],
      selectedMR: null,
      remainingItems: [],
      mrs: [],
      prns: [],
      mrRequests: 0,
      selectedPRN: null,
      activeTab: 'prn',
      rejectingId: null,
    };
  },
  mounted() {
    this.fetchMR_PRN();
  },
  watch: {
    activeTab(newTab) {
      this.$nextTick(() => {
        // Destroy any existing DataTable instance before re-initializing
        $('.dataTable1').DataTable().destroy();
        $('.dataTable1').DataTable();
      });
    },
  },
  methods: {
    prnStatusClass(prn) {
      if (prn.status == 0 || prn.mr?.status == 0) return 'badge-danger';
      if (!prn.mr || prn.mr.status == 6) return 'badge-dark';
      if (prn.mr.status == 1) return 'badge-warning';
      if (prn.mr.status == 2 || prn.mr.status == 7) return 'badge-success';
      if (prn.mr.status == 3) return 'badge-info';
      if (prn.mr.status == 4) return 'badge-primary';
      if (prn.mr.status == 5) return 'badge-secondary';
      return 'badge-light';
    },
    prnStatusLabel(prn) {
      if (prn.status == 0 || prn.mr?.status == 0) return 'Rejected';
      if (!prn.mr) return 'No MR Found';
      return ({ 1: 'Pending', 2: 'Store Issued', 3: 'Processing', 4: 'BID Generated', 5: 'PO Generated', 6: 'InWard Generated', 7: 'Partial Store Issued' })[prn.mr.status] || 'Unknown';
    },
    async fetchMR_PRN() {
      try {
        const response = await this.callApi('post', 'prn');
        if (response.data.success) {
          this.mrs = response.data.mrs;
          this.prns = response.data.prns;
          this.mrRequests = this.mrs.length;
          this.$nextTick(() => {
            $('.dataTable1').DataTable(); // Initial setup after data load
          });
        }
      } catch (error) {
        console.error('Failed to fetch data:', error);
      }
    },
    getStock(productName) {
      const product = this.stockList.find(p => p.product === productName);
      return product ? product.available : 0;
    },
    viewMR(mr) {
      this.selectedMR = {
        ...mr,
        details: mr.details.map(detail => ({
          ...detail,
          issueQty: 0,
        }))
      };

      $('#viewMRModal').modal('show');
    },
    viewPRN(prn) {
      this.selectedPRN = prn;
      $('#viewPRNModal').modal('show');
    },
    issueSelectedItems(mr) {
      const issuedItems = mr.details.filter(item => item.issueQty > 0);
      if (issuedItems.length === 0) {
        alert("Please enter issue quantity.");
        return;
      }
      issuedItems.forEach(item => {
        const stock = this.stockList.find(s => s.product === item.product.name);
        if (stock) stock.available -= item.issueQty;
      });
      mr.status = 'issued';
      this.$nextTick(() => {

      });
      $('#viewMRModal').modal('hide');
    },
    createPRNForRemaining(mr) {
      this.remainingItems = mr.details
        .filter(item => (item.qty - (item.issueQty || 0)) > 0)
        .map(item => ({
          product: item.product,
          product_id: item.product.id,
          qty: item.qty - (item.issueQty || 0),
        }));

      $('#prnModal').modal('show');
    },
    async submitInlinePRN() {
      if (this.loading) return;
      const prnItems = this.selectedMR.details
        .filter(item => item.prnQty && item.prnQty > 0)
        .map(item => ({
          product_id: item.product.id,
          qty: item.prnQty
        }));

      if (prnItems.length === 0) {
        Swal.fire('Warning', 'No PRN quantities entered.', 'warning');
        return;
      }
      const payload = {
        mr_id: this.selectedMR.id,
        items: prnItems
      };
      this.loading = true;
      try {
      const response = await this.callApi('post', 'prn/store', payload);
      if (response.status === 200 || response.status === 201) {
        $(".dataTable1").DataTable().destroy();
        this.loading = false;
        this.fetchMR_PRN();
        return Swal.fire({
          icon: 'success',
          title: 'Created',
          text: 'PRN created successfully!',
        });
      }
      if (response.status == 422) {
        this.loading = false;
        Swal.fire({
          icon: 'error',
          title: 'Validation Error',
          text: 'Please fill all field',
        });
      }
      else {
        Swal.fire('Error', response.data?.message || 'Unable to create PRN.', 'error');
      }
      } finally {
        this.loading = false;
      }
    },
    submitPRNPdf(prnId) {
      const form = this.$refs.printPRNPdfForm;
      form.querySelector('input[name="prn_id"]').value = prnId;
      form.submit();
    },
    async rejectMaterialRequest(mr) {
      const confirmation = await Swal.fire({
        title: 'Reject this Material Request?',
        text: `MR-${mr.id} will be marked as Rejected. No record will be deleted.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, reject it'
      });
      if (!confirmation.isConfirmed) return;

      this.rejectingId = mr.id;
      try {
        const response = await this.callApi('post', 'mr/reject', { id: mr.id });
        if (response.status === 200 && response.data.success) {
          if ($.fn.DataTable.isDataTable('.dataTable1')) $('.dataTable1').DataTable().destroy();
          this.mrs = this.mrs.filter(item => item.id !== mr.id);
          this.mrRequests = this.mrs.length;
          this.$nextTick(() => $('.dataTable1').DataTable());
          return Swal.fire('Rejected', response.data.message, 'success');
        }
        Swal.fire('Error', response.data?.message || 'Unable to reject this Material Request.', 'error');
      } finally {
        this.rejectingId = null;
      }
    }
  },
};
</script>

<style scoped>
.table td,
.table th {
  vertical-align: middle;
}
</style>
