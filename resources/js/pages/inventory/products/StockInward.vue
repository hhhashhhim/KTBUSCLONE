<template>
    <section class="section">
      <div class="section-body">
        <div class="row">
          <div class="col-12 mt-4">
            <ul class="nav nav-tabs mb-3">
              <li class="nav-item ml-2">
                <a class="nav-link" :class="{ active: activeTab === 'inward' }" href="#" @click.prevent="activeTab = 'inward'">
                  <b>Stock Inwards</b>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'pos' }" href="#" @click.prevent="activeTab = 'pos'">
                  <b>
                    Purchase Orders Requests- POs
                    <span class="badge badge-primary">{{ pos.length }}</span>
                  </b>
                </a>
              </li>
            </ul>
        </div>
        <!-- Inward Table -->
        <div class="col-12"  v-if="activeTab === 'inward'">
            <div class="card card-primary">
              <div class="card-header">
                <h4>Stock Inward</h4> 
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-striped table-hover dataTable1">
                    <thead>
                      <tr>
                        <th>Sr No.</th>
                        <th>GRN #</th>  
                        <th>PO #</th>  
                        <th>Date</th>
                        <th>Supplier</th> 
                        <th>Received By</th> 
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(grn, index) in inwards" :key="grn.id">
                          <td>{{ index + 1 }}</td>
                          <td>GRN - {{ grn.id }}</td> 
                          <td>PO - {{ grn.po_id }}</td> 
                          <td>{{ new Date(grn.created_at).toLocaleString() }}</td>
                          <td>{{ grn.supplier?.name || '-' }}</td>
                          <td>{{ grn.received_by || '-' }}</td>
                          <td>
                            <!-- Inside your table -->
                              <button class="btn btn-info btn-sm" @click="viewInward(grn.id)">
                                <i class="fas fa-eye"></i>
                              </button>
                              <button class="btn btn-dark btn-sm mx-1" @click="submitInwardPdf(grn.id)">
                                <i class="fas fa-print"></i>
                              </button>
                          </td>
                        </tr>
                      </tbody>
                  </table>
                </div>
              </div>
            </div>
        </div>  
          <!-- POs Table -->
          <div class="col-12" v-if="activeTab === 'pos'">
              <div class="card card-primary">
                <div class="card-header">
                  <h4>Purchase Orders Requests- PO</h4>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-striped table-hover dataTable1">
                      <thead>
                        <tr>
                          <th>Sr No.</th>
                          <th>PO #</th>
                          <th>PRN #</th>
                          <th>MR #</th>
                          <th>Request By</th>
                          <th>BID #</th>
                          <th>Date</th>
                          <th>Grand Total</th>
                          <th>Supplier Name</th> 
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <template v-for="(po, index) in pos" :key="po.id">
                          <tr>
                            <td>{{ index + 1 }}</td>
                            <td>PO - {{ po.id }}</td>
                            <td>PRN-{{ po.prn?.id || '-' }}</td>
                            <td>MR-{{ po.mr?.id || '-' }}</td>
                            <td>{{ po.mr?.requested_by_user?.name || '-' }}</td>
                            <td>BID - {{ po.bid_id || '-' }}</td>
                            <td>{{ new Date(po.created_at).toLocaleString() }}</td>
                            <td>{{ po.total }} PKR</td>
                            <td>{{ po.supplier?.name || '-' }}</td>
                            <td>
                              <button class="btn btn-success btn-sm mr-1 px-2" @click="addInward(po)">
                                <i class="fas fa-plus"></i>
                              </button>
                              <!-- <button class="btn btn-info btn-sm" @click="viewPODetail(po.id)">
                                <i class="fas fa-eye"></i> View
                              </button> -->
                            </td>
                          </tr>
                        </template>

                        <tr v-if="pos.length === 0">
                          <td colspan="9" class="text-center">No Purchase Orders Found</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
          </div>
        </div> 
         <!-- Add Inward Modal -->   
          <Add
            id="addInwardModal"
            :heading="'Add New Inward'"
            :errors="validationErrors"
            :success="success"
            :formID="formID"
            :poDetails="selectedPO"
          >
          <div
              class="row"
              v-for="(product, index) in selectedPO.products"
              :key="index"
            >
              <!-- Product ID (hidden if you need it for submission) -->
              <input type="hidden" :value="product.product_id" />
              <!-- Product Name -->
              <div class="form-group col-md-3">
                <label>Product Name</label>
                <input type="text" class="form-control" :value="product.product_name" disabled />
              </div>
              <!-- Receivable Quantity -->
              <div class="form-group col-md-3">
                <label>Receivable Quantity</label>
                <input type="text" class="form-control" :value="product.qty" disabled />
              </div>
              <!-- Already Received -->
              <div class="form-group col-md-3">
                <label>Already Received Quantity</label>
                <input type="text" class="form-control" :value="product.already_received_qty" disabled />
              </div>
              <!-- Newly Received -->
              <div class="form-group col-md-3">
                  <label>Received Quantity <span class="text-danger">*</span></label>
                  <input
                    type="number"
                    class="form-control"
                    placeholder="Enter Received Quantity"
                    v-model.number="product.received_qty"
                    :max="getRemainingQty(product)"
                    min="0"
                    @input="validateQty(product)"
                    :disabled="isCompleted(product)"
                  />
                  <small class="text-danger" v-if="product.received_qty > getRemainingQty(product)">
                    ❌ You can't enter more than the remaining quantity ({{ getRemainingQty(product) }})
                  </small>
                  <small class="text-success" v-else-if="isCompleted(product)">
                    ✅ Quantity fully received.
                  </small>
                  <small class="text-muted" v-else>
                    Max allowed: <b>{{ getRemainingQty(product) }}</b>
                  </small>
                </div>

            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="button" class="btn btn-primary" @click="submitInward">Submit Inward</button>
            </div>
          </Add>
          <!-- POs Modal --> 
          <div class="modal fade" id="viewPOModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document" v-if="selectedPOs.length">
              <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                  <h5 class="modal-title p-2">
                   Purchase Order - POs Details
                  </h5>
                  <button type="button" class="close text-white" data-dismiss="modal" @click="selectedPOs = []">
                    <span>&times;</span>
                  </button>
                </div>
                <div class="modal-body bg-light">
                  <div
                    v-for="(po, idx) in selectedPOs"
                    :key="po.id"
                    class="mb-3 p-4 border rounded shadow-sm"
                  >
                  <h6 class="mb-3 font-weight-bold border-bottom pb-2 d-flex justify-content-between align-items-center">
                    <span>
                        PO #{{ po.id }} &nbsp;&nbsp; | &nbsp;&nbsp;
                        BID #: <span class="text-dark">{{ po.bid_id }}</span> &nbsp;&nbsp; | &nbsp;&nbsp;
                        PRN #: <span class="text-dark">{{ po.prn_id }}</span> &nbsp;&nbsp; | &nbsp;&nbsp;
                        MR #: <span class="text-dark">{{ po.mr_id }}</span>
                      </span>
                      <h6 class="text-dark">
                        Date :
                        {{ new Date(po.created_at).toLocaleString() }}
                      </h6>
                  </h6>
                  <div class="mb-2">
                      <h6 class="font-weight-bold border-bottom pb-1 mb-2">Supplier Information</h6>
                      <div class="row">
                        <div class="col-md-4 mb-1"><strong>Name:</strong> {{ po.supplier?.name }}</div>
                        <div class="col-md-4 mb-1"><strong>Contact:</strong> {{ po.supplier?.contact }}</div>
                        <div class="col-md-4 mb-1"><strong>CNIC:</strong> {{ po.supplier?.cnic }}</div>
                        <div class="col-md-12 mb-1"><strong>Address:</strong> {{ po.supplier?.address }}</div>
                      </div>
                    </div>
                    <table class="table table-bordered table-striped table-sm shadow-sm dataTable">
                    <thead class="thead text-center">
                      <tr>
                        <th class="py-2 px-3">#</th>
                        <th class="py-2 px-3">Product</th>
                        <th class="py-2 px-3">Qty</th>
                        <th class="py-2 px-3">Rate</th>
                        <th class="py-2 px-3">Subtotal</th>
                        <th class="py-2 px-3">Tax</th>
                        <th class="py-2 px-3">Delivery</th>
                        <th class="py-2 px-3">Discount</th>
                        <th class="py-2 px-3">Grand Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr 
                        v-for="(item, i) in po.po_details" 
                        :key="item.id" 
                        class="text-center align-middle"
                      >
                        <td class="py-2 px-3">{{ i + 1 }}</td>
                        <td class="py-2 px-3">{{ item.product?.name || 'N/A' }}</td>
                        <td class="py-2 px-3">{{ item.qty }}</td>
                        <td class="py-2 px-3">{{ parseFloat(item.rate).toFixed(2) }}</td>
                        <td class="py-2 px-3">{{ parseFloat(item.sub_total).toFixed(2) }}</td>
                        <td class="py-2 px-3">{{ parseFloat(item.tax).toFixed(2) }}</td>
                        <td class="py-2 px-3">{{ parseFloat(item.delivery).toFixed(2) }}</td>
                        <td class="py-2 px-3">{{ parseFloat(item.discount).toFixed(2) }}</td>
                        <td class="py-2 px-3 font-weight-bold text-success">{{ parseFloat(item.net_amount).toFixed(2) }}</td>
                      </tr>
                      <tr class="table-info font-weight-bold">
                        <td colspan="8" class="text-right py-2 px-3">PO Grand Total</td>
                        <td class="text-success py-2 px-3"><h6>{{ parseFloat(po.total).toFixed(2) }}</h6></td>
                      </tr>
                    </tbody>
                  </table>

                  </div>
                </div>
              </div>
              </div>
          </div>
           <!-- Inward Modal --> 
          <div class="modal fade" id="inwardModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document" v-if="selectedGRN && selectedGRN.details?.length">
              <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                  <h5 class="modal-title p-2">
                   Good Receive Note - GRNs Details
                  </h5>
                  <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                  </button>
                </div>
                <div class="modal-body bg-light">
                  <table class="table table-bordered table-sm dataTable">
                    <thead>
                      <tr class="bg-light text-center">
                        <th>#</th>
                        <th>Received By</th>
                        <th>Product</th>
                        <th>Received Quantity</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr class="table-info font-weight-bold">
                        <td colspan="5">
                          GRN #{{ selectedGRN.id }}
                        </td>
                      </tr>
                      <tr
                        v-for="(item, idx) in selectedGRN.details"
                        :key="item.id"
                        class="text-center"
                      >
                        <td>{{ idx + 1 }}</td>
                        <td>{{ selectedGRN.received_by }}</td>
                        <td>{{ item.product?.name || 'N/A' }}</td>
                        <td>{{ item.qty ?? 0 }}</td>
                        <td>{{ new Date(item.created_at).toLocaleString() }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
      </div>
        <form ref="printInwardPdfForm" :action="`${$store.state.api_url}api/web/v1/grn/pdf`" method="POST" target="_blank">
        <input type="hidden" name="token" :value="$store.state.token" />
        <input type="hidden" name="inward_id" />
      </form>
    </section>
  </template>
  
  <script>
import Add from '../../../components/Add.vue'; 
  
  export default {
    name: "POManager",
    components: {
      Add, 
    },
    data() {
      return { 
        selectedGRN: null, 
        formID: 'addPOForm',
        activeTab: 'inward', // default tab
        inwards: [],
        pos: [],    
        selectedPOs: [],
        selectedPO: {},
        po: null, 
        data: {
          product_name: '',
          po_no: '',
          qty: '',
          received_qty: '',
          alreadey_received_qty: '', 
          date: '',  
          status: '',  
          supplier: '', 
          receivedBy: '',
        },
        inward: [
          { 
            id: '',
            product_name: '',
            po_id: '',
            qty: '',
            received_qty: '', 
            alreadey_received_qty: '', 
            date: '',
            status: '',  
            supplier: '',
            receivedBy: '',
          }, 
        ],
        poFormRows: [    // This is just for the form
        {
            id: Date.now(),
            product_name: '',
            po_no: '', 
            qty: '', 
            received_qty: '',
            alreadey_received_qty: '', 
            date: new Date().toLocaleDateString('en-GB'),
            status: '',  
            supplier: '',
            receivedBy: '',
        }
        ],
        isModalOpen: false,
            loading: false,
            validationErrors: [],
            success: '',
        };
        },
        mounted() {
          
          this.fetchPOAndInwards();  
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
        async fetchPOAndInwards() {
            try {
              const response = await this.callApi('post', 'inward');
              if (response.status == 200) {
                this.pos     = response.data.pos;
                this.inwards = response.data.inwards;
                this.$nextTick(() => {
                 $('.dataTable1').DataTable(); // Initial setup after data load
                });
              } else {
                console.error("Error loading data", response.data.message);
              }
            } catch (error) {
              console.error("API error:", error);
            }
        },
        async viewPODetail(po_id) {
            try {
              // Fetch the PO data for the given PRN ID
              const response = await this.callApi('post', 'pos/show', { po_id });
              if (response.data.success) { 
                this.selectedPOs = response.data.pos;  
                this.fetchPOAndInwards(); 
                setTimeout(() => {
                  $('#viewPOModal').modal('show');
                }, 100); // Delay in milliseconds (100ms)
              }
            } catch (error) {
              console.error('Error fetching PO data for PRN:', error);
            }
        }, 
        async addInward(po_id) {
            try {
              const response = await this.callApi('post', 'pos/getSingle', { po_id });
              if (response.data.success) {
                this.selectedPO = {
                  ...response.data.po,
                  products: response.data.po.products.map(p => ({
                    ...p,
                    received_qty: 0 // Add editable quantity field
                  }))
                };
                this.$nextTick(() => {
                  $('#addInwardModal').modal('show');
                });
              }
            } catch (error) {
              console.error('Error fetching PO for inward:', error);
            }
        },
        getRemainingQty(product) {
             return product.qty - (product.already_received_qty || 0);
        },
        validateQty(product) {
            const max = this.getRemainingQty(product);
            if (product.received_qty > max) {
              product.received_qty = max;
            } else if (product.received_qty < 0) {
              product.received_qty = 0;
            }
          },

        isCompleted(product) {
            return product.received_qty_so_far >= product.total_qty;
        },
        async submitInward() { 
              const payload = {
                po_id: this.selectedPO.id,
                products: this.selectedPO.products.map(p => ({
                  product_id: p.product_id,
                  product_name: p.product_name,
                  qty: p.qty,
                  already_received_qty: p.already_received_qty,
                  received_qty: p.received_qty ?? 0
                }))
              };
              const response = await this.callApi('post', 'inward/store', payload);
              console.log(response.status);
                 if (response.status === 200 || response.status === 201) {
                  $(".dataTable1").DataTable().destroy();
                    this.loading = false;
                    this.fetchPOAndInwards();
                    this.clearForm();
                    return Swal.fire({
                      icon: 'success',
                      title: 'Created',
                      text: 'Stock Inward added successfully!',
                    });
                } 
                if(response.status == 422){ 
                    this.loading = false;
                    Swal.fire({
                      icon: 'error',
                      title: 'Validation Error',
                      text: 'Please fill all field',
                    });
                }
                else{
                    Swal.fire('Error', err.response?.data , 'error');
                } 
        }, 
        async viewInward(grn) {
          try {
            const res = await this.callApi('post', 'inward/get-inward-details', { grn });
            this.selectedGRN = res.data;
            this.$nextTick(() => {
              $('#inwardModal').modal('show');
            });
          } catch (error) {
            this.$swal('Error', 'Failed to fetch GRN details.', 'error');
          }
        },
        submitInwardPdf(inwardId) {
          const form = this.$refs.printInwardPdfForm; 
          form.querySelector('input[name="inward_id"]').value = inwardId; 
          form.submit();
        },
        clearForm() {
            this.data = {
            product_name: '',
            po_no: '',
            qty: '',
            received_qty: '',
            alreadey_received_qty: '', 
            date: '',
            status: '',  
            supplier: '',
            receivedBy: '',
            };
        },  
    }
  };
  </script>
  