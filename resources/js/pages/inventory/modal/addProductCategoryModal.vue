<template>
     <div class="modal fade" id="addProductCategoryModal" tabindex="-1" aria-labelledby="seatAllDetailsModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="">Add New Product Category</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="name">Name <span class="text-danger ml-1">*</span></label>
                                <input v-model="newCategory" type="text" required class="form-control" placeholder="Enter Name">
                            </div> 
                        </div>
                    </div>
                    <div class="modal-footer bg-whitesmoke br">
                      <button type="submit" class="btn btn-primary" @click="createCategory">Add</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            close
                        </button>
                    </div>
                    <div class="card m-2 p-2">  
                        <div class="table-responsive">
                    <table class="table table-striped table-hover dataTable1">
                      <thead>
                        <h5 class="modal-title" id="">Product Categories</h5>
                        <tr>
                          <th>Sr No.</th>
                          <th>Category Name</th>
                          <th>Date</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                      <tr v-for="(category, index) in categoryData" :key="category.id">
                        <td>{{ index + 1 }}</td>
                        <td>
                          <input v-if="editIndex === index" required v-model="editCategory" class="form-control" />
                          <span v-else>{{ category.name }}</span>
                        </td>
                        <td>{{ formatDate(category.created_at) }}</td>
                        <td>
                          <button v-if="editIndex === index" class="btn btn-success" @click="submitCategoryEdit()">Save</button>
                          <button v-else class="btn btn-primary" @click="editCategoryRow(index, category)"><i class="far fa-edit"></i></button>
                          <button 
                            v-if="category.is_deletable && editIndex !== index" 
                            class="btn btn-danger ml-1" 
                            @click="confirmCategoryDelete(category.id, index)"
                          >
                            <i class="fas fa-trash"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                    </table>
                  </div>

                      <!-- Delete Modal -->
                      <div v-if="showDeleteModal">
                        <div class="modal-backdrop fade show"></div>
                        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
                          <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title">Confirm Delete</h5> 
                                <button type="button" class="close" @click="showDeleteModal = false">
                                  <span>&times;</span>
                                </button>
                              </div>
                              <div class="modal-body">
                                Are you sure you want to delete this expense?
                              </div>
                              <div class="modal-footer">
                                <button class="btn btn-secondary" @click="showDeleteModal = false">Cancel</button>
                                <button class="btn btn-danger" @click="deleteCategory">Delete</button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                    </div>
                </div>
            </div>
        </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      categoryData: [],
      newCategory: '',
      editCategory: '',
      editIndex: null,
      editId: null,
    };
  },
  mounted() {
    this.fetchCategories();
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
    formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString();
  },
  async fetchCategories() {
    try {
      const res = await this.callApi('post', 'inventory-product-category');
      this.categoryData = res.data.data;
      this.$emit('categoryChanged', this.categoryData)
      this.$nextTick(() => {
              $('.dataTable1').DataTable(); // Initial setup after data load
            });
    } catch (err) {
      console.error(err);
    }
  },

    async createCategory() {
    if (!this.newCategory) return;
    try {
      const response = await this.callApi('post', 'inventory-product-category/store', {
        category: this.newCategory,
      });
      const newCategory = response.data.data;
      this.categoryData.unshift(newCategory);
      this.newCategory = '';
      this.fetchCategories();
      if (response.status === 200 || response.status === 201) {
        $(".dataTable1").DataTable().destroy();
            this.loading = false;
            return Swal.fire({
              icon: 'success',
              title: ' Category Created',
              text: 'Category added successfully!',
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
    } catch (err) {
      console.error(err.response?.data || err);
      Swal.fire('Error', 'Could not add category.', 'error');
    }
  },

  editCategoryRow(index, category) {
  this.editIndex = index;
  this.editCategory = category.name; // Correct field here
  this.editId = category.id;
},

async submitCategoryEdit() {
  try {
    const response = await this.callApi("post", "inventory-product-category/update", {
      id: this.editId,
      name: this.editCategory,
    });

    // Update local data directly without re-fetching
    if (this.editIndex !== null) {
      this.categoryData[this.editIndex].name = this.editCategory;
    }

    this.editIndex = null;
    this.editCategory = '';
    this.editId = null;
    this.fetchCategories();
    console.log(response);
    
    if (response.status === 200 || response.status === 201) {
      $(".dataTable1").DataTable().destroy();
            this.loading = false;
            this.fetchCategories();
            return Swal.fire({
              icon: 'success',
              title: 'Product Category Updated',
              text: 'Product Category Updated successfully!',
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
          } catch (err) {
            console.error(err.response?.data || err);
            Swal.fire('Error', 'Failed to update category.', 'error');
          }
        },

    async confirmCategoryDelete(id, index) {
          const result = await Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Yes, delete it!',
          });

          if (result.isConfirmed) {
            this.deleteCategory (id, index);
          }
        },

async deleteCategory(id, index) {
 
   const response = await this.callApi("post", "inventory-product-category/delete", { id });
    // Instantly remove from local array
    this.categoryData.splice(index, 1);
    console.log(response);
    
    this.fetchCategories();
    if (response.status === 200 || response.status === 201) {
      $(".dataTable1").DataTable().destroy();
            this.loading = false;
            this.fetchCategories();
            return Swal.fire({
              icon: 'success',
              title: 'Category Deleted',
              text: 'Category deleted successfully!',
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

  },
};

</script>

