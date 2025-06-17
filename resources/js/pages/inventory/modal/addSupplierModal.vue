<template>
    <div class="modal fade" id="addSupplier" tabindex="-1" aria-labelledby="seatAllDetailsModalLabel"
            aria-hidden="true">
           <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
               <div class="modal-content">
                   <div class="modal-header">
                       <h5 class="modal-title" id="">Add New Supplier</h5>
                       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                           <span aria-hidden="true">&times;</span>
                       </button>
                   </div>
                   <div class="modal-body p-0">
                       <div class="card-body">
                        <div class="row">
              
              <div class="form-group col-md-4">
                <label>Supplier Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" placeholder="Enter Name" v-model="data.name" />
              </div>
            <div class="form-group col-md-4">
                <label>Contact Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control" placeholder="Enter Contact Number" v-model="data.contact_number" />
            </div>
            <div class="form-group col-md-4">
                <label>CNIC Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control" placeholder="Enter Cnic Number" v-model="data.cnic_number" />
              </div>
            </div>
              <div class="form-group col-md-12">
                <label>Address <span class="text-danger">*</span></label>
                <input type="text" class="form-control" placeholder="Enter Address" v-model="data.address" />
            </div>
                       </div>
                   </div>
                   <div class="modal-footer bg-whitesmoke br">
                       <button type="submit" class="btn btn-primary">
                           Add
                       </button>
                       <button type="button" class="btn btn-secondary" data-dismiss="modal">
                           close
                       </button>
                   </div>

                   <div class="table-responsive px-3">
                  <table class="table table-striped table-hover">
                    <thead>
                        <h5 class="modal-title" id="">Suppliers</h5>
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
                          <input v-model="supplier.name" class="form-control form-control-sm" />
                        </td>
                        <td v-else>{{ supplier.name }}</td>

                        <td v-if="editId === supplier.id">
                          <input v-model="supplier.contact_number" class="form-control form-control-sm" />
                        </td>
                        <td v-else>{{ supplier.contact_number }}</td>
   
                        <td v-if="editId === supplier.id">
                          <input v-model="supplier.cnic_number" class="form-control form-control-sm" />
                        </td>
                        <td v-else>{{ supplier.cnic_number }}</td>

                        <td v-if="editId === supplier.id">
                          <input v-model="supplier.address" class="form-control form-control-sm" />
                        </td>
                        <td v-else>{{ supplier.address }}</td>
                        <td>
                          <button v-if="editId === supplier.id" class="btn btn-success btn-sm" @click="saveSupplier(supplier)">
                            Save
                          </button>
                          <button v-else class="btn btn-primary btn-sm mx-1" @click="editSupplier(supplier)">
                            <i class="far fa-edit"></i>
                          </button>
                          <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteConfirmModal" @click="confirmDelete(supplier.id)">
                            <i class="fas fa-trash"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                 <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog">
          <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header py-2">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
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
           </div>
       </div>
</template>


<script> 
import Add from '../../../components/Add.vue';

export default {    
  name: "SupplierManager",
  components: {
    Add,
  },
  data() {
    return {
      formID: 'addSupplierForm',
      editId: null,
      deleteId: null,
      data: {
        name           : '',
        contact_number : '',
        cnic_number    : '',
        address        : ''
      },
      suppliers: [
        { id: 1, name: 'Test', contact_number: '0309-12365479', cnic_number: '33100-2356987-6' ,address: 'etc' },
      ],
      loading: false,
      validationErrors: [],
      success: '',
    };
  },
  methods: {
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-GB');
    },
    clearForm() {
      this.data = { name: '', contact_number: '', cnic_number: '', address: ''  };
    },
    add() {
      if (!this.data.name) {
        alert('All fields are required.');
        return;
      }
      const newProduct = {
        id: Date.now(),
        name: this.data.name,
        category: this.data.category,
        unit: this.data.unit,
        date: new Date(),
      };
      this.suppliers.push(newProduct);
      this.clearForm();
      $('#' + this.formID).modal('hide');
    },
    editSupplier(supplier) {
      this.editId = supplier.id;
    },
    saveSupplier(supplier) {
      this.editId = null;
    },
    confirmDelete(id) {
      this.deleteId = id;
    },
    deleteSupplier() {
      this.suppliers = this.supplier.filter(p => p.id !== this.deleteId);
      this.deleteId = null;
      $('#deleteConfirmModal').modal('hide');
    }
  }
};
</script>
