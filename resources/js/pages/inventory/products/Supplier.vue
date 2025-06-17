<template>
  <section class="section">
    <div class="section-body">
      <div class="row">
        <div class="col-12">
          <div class="card card-primary">
            <div class="card-header">
              <h4>Suppliers</h4>
              <div class="card-header-action">
                <button class="btn btn-primary" data-toggle="modal" :data-target="'#' + formID" @click="clearForm()">
                  Add New Supplier
                </button>
              </div>
            </div>

            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-hover dataTable1">
                  <thead>
                    <tr>
                      <th>Sr No.</th>
                      <th>Supplier Name</th>
                      <th>Contact Number</th>
                      <th>CNIC Number</th>
                      <th>Address</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(supplier, index) in suppliers" :key="supplier.id">
                      <td>{{ index + 1 }}</td>
                      <td v-if="editId === supplier.id">
                        <input v-model="supplier.name" required class="form-control form-control-sm" />
                      </td>
                      <td v-else>{{ supplier.name }}</td>

                      <td v-if="editId === supplier.id">
                        <input v-model="supplier.contact" required class="form-control form-control-sm" />
                      </td>
                      <td v-else>{{ supplier.contact }}</td>

                      <td v-if="editId === supplier.id">
                        <input v-model="supplier.cnic" required class="form-control form-control-sm" />
                      </td>
                      <td v-else>{{ supplier.cnic }}</td>

                      <td v-if="editId === supplier.id">
                        <input v-model="supplier.address" required class="form-control form-control-sm" />
                      </td>
                      <td v-else>{{ supplier.address }}</td>

                      <td>
                        <button v-if="editId === supplier.id" required class="btn btn-success btn-sm" @click="updateSupplier(supplier)">
                          Save
                        </button>
                        <button v-else class="btn btn-primary btn-sm mx-1" @click="editSupplier(supplier)">
                          <i class="far fa-edit"></i>
                        </button>
                        <button 
                          v-if="supplier.is_deletable && editId !== supplier.id" 
                          class="btn btn-danger btn-sm" 
                          @click="confirmDelete(supplier.id)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>

                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- Add Supplier Modal -->
      <Add :heading="'Add New Supplier'" :errors="validationErrors" :success="success" :formID="formID">
        <div class="row">
          <div class="form-group col-md-4">
            <label>Supplier Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" required placeholder="Enter Name" v-model="data.name" />
          </div>
          <div class="form-group col-md-4">
            <label>Contact Number <span class="text-danger">*</span></label>
            <input type="text" class="form-control" required placeholder="Enter Contact Number" v-model="data.contact" />
          </div>
          <div class="form-group col-md-4">
            <label>CNIC Number <span class="text-danger">*</span></label>
            <input type="text" class="form-control" required placeholder="Enter CNIC Number" v-model="data.cnic" />
          </div>
          <div class="form-group col-md-12">
            <label>Address <span class="text-danger">*</span></label>
            <input type="text" class="form-control" required placeholder="Enter Address" v-model="data.address" />
          </div>
          <div class="col-md-12 text-right">
            <button type="button" class="btn btn-primary px-3" :disabled="loading" @click="addSupplier">
              {{ loading ? 'Loading...' : 'Add' }}
            </button> 
          </div>
        </div>
       

        <div class="card-body">
              <div class="table-responsive">
                <h4>Suppliers</h4>
                <table class="table table-striped table-hover dataTable1">
                  <thead>
                    <tr>
                      <th>Sr No.</th>
                      <th>Supplier Name</th>
                      <th>Contact Number</th>
                      <th>CNIC Number</th>
                      <th>Address</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(supplier, index) in suppliers" :key="supplier.id">
                      <td>{{ index + 1 }}</td>
                      <td v-if="editId === supplier.id">
                        <input v-model="supplier.name" required class="form-control form-control-sm" />
                      </td>
                      <td v-else>{{ supplier.name }}</td>

                      <td v-if="editId === supplier.id">
                        <input v-model="supplier.contact" required class="form-control form-control-sm" />
                      </td>
                      <td v-else>{{ supplier.contact }}</td>

                      <td v-if="editId === supplier.id">
                        <input v-model="supplier.cnic" required class="form-control form-control-sm" />
                      </td>
                      <td v-else>{{ supplier.cnic }}</td>

                      <td v-if="editId === supplier.id">
                        <input v-model="supplier.address" required class="form-control form-control-sm" />
                      </td>
                      <td v-else>{{ supplier.address }}</td>

                      <td>
                        <button v-if="editId === supplier.id" required class="btn btn-success btn-sm" @click="updateSupplier(supplier)">
                          Save
                        </button>
                        <button v-else class="btn btn-primary btn-sm mx-1" @click="editSupplier(supplier)">
                          <i class="far fa-edit"></i>
                        </button>
                        <button 
                          v-if="supplier.is_deletable && editId !== supplier.id" 
                          class="btn btn-danger btn-sm" 
                          @click="confirmDelete(supplier.id)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>

                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
      </Add>

      <!-- Delete Confirm Modal -->
      <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header py-2">
              <h5 class="modal-title">Confirm Delete</h5>
              <button type="button" class="close" data-dismiss="modal">
                <span>&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <p>Are you sure you want to delete this supplier?</p>
            </div>
            <div class="modal-footer py-2">
              <button class="btn btn-danger btn-sm" @click="deleteSupplier">Yes, Delete</button>
              <button class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</template>

 
