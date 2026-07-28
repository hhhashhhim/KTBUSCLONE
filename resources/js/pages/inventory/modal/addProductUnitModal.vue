<template>
     <div class="modal fade" id="addProductUnitModal" tabindex="-1" aria-labelledby="seatAllDetailsModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="">Add New Product Unit</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0" style="overflow-y:unset">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="name">Name <span class="text-danger ml-1">*</span></label>
                                <input v-model="newUnit" type="text" required class="form-control" placeholder="Enter Name">
                            </div> 
                        </div>
                    </div>
                    <div class="modal-footer bg-whitesmoke br">
                      <button type="button" class="btn btn-primary" :disabled="loading" @click="createUnit">
                        <span v-if="loading" class="spinner-border spinner-border-sm mr-1"></span>
                        {{ loading ? 'Adding...' : 'Add' }}
                      </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            close
                        </button>
                    </div>
                    <div class="card m-2 p-2 overflow-auto">  
                        <div class="table-responsive">
                          <table class="table table-striped table-hover dataTable1">
                            <thead>
                              <h5 class="modal-title" id="">Product Units</h5>
                              <tr>
                                <th>Sr No.</th>
                                <th>Unit Name</th>
                                <th>Date</th>
                                <th>Action</th>
                              </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(unit, index) in unitData" :key="unit.id">
                              <td>{{ index + 1 }}</td>
                              <td>
                                <input v-if="editIndex === index" v-model="editUnit" required class="form-control" />
                                <span v-else>{{ unit.name }}</span>
                              </td>
                              <td>{{ formatDate(unit.created_at) }}</td>
                              <td>
                                <button v-if="editIndex === index" class="btn btn-success" :disabled="loading" @click="submitUnitEdit()">
                                  <span v-if="loading" class="spinner-border spinner-border-sm mr-1"></span>
                                  {{ loading ? 'Saving...' : 'Save' }}
                                </button>
                                <button v-else class="btn btn-primary" @click="editUnitRow(index, unit)"><i class="far fa-edit"></i></button>
                                <button 
                                  v-if="unit.is_deletable && editIndex !== index" 
                                  class="btn btn-danger ml-1" 
                                  @click="confirmUnitDelete(unit.id, index)"
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
                                Are you sure you want to delete this Product Unit?
                              </div>
                              <div class="modal-footer">
                                <button class="btn btn-secondary" @click="showDeleteModal = false">Cancel</button>
                                <button class="btn btn-danger" @click="deleteUnitRow">Delete</button>
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
      unitData: [],
      newUnit: '',
      editUnit: '',
      editIndex: null,
      editId: null,
      loading: false,
    };
  },
  mounted() {
    this.fetchUnits();
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
  async fetchUnits() {
    try {
      const res = await this.callApi('post', 'inventory-product-unit');
      this.unitData = res.data.data;
      this.$emit('unitChanged', this.unitData)
      this.$nextTick(() => {
       $('.dataTable1').DataTable(); // Initial setup after data load
      });
    } catch (err) {
      console.error(err);
    }
  },

    async createUnit() {
    if (!this.newUnit) return;
    if (this.loading) return;
    this.loading = true;
    try {
      const response = await this.callApi('post', 'inventory-product-unit/store', {
        unit: this.newUnit,
      });
      const newUnit = response.data.data;
      this.unitData.unshift(newUnit);
      this.newUnit = '';
      console.log(response);
      
      if (response.status === 200 || response.status === 201) {    
        $(".dataTable1").DataTable().destroy();   
        this.loading = false;
        this.fetchUnits();
            return Swal.fire({
              icon: 'success',
              title: 'Unit Added',
              text: 'Unit Added successfully!',
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
            Swal.fire('Error', response.data?.message || 'Unable to add unit.', 'error');
        }  
    } finally {
      this.loading = false;
    }
  },

  editUnitRow(index, unit) {
  this.editIndex = index;
  this.editUnit  = unit.name; // Correct field here
  this.editId    = unit.id;
},

async submitUnitEdit() {
  if (this.loading) return;
  this.loading = true;
  try {
    const response = await this.callApi("post", "inventory-product-unit/update", {
      id: this.editId,
      name: this.editUnit,
    });

    // Update local data directly without re-fetching
    if (this.editIndex !== null) {
      this.unitData[this.editIndex].name = this.editUnit;
    }

    this.editIndex = null;
    this.editUnit = '';
    this.editId = null;

    if (response.status === 200 || response.status === 201) {
      $(".dataTable1").DataTable().destroy();
            this.loading = false;
            this.fetchUnits();
            return Swal.fire({
              icon: 'success',
              title: 'Unit Updated',
              text: 'Unit Updated successfully!',
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
  } catch (err) {
    console.error(err.response?.data || err);
    Swal.fire('Error', 'Failed to update unit.', 'error');
  } finally {
    this.loading = false;
  }
},

async confirmUnitDelete(id, index) {
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
    this.deleteUnitRow(id, index);
  }
},

async deleteUnitRow(id, index) {
 
   const response = await this.callApi("post", "inventory-product-unit/delete", { id });

    // Instantly remove from local array
    this.unitData.splice(index, 1);
    if (response.status === 200 || response.status === 201) {
      $(".dataTable1").DataTable().destroy();
            this.loading = false;
            this.fetchUnits();
            return Swal.fire({
              icon: 'success',
              title: 'Unit Deleted',
              text: 'Unit deleted successfully!',
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
  }
};

</script>

