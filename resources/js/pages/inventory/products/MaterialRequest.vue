<template>
  <section class="section">
    <div class="section-body">
      <div class="row">
        <div class="col-12" v-if="activeTab === 'mr'">
          <div class="card card-primary">
            <div class="card-header">
              <h4>Material Requests - MR</h4>
              <div class="card-header-action">
                <button class="btn btn-primary" data-toggle="modal" :data-target="'#' + formID" @click="clearForm">
                  Add New MR
                </button>
              </div>
            </div>
            <div class="card-body">
              <!-- MR List Table -->
              <div class="table-responsive">
                <table class="table table-striped dataTable">
                  <thead>
                    <tr>
                      <th>Sr No.</th>
                      <th>MR No</th>
                      <th>Date</th>
                      <th>Request By</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(mr, index) in mrs" :key="mr.id">
                      <td>{{ index + 1 }}</td>
                      <td>MR-{{ mr.id }}</td>
                      <td>{{ new Date(mr.created_at).toLocaleString() }}</td>
                      <td><span>{{ mr.requested_by_user?.name }}</span></td>
                      <td>
                        <span
                          class="badge text-white"
                          :class="{
                            'badge-danger': mr.status == 0,
                            'badge-warning': mr.status == 1,
                            'badge-success': mr.status == 2 || mr.status == 7, // shared badge color
                            'badge-primary': mr.status == 3,
                            'badge-secondary': mr.status == 4,
                            'badge-info': mr.status == 5,
                            'badge-dark': mr.status == 6,
                          }"
                        >
                          {{
                            mr.status == 0 ? 'Rejected' :
                            mr.status == 1 ? 'Processing' :
                            mr.status == 2 ? 'Store Issued' :
                            mr.status == 3 ? 'PRN Generated' :
                            mr.status == 4 ? 'BID Generated' :
                            mr.status == 5 ? 'PO Generated' :
                            mr.status == 6 ? 'Inward Generated' :
                            mr.status == 7 ? 'Store Partial Issued' :
                            'Unknown'
                          }}
                        </span>
                      </td>
                      <td>
                        <button
                          v-if="mr.status == 1"
                          class="btn btn-danger btn-sm mx-1"
                          @click="confirmDelete(mr.id)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                          <button class="btn btn-info btn-sm mx-1" @click="viewMR(mr)" data-toggle="modal" data-target="#viewMRModal">
                            <i class="fas fa-eye"></i>
                          </button>
                            <!-- Pass mr.id instead of full mr object -->
                            <button class="btn btn-dark btn-sm mx-1" @click="printMR(mr.id)">
                              <i class="fas fa-print"></i>
                            </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <!-- End Table -->
            </div>
          </div>
        </div>
      </div>
      <!-- Add MR Modal -->
      <div class="modal fade" :id="formID" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-80w modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Add New Material Request</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="row">
                <!-- Product select --> 
                <div class="form-group col-md-4">
                  <div class="d-flex justify-content-between">
                    <label>Select Product</label>
                    <!-- <button class="btn btn-primary p-0 m-0 px-2" data-toggle="modal" data-target="#addProducts">Add New</button> -->
                  </div>
                  <select v-model="singleProduct.product_id" @change="singleProduct.product_id = $event.target.value" class="form-control ">
                    <option value="">Select</option>
                    <option v-for="prod in products" :key="prod.id" :value="prod.id">{{ prod.name }}</option>
                  </select>
                </div>
                <!-- Qty -->
                <div class="form-group col-md-3">
                  <label>Quantity</label>
                  <input type="number" class="form-control" v-model="singleProduct.qty">
                </div>
                <!-- Reason -->
                <div class="form-group col-md-4">
                  <label>Reason</label>
                  <textarea class="form-control" v-model="singleProduct.reason"></textarea>
                </div>
                <!-- Add Button -->
                <div class="form-group col-md-1">
                  <label>Action</label>
                  <button class="btn btn-success btn-sm" @click="addProduct">
                    <i class="fa fa-plus"></i> Add
                  </button>
                </div>
              </div>

              <!-- Products Added Table -->
              <div class="table-responsive mt-3">
                <h5>Products Added</h5>
                <table class="table table-bordered">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Product</th>
                      <th>Qty</th>
                      <th>Reason</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in productsList" :key="index">
                      <td>{{ index + 1 }}</td>
                      <td>{{ getProductName(item.product_id) }}</td>
                      <td>{{ item.qty }}</td>
                      <td>{{ item.reason }}</td>
                      <td>
                        <button class="btn btn-danger btn-sm" @click="removeProduct(index)">
                          <i class="fa fa-trash"></i> Remove
                        </button>
                      </td>
                    </tr>
                    <tr class="text-center w-100" v-if="productsList.length == 0">
                      <p><i>No Product Added</i></p>
                    </tr>
                  </tbody>
                </table>
              </div>

            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-primary" :disabled="loading" @click="submitMR">
                {{ loading ? 'Submitting...' : 'Submit' }}
              </button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
      <!-- View MR Details Modal -->
      <div class="modal fade" id="viewMRModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
          <div class="modal-content" v-if="selectedMR">
            <div class="modal-header border-bottom">
              <h5 class="modal-title">MR Details - MR-{{ selectedMR.id }}</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
              <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th class="text-center">#</th>
                      <th>Product Name</th>
                      <th>Qty</th>
                      <th>Store Issued Qty</th>
                      <th>Reason</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(detail, index) in selectedMR.details" :key="index">
                      <td class="text-center">{{ index + 1 }}</td>
                      <td v-if="editingIndex === index">
                        <select v-model="editDetailData.product_id" class="form-control form-control-sm select2">
                          <option v-for="prod in products" :key="prod.id" :value="prod.id">{{ prod.name }}</option>
                        </select>
                      </td>
                      <td v-else>
                        {{ detail.product.name }}
                      </td>
                      <td v-if="editingIndex === index">
                        <input type="number" v-model="editDetailData.qty" class="form-control form-control-sm" />
                      </td>
                      <td v-else>
                        {{ detail.qty }}
                      </td>
                      <td> {{ detail.store_issued_qty ?? 0 }}</td> 
                      <td v-if="editingIndex === index">
                        <input type="text" v-model="editDetailData.reason" class="form-control form-control-sm" />
                      </td>
                      <td v-else>
                        {{ detail.reason }}
                      </td>
                      <td v-if="selectedMR.status == 1">
                        <!-- If editing, show Save/Cancel -->
                        <template v-if="editingIndex === index">
                          <button class="btn btn-success btn-sm mx-1" @click="saveDetail(index)">Save</button>
                          <button class="btn btn-secondary btn-sm" @click="cancelEdit()">Cancel</button>
                        </template>

                        <!-- Otherwise, show Edit/Delete -->
                        <template v-else>
                          <button class="btn btn-primary btn-sm mx-1" @click="editDetail(detail, index)">Edit</button>
                          <button class="btn btn-danger btn-sm" @click="deleteDetail(detail.id, index)">Delete</button>
                        </template>
                      </td>
                      <td v-else></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer bg-light border-top">
              <button class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
      <AddProductModal></AddProductModal>
    </div>
    <form :action="`${this.$store.state.api_url}api/web/v1/mr/pdf`" method="post" ref="printmrPdf" target="_blank">
      <input type="hidden" name="token" :value="$store.state.token"> 
      <input type="hidden" name="mr_id" :value="selectedMR"> <!-- new hidden input -->
    </form>
  </section>
