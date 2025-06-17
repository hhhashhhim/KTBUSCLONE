  <template>

      <section class="section">
        <div class="section-body">
          <div class="row">
            <div class="col-12">
              <div class="card card-primary">
                <div class="card-header">
                  <h4>Reports</h4> 
                </div>
                <div class="card-body">
                  <div class="col-md-12">
                    <div class="">
                      <div class="card-body row">
                        <div class="col-md-3">
                          <b @click="currentTab = 'inventoryControlRegister'" class="cursor-pointer">
                            1. <i class="far fa-file"></i> <u>Inventory Control Register</u>
                          </b>
                        </div>
                        <div class="col-md-3">
                          <b @click="currentTab = 'inventoryGoodReceived'" class="cursor-pointer">
                            2. <i class="far fa-file"></i> <u>Inventory Good Received</u>
                          </b>
                        </div>
                        <div class="col-md-3">
                          <b @click="currentTab = 'inventoryGoodIssued'" class="cursor-pointer">
                            3. <i class="far fa-file"></i> <u>Inventory Good Issued</u>
                          </b>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- Inventory Control Register -->
                  <div class="table-responsive card px-3 py-2" v-if="currentTab === 'inventoryControlRegister'">
                    <h5>Inventory Control Register</h5>
                    <!-- Filters -->
                    <div class="row">
                      
                      <div class="col-md-3">
                        <label>From Date</label>
                        <input type="date" class="form-control" v-model="filters.from_date">
                      </div>
                      <div class="col-md-3">
                        <label>To Date</label>
                        <input type="date" class="form-control" v-model="filters.to_date">
                      </div>
                      <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" @click="applyProductControlFilters">
                          Apply Filter
                        </button>
                      </div>
                      <div class="col-md-1 d-flex align-items-end">
                        <button class="btn btn-danger w-100" @click="removeFilters">
                          Close
                        </button>
                      </div>
                    </div>
                    
                    <table class="table table-striped table-hover dataTable">
                      <thead>
                        <tr>
                          <th colspan="2">Item Description</th>
                          <th colspan="3">Opening</th>
                          <th colspan="3">Purchase</th>
                          <th colspan="3">Issuance</th>
                          <th colspan="1">Returned</th>
                          <th colspan="1">Balance</th>
                        </tr>
                        <tr>
                          <th>Sr No.</th>
                          <th>Product Name</th>

                          <!-- Opening -->
                          <th>Rate</th>
                          <th>QTY</th>
                          <th>Total Value</th>

                          <!-- Purchase -->
                          <th>Avg Rate</th>
                          <th>QTY</th>
                          <th>Total Value</th>

                          <!-- Issuance -->
                          <th>Avg Rate</th>
                          <th>Issued QTY</th>
                          <th>Total Cost</th>

                          <th>QTY</th> <!-- Returned -->
                          <th>Balance QTY</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in data" :key="index">
                          <td>{{ index + 1 }}</td>
                          <td>{{ item.name }}</td>
                          <!-- Opening: Placeholder for future -->
                          <td>{{ item.avg_opening_price || '-' }}</td>
                          <td>{{ item.opening_qty || '-' }}</td>
                          <td>{{ item.opening_value ? item.opening_value.toFixed(2) : '-' }}</td>
                          <!-- Purchase -->
                          <td>{{ item.avg_purchase_price || '-' }}</td>
                          <td>{{ item.purchase_qty || '-' }}</td>
                          <td>{{ item.purchase_value ? item.purchase_value.toFixed(2) : '-' }}</td>
                          <!-- Issuance -->
                          <td>{{ item.avg_issuance_price || '-' }}</td>
                          <td>{{ item.issuance_qty || '-' }}</td>
                          <td>{{ item.issuance_value ? item.issuance_value.toFixed(2) : '-' }}</td>
                          <!-- Returned: Static placeholder for now -->
                          <td>-</td>
                          <!-- Balance -->
                          <td>{{ item.balance_qty.toFixed(2) || '-' }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <!-- Inventory Good Received -->
                  <div class="table-responsive card px-3 py-2" v-if="currentTab === 'inventoryGoodReceived'">
                  <h5>Inventory Good Received Register</h5>
                    <!-- Filters -->
                    <div class="row">
                      <div class="col-md-2">
                        <label>GRN #</label>
                        <input type="number" class="form-control" v-model.number="filters.grn" placeholder="Enter GRN Number">
                      </div>
                      <div class="col-md-2">
                        <label>PO #</label>
                        <input type="number" class="form-control" v-model.number="filters.po" placeholder="Enter PO Number">
                      </div>
                      <div class="col-md-2">
                        <label>Products</label>
                        <select v-model="filters.product_id" class="form-control">
                          <option value="">Select Products</option>
                          <option v-for="product in products" :key="product.id" :value="product.id">
                            {{ product.name }}
                          </option>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <label>From Date</label>
                        <input type="date" class="form-control" v-model="filters.from_date">
                      </div>
                      <div class="col-md-2">
                        <label>To Date</label>
                        <input type="date" class="form-control" v-model="filters.to_date">
                      </div>
                      <div class="col-md-1 d-flex align-items-end">
                        <button class="btn btn-primary w-100" @click="applyReceivelFilters">
                          Apply Filter
                        </button>
                      </div>
                      <div class="col-md-1 d-flex align-items-end">
                        <button class="btn btn-danger w-100" @click="removeFilters">
                          Close
                        </button>
                      </div>
                    </div>
                    <table class="table table-striped table-hover dataTable">
                        <thead>
                          <tr>
                            <th>Sr No.</th>
                            <th>Date</th>
                            <th>GRN #</th>
                            <th>PO #</th>
                            <th>Supplier</th>
                            <th>Product Name</th>
                            <th>Rate</th>
                            <th>Received QTY</th>
                            <th>Tax</th>
                            <th>Delivery Charges</th>
                            <th>Discount</th>
                            <th>Total</th> 
                          </tr>
                        </thead>
                        <tbody>
                          <template v-for="(grn, index) in inward" :key="grn.id">
                            <tr v-for="(detail, idx) in grn.details" :key="detail.id">
                              <td>{{ index + 1 }}</td>
                              <td>{{ new Date(grn.created_at).toLocaleDateString() }}</td>
                              <td>GRN - {{ grn.id }}</td>
                              <td>PO - {{ grn.po_id }}</td>
                              <td>{{ grn.supplier?.name || '-' }}</td>
                              <td>{{ detail.product?.name || '-' }}</td>
                              <td>{{ parseFloat(detail.rate).toFixed(2) }}</td>
                              <td>{{ detail.received_quantity ?? detail.qty }}</td>
                              <td>{{ parseFloat(detail.tax ?? 0).toFixed(2) }}</td>
                              <td>{{ parseFloat(detail.delivery_charges ?? 0).toFixed(2) }}</td>
                              <td>{{ parseFloat(detail.discount ?? 0).toFixed(2) }}</td>
                              <td>{{ parseFloat(detail.net_amount ?? 0).toFixed(2) }}</td>
                            </tr>
                          </template>
                          <tr v-if="inward.length === 0">
                            <td colspan="12" class="text-center text-danger">No records found</td>
                          </tr>
                        </tbody>
                      </table>

                  </div>
                  <!-- Inventory Good Issued -->
                  <div class="table-responsive card px-3 py-2" v-if="currentTab === 'inventoryGoodIssued'">
                    <h5>Inventory Good Issued Register</h5>
                      <!-- Filters -->
                      <div class="row">
                        <div class="col-md-2">
                        <label>Products</label>
                        <select v-model="filters.product_id" class="form-control">
                          <option value="">Select Products</option>
                          <option v-for="product in products" :key="product.id" :value="product.id">
                            {{ product.name }}
                          </option>
                        </select> 
                      </div>
                      <div class="col-md-2">
                        <label>MR#</label>
                        <input type="number" class="form-control" v-model="filters.mr" placeholder="Enter MR Number">
                      </div>
                      <div class="col-md-2">
                        <label>SIN#</label>
                        <input type="number" class="form-control" v-model="filters.sin" placeholder="Enter SIN Number">
                      </div>
                      <div class="col-md-2">
                        <label>From Date</label>
                        <input type="date" class="form-control" v-model="filters.from_date">
                      </div>
                      <div class="col-md-2">
                        <label>To Date</label>
                        <input type="date" class="form-control" v-model="filters.to_date">
                      </div>
                      <div class="col-md-1 d-flex align-items-end">
                        <button class="btn btn-primary w-100" @click="applyIssuedFilters">
                          Apply Filter
                        </button>
                      </div>
                      <div class="col-md-1 d-flex align-items-end">
                        <button class="btn btn-danger w-100" @click="removeFilters">
                          Close
                        </button>
                      </div>
                    </div>
                    <table class="table table-striped table-hover dataTable mt-3">
                        <thead>
                          <tr>
                            <th>Sr No.</th>
                            <th>Date</th>
                            <th>MR#</th> 
                            <th>SIN#</th> 
                            <th>Product Name</th>
                            <th>Rate</th>
                            <th>Issued QTY</th>
                            <th>Total</th>
                          </tr>
                        </thead>
                        <tbody>
                          <template v-for="(item, index) in outward" :key="`item-${index}`">
                            <tr v-for="(detail, dIndex) in item.details" :key="`detail-${index}-${dIndex}`">
                              <td>{{ index + 1 }}</td>
                              <td>{{ new Date(item.created_at).toLocaleDateString() }}</td>
                              <td>MR-{{ item.mr_id }}</td>
                              <td>SIN-{{ item.id }}</td>
                              <td>{{ detail.product?.name || 'N/A' }}</td>
                              <td>{{ detail.rate }}</td>
                              <td>{{ detail.qty }}</td>
                              <td>{{ detail.total }}</td>
                            </tr>
                          </template>
                          <tr v-if="outward.length === 0">
                            <td colspan="12" class="text-center text-danger">No records found</td>
                          </tr>
                        </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </template>
    
    <script>
    export default {
      data() {
      return {
          currentTab: 'inventoryControlRegister',
          products: [],
          categoryOptions: [],
          unitOptions: [],
          editId: null,
          inward: [],
          outward: [],
          data: [],
          dataTableInstances: [], // To store DataTable instances
          filters: {
            product_id: '',
            from_date: '',
            to_date: '',
            po: '',
            grn: '',
            mr: '',
            sin: '',
          },
          data: [],       // Inventory Control Register
      };
      },
      mounted() { 
          this.fetchProducts(); 
          const today = new Date().toISOString().substr(0, 10);
          this.filters.from_date = today;
          this.filters.to_date = today;
        },
      beforeDestroy() {
        // Clean up all DataTable instances when component is destroyed
        this.destroyDataTables();
      },
      watch: {
        currentTab(newTab, oldTab) {
          this.$nextTick(() => {
            this.destroyDataTables();
            this.initDataTables();
          });
        }
      },
      methods: {
        destroyDataTables() {
          // Destroy all existing DataTables
          this.dataTableInstances.forEach(dt => {
            dt.destroy();
          });
          this.dataTableInstances = [];
        },
        initDataTables() {
          // Initialize DataTables for all tables with class .dataTable
          this.$nextTick(() => {
            const tables = document.querySelectorAll('.dataTable');
            tables.forEach((table) => {
              if (!$.fn.DataTable.isDataTable(table)) {
                const dataTable = $(table).DataTable({
                  // Add your DataTable options here
                  responsive: true,
                  paging: true,
                  searching: true,
                  ordering: true,
                  info: true
                });
                this.dataTableInstances.push(dataTable);
              }
            });
          });
        },
        // async fetchproductControl() {
        //   const response = await this.callApi('post', 'reports/product-control-register');
        //   this.data = response.data.data;
        //   this.$nextTick(() => {
        //     this.initDataTables();
        //   });
        // },
        // async fetchreceived() {
        //   const response = await this.callApi('post', 'reports/received');
        //   this.inward = response.data.inward;
        //   this.$nextTick(() => {
        //     this.initDataTables();
        //   });
        // },
        // async fetchissued() {
        //   const response = await this.callApi('post', 'reports/issued');
        //   this.outward = response.data.outward;
        //   this.$nextTick(() => {
        //     this.initDataTables();
        //   });
        // },
        async fetchProducts() {
            const response = await this.callApi('post', 'inventory-product');
            console.log(response.data); // For debugging
            this.products = response.data.products || [];
        },
        removeFilters() {
          this.filters = {
            product_id: '',
            from_date: '',
            to_date: '',
            po: '',
            mr: '',
            grn: '',
            sin: '',
          }; 
        },
        async applyProductControlFilters() {
          const payload = {
            from_date: this.filters.from_date,
            to_date: this.filters.to_date,
          };

          const response = await this.callApi('post', 'reports/product-control-register', payload);
          this.data = response.data.data;
          this.$nextTick(() => {
            this.destroyDataTables();
            this.initDataTables();
          });
        },
        async applyReceivelFilters() {
          const payload = {
            grn: this.filters.grn || null,
            po: this.filters.po || null,
            product_id: this.filters.product_id || null,
            from_date: this.filters.from_date || null,
            to_date: this.filters.to_date || null,
          };

          const response = await this.callApi('post', 'reports/filter/filter_received', payload);
          this.inward = response.data.inward;
          this.$nextTick(() => {
            this.destroyDataTables();
            this.initDataTables();
          });
        },
        async applyIssuedFilters() {
          const payload = {
            sin: this.filters.sin || null,
            mr: this.filters.mr || null,
            product_id: this.filters.product_id || null,
            from_date: this.filters.from_date || null,
            to_date: this.filters.to_date || null,
          };

            const response = await this.callApi('post', 'reports/filter/filter_issued', payload);
            this.outward = response.data.outward; // not `inward`
            this.$nextTick(() => {
              this.destroyDataTables();
              this.initDataTables();
            });
        }

      }
    };
    </script>
    
    <style scoped>
    .cursor-pointer {
      cursor: pointer; 
      color: #6777ef;
      font-size: 16px;
    }
    </style>