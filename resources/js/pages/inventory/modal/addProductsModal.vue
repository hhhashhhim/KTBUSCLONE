<template>
     <div class="modal fade" id="addProducts" tabindex="-1" aria-labelledby="seatAllDetailsModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="">Add New Product</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="card-body row">
                            <div class="form-group col-md-6">
                            <div class="d-flex justify-content-between">
                                <label for="category">Select Product Category</label>
                                <button class="btn btn-primary p-0 m-0 px-2" data-target="#addProductCategoryModal" 
                                        data-toggle="modal">Add New</button>
                            </div>
                            <select id="category" name="category" class="form-control select2">
                                <option value="">Select </option>
                                <option value="electronics">Electronics</option>
                                <option value="furniture">Furniture</option>
                                <option value="vehicles">Vehicles</option>
                                <option value="tools">Tools</option>
                            </select>
                            </div>
                            <div class="form-group col-md-6">
                            <div class="d-flex justify-content-between">
                                <label for="category">Select Product Unit</label>
                                <button class="btn btn-primary p-0 m-0 px-2" data-target="#addProductUnitModal" 
                                        data-toggle="modal">Add New </button>
                            </div>
                            <select id="category" name="category" class="form-control select2">
                                <option value="">Select </option>
                                <option value="electronics">Electronics111</option>
                                <option value="furniture">Furniture</option>
                                <option value="vehicles">Vehicles</option>
                                <option value="tools">Tools</option>
                            </select>
                            </div>
                            <div class="form-group px-2">
                                <label for="name">Product Name <span class="text-danger ml-1">*</span></label>
                                <input type="text" class="form-control" placeholder="Enter Product Name">
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

                    
            <div class="table-responsive">
                  <table class="table table-striped table-hover p-3">
                    <thead>
                        <h5 class="modal-title" id="">Products</h5>
                      <tr>
                        <th>Sr No.</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Unit</th>
                        <th>Quantity</th>
                        <th>Average Price</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(product, index) in products" :key="product.id">
                        <td>{{ index + 1 }}</td>
  
                        <td v-if="editId === product.id">
                          <input v-model="product.name" class="form-control form-control-sm" />
                        </td>
                        <td v-else>{{ product.name }}</td>
  
                        <td v-if="editId === product.id">
                          <select v-model="product.category" class="form-control form-control-sm">
                            <option value="">Select</option>
                            <option v-for="cat in categoryOptions" :key="cat">{{ cat }}</option>
                          </select>
                        </td>
                        <td v-else>{{ product.category }}</td>
  
                        <td v-if="editId === product.id">
                          <select v-model="product.unit" class="form-control form-control-sm">
                            <option value="">Select</option>
                            <option v-for="unit in unitOptions" :key="unit">{{ unit }}</option>
                          </select>
                        </td>
                        <td v-else>{{ product.unit }}</td>
  
                        <td v-if="editId === product.id">
                          <input v-model="product.qty" disabled class="form-control form-control-sm" />
                        </td>
                        <td v-else>{{ product.qty }}</td>

                        <td v-if="editId === product.id">
                          <input v-model="product.avg_price" disabled class="form-control form-control-sm" />
                        </td>
                        <td v-else>{{ product.avg_price }}</td>
  
                        <td>
                          <button v-if="editId === product.id" class="btn btn-success btn-sm" @click="saveProduct(product)">
                            Save
                          </button>
                          <button v-else class="btn btn-primary btn-sm mx-1" @click="editProduct(product)">
                            <i class="far fa-edit"></i>
                          </button>
                          <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteConfirmModal" @click="confirmDelete(product.id)">
                            <i class="fas fa-trash"></i>
                          </button>
                          <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#viewConfirmModal">
                            <i class="fas fa-eye"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                </div>
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
                    <p>Are you sure you want to delete this product?</p>
                  </div>
                  <div class="modal-footer py-2">
                    <button class="btn btn-danger btn-sm" @click="deleteProduct">Yes, Delete</button>
                    <button class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Category/Unit Modals -->
            <addProductCategoryModal></addProductCategoryModal>
            <addProductUnitModal></addProductUnitModal>
        </div>
</template>

<script>
import Add from '../../../components/Add.vue';
import addProductCategoryModal from '../modal/addProductCategoryModal.vue';
import addProductUnitModal from '../modal/addProductUnitModal.vue';

export default {
  name: "ProductManager",
  components: {
    Add,
    addProductCategoryModal,
    addProductUnitModal
  },
  data() {
    return {
      formID: 'addProductForm',
      editId: null,
      deleteId: null,
      data: {
        name: '',
        category: '',
        unit: '',
        qty: '',
        avg_price: '',
      },
      products: [
        { id: 1, name: 'Test', category: 'Electronics', unit: 'Items', qty: '10', avg_price: '120' },
      ],
      categoryOptions: ['Electronics', 'Furniture', 'Vehicles', 'Tools'],
      unitOptions: ['Items', 'Kg', 'Ltr'],
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
      this.data = { name: '', category: '', unit: '', qty: '', avg_price: ''  };
    },
    add() {
      if (!this.data.name || !this.data.category || !this.data.unit) {
        alert('All fields are required.');
        return;
      }
      const newProduct = {
        id: Date.now(),
        name: this.data.name,
        category: this.data.category,
        unit: this.data.unit, 
      };
      this.products.push(newProduct);
      this.clearForm();
      $('#' + this.formID).modal('hide');
    },
    editProduct(product) {
      this.editId = product.id;
    },
    saveProduct(product) {
      this.editId = null;
    },
    confirmDelete(id) {
      this.deleteId = id;
    },
    deleteProduct() {
      this.products = this.products.filter(p => p.id !== this.deleteId);
      this.deleteId = null;
      $('#deleteConfirmModal').modal('hide');
    }
  }
};
</script>
