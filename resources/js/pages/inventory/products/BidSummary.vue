<template>
  <div class="">
    <section class="section">
    <div class="section-body">
      <div class="row">
        <div class="col-12 mt-4">
          <ul class="nav nav-tabs mb-3">
            <li class="nav-item ml-2">
              <a class="nav-link" :class="{ active: activeTab === 'bid' }" href="#" @click.prevent="activeTab = 'bid'">
                <b>Bid Summaries</b>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" :class="{ active: activeTab === 'prn' }" href="#" @click.prevent="activeTab = 'prn'">
                <b>
                  Purchase Requisition Note - PRNs
                  <span class="badge badge-primary">{{ prns.length }}</span>
                </b>
              </a>
            </li>
          </ul>
        </div>
        <!-- BID TABLE -->
        <div class="col-12" v-if="activeTab === 'bid'">
          <div class="card card-primary">
            <div class="card-header">
              <h4>Bid Summaries</h4>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-hover dataTable2">
                  <thead>
                    <tr>
                      <th>Sr No.</th> 
                      <th>Bid #</th>
                      <th>PRN #</th>
                      <th>MR #</th>
                      <th>Date</th>
                      <th>Request By</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(bid, index) in uniquePRNBids" :key="bid.id">
                    <td>{{ index + 1 }}</td>
                    <td>Bid - {{ bid.id || 'N/A' }}</td>
                    <td>PRN - {{ bid.prn?.id || 'N/A' }}</td>
                    <td>MR - {{ bid.prn?.mr_id || 'N/A' }}</td>
                    <td>{{ new Date(bid.created_at).toLocaleString() }}</td>
                    <td>{{ bid.prn?.mr?.requested_by_user?.name || 'N/A' }}</td>
                     
                    <td>
                      <button class="btn btn-info btn-sm" @click="viewBidsByPRN(bid.prn?.id)">
                        <i class="fas fa-eye"></i>
                      </button>
                        <!-- Pass mr.id instead of full mr object -->
                      <button class="btn btn-dark btn-sm mx-1" @click="submitBIDPdf(bid.prn?.id)">
                        <i class="fas fa-print"></i>
                      </button>
                    </td>
                  </tr>
                  <tr v-if="uniquePRNBids.length == 0">
                    <td colspan="7" class="text-center">No Bid Summaries Found</td>
                  </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <!-- PRN TABLE -->
        <div class="col-12" v-if="activeTab === 'prn'">
          <div class="card card-primary">
            <div class="card-header">
              <h4>Purchase Requisition Note - PRNs</h4> <span class="badge badge-primary">{{ prns.length }}</span>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-hover dataTable2">
                  <thead>
                    <tr>
                      <th>Sr No.</th>
                      <th>MR #</th>
                      <th>PRN #</th>
                      <th>Date</th>
                      <th>Request By</th> 
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(prn, index) in prns" :key="prn.id">
                      <td>{{ index + 1 }}</td>
                      <td>MR - {{ prn.mr_id || 'N/A' }}</td>
                      <td>PRN - {{ prn.id || 'N/A' }}</td>
                      <td>{{ new Date(prn.created_at).toLocaleString() }}</td>
                      <td>{{ prn.mr?.requested_by_user?.name || 'N/A' }}</td>
                     
                      <td>
                        <button 
                            class="btn btn-success btn-sm mx-1" 
                            :data-target="'#' + formID" 
                            data-toggle="modal" 
                            @click="openBidModal(prn)">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#viewPRNModal"
                            @click="viewPRN(prn.id)">
                              <i class="fas fa-eye"></i>
                        </button>
                      </td>
                    </tr>
                    <tr v-if="prns.length === 0">
                      <td colspan="6" class="text-center">No Purchase Requisition Note Found</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <!-- View PRN Detail Modal -->
        <div class="modal fade" id="viewPRNModal" tabindex="-1" role="dialog">
          <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
              <!-- Modal Header -->
               <div class="modal-header py-3">
                <h5 class="modal-title">
                  <i class="fas fa-file-alt mr-2"></i>
                  Purchase Requisition Note Detail - PRN # {{ selectedPRN?.prn_no || selectedPRN?.id || 'N/A' }}
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                  <span>&times;</span>
                </button>
              </div>

              <!-- Modal Body -->
              <div class="modal-body">
                <div v-if="selectedPRN && selectedPRN.details && selectedPRN.details.length">
                  <table class="table table-bordered table-hover dataTable">
                    <thead class="thead-light bg-light border-top">
                      <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Quantity</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(detail, i) in selectedPRN.details" :key="detail.id || i">
                      <td>{{ i + 1 }}</td>
                      <td>{{ detail.product?.name || detail.product?.product_name || 'N/A' }}</td>
                      <td>{{ detail.quantity || detail.qty || 0 }}</td>
                    </tr>
                    </tbody>
                  </table>
                </div>
                <div v-else class="text-muted text-center">
                  No product data available for this PRN.
                </div>
              </div>

              <!-- Modal Footer -->
              <div class="modal-footer py-2 thead-light bg-light border-top">
                <button class="btn btn-secondary" data-dismiss="modal">Close</button>
              </div>

            </div>
          </div>
        </div>
        <!-- Add Bid -->
        <Add :heading="'Add New Bid Summary'" :errors="validationErrors" :success="success" :formID="formID">
          <!-- Section: Requested Products -->  
        <div v-for="(item, index) in bidFormRows" :key="item.id" class="border p-3 mb-3 rounded bg-light">
          <h6 class="mb-3 font-weight-bold">Bidder {{ index + 1 }}</h6>
          <div class="row">
            <!-- Supplier -->
            <div class="form-group col-md-3">
            <div class="d-flex justify-content-between">
              <label>Select Supplier</label>
            </div>
            <select v-model="item.supplier_id" class="form-control select2">
                <option value="">Select Supplier</option>
                <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">
                  {{ sup.name }}
                </option>
              </select>
          </div>
          <div class="form-group col-md-3">
              <label>Contact Person</label>
              <input type="text" class="form-control" v-model="item.contact_person">
            </div>
            <div class="form-group col-md-3">
              <label>Contact Person Contact</label>
              <input type="text" class="form-control" v-model="item.contact_person_contact">
            </div> 
            <!-- Quotation Ref -->
            <div class="form-group col-md-3">
              <label>Quotation Ref</label>
              <input type="text" class="form-control" v-model="item.quotation_ref" placeholder="e.g. QUO-0012">
            </div>
            <!-- Quotation Date -->
            <div class="form-group col-md-3">
              <label>Quotation Date</label>
              <input type="date" class="form-control" v-model="item.quotation_date">
            </div>
            <!-- Credit Days -->
            <div class="form-group col-md-3">
              <label>Credit Days</label>
              <input type="number" class="form-control" v-model="item.credit_days">
            </div>
            <!-- Advance -->
            <div class="form-group col-md-3"> 
                <label >Advance (%)</label> 
                <input type="number" class="form-control " v-model="item.advance_percent">
            </div>
            <!-- After Delivery -->
              <div class="form-group col-md-3"> 
                <label >After Delivery (%)</label>  
                <input type="number" class="form-control" v-model="item.after_delivery_percent">
              </div> 
              <!-- Terms -->
            <div class="form-group col-md-12">
              <label>Terms & Conditions</label>
              <textarea class="form-control" v-model="item.terms" rows="3" placeholder="..."></textarea>
            </div> 
          <!-- Products Quoted -->
          <div class="col-md-12">
              <h6 class="font-weight-bold">Products Quoted</h6>
              <div v-for="(product, pIndex) in item.products" :key="pIndex" class="row">
                
                <!-- Product Dropdown (read-only display instead of select) -->
                <div class="form-group col-md-3">
                  <label>Product</label>
                  <input type="text" class="form-control" :value="getProductName(product.product_id)" readonly>
                </div>
                <!-- Qty Display -->
                <div class="form-group col-md-2">
                  <label>Qty</label> 
                  <input type="number" class="form-control" v-model="product.qty" readonly @input="updateTotal(item, product)">
                </div>
                <!-- Rate Input -->
                <div class="form-group col-md-3">
                  <label>Rate</label>
                  <input type="number" class="form-control" v-model.number="product.rate" @input="updateTotal(item, product)">
                </div>
                <!-- Total (auto-calculated) -->
                <div class="form-group col-md-3">
                  <label>Total</label>
                  <input type="number" class="form-control" :value="product.qty * product.rate" readonly>
                </div>
              </div>
            </div> 
            
            <div class="col-md-6 ml-auto">
              <div class="form-group col-md-12">
                <h6>Summary</h6>
                <hr>
              </div>
              <div class="form-group col-md-12 d-flex p-0 m-0 mt-1">
              <label class="w-50"><h6>Sub Total</h6></label>
              <h5 id="subTotal" class="text-right w-50">{{ item.sub_total }} PKR</h5> 
            </div>
            <div class="form-group col-md-12 d-flex p-0 m-0">
              <label class="w-25">Delivery Charges</label>
              <input type="text" class="form-control w-75" placeholder="Enter Delivery Charges" v-model="item.delivery_charges" @input="calculateGrandTotal(item)">
            </div>
            <div class="form-group col-md-12 d-flex p-0 m-0 mt-1">
              <label class="w-25">Tax %</label>
              <div class="input-group w-75">
                <input 
                  type="text" 
                  class="form-control" 
                  placeholder="Enter Percentage of Tax" 
                  v-model="item.tax" 
                  @input="calculateGrandTotal(item)"
                >
                <div class="input-group-append">
                  <span class="input-group-text">%</span>
                </div>
              </div>
             </div>
            <div class="form-group col-md-12 p-0 m-0 text-right">
              <div><small>Tax Amount:<strong class="tax-amount"> {{ item.tax_amount ?? 0 }}</strong></small></div>
            </div>
            
            <div class="form-group col-md-12 d-flex p-0 m-0 mt-1">
              <label class="w-25">Discount (Amount)</label>
              <input type="text" class="form-control w-75" v-model="item.discount" placeholder="Enter Discount Amount" @input="calculateGrandTotal(item)">
            </div>
            <div class="form-group col-md-12 d-flex p-0 m-0 mt-1">
              <label class="w-50"><h6>Grand Total</h6></label>
              <h5 id="grandTotal" class="text-right w-50">{{ item.total_amount }} PKR</h5>
            </div>
            </div> 
            <!-- Add / Remove Bidders -->
            <div class="form-group col-md-12 d-flex justify-content-end mt-3">
              <button class="btn btn-info btn-sm" @click="addRow">
                <i class="fa fa-plus"></i> Add Bidder
              </button>
              <button class="btn btn-danger btn-sm ml-2" @click="removeRow(index)" v-if="bidFormRows.length > 1">
                <i class="fa fa-minus"></i> Remove
              </button>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <template v-slot:button>
          <button type="button" class="btn btn-primary" :disabled="loading" @click="createBid">
            {{ loading ? 'Submitting...' : 'Submit Bid Summary' }}
          </button>
        </template>
        </Add> 

        <!-- VIEW BID MODAL -->
        <div class="modal fade" id="viewBidModal" tabindex="-1" role="dialog" @hidden="removeNewBidForm">
          <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
            <div class="modal-header d-flex justify-content-between">
              <h5 class="modal-title">Bid Summary for PRN</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="text-right m-3">
              <button v-if="selectedPRNStatus == '1'" class="btn btn-info btn-sm" @click="addNewBid">
                <i class="fa fa-plus"></i> Add New Bidder
              </button> 
            </div>
            <div class="modal-body">
              <div  id="addNewBidForm">
                <div v-for="(item, index) in newBidFormRows" :key="index" class="row border-top pt-3 mt-4" id="addNewBidForm">
               <div class="col-md-12">
                <h5>Add New Bidder</h5>
               </div>
                  <div class="form-group col-md-3">
                  <label>Select Supplier</label>
                  <select v-model="item.supplier_id" class="form-control select2">
                    <option value="">Select Supplier</option>
                    <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.name }}</option>
                  </select>
                </div>
                  <div class="form-group col-md-3">
                    <label>Contact Person</label>
                    <input type="text" class="form-control" v-model="item.contact_person">
                  </div>
                  <div class="form-group col-md-3">
                    <label>Contact Contact</label>
                    <input type="text" class="form-control" v-model="item.contact_person_contact">
                  </div>
                  <div class="form-group col-md-3">
                    <label>Quotation Ref</label>
                    <input type="text" class="form-control" v-model="item.quotation_ref">
                  </div>

                  <div class="form-group col-md-3">
                    <label>Quotation Date</label>
                    <input type="date" class="form-control" v-model="item.quotation_date">
                  </div>
                  <div class="form-group col-md-3">
                    <label>Credit Days</label>
                    <input type="number" class="form-control" v-model="item.credit_days">
                  </div>
                  <div class="form-group col-md-3">
                    <label>Advance (%)</label>
                    <input type="number" class="form-control" v-model="item.advance_percent">
                  </div>
                  <div class="form-group col-md-3">
                    <label>After Delivery (%)</label>
                    <input type="number" class="form-control" v-model="item.after_delivery_percent">
                  </div>

                  <div class="form-group col-md-12">
                    <label>Terms & Conditions</label>
                    <textarea class="form-control" v-model="item.terms" rows="2"></textarea>
                  </div>
                  <div class="col-md-12">
                    <h5>Products Quoted</h5>
                    <table class="table table-bordered dataTable">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Product</th>
                          <th>Qty</th>
                          <th>Rate</th>
                          <th>Total</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(product, pIndex) in item.products" :key="pIndex">
                            <td>{{ pIndex + 1 }}</td>
                            <td>{{ product.name }}</td>
                            <td><input type="number" class="form-control" v-model="product.qty" readonly @input="updateTotal(item, product)"></td>
                            <td>
                              <input type="number" class="form-control" v-model.number="product.rate" @input="updateTotal(item, product)">
                            </td>
                            <td>{{ product.qty * product.rate }}</td>
                          </tr>
                          <tr v-if="!item.products || item.products.length === 0">
                            <td colspan="5">No products found.</td>
                          </tr>
                      </tbody>
                    </table>
                  </div>
                  <div class="col-md-6 ml-auto">
                    <div class="form-group col-md-12 d-flex p-0 m-0 mt-1">
                      <label class="w-50"><h6>Sub Total</h6></label>
                      <h5 class="text-right w-50">{{ item.sub_total }} PKR</h5>
                    </div>
                    <div class="form-group col-md-12 d-flex p-0 m-0">
                      <label class="w-25">Delivery Charges</label>
                      <input type="text" class="form-control w-75" v-model="item.delivery_charges" @input="calculateGrandTotal(item)">
                    </div> 
                    <div class="form-group col-md-12 d-flex p-0 m-0 mt-1">
                        <label class="w-25">Tax %</label>
                        <div class="input-group w-75">
                          <input 
                            type="text" 
                            class="form-control" 
                            placeholder="Enter Percentage of Tax" 
                            v-model="item.tax" 
                            @input="calculateGrandTotal(item)"
                          >
                          <div class="input-group-append">
                            <span class="input-group-text">%</span>
                          </div>
                        </div>
                      </div>
                      <div class="form-group col-md-12 p-0 m-0 text-right">
                        <div><small>Tax Amount:<strong class="tax-amount"> {{ item.tax_amount ?? 0 }}</strong></small></div>
                      </div>
                    <div class="form-group col-md-12 d-flex p-0 m-0">
                      <label class="w-25">Discount</label>
                      <input type="text" class="form-control w-75" v-model="item.discount" @input="calculateGrandTotal(item)">
                    </div>
                    <div class="form-group col-md-12 d-flex p-0 m-0 mt-2">
                      <label class="w-50"><h6>Grand Total</h6></label>
                      <h5 class="text-right w-50">{{ item.total_amount }} PKR</h5>
                    </div>
                  </div>
                  <div class="form-group col-md-12 d-flex justify-content-end">
                    <button class="btn btn-danger btn-sm" @click="removeNewBidRow(index)">
                      <i class="fa fa-minus"></i> Remove Bidder
                    </button>
                  </div>
                </div>
                <div v-if="newBidFormRows.length > 0" class="form-group col-md-12 d-flex justify-content-end">
                  <button class="btn btn-success btn-sm mr-3" @click="createNewBid">
                    <i class="fa fa-save"></i> Submit New Bidders
                  </button>
                  <button v-if="selectedPRNStatus == '1'" class="btn btn-info btn-sm" @click="addNewBid">
                    <i class="fa fa-plus"></i> Add New Bidder
                  </button> 
                </div>
              </div>
              <!-- Loop over each bid -->
              <div v-for="(item, bIndex) in groupedBids" :key="bIndex" class="mb-4 border-bottom pb-3">
                <h5 class="text-warning d-flex justify-content-between">
                  Bid #{{ bIndex + 1 }}
                  <span>
                    <template v-if="item.isEditing" >
                      <button class="btn btn-sm btn-secondary mr-2" @click="item.isEditing = false">
                        <i class="fas fa-times"></i> Cancel
                      </button>
                      <button class="btn btn-sm btn-success mr-2" @click="updateBid(item, bIndex)">
                        <i class="fas fa-save"></i> Update
                      </button>
                    </template>
                    <template v-else>
                      <button v-if="selectedPRNStatus == '1'" class="btn btn-sm btn-primary mr-2" @click="item.isEditing = true">
                        <i class="fas fa-edit"></i> Edit
                      </button>
                    </template>
                    <button v-if="selectedPRNStatus == '1'" class="btn btn-sm btn-danger"
                      @click="confirmDeleteBid(item.id, bIndex)" >
                      <i class="fas fa-trash"></i> Delete
                    </button>
                  </span>
                </h5>
                
                <div class="row p-3" style="background-color: #eaeff2;">
                  <!-- Supplier and other bid details -->
                  <div class="col-md-12"><h6><strong>Supplier:</strong> {{ item.supplier?.name }}</h6></div>
                  <div class="col-md-4"><strong>Date:</strong> {{ new Date(item.created_at).toLocaleString() }}</div>
                  <div class="col-md-4"><strong>MR #:</strong> MR - {{ item.prn?.mr_id || 'N/A' }}</div>
                  <div class="col-md-4"><strong>PRN #:</strong> PRN - {{ item.prn?.id || 'N/A' }}</div>
                  <div class="col-md-12"><strong>Requested By:</strong> {{ item.prn?.mr?.requested_by_user?.name || 'N/A' }}</div>
                  <table class="table table-bordered mt-2">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(detail, dIndex) in item.details" :key="dIndex">
                        <td>{{ dIndex + 1 }}</td>
                        <td>{{ detail.product?.name || 'N/A' }}</td>
                        <td>{{ detail.qty }}</td>
                        <td v-if="item.isEditing">
                          <input type="number" class="form-control" v-model.number="detail.rate" @input="updateTotal(item, detail)">
                        </td>
                        <td v-else>{{ detail.rate }}</td>
                        <td>{{ detail.total }}</td>
                      </tr>
                    </tbody>
                  </table>
                  <!-- Editable fields for bid -->
                  <div class="col-md-12 d-flex my-2 bg-light"><strong>Terms Condition:</strong>
                    <template v-if="item.isEditing">
                      <input type="text" class="form-control ml-2" v-model.number="item.terms_condition">
                    </template>
                    <template v-else>
                      {{ item.terms_condition }}
                    </template>
                  </div>
                  <div class="col-md-3 d-flex my-2 bg-light"><strong>Contact Person:</strong>
                    <template v-if="item.isEditing">
                      <input type="text" class="form-control ml-2 border" v-model.number="item.contact_person">
                    </template>
                    <template v-else>
                      {{ item.contact_person }}
                    </template>
                  </div>
                  <div class="col-md-3 d-flex my-2 bg-light"><strong>Contact Person Contact:</strong>
                    <template v-if="item.isEditing">
                      <input type="text" class="form-control ml-2" v-model.number="item.contact_person_contact">
                    </template>
                    <template v-else>
                      {{ item.contact_person_contact }}
                    </template>
                  </div>
                  <div class="col-md-3 d-flex my-2 bg-light"><strong>Quotation Ref:</strong>
                    <template v-if="item.isEditing">
                      <input type="text" class="form-control ml-2" v-model.number="item.quotation_ref">
                    </template>
                    <template v-else>
                      {{ item.quotation_ref }}
                    </template>
                  </div>
                  <div class="col-md-3 d-flex my-2 bg-light"><strong>Quotation Date:</strong>
                    <template v-if="item.isEditing">
                      <input type="text" class="form-control ml-2" v-model.number="item.Quotation_date">
                    </template>
                    <template v-else>
                      {{ item.quotation_date }}
                    </template>
                  </div>
                  <div class="col-md-3 d-flex my-2 bg-light"><strong>Credit Days:</strong>
                    <template v-if="item.isEditing">
                      <input type="text" class="form-control ml-2" v-model.number="item.credit_days">
                    </template>
                    <template v-else>
                      {{ item.credit_days }}
                    </template>
                  </div>
                  <div class="col-md-3 d-flex my-2 bg-light"><strong>Advance %:</strong>
                    <template v-if="item.isEditing">
                      <input type="number" class="form-control ml-2" v-model.number="item.advance">
                    </template>
                    <template v-else>
                      {{ item.advance }}
                    </template>
                  </div>
                  <div class="col-md-3 d-flex my-2 bg-light"><strong>After Delivery %:</strong>
                    <template v-if="item.isEditing">
                      <input type="number" class="form-control ml-2" v-model.number="item.after_delivery">
                    </template>
                    <template v-else>
                      {{ item.after_delivery }}
                    </template>
                  </div>
                  <div class="col-md-12 text-right"><h6><strong>Subtotal:</strong> {{ getSubtotal(item.details).toFixed(2) }}</h6></div>
                  <div class="col-md-5 ml-auto">
                    <!-- Tax Input -->
                  <div class="col-md-12 my-2 bg-light">
                    <div class="d-flex">
                      <strong>Tax %:</strong>
                    <template v-if="item.isEditing">
                      <input 
                        type="text" 
                        class="form-control" 
                        placeholder="Enter Percentage of Tax" 
                         v-model.number="item.tax"
                        @input="calculateGrandTotal(item)"
                      >
                      <div class="input-group-append">
                        <span class="input-group-text">%</span>
                      </div>
                    </template>
                    <template v-else>
                      {{ item.tax }}
                    </template>
                    </div>
                    <div class="text-right"><small>Tax Amount: </small><strong> {{  item.tax_amount ?? 0 }}</strong></div>
                  </div>
                  <!-- Delivery Charges Input -->
                  <div class="col-md-12 d-flex my-2 bg-light">
                    <strong>Delivery Charges:</strong>
                    <template v-if="item.isEditing">
                      <input
                        type="number"
                        class="form-control ml-2"
                        v-model.number="item.delivery_charges"
                        @input="calculateGrandTotal(item)"
                      />
                    </template>
                    <template v-else>
                      {{ item.delivery_charges }}
                    </template>
                  </div>
                  <!-- Discount Input -->
                  <div class="col-md-12 d-flex my-2 bg-light">
                    <strong>Discount:</strong>
                    <template v-if="item.isEditing">
                      <input
                        type="number"
                        class="form-control ml-2"
                        v-model.number="item.discount"
                        @input="calculateGrandTotal(item)"
                      />
                    </template>
                    <template v-else>
                      {{ item.discount }}
                    </template>
                  </div>
                  </div>
                  
                  <!-- Grand Total Display -->
                  <div class="col-md-12 text-right mt-2">
                    <h6>
                      <strong>Grand Total:</strong> {{ item.total_amount }}
                    </h6>
                  </div>
                  
                </div>
              </div>
            </div>
            <div class="modal-footer">
             
              <button class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
          </div>

          </div>
        </div>
        <AddSupplierModal></AddSupplierModal>
        <AddProductModal></AddProductModal>
      </div>
    </div>
    <form ref="printBIDPdfForm" :action="`${$store.state.api_url}api/web/v1/bid/pdf`" method="POST" target="_blank">
      <input type="hidden" name="token" :value="$store.state.token" />
      <input type="hidden" name="prn_id" />
    </form>
  </section>
  </div>
