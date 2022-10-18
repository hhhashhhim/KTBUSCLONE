<template>
  <section class="section">
    <div class="section-body">
      <div class="row">
        <div class="col-12 col-md-12 col-lg-12">
          <div class="card">
            <div class="card-header">
              <h4>Terminals</h4>
              <div class="card-header-action">
                <a
                  href="#add-modal"
                  data-toggle="modal"
                  :data-target="'#' + formID"
                  class="btn btn-primary"
                >
                  Add New Terminal
                </a>
              </div>
            </div>
            <div class="card-body">
              <!-- Table -->
              <div class="row">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header">
                      <h4></h4>
                    </div>
                    <div class="card-body">
                      <div class="table-responsive">
                        <table
                          class="
                            table table-striped table-hover
                            dataTable
                            no-footer
                          "
                          id="edit_loc"
                        >
                          <thead>
                            <tr>
                              <th>Sr No.</th>
                              <th>City Name</th>
                              <th>No.of Terminals</th>
                              <th>Added By</th>
                              <th>Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="(terminal, i) in terminals" :key="i">
                              <td>{{ i + 1 }}</td>
                              <td>{{ terminal.name }}</td>
                              <td>{{ terminal.terminal_count }}</td>
                              <td>{{ terminal.added_by.name }}</td>
                              <td>
                                <a
                                  href="#detail-modal"
                                  data-toggle="modal"
                                  @click="terminalDetail(terminal.id)"
                                  class="btn btn-info mx-2"
                                >
                                  <i class="far fa-eye"></i>
                                </a>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- END TABLE -->
            </div>
          </div>
        </div>
      </div>

      <!-- Add Modal -->
      <Add
        heading="New Terminal"
        :errors="this.validationErrors"
        :success="success"
        :formID="formID"
      >
        <div class="row">
          <div class="form-group col-md-4">
            <label for="city_id"
              >Terminal City <span class="text-danger">*</span></label
            >
            <select class="form-control" v-model="data.city_id">
              <option value="">Select City</option>
              <option v-for="(city, i) in cities" :key="i" :value="city.id">
                {{ city.name }}
              </option>
            </select>
          </div>
          <div class="form-group col-md-4">
            <label for="name"
              >Terminal Name <span class="text-danger">*</span></label
            >
            <input type="text" class="form-control" v-model="data.name" />
          </div>
          <div class="form-group col-md-4">
            <label for="available_seats">Allowed Seats</label>
            <input
              type="text"
              class="form-control"
              v-model="data.available_seats"
            />
          </div>
          <div class="form-group col-md-4">
            <label for="contact"
              >Terminal Contact <span class="text-danger">*</span>
            </label>
            <input
              type="text"
              class="form-control"
              maxlength="11"
              v-model="data.contact"
              @keypress="isNumber($event)"
            />
          </div>
          <div class="form-group col-md-4">
            <label for="address">Address</label>
            <input type="text" class="form-control" v-model="data.address" />
          </div>
          <div class="form-group col-md-4">
            <label for="time_difference">Time Difference ( eg HH:MM )</label>
            <!--                        <input type="text" class="form-control" id="time_diff" v-model="data.time_difference">-->
            <!--                        <input type="text" id="timePicker" class="form-control" v-model="data.time_difference">-->
            <vue-mask
              class="form-control"
              v-model="data.time_difference"
              mask="00:00"
              :raw="false"
              :options="options"
            >
            </vue-mask>
          </div>
          <div class="form-group col-md-4">
            <label for="advance_booking">Advance Booking Allowed(Days)</label>
            <input
              type="number"
              class="form-control"
              v-model="data.advance_booking"
            />
          </div>
          <div class="form-group col-md-4">
            <label for="longitude">Longitude</label>
            <input type="text" class="form-control" v-model="data.longitude" />
            <small
              ><a href="https://www.google.com/maps" target="_blank"
                >Click Here to get</a
              ></small
            >
          </div>
          <div class="form-group col-md-4">
            <label for="Latitude">Latitude</label>
            <input type="text" class="form-control" v-model="data.latitude" />
          </div>
          <div class="form-group col-md-4">
            <label for="online_terminal_name">Online Terminal Name</label>
            <input
              type="text"
              class="form-control"
              v-model="data.online_terminal_name"
            />
          </div>
          <div class="form-group col-md-2 d-flex align-items-center">
            <label class="mt-4" for="active">Online Availability</label>
            <label class="colorinput mx-3 mt-3">
              <span
                ><input
                  type="checkbox"
                  class="colorinput-input"
                  v-model="data.active"
                />
                <span class="colorinput-color bg-success"></span>
              </span>
            </label>
          </div>
          <div class="form-group col-md-2 d-flex align-items-center">
            <label class="mt-4" for="sms">SMS</label>
            <label class="colorinput mx-3 mt-3">
              <span>
                <input
                  type="checkbox"
                  class="colorinput-input"
                  v-model="data.active_sms"
                />
                <span class="colorinput-color bg-success"></span>
              </span>
            </label>
          </div>
          <div class="form-group col-md-2 d-flex align-items-center">
            <label class="mt-4" for="sms">Main Terminal</label>
            <label class="colorinput mx-3 mt-3">
              <span>
                <input
                  type="checkbox"
                  class="colorinput-input"
                  v-model="data.is_main"
                />
                <span class="colorinput-color bg-success"></span>
              </span>
            </label>
          </div>
        </div>
        <template v-slot:button>
          <button type="button" class="btn btn-primary" @click="add">
            Add New Terminal
          </button>
        </template>
      </Add>

      <!-- Edit Modal -->
      <Edit
        heading="Edit terminal"
        :errors="this.validationErrors"
        :success="success"
        :formID="formID"
      >
        <div class="row">
          <div class="form-group col-md-4">
            <label for="city_id"
              >Terminal City <span class="text-danger">*</span></label
            >
            <select class="form-control" v-model="dataEdit.city_id">
              <option value="">Select City</option>
              <option v-for="(city, i) in cities" :key="i" :value="city.id">
                {{ city.name }}
              </option>
            </select>
          </div>
          <div class="form-group col-md-4">
            <label for="name"
              >Terminal Name <span class="text-danger">*</span></label
            >
            <input type="text" class="form-control" v-model="dataEdit.name" />
          </div>
          <div class="form-group col-md-4">
            <label for="available_seats">Allowed Seats</label>
            <input
              type="text"
              class="form-control"
              v-model="dataEdit.available_seats"
            />
          </div>
          <div class="form-group col-md-4">
            <label for="contact"
              >Terminal Contact <span class="text-danger">*</span>
            </label>
            <input
              type="text"
              class="form-control"
              maxlength="11"
              v-model="dataEdit.contact"
              @keypress="isNumber($event)"
            />
          </div>
          <div class="form-group col-md-4">
            <label for="address">Address</label>
            <input
              type="text"
              class="form-control"
              v-model="dataEdit.address"
            />
          </div>
          <div class="form-group col-md-4">
            <label for="time_difference">Time Difference ( eg HH:MM )</label>
            <vue-mask
              class="form-control"
              v-model="dataEdit.time_difference"
              mask="00:00"
              :raw="false"
              :options="options"
            >
            </vue-mask>
          </div>
          <div class="form-group col-md-4">
            <label for="advance_booking">Advance Booking Allowed(Days)</label>
            <input
              type="number"
              class="form-control"
              v-model="dataEdit.advance_booking"
            />
          </div>
          <div class="form-group col-md-4">
            <label for="longitude">Longitude</label>
            <input
              type="text"
              class="form-control"
              v-model="dataEdit.longitude"
            />
          </div>
          <div class="form-group col-md-4">
            <label for="Latitude">Latitude</label>
            <input
              type="text"
              class="form-control"
              v-model="dataEdit.latitude"
            />
          </div>
          <div class="form-group col-md-4">
            <label for="online_terminal_name">Online Terminal Name</label>
            <input
              type="text"
              class="form-control"
              v-model="dataEdit.online_terminal_name"
            />
          </div>
          <div class="form-group col-md-2 d-flex align-items-center">
            <label class="mt-4" for="active">Online Availability </label>
            <label class="colorinput mx-3 mt-3">
              <span
                ><input
                  type="checkbox"
                  class="colorinput-input"
                  v-model="dataEdit.active"
                  v-bind:checked="parseInt(dataEdit.status) === 1"
                />
                <span class="colorinput-color bg-success"></span>
              </span>
            </label>
          </div>
          <div class="form-group col-md-2 d-flex align-items-center">
            <label class="mt-4" for="sms">SMS</label>
            <label class="colorinput mx-3 mt-3">
              <span>
                <input
                  type="checkbox"
                  class="colorinput-input"
                  v-model="dataEdit.active_sms"
                  v-bind:checked="dataEdit.active_sms === 1"
                />
                <span class="colorinput-color bg-success"></span>
              </span>
            </label>
          </div>
          <div class="form-group col-md-2 d-flex align-items-center">
            <label class="mt-4" for="sms">Main Terminal</label>
            <label class="colorinput mx-3 mt-3">
              <span>
                <input
                  type="checkbox"
                  class="colorinput-input"
                  v-model="dataEdit.is_main"
                  v-bind:checked="dataEdit.is_main === 1"
                />
                <span class="colorinput-color bg-success"></span>
              </span>
            </label>
          </div>
        </div>
        <template v-slot:button>
          <button type="button" class="btn btn-primary" @click="update">
            Update Terminal
          </button>
        </template>
      </Edit>

      <!--View Details Model-->
      <div
        class="modal fade"
        id="detail-modal"
        tabindex="-1"
        aria-labelledby="detailModalLabel"
        aria-hidden="true"
      >
        <div class="modal-dialog modal-xl modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="exampleModalLabel">
                Terminal Details
              </h5>
              <button
                type="button"
                class="close"
                data-dismiss="modal"
                aria-label="Close"
                @click="close"
              >
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body m-1 p-1">
              <div class="card-body my-0 py-0">
                <!-- Table -->
                <div class="row">
                  <div class="col-12">
                    <div class="card">
                      <div class="card-body">
                        <div class="table-responsive">
                          <table
                            class="
                              table table-striped table-hover
                              dataTable
                              no-footer
                            "
                            id="show_terminal"
                          >
                            <thead>
                              <tr>
                                <th>Sr No.</th>
                                <th>Terminal Name</th>
                                <th>Address</th>
                                <th>Contact Number</th>
                                <th>Added By</th>
                                <th>Action</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr
                                v-for="(single, i) in terminalsDetails"
                                :key="i"
                              >
                                <td>{{ i + 1 }}</td>
                                <td v-if="single.name">{{ single.name }}</td>
                                <td v-else>N/A</td>
                                <td v-if="single.address">
                                  {{ single.address }}
                                </td>
                                <td v-else>N/A</td>
                                <td v-if="single.contact">
                                  {{ single.contact }}
                                </td>
                                <td v-else>N/A</td>
                                <td v-if="single.added_by">
                                  {{ single.added_by.name }}
                                </td>
                                <td v-else>N/A</td>
                                <td>
                                  <a
                                    href="#edit-modal"
                                    data-toggle="modal"
                                    @click="editTerminal(single)"
                                    class="btn btn-warning mx-2"
                                  >
                                    <i class="far fa-edit"></i>
                                  </a>
                                  <a
                                    href="#delete-modal"
                                    data-toggle="modal"
                                    @click="deleteModal(single, i)"
                                    class="btn btn-danger"
                                  >
                                    <i class="far fa-trash-alt"></i>
                                  </a>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- END TABLE -->
              </div>
            </div>
            <div class="modal-footer bg-whitesmoke br">
              <button
                type="button"
                class="btn btn-secondary"
                data-dismiss="modal"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Delete Modal -->
      <Delete
        confirmationMessage="Are You Sure You want To Delete This terminal ???"
      />
    </div>
  </section>