</template>

<script>
import AddProductModal from '../modal/addProductsModal.vue'; 

  export default {
    components: { 
      AddProductModal,
    },
    data() {
      return {
        printMrId: null,
        activeTab:'mr',
        formID: 'addMRForm',
        products: [],
        mrs: [],
        singleProduct: { product_id: '', qty: '', reason: '' },
        productsList: [],
        selectedMR: null,
        loading: false,
        deleteId: null,
        editingIndex: null,
        editDetailData: {}
      };
    },
    mounted() { 
      this.fetchMRs();
      // Attach modal close listener for Bootstrap 4
      const modalEl = document.getElementById('viewMRModal');
      if (modalEl) {
        $(modalEl).on('hidden.bs.modal', this.cancelEdit);
      }
      this.$nextTick(function () {
        $('.select2').select2().on('change', (e) => {
          this.singleProduct.product_id = e.target.value;
        });
      })
    },
    beforeUnmount() {
      const modalEl = document.getElementById('viewMRModal');
      if (modalEl) {
        $(modalEl).off('hidden.bs.modal', this.cancelEdit);
      }
    }, 
  methods: {
    async fetchMRs() { 
        const response = await this.callApi('post', 'mr');  // Correct your API endpoint here
        this.mrs      = response.data.data;
        this.products = response.data.products;
        this.users    = response.data.users;
        this.$nextTick(() => {
          $(".dataTable").DataTable(); // Initial setup after data load
        }); 
    }, 
    getProductName(id) {
      const product = this.products.find(p => p.id == id);
      return product ? product.name : 'Unknown';
    },
    addProduct() {
      if (!this.singleProduct.product_id || !this.singleProduct.qty) {
        Swal.fire('Error', 'Please select product and quantity.', 'error');
        return;
      }
      this.productsList.push({...this.singleProduct});
      this.singleProduct = { product_id: '', qty: '', reason: '' };
    },
    removeProduct(index) {
      this.productsList.splice(index, 1);
    },
    async submitMR() {
      if (this.productsList.length === 0) {
        Swal.fire('Error', 'Add products before submitting.', 'error');
        return;
      } 
        const payload = { details: this.productsList };
        const response = await this.callApi('post', 'mr/store', payload);
        if (response.status === 200 || response.status === 201) { 
            this.loading = false;
            this.fetchMRs();
            return Swal.fire({
              icon: 'success',
              title: 'Created',
              text: 'MR Created successfully!',
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
    viewMR(mr) {
      this.selectedMR = JSON.parse(JSON.stringify(mr)); // Deep clone to avoid direct mutation
      this.editingIndex = null;
      this.editDetailData = {};
    },
    editDetail(detail, index) {
      this.editingIndex = index;
      this.editDetailData = JSON.parse(JSON.stringify(detail)); // deep clone
      this.editDetailData.product_id = detail.product.id; // Ensure product_id is set
    },
    cancelEdit() {
        this.editingIndex = null;
        this.editDetailData = {};
    },
    async saveDetail(index) {
        
          if (!this.editDetailData || !this.editDetailData.id) {
            throw new Error('No detail selected for update.');
          }
          const payload = {
            id: this.editDetailData.id,
            qty: this.editDetailData.qty,
            reason: this.editDetailData.reason,
          };
          const response = await this.callApi('post', 'mr/detail-update', payload);
          if (response.status === 200 || response.status === 201) { 
            this.loading = false;
            this.fetchMRs();
            this.clearForm();
            return Swal.fire({
              icon: 'success',
              title: 'Updated',
              text: 'MR Updated successfully!',
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
    async deleteDetail(id, index) {
      try {
        const confirm = await Swal.fire({
          title: 'Are you sure?',
          text: 'You will not be able to recover this detail!',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Yes, delete it!',
        });

        if (confirm.isConfirmed) {
          const response = await this.callApi('post', 'mr/detail-delete', { id });
          if (response.data && response.data.success) {
            // Remove detail from local array
            this.selectedMR.details.splice(index, 1);
            Swal.fire('Success', 'Deleted successfully!', 'success');
          } else {
            Swal.fire('Error', error.response?.data?.message || 'Something went wrong!', 'error');
          }
        }
      } catch (error) { 
        Swal.fire('Error', error.response?.data?.message || 'Failed to delete!', 'error');
      }
    },
    async confirmDelete(id) {
    
        const confirm = await Swal.fire({
          title: 'Delete Material Request',
          text: 'Are you sure you want to permanently delete this Material Request?',
          icon: 'error',
          showCancelButton: true,
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Delete',
          cancelButtonText: 'Cancel',
          reverseButtons: true,
          focusConfirm: false,
          customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
          }
        });

        if (confirm.isConfirmed) {
          // User clicked "Yes, delete it!"
          const response = await this.callApi('post', 'mr/mr-delete', { id });
          if (response.status === 200 || response.status === 201) { 
            this.loading = false;
            this.fetchMRs();
            this.clearForm();
            return Swal.fire({
              icon: 'success',
              title: 'Deleted',
              text: 'Bid Summary Deleted successfully!',
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
        } 
      
    },
    async printMR(id) {
      this.selectedMR = id;
      // Wait for DOM to update the input field
      this.$nextTick(() => {
        this.$refs.printmrPdf.submit();
      });
    },
    beforeUnmount() {
      const modalEl = document.getElementById('viewMRModal');
      if (modalEl) {
        modalEl.removeEventListener('hidden.bs.modal', this.cancelEdit);
      }
    },
     clearForm() {
      this.productsList = [];
      this.singleProduct = { product_id: '', qty: '', reason: '' };
    },
  }
}
</script>