<script>
import Add from '../../../components/Add.vue';

export default {
  name: "SupplierManager",
  components: { Add },
  data() {
    return {
      formID: 'addSupplierForm',
      editId: null,
      deleteId: null,
      data: {
        name: '',
        contact: '',
        cnic: '',
        address: ''
      },
      suppliers: [],
      loading: false,
      validationErrors: [],
      success: ''
    };
  },
  mounted() {
    this.fetchSuppliers();
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

    clearForm() {
      this.data = { name: '', contact: '', cnic: '', address: '' };
      this.validationErrors = [];
      this.success = '';
    },
    async fetchSuppliers() {
  try {
    const response = await this.callApi('post', 'supplier');
    this.suppliers = response.data.suppliers; // ✅ Correct field
    this.$nextTick(() => {
        $('.dataTable1').DataTable(); // Initial setup after data load
      });
  } catch (error) {
    console.error(error);
  }
},

    async addSupplier() {
      try {
        this.loading = true;
        const payload = {
          name: this.data.name,
          contact: this.data.contact,
          address: this.data.address,
          cnic: this.data.cnic
        };
        const response = await this.callApi('post', 'supplier/store', payload);
      

        if (response.status === 200 || response.status === 201) {
          
          $(".dataTable1").DataTable().destroy();
            this.loading = false;
            this.clearForm();
            this.fetchSuppliers(); 
            return Swal.fire({
              icon: 'success',
              title: 'Created',
              text: 'Supplier Created successfully!',
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
      } finally {
        this.loading = false;
      }
    },

    editSupplier(supplier) {
      this.editId = supplier.id;
    },

    async updateSupplier(supplier) {
    const payload = {
      id: supplier.id, // 👈 must include id
      name: supplier.name,
      contact: supplier.contact_number, // you need to change your model later maybe
      address: supplier.address,
      cnic: supplier.cnic_number
    };
    const response = await this.callApi('post', 'supplier/update', payload);
    $(".dataTable1").DataTable().destroy();
    if (response.status === 200 || response.status === 201) {
          $(".dataTable1").DataTable().destroy();
            this.loading = false;
            this.fetchSuppliers();
            this.clearForm();
            this.editId = null;
            return Swal.fire({
              icon: 'success',
              title: 'Updated',
              text: 'Supplier Updated successfully!',
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
    confirmDelete(id) {
      this.deleteId = id;
      $('#deleteConfirmModal').modal('show');
    },

    async deleteSupplier() {
      try {
        const response = await this.callApi('post', `supplier/delete`,{ id: this.deleteId }); 
        
        if (response.status === 200 || response.status === 201) {
          $(".dataTable1").DataTable().destroy();
            this.loading = false;
            
            this.fetchSuppliers();
            return Swal.fire({
              icon: 'success',
              title: 'Deleted',
              text: 'Supplier Deleted successfully!',
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
      } catch (error) {
        console.error('Error deleting product:', error);
      }
    }
    
  }
};
</script>
