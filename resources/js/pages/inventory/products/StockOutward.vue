<template>
  <section class="section">
    <div class="section-body">
      <div class="row">
        <div class="col-12 mt-4">
          <ul class="nav nav-tabs mb-3">
            <li class="nav-item ml-2">
              <a class="nav-link" :class="{ active: activeTab === 'outward' }" href="#"
                @click.prevent="activeTab = 'outward'">
                <b>Stock Outwards</b>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" :class="{ active: activeTab === 'mrs' }" href="#" @click.prevent="activeTab = 'mrs'">
                <b>
                  Material Requests - MRs
                  <span class="badge badge-primary">{{ mrs.length }}</span>
                </b>
              </a>
            </li>
          </ul>
        </div>

        <!-- Stock Outwards Table -->
        <div class="col-12" v-if="activeTab === 'outward'">
          <div class="card card-primary">
            <div class="card-header">
              <h4>Stock Outward</h4>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-hover dataTable2">
                  <thead>
                    <tr>
                      <th>Sr No.</th>
                      <th>MR #</th>
                      <th>SIN #</th>
                      <th>Date</th>
                      <th>Requested By</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in outwards" :key="item.id">
                      <td>{{ index + 1 }}</td>
                      <td>MR-{{ item.mr_id }}</td>
                      <td>SIN-{{ item.id }}</td>
                      <td>{{ new Date(item.created_at).toLocaleString() }}</td>
                      <td>{{ item.requested_by }}</td>
                      <td>
                        <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#viewOutwardModal"
                          @click="viewOutward(item)">
                          <i class="fas fa-eye"></i> View
                        </button>
                        <button class="btn btn-dark btn-sm mx-1" @click="submitOutwardPdf(item.id)">
                          <i class="fas fa-print"></i>
                        </button>
                      </td>
                    </tr>
                    <tr v-if="outwards.length === 0">
                      <td colspan="5" class="text-center">No Stock Outward Records Found</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <!-- Material Requests Table -->
        <div class="col-12" v-if="activeTab === 'mrs'">
          <div class="card card-primary">
            <div class="card-header">
              <h4>Material Requests - MRs <span class="badge badge-primary">{{ mrs.length }}</span></h4>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-hover dataTable2">
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
                      <td>{{ mr.requested_by_user?.name || 'N/A' }}</td>
                      <td>
                        <button class="btn btn-info btn-sm" @click="viewMR(mr)">
                          <i class="fas fa-eye"></i> View & Issue
                        </button>
                      </td>
                    </tr>
                    <tr v-if="mrs.length === 0">
                      <td colspan="5" class="text-center">No Material Requests Found</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- MR Details Modal -->
        <div class="modal fade" id="viewMRModal" tabindex="-1">
          <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">MR Details - MR-{{ selectedMR?.id }}</h5> <!-- Fixed here -->
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
              </div>

              <div class="modal-body" v-if="selectedMR">
                <table class="table table-bordered dataTable2">
                  <thead class="table-light bg-light border-top ">
                    <tr>
                      <th>#</th>
                      <th>Bus Number</th>
                      <th>Product</th>
                      <th>Requested Qty</th>
                      <th>Available Stock</th>
                      <th>Already Issued Qty</th>
                      <th>Issuance Qty</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in selectedMR.details" :key="index">
                      <td>{{ index + 1 }}</td>
                      <td>{{ item.bus?.bus_number }}</td>
                      <td>{{ item.product?.name }}</td>
                      <td>{{ item.qty }}</td>
                      <td>{{ item.product?.qty }}</td>
                      <td>{{ item.issued_qty || 0 }}</td>
                      <td>
                        <input type="number" class="form-control form-control-sm" :max="remainingQty(item)" :min="0"
                          v-model.number="item.prnQty" @input="validateQty(item)"
                          :disabled="item.product?.qty === 0 || remainingQty(item) === 0"
                          placeholder="Enter Issuance Qty" />

                        <small v-if="remainingQty(item) === 0" class="text-danger d-block mt-1">
                          Issuance Completed
                        </small>
                      </td>
                    </tr>
                  </tbody>
                </table>

              </div>

              <div class="modal-footer table-light bg-light border-top">
                <button class="btn btn-primary" @click="submitIssuance">Submit Issuance</button>
                <button class="btn btn-secondary" data-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
        <!-- Outward Details Modal -->
        <div class="modal fade" id="viewOutwardModal" tabindex="-1">
          <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Outward Details - Invoice-{{ selectedOutward?.id }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
              </div>

              <div class="modal-body" v-if="selectedOutward">
                <p><strong>Requested By:</strong> {{ selectedOutward.requested_by }}</p>
                <p><strong>Date:</strong> {{ new Date(selectedOutward.created_at).toLocaleString() }}</p>

                <table class="table table-bordered dataTable">
                  <thead class="table-light bg-light border-top">
                    <tr>
                      <th>#</th>
                      <th>Bus Number</th>
                      <th>Product</th>
                      <th>Qty</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(detail, index) in selectedOutward.details" :key="index">
                      <td>{{ index + 1 }}</td>
                      <td>{{ detail.bus?.bus_number ?? 'N/A'}}</td>
                      <td>{{ detail.product?.name }}</td>
                      <td>{{ detail.qty }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="modal-footer table-light bg-light border-top">
                <button class="btn btn-secondary" data-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <AddProductModal></AddProductModal>
    </div>
    <form ref="printOutwardPdfForm" :action="`${$store.state.api_url}api/web/v1/sin/pdf`" method="POST" target="_blank">
      <input type="hidden" name="token" :value="$store.state.token" />
      <input type="hidden" name="outward_id" />
    </form>
  </section>
</template>

<script>
import Add from '../../../components/Add.vue';
import AddProductModal from '../modal/addProductsModal.vue';
export default {
  name: "POManager",
  components: {
    Add,
    AddProductModal,
  },
  data() {
    return {
      activeTab: 'outward',
      outwards: [],
      mrs: [],
      buses: [],
      bus_id: '',
      selectedMR: null,
      selectedOutward: null,
      data: {
        product_name: '',
        invoice_no: '',
        qty: '',
        date: '',
        requestBy: '',
        assignedBy: ''
      },
      isModalOpen: false,
      loading: false,
      validationErrors: [],
      success: '',
    };
  },
  mounted() {
    this.fetchMRAndOutwards();
  },
  watch: {
    activeTab(newTab) {
      this.$nextTick(() => {
        // Destroy any existing DataTable instance before re-initializing
        $('.dataTable2').DataTable().destroy();
        $('.dataTable2').DataTable();
      });
    },
  },
  methods: {
    async fetchMRAndOutwards() {
      try {
        const response = await this.callApi('post', 'outward');
        if (response.data.success) {
          this.outwards = response.data.outwards || []; // Store Issuance Notes
          this.mrs = response.data.mrs || [];           // Material Requests, if included
          this.$nextTick(() => {
            $('.dataTable2').DataTable(); // Initial setup after data load
          });
        } else {
          console.error("Error loading data:", response?.data?.message || 'Unknown error');
        }

      } catch (error) {
        console.error("API error:", error.message || error);
      }
    },
    viewMR(mr) {
      this.selectedMR = {
        ...mr,
        details: mr.details.map(detail => ({
          ...detail,
          prnQty: 0, // Use prnQty here to match the v-model in input
        }))
      };

      $('#viewMRModal').modal('show'); // Trigger Bootstrap modal
    },
    viewOutward(item) {
      this.selectedOutward = item; // Store selected item for modal display
    },
    async submitIssuance() {
      // Prepare payload
      const payload = {
        mr_id: this.selectedMR.id,
        requested_by: this.selectedMR.requested_by_user?.name || 'Unknown', // adjust if needed
        details: this.selectedMR.details
          .filter(item => item.prnQty > 0)
          .map(item => ({
            product_id: item.product_id || item.product?.id,
            bus_id: item.bus_id || item.bus?.id,
            qty: item.prnQty ?? 0,
            rate: item.product?.rate || 0,
            total: (item.prnQty * (item.product?.rate || 0)).toFixed(2),
          })),

      };
      if (payload.details.length === 0) {
        alert("Please enter at least one valid issuance quantity.");
        return;
      }
      const response = await this.callApi("post", "outward/store", payload);
      console.log(response);
      if (response.status === 200 || response.status === 201) {
        $(".dataTable2").DataTable().destroy();
        this.loading = false;
        $('#viewMRModal').modal('hide');
        this.fetchMRAndOutwards(); // refresh your data
        this.viewMR(this.selectedMR);

        return Swal.fire({
          icon: 'success',
          title: 'Issuaed',
          text: 'Stock Issued successfully!',
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
        Swal.fire('Error', err.response?.data, 'error');
      }
    },
    remainingQty(item) {
      return item.qty - (item.issued_qty || 0);
    },
    validateQty(item) {
      const max = this.remainingQty(item);
      if (item.prnQty < 0) {
        item.prnQty = 0;
      } else if (item.prnQty > max) {
        item.prnQty = max;
      }
    },
    submitOutwardPdf(outwardId) {
      const form = this.$refs.printOutwardPdfForm;
      form.querySelector('input[name="outward_id"]').value = outwardId;
      form.submit();
    },
    clearForm() {
      this.data = {
        product_name: '',
        invoice_no: '',
        qty: '',
        date: '',
        requestBy: '',
        assignedBy: ''
      };
    },

  }
};
</script>