</template>

<script>
import Add from '../../../components/Add.vue';
import AddSupplierModal from '../modal/addSupplierModal.vue';
import AddProductModal from '../modal/addProductsModal.vue';

export default {
  name: "BidSummaryManager",
  components: {
    Add,
    AddSupplierModal,
    AddProductModal,
  },
  data() {
    return { 
      showNewBidForm: false,
      newBidFormRows: [],
      groupedBids: [],
      selectedPRNStatus: null,
      selectedBids: null, // this must match the modal binding
      activeTab: 'bid',
      prnID:'',
      bids: [],
      prns: [],
      selectedPRN: null, // holds the MR details for the modal
      products: [],
      suppliers: [],
      prnRequests: 0,
      prnProducts: [],
      formID: 'bidFormModal', 
      validationErrors: [],
      success: '',
      loading: false,
      isModalOpen: false,
      supplier: {
          supplier_id: ''
      },
      data: {
        product_name: '',
        prn_no: '',
        mr_no: '',
        qty: '',
        price: '',
        date: '',
        sub_total: '',
        tax_amount: '',
        delivery_amount: '',
        discount: '',
        net_amount: '',
        status: '',
        supplier: '',
        requestBy: '',
      },
      selectedPRN: '',
      bid_number: '',
      bid_date: '',
      bidFormRows: [
      {
        advance_amount: 0,
        after_delivery_amount: 0,
        sub_total: 0,
        total_amount: 0,
        supplier_id: '',
        quotation_ref: '',
        quotation_date: '', 
        advance_percent: '',
        after_delivery_percent: '',
        credit_days: '',
        discount: '',
        delivery_charges: '',
        contact_person: '',
        contact_person_contact: '',
        tax:'',
        tax_amount:'',
        terms: '',
        total: '',
        products: [
          {
            product_id: '',
            rate: '',
            quantity: 1
          }
        ]
      }
    ],
    };
   },
  mounted() { 
      this.loadTinyMCE();
      const script = document.createElement('script');
      script.src = "https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js";
      script.referrerPolicy = "origin";
      document.head.appendChild(script);
      this.groupedBids.forEach(item => {
        this.calculateSubTotal(item);
        this.calculateGrandTotal(item);
      });
    $('#viewBidModal').on('hidden.bs.modal', () => {
        this.newBidFormRows = [];
      });
    this.fetchBid_PRN();
    },
  computed: {
    uniquePRNBids() {
        const seen = new Set();
        return this.bids.filter(bid => {
          if (!bid.prn?.id || seen.has(bid.prn.id)) return false;
          seen.add(bid.prn.id);
          return true;
        });
      }
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
    removeNewBidForm() {
      this.newBidFormRows = [];
    },
    async fetchBid_PRN() {
      try {
        const response = await this.callApi('post', 'bid-summaries');
        if (response.data.success) {
          this.bids = response.data.bids;
          this.prns = response.data.prns;
          this.products    = response.data.products || [];
          this.suppliers   = response.data.suppliers || [];
          this.prnRequests = this.prns.length;
          this.$nextTick(() => {
            $('.dataTable2').DataTable(); // Initial setup after data load
          });
        }
      } catch (error) {
        console.error('Failed to fetch bids and PRNs:', error);
      }
     },
    viewPRN(id) {
     this.selectedPRN = this.prns.find(prn => prn.id === id);
     }, 
    async createBid() {  
      const payload = {
        prn_id: this.selectedPRN?.id,
        mr_id : this.selectedPRN?.mr_id,
        suppliers: this.bidFormRows.map(row => ({
          supplier_id: row.supplier_id,
          quotation_ref: row.quotation_ref,
          quotation_date: row.quotation_date, 
          advance_percent: row.advance_percent,
          advance_amount: row.advance_amount,
          after_delivery_percent: row.after_delivery_percent,
          after_delivery_amount:  row.after_delivery_amount,
          credit_days: row.credit_days,
          discount: row.discount,
          delivery_charges: row.delivery_charges,
          contact_person: row.contact_person,
          contact_person_contact: row.contact_person_contact,
          terms_condition: row.terms,
          tax: row.tax,
          tax_amount: row.tax_amount,

          sub_total: row.sub_total,
          total_amount: row.total_amount,
          products: row.products.map(product => ({
            product_id: product.product_id,
            rate: product.rate,
            quantity: product.qty
          }))
        }))
      };
      console.log("Payload being submitted:", payload); // Check the payload
        const response = await this.callApi('post', 'bid-summaries/store', payload);
        if (response.status === 200 || response.status === 201) {
          $(".dataTable2").DataTable().destroy();  
          this.loading = false;
            this.fetchBid_PRN();
            this.clearForm();
            return Swal.fire({
              icon: 'success',
              title: 'Created',
              text: 'Bid Summary submitted successfully!',
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
    async createNewBid() {
        const payload = {
          prn_id: this.selectedPRNId,
          mr_id: this.selectedMRId,
          suppliers: this.newBidFormRows.map(row => ({
            supplier_id: row.supplier_id,
            quotation_ref: row.quotation_ref,
            quotation_date: row.quotation_date, 
            advance_percent: row.advance_percent,
            advance_amount: row.advance_amount,
            after_delivery_percent: row.after_delivery_percent,
            after_delivery_amount: row.after_delivery_amount,
            credit_days: row.credit_days,
            discount: row.discount,
            delivery_charges: row.delivery_charges,
            contact_person: row.contact_person,
            contact_person_contact: row.contact_person_contact,
            terms_condition: row.terms,
            tax: row.tax,
            tax_amount: row.tax_amount,
            sub_total: row.sub_total,
            total_amount: row.total_amount,
            products: row.products.map(product => ({
              product_id: product.product_id,
              rate: product.rate,
              quantity: product.qty
            }))
          }))
        };
        const response = await this.callApi('post', 'bid-summaries/store', payload);
        const newBid = response.data; // Assuming this is the new bid data
        this.uniquePRNBids.push(newBid); // Assuming this is the array that holds the bid data
        this.fetchBid_PRN();
        this.viewBidsByPRN(this.selectedPRNId);

        this.clearForm(); 
        this.newBidFormRows = [];
        if (response.status === 200 || response.status === 201) {
          $(".dataTable2").DataTable().destroy();
            this.loading = false;
            this.fetchBid_PRN();
            this.clearForm();
            return Swal.fire({
              icon: 'success',
              title: 'Created',
              text: 'Bid Summary submitted successfully!',
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
    addRow() {
        if (!this.prnProducts || !this.prnProducts.length) {
          alert("Please select a PRN first to load products.");
          return;
        }
        this.bidFormRows.push({
          id: Date.now(),
          advance_amount: 0,
          after_delivery_amount: 0,
          sub_total: 0,
          total_amount: 0,
          supplier_id: '',
          quotation_ref: '',
          quotation_date: '',
          advance_percent: '',
          after_delivery_percent: '',
          credit_days: '',
          discount: '',
          delivery_charges: '',
          contact_person: '',
          contact_person_contact: '',
          terms: '',
          tax: '',
          tax_amount:'',
          products: this.prnProducts.map(p => ({
            product_id: p.id,
            name: p.name,
            qty: p.qty,
            rate: '',
            total: 0,
          })),
        });

     },
    removeRow(index) {
      this.bidFormRows.splice(index, 1);
     },
     addNewBid() {
      const selectedPRN = this.groupedBids[0]?.prn;
      // ✅ Set PRN and MR ID here
      this.selectedPRNId = selectedPRN.id;
      this.selectedMRId = selectedPRN.mr_id;
      const newBid = {
        id: Date.now(),
        advance_amount: 0,
        after_delivery_amount: 0,
        sub_total: 0,
        total_amount: 0,
        supplier_id: '',
        quotation_ref: '',
        quotation_date: '',
        advance_percent: '',
        after_delivery_percent: '',
        credit_days: '',
        discount: '',
        delivery_charges: '',
        contact_person: '',
        contact_person_contact: '',
        terms: '',
        tax: '',
        tax_amount: '',
        products: this.groupedBids[0].details.map(detail => ({
          product_id: detail.product_id,
          name: detail.product?.name,
          qty: detail.qty,
          rate: 0,
          total: 0,
        })),
      };
      this.newBidFormRows.push(newBid);
     },
     removeNewBidRow(index) {
        this.newBidFormRows.splice(index, 1);
        if (this.newBidFormRows.length === 0) {
          this.newBidFormRows = [];
        }

     },
    openBidModal(prn) {
        this.selectedPRN = prn;
        this.clearForm(); // Clears existing form rows
        this.fetchPRNProducts(prn); // Fetch products for this PRN
        this.groupedBids.forEach(item => {
        this.calculateSubTotal(item);
        this.calculateGrandTotal(item);
    });
     }, 
    async fetchPRNProducts(prn) {
        try {
          const response = await this.callApi('post', 'prn/fetch-prn-products', { prn_id: prn.id });
          const productsFromPRN = response.data.products;
          this.prnProducts = productsFromPRN; // ✅ <-- THIS LINE IS CRUCIAL
          this.bidFormRows = [
            {
              id: Date.now(),
              advance_amount: 0,
              after_delivery_amount: 0,
              sub_total: 0,
              total_amount: 0,
              supplier_id: '',
              quotation_ref: '',
              quotation_date: '',
              advance_percent: '',
              after_delivery_percent: '',
              credit_days: '',
              discount: '',
              delivery_charges: '',
              contact_person: '',
              contact_person_contact: '',
              terms: '',
              tax: '',
              tax_amount:'',
              products: productsFromPRN.map(p => ({
                product_id: p.id,
                name: p.name,
                qty: p.qty,
                rate: '',
                total: 0,
              })),
            }
          ];
  
        } catch (error) {
          console.error("Error fetching PRN products:", error);
        }
     },
    clearForm() {
        this.validationErrors = [];
        this.success = '';
        this.bidFormRows = [
          {
            id: Date.now(),
            advance_amount: 0,
            after_delivery_amount: 0,
            sub_total: 0,
            total_amount: 0,
            supplier_id: '',
            quotation_ref: '',
            quotation_date: '', 
            advance_percent: '',
            after_delivery_percent: '',
            credit_days: '',
            discount: '',
            delivery_charges: '',
            contact_person: '',
            contact_person_contact:'',
            terms: '',
            tax: '',
            tax_amount: '',
            products: [
              {
                product_id: '',
                rate: '',
                quantity: 1
              }
            ]
          }
        ];
        // 🔒 Don't touch this.selectedPRN here!
     },
    async viewBidsByPRN(prnId) {
      const payload = { prn_id: prnId };
      try {
        const res = await this.callApi('post', 'bid-summaries/show', payload);
        if (res.data.success) {
          console.log(res.data);
          this.groupedBids = res.data.bids_by_prn; // result will be an array of PRN objects with their bids
          const selectedBid = this.uniquePRNBids.find(bid => bid.prn?.id === prnId);
          this.selectedPRNStatus = selectedBid?.status;  
  
          this.$nextTick(() => {
            $('#viewBidModal').modal('show');
          });
        }
      } catch (error) {
        console.error("Failed to fetch bids by PRN:", error);
      }
     },
    calculateTax(bid) {
      const subtotal = this.getSubtotal(bid.details);
      const taxPercent = parseFloat(bid.tax_percent || 0);
      return (subtotal * taxPercent / 100).toFixed(2);
     },
    getGrandTotal(bid) {
      const subtotal = this.getSubtotal(bid.details);
      const tax = parseFloat(this.calculateTax(bid));
      return (subtotal + tax).toFixed(2);
     },
    getProductName(productId) {
      const prod = this.products.find(p => p.id === productId);
      return prod ? prod.name : 'Unknown';
     }, 
     getSelectedProductName(productId) {
      const product = this.groupedBids[0]?.prn?.products.find(p => p.id === productId);
      return product ? product.name : '';
    },
    updateTotal(item, detail = null) {
    // If called with a product detail (from input)
    if (detail && typeof detail.qty !== 'undefined' && typeof detail.rate !== 'undefined') {
      detail.total = detail.qty * detail.rate;
    }
    // Always recalculate subtotal and grand total
    this.calculateSubTotal(item);
    this.calculateGrandTotal(item);
     },
    // Calculates subtotal from item.details
    calculateSubTotal(item) {
      let list = item.details || item.products || [];

      let subTotal = 0;
      list.forEach(detail => {
        const qty = parseFloat(detail.qty) || 0;
        const rate = parseFloat(detail.rate) || 0;
        detail.total = qty * rate;
        subTotal += detail.total;
      });

      item.sub_total = parseFloat(subTotal.toFixed(2));
     },
    getSubtotal(details) {
       if (!Array.isArray(details)) return 0;
       return details.reduce((sum, item) => sum + parseFloat(item.total || 0), 0);
     },
    async updateBid(item, index) {
        
          // Step 1: Calculate sub_total
          const sub_total = item.details.reduce((sum, detail) => {
            const qty = parseFloat(detail.qty) || 0;
            const rate = parseFloat(detail.rate) || 0;
            detail.total = qty * rate;
            return sum + detail.total;
          }, 0);
          const tax      = parseFloat(item.tax) || 0; 
          const taxAmount = (sub_total * tax) / 100;
          const discount = parseFloat(item.discount) || 0;
          const delivery = parseFloat(item.delivery_charges) || 0;
          const total_amount = parseFloat((sub_total + taxAmount + delivery - discount).toFixed(2));
          // Step 2: Prepare payload for API
          const payload = {
            id: item.id,
            supplier_id: item.supplier_id,
            contact_person: item.contact_person,
            contact_person_contact: item.contact_person_contact,
            quotation_ref: item.quotation_ref,
            quotation_date: item.quotation_date,
            credit_days: item.credit_days,
            advance_percent: item.advance ?? item.advance_percent ?? 0,
            after_delivery_percent: item.after_delivery ?? item.after_delivery_percent ?? 0,
            terms_condition: item.terms || item.terms_condition || '',
            discount: discount,
            tax: tax,
            tax_amount: taxAmount,
            delivery_charges: delivery,
            sub_total: sub_total,
            total_amount: total_amount,
            details: item.details.map(detail => ({
              product_id: detail.product_id || detail.product?.id || null,
              qty: detail.qty,
              rate: detail.rate,
              total: detail.total,
            })),
          };

          // Step 3: API call
          const response = await this.callApi('post', 'bid-summaries/update', payload);
          // Step 4: Handle response
          if (response.status == 200) {
            $(".dataTable2").DataTable().destroy();
            this.fetchBid_PRN(); 
            this.clearForm();
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: 'Bid updated successfully.',
              confirmButtonText: 'OK',
            });
            item.isEditing = false; 
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Update Failed',
              text: response.data?.message || 'Failed to update bid.',
            });
          } 
     },
     calculateGrandTotal(item) {
        if (Array.isArray(item.details)) {
          item.sub_total = item.details.reduce((sum, detail) => {
            const qty  = parseFloat(detail.qty) || 0;
            const rate = parseFloat(detail.rate) || 0;
            return sum + (qty * rate);
          }, 0);
        }

        const sub = parseFloat(item.sub_total) || 0;
        const tax = parseFloat(item.tax) || 0; // Treat tax as percentage
        const taxAmount = (sub * tax) / 100;

        const discount = parseFloat(item.discount) || 0;
        const delivery = parseFloat(item.delivery_charges) || 0;

        const total = sub + taxAmount + delivery - discount;
        
        item.tax_amount = parseFloat(taxAmount.toFixed(2)); // Optional: show tax amount separately
        item.total_amount = parseFloat(total.toFixed(2));
      },
     async confirmDeleteBid(bidId, index) {
      const confirm = await Swal.fire({
        title: 'Are you sure?',
        text: 'This bid will be permanently deleted!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
      });

      if (confirm.isConfirmed) {
        try {
          const payload = { id: bidId };
          const response = await this.callApi('post', 'bid-summaries/delete', payload);
          const deletedPRNId = response.data.prn_id;  
          if (response.status == 200) {
            $(".dataTable2").DataTable().destroy();
            this.fetchBid_PRN();  
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: 'Bid has been deleted successfully.',
            }); 
            await this.viewBidsByPRN(deletedPRNId);
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Delete Failed',
              text: response.data?.message || 'Could not delete bid.',
            });
          }
        } catch (error) {
          console.error(error);
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: error.response?.data?.details || 'An error occurred during deletion.',
          });
        }
      }
    },
    submitBIDPdf(prnId) {
      const form = this.$refs.printBIDPdfForm; 
      form.querySelector('input[name="prn_id"]').value = prnId; 
      form.submit();
    },
    loadTinyMCE() {
      const script = document.createElement('script');
      script.src = "https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js";
      script.referrerPolicy = "origin";
      document.head.appendChild(script);
     }, 
  },
};
</script>
