<template>
    <section class="section">
      <div class="section-body">
        <div class="row">
          <div class="col-12">
            <div class="card card-primary">
              <div class="card-header">
                <h4>Products</h4>
                <div class="card-header-action">
                  <a href="#" class="btn btn-primary" data-toggle="modal" :data-target="'#' + formID" @click="clearForm()">
                    Add new Product
                  </a>
                </div>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-striped dataTable">
                    <thead>
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
                          <select v-model="product.category_id" class="form-control form-control-sm">
                            <option value="">Select</option>
                            <option v-for="(cat, idx) in categoryOptions" :key="idx" :value="cat.id">
                              {{ cat.name }}
                            </option>
                          </select>
                        </td>
                        <td v-else>{{ product.category?.name }}</td>

                        <td v-if="editId === product.id">
                          <select v-model="product.unit_id" class="form-control form-control-sm">
                            <option value="">Select</option>
                            <option v-for="(unit, idx) in unitOptions" :key="idx" :value="unit.id">
                              {{ unit.name }}
                            </option>
                          </select>
                        </td>
                        <td v-else>{{ product.unit?.name }}</td>

                        <td>{{ product.qty }}</td>
                        <td>{{ product.avg_price }}</td>

                        <td>
                          <button v-if="editId === product.id" class="btn btn-success btn-sm" @click="saveProduct(product)">
                            Save
                          </button>
                          <button v-else class="btn btn-primary btn-sm mx-1" @click="editProduct(product)">
                            <i class="far fa-edit"></i>
                          </button>
                              <!-- Button inside your table -->
                              <button 
                                  v-if="product.is_deletable && editId !== product.id" 
                                  class="btn btn-danger btn-sm mx-1" 
                                  data-toggle="modal" 
                                  data-target="#deleteConfirmModal" 
                                  @click="confirmDelete(product.id)"
                                >
                                  <i class="fas fa-trash"></i>
                                </button>

                          <!-- <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#productDetailModal">
                            <i class="fas fa-eye"></i>
                          </button> -->
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
  
          <!-- Add Product Modal -->
          <Add :heading="'Add New Product'" :errors="validationErrors" :success="success" :formID="formID">
                    <div class="row">
                      <div class="form-group col-md-4">
                        <input type="hidden" v-model="data.qty"/>
                        <input type="hidden" v-model="data.avg_price"/>
                        <div class="d-flex justify-content-between">
                          <label>Select Product Category</label>
                          <button class="btn btn-primary p-0 m-0 px-2" data-toggle="modal" data-target="#addProductCategoryModal">Add New</button>
                        </div>
                        <!-- Category Dropdown -->
                         
                          <select v-model="data.category_id" class="form-control">
                              <option value="">Select Category</option>
                              <option v-for="cat in categoryOptions" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                          </select>
                        </div>

                        <div class="form-group col-md-4">
                          <div class="d-flex justify-content-between">
                            <label>Select Product Unit</label>
                            <button class="btn btn-primary p-0 m-0 px-2" data-toggle="modal" data-target="#addProductUnitModal">Add New</button>
                          </div>
                          <!-- Unit Dropdown -->
                          
                          <select v-model="data.unit_id" class="form-control">
                              <option value="">Select Unit</option>
                              <option v-for="unit in unitOptions" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                          <label>Product Name <span class="text-danger">*</span></label>
                          <input type="text" class="form-control" required placeholder="Enter Product Name" v-model="data.name" />
                        </div>
                        <div class="col-md-12 text-right"> 
                          <button type="button" class="btn btn-primary px-3" :disabled="loading" @click="createProduct">
                            {{ loading ? 'Loading...' : 'Add' }}
                          </button>
                        </div>
                      </div>
                      <div class="table-responsive">
                              <table class="table table-striped table-hover dataTable">
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
                                      <select v-model="product.category_id" class="form-control form-control-sm">
                                        <option value="">Select</option>
                                        <option v-for="(cat, idx) in categoryOptions" :key="idx" :value="cat.id">
                                          {{ cat.name }}
                                        </option>
                                      </select>
                                    </td>
                                    <td v-else>{{ product.category?.name }}</td>
                                    <td v-if="editId === product.id">
                                      <select v-model="product.unit_id" class="form-control form-control-sm">
                                        <option value="">Select</option>
                                        <option v-for="(unit, idx) in unitOptions" :key="idx" :value="unit.id">
                                          {{ unit.name }}
                                        </option>
                                      </select>
                                    </td>
                                    <td v-else>{{ product.unit?.name }}</td>
                                    <td>{{ product.qty }}</td>
                                    <td>{{ product.avg_price }}</td>
                                    <td>
                                      <button v-if="editId === product.id" class="btn btn-success btn-sm" @click="saveProduct(product)">
                                        Save
                                      </button>
                                      <button v-else class="btn btn-primary btn-sm mx-1" @click="editProduct(product)">
                                        <i class="far fa-edit"></i>
                                      </button>
                                        <!-- Button inside your table -->
                                        <button 
                                            v-if="product.is_deletable && editId !== product.id" 
                                            class="btn btn-danger btn-sm mx-1" 
                                            data-toggle="modal" 
                                            data-target="#deleteConfirmModal" 
                                            @click="confirmDelete(product.id)"
                                          >
                                            <i class="fas fa-trash"></i>
                                          </button>
                                      <!-- <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#productDetailModal">
                                        <i class="fas fa-eye"></i>
                                      </button> -->
                                    </td>
                                  </tr>
                                </tbody>
                              </table>
                        </div>
                    </Add>
                  <!-- Delete Confirmation Modal --> 
                  <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">Confirm Delete</h5>
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                          </button>
                        </div>
                        <div class="modal-body">
                          Are you sure you want to delete this product?
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                          <button type="button" class="btn btn-danger" @click="deleteProduct">Delete</button>
                        </div>
                      </div>
                    </div>
                  </div>
                <!-- Product detail -->
                <div class="modal fade" id="productDetailModal" tabindex="-1" role="dialog" aria-labelledby="actionModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                      <div class="modal-content pt-3">
                        <div class="modal-header py-2">
                          <h5 class="modal-title">Product Detail</h5>
                          <button type="button" class="close" data-dismiss="modal" ><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                          <div class="table-responsive">
                            <table class="table table-striped table-hover dataTable">
                              <thead>
                                <tr>
                                  <th>Sr No.</th>
                                  <th>Product Name</th>
                                  <th>Category</th>
                                  <th>Unit</th>
                                  <th>Quantity</th>
                                  <th>Average Price</th> 
                                  <!-- <th>Action</th> -->
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
                                    <select v-model="product.category_id" class="form-control form-control-sm">
                                      <option value="">Select</option>
                                      <option v-for="(cat, idx) in categoryOptions" :key="idx" :value="cat.id">
                                        {{ cat.name }}
                                      </option>
                                    </select>
                                  </td>
                                  <td v-else>{{ product.category?.name }}</td>
                                  <td v-if="editId === product.id">
                                    <select v-model="product.unit_id" class="form-control form-control-sm">
                                      <option value="">Select</option>
                                      <option v-for="(unit, idx) in unitOptions" :key="idx" :value="unit.id">
                                        {{ unit.name }}
                                      </option>
                                    </select>
                                  </td>
                                  <td v-else>{{ product.unit?.name }}</td>

                                  <td>{{ product.qty }}</td>
                                  <td>{{ product.avg_price }}</td>

                                  <!-- <td>
                                    <button class="btn btn-dark btn-sm" @click="printProduct(product)">
                                      <i class="fas fa-print"></i>
                                    </button>
                                  </td> -->
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                        <div class="modal-footer py-2"> 
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
  
      <!-- Category/Unit Modals -->
      <addProductCategoryModal @categoryChanged="categoryChanged($event)"></addProductCategoryModal>
      <addProductUnitModal @unitChanged="unitChanged($event)"></addProductUnitModal>
    </section>
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
      addProductUnitModal,
    },
    data() {
    return { 
          formID: 'addProductForm',
          editId: null,
          deleteId: null,
          products: [],
          data: {
            id: null,
            name: '',
            category_id: '',
            unit_id: '',
            qty: 0,          // ✅ initialize 0
            avg_price: 0,    // ✅ initialize 0
          },
          categoryOptions: [],
          unitOptions: [],
          validationErrors: [],
          loading: false,
          success: '',
      }
    },
    mounted() {
        this.fetchProducts();
     },
    methods: { 
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-GB');
    },
    categoryChanged( data ){
      this.categoryOptions = data;
    },
    unitChanged( data ){
      this.unitOptions = data;
    },
    async fetchProducts() {
        try {
          const response = await this.callApi('post', 'inventory-product');
          this.products = response.data.products;
          this.categoryOptions = response.data.categories; // <-- set categories
          this.unitOptions = response.data.units;          // <-- set units
          this.$nextTick(() => {
              $('.dataTable').DataTable(); // Initial setup after data load
          });
        } catch (error) {
          console.error('Error fetching products:', error);
        }
    },
    async createProduct() {    
          this.loading = true;
          // Make sure qty and avg_price are numbers
          const response = await this.callApi('post', 'inventory-product/store', this.data);
          console.log(response); 
          if (response.status === 200 || response.status === 201) {
            //this.clearForm();
            this.fetchProducts();
            this.loading = false;
            return Swal.fire({
              icon: 'success',
              title: 'Product Created',
              text: 'Product added successfully!',
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
    }, 
    editProduct(product) {
      this.editId = product.id; // 🛠 Important - set edit mode
      this.fetchProducts();
    },
    async saveProduct(product) {
        try {
          this.loading = true;
          const payload = {
            id: product.id,
            name: product.name,
            category_id: product.category_id,
            unit_id: product.unit_id,
            qty: product.qty,
            avg_price: product.avg_price,
          };
          const response = await this.callApi('post', 'inventory-product/update', payload);
          this.fetchProducts();
          this.editId = null; // 🛠 Exit edit mode
          if (response.status === 200 || response.status === 201) {
            //this.clearForm();
            this.loading = false;
            this.fetchProducts();
            return Swal.fire({
              icon: 'success',
              title: 'Product Updated',
              text: 'Product Updated successfully!',
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
        } catch (error) {
          if (error.response && error.response.status === 422) {
            this.validationErrors = error.response.data.errors;
          } else {
            console.error('Error updating product:', error);
          }
        } finally {
          this.loading = false;
        }
    },
    confirmDelete(id) {
        this.deleteId = id; // Store the product ID to delete
    },
    async deleteProduct() {
        try {
          await this.callApi('post', 'inventory-product/delete', { id: this.deleteId });
          this.success = 'Product deleted successfully.';
          this.fetchProducts(); // Refresh list
          this.deleteId = null; // Reset
          if (response.status === 200 || response.status === 201) {
            this.loading = false;
            this.fetchProducts();
            //this.clearForm();
            return Swal.fire({
              icon: 'success',
              title: 'Product Deleted',
              text: 'Product Deleted successfully!',
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
        } catch (error) {
          console.error('Error deleting product:', error);
        }
    },
    clearForm() {
        this.data = { id: null, name: '', unit_id: '', category_id: '', qty: 0, avg_price: 0 };
        this.validationErrors = [];
        this.success = '';
     },
    },
  };
  
  </script>
  