</template>

<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Delete from "../../components/Delete.vue";
import vueMask from "vue-jquery-mask";
import { mapGetters } from "vuex";

export default {
  name: "Terminal",
  components: {
    Add,
    Edit,
    Delete,
    vueMask,
  },
  data() {
    return {
      date: null,
      options: {
        placeholder: "HH:MM",
        // http://igorescobar.github.io/jQuery-Mask-Plugin/docs.html
      },
      validationErrors: "",
      seen: true,
      terminals: [],
      terminalsDetails: [],
      companies: [],
      formID: "newTerminal",
      cities: [],
      data: {
        company_id: "",
        name: "",
        available_seats: "",
        contact: "",
        address: "",
        time_difference: "",
        active_sms: "",
        advance_booking: "",
        longitude: "",
        latitude: "",
        city_id: "",
        online_terminal_name: "",
        active: "",
        inactive: "",
        order: "",
      },
      dataEdit: {},
      success: false,
    };
  },

  async created() {
    const terminalRes = await this.callApi("post", "terminals");
    console.log(terminalRes.data);
    const compRes = await this.callApi("post", "company");
    const cities = await this.callApi("post", "cities");
    this.terminals = terminalRes.data;
    this.companies = compRes.data;
    this.cities = cities.data;
    setTimeout(() => {
      $("#edit_loc").DataTable();
    }, 500);
  },
  methods: {
    isNumber: function (evt) {
      evt = evt ? evt : window.event;
      var charCode = evt.which ? evt.which : evt.keyCode;
      if (
        charCode > 31 &&
        (charCode < 48 || charCode > 57) &&
        charCode !== 46
      ) {
        evt.preventDefault();
      } else {
        return true;
      }
    },
    isAlphabet: function (evet) {
      if (!/[a-zA-Z\s]/.test(event.key)) {
        this.ignoredValue = event.key ? event.key : "";
        event.preventDefault();
      }
    },
    async add() {
      this.validationErrors = [];
      if (this.data.name === "")
        return this.errorsArray("Terminal Name is Required", "Name");
      if (
        this.$store.state.user.is_super_admin === 1 &&
        this.data.company_id === ""
      )
        return this.errorsArray("Company is Required", "Password");
      if (this.data.city_id === "")
        return this.errorsArray("Terminal City is Required", "City");
      if (this.data.contact === "")
        return this.errorsArray("Terminal Contact is Required", "Contact");
      const res = await this.callApi("post", "terminals/store", this.data);
      if (res.status === 200) {
        this.success = "Terminal Created Successfully";
        this.terminals = res.data;
        this.data = "";
        setTimeout(() => {
          this.success = "";
          $("#add-modal").modal("hide");
          // window.location.reload();
        }, 2000);
      } else {
        if (res.status === 422) {
          for (const key in res.data.errors) {
            res.data.errors[key].forEach((element) => {
              this.errorsArray(element, key);
            });
          }
        }
        if (res.status === 423) {
          this.errorsArray(res.data.is_main, "Main Terminal");
        }
      }
    },
    async editTerminal(single) {
      console.log(single);
      this.dataEdit = single;
    },
    async terminalDetail(id) {
      const getTerminalRes = await this.callApi(
        "post",
        "terminals/getTerminal",
        { id: id }
      );
      this.terminalsDetails = getTerminalRes.data;
      setTimeout(() => {
        $("#show_terminal").DataTable();
      }, 500);
    },
    async update() {
      this.validationErrors = [];
      if (this.dataEdit.name === "")
        return this.errorsArray("terminal Name is Required", "Name");
      const res = await this.callApi("post", "terminals/update", this.dataEdit);
      if (res.status === 201) {
        this.success = "terminal Updated Successfully";
        this.dataEdit = "";
        const terminalRes = await this.callApi("post", "terminals", {});
        this.terminals = terminalRes.data;
        setTimeout(() => {
          this.success = "";
          $("#edit-modal").modal("hide");
          // window.location.reload();
        }, 3000);
      } else {
        if (res.status === 422) {
          console.log();
          for (const key in res.data.errors) {
            res.data.errors[key].forEach((element) => {
              this.errorsArray(element, key);
            });
          }
        }
      }
    },
    async deleteModal(terminal, i) {
      const deletingObj = {
        url: "terminals/delete",
        data: terminal,
        index: i,
      };
      this.$store.commit("setDeleteObj", deletingObj);
      setTimeout(() => {}, 3000);
    },
  },
  computed: {
    ...mapGetters(["getDeletingObj"]),
  },
  watch: {
    getDeletingObj(obj) {
      console.log(obj);
      if (obj.isDeleted) {
        this.terminals.splice(obj.index, 1);
      }
    },
  },
};
</script>
