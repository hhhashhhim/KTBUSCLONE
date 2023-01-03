<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Tickets Template</h4>
                            <div class="card-header-action">
                                <a
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary" @click="clearForm()"
                                >
                                    Add New Template
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table dataTables table-striped table-hover"
                                                       id="ticket_templates">
                                                    <thead>
                                                    <tr>
                                                        <th>Sr No.</th>
                                                        <th>Terminal</th>
                                                        <th>Address</th>
                                                        <th>UAN #</th>
                                                        <th>Phone #</th>
                                                        <th>Terms & Condition</th>
                                                        <th>status</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(template, i) in templates" :key="i">
                                                        <td>{{ i + 1 }}</td>
                                                        <td v-if="template.terminal_id != null">
                                                            {{ template.terminal.city.name }} -
                                                            {{ template.terminal.name }}
                                                        </td>
                                                        <td v-else>N/A</td>
                                                        <td>{{ template.address }}</td>
                                                        <td>{{ uanFormat(template.uan) }}</td>
                                                        <td>{{ phoneFormat(template.phone) }}</td>
                                                        <td>{{ template.terms_condition }}</td>
                                                        <td v-if="template.status == 1">
                                                            <div class="badge badge-success">Active</div>
                                                        </td>
                                                        <td v-else>
                                                            <div class="badge badge-danger">InActive</div>
                                                        </td>
                                                        <td>
                                                            <button :data-target="'#' + editFormID" data-toggle="modal"
                                                                    @click="edit(template)"
                                                                    class=" text-light btn btn-primary mx-1">
                                                                <i class="far fa-edit"></i>
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
                            <!-- END TABLE -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Modal -->
            <Add
                :heading="'Add Template'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row mt-3">

                    <div class="form-group col-md-6">
                        <label for="terminals">Terminals <span class="text-danger">*&nbsp;&nbsp; (Just For Company Admin)</span></label>
                        <select class="form-control" id="terminals"
                                v-model="addForm.terminal">
                            <option value="0" selected>Select Terminal</option>
                            <option
                                v-for="(terminal, i) in terminals"
                                :value="terminal.id"
                                :key="i"
                            >{{ terminal.city.name }} - {{ terminal.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="uanNumber">UAN Number <span class="text-danger ml-1">*</span></label>
                        <vue-mask id="uanNumber"
                                  class="form-control"
                                  v-model="addForm.uanNumber"
                                  mask="00-000-000-000"
                                  :raw="false"
                                  :options="optionsUan"
                        >
                        </vue-mask>
                    </div>
<!--                    <div class="form-group col-md-6">-->
<!--                        <label for="phoneNumber">Phone Number <span class="text-danger ml-1">*</span></label>-->

<!--                        <vue-mask id="phoneNumber"-->
<!--                                  class="form-control"-->
<!--                                  v-model="addForm.phoneNumber"-->
<!--                                  mask="0000-0000000"-->
<!--                                  :raw="false"-->
<!--                                  :options="optionsPhone"-->
<!--                        >-->
<!--                        </vue-mask>-->
<!--                    </div>-->
<!--                    <div class="form-group col-md-12">-->
<!--                        <label for="address">Address<span class="text-danger ml-1">*</span></label>-->
<!--                        <textarea class="form-control" id="address" spellcheck="false" maxlength="45"-->
<!--                                  @keypress="countWords(this.addForm.address.length, 'address', 45)"-->
<!--                                  v-model="addForm.address"></textarea>-->
<!--                        <span class="text-danger">Length : {{ this.countAddressLength }}/45</span>-->

<!--                    </div>-->
                    <div class="form-group col-md-12">
                        <label for="refOfHiring">Terms & Condition <span class="text-danger ml-1">*</span></label>
                        <textarea id="refOfHiring" class="form-control" spellcheck="false"
                                  @keypress="countWords(this.addForm.termsCondition.length, 'terms', 140)"
                                  maxlength="140"
                                  v-model="addForm.termsCondition"></textarea>
                        <span class="text-danger">Length : {{ this.countWordsLength }}/140</span>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="addTemplate()" :disabled="loading">
                        {{ loading ? 'Loading...' : 'Add Template' }}
                    </button>
                </template>
            </Add>
            <Edit
                heading="Edit Template"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row mt-3">
                    <div class="form-group col-md-12">
                        <label for="terminals">Terminals <span class="text-danger">*&nbsp;&nbsp; (Just For Company Admin)</span></label>
                        <select class="form-control" id="terminals"
                                v-model="dataEdit.terminal_id">
                            <option value="0" selected>Select Terminal</option>
                            <option
                                v-for="(terminal, i) in terminals"
                                :value="terminal.id"
                                :key="i"
                            >{{ terminal.city.name }} - {{ terminal.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="uanNumber">UAN Number <span class="text-danger ml-1">*</span></label>
                        <vue-mask id="uanNumber"
                                  class="form-control"
                                  v-model="dataEdit.uan"
                                  mask="00-000-000-000"
                                  :raw="false"
                                  :options="optionsUan"
                        >
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="phoneNumber">Phone Number <span class="text-danger ml-1">*</span></label>

                        <vue-mask id="phoneNumber"
                                  class="form-control"
                                  v-model="dataEdit.phone"
                                  mask="0000-0000000"
                                  :raw="false"
                                  :options="optionsPhone"
                        >
                        </vue-mask>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="address">Address<span class="text-danger ml-1">*</span></label>
                        <textarea class="form-control" id="address" spellcheck="false" maxlength="45"
                                  v-model="dataEdit.address"></textarea>

                    </div>
                    <div class="form-group col-md-12">
                        <label for="refOfHiring">Terms & Condition <span class="text-danger ml-1">*</span></label>
                        <textarea id="refOfHiring" class="form-control" spellcheck="false"
                                  maxlength="140"
                                  v-model="dataEdit.terms_condition"></textarea>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="status">Status</label>
                        <select class="form-control" id="status"
                                v-model="dataEdit.status">
                            <option value="1">Active</option>
                            <option value="0">In Active</option>
                        </select>
                    </div>
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" @click="updateTemplate()" :disabled="loadingEdit">
                        {{ loadingEdit ? 'Loading...' : 'Update Template' }}
                    </button>
                </template>
            </Edit>
            <!--            Edit modal End-->

        </div>
    </section>
</template>

<script>
import Add from "../../../components/Add.vue";
import Edit from "../../../components/Edit.vue";
import Delete from "../../../components/Delete.vue";
import {mapGetters} from "vuex";
import vueMask from "vue-jquery-mask";

export default {
    name: "ticketSettings",
    components: {
        Add,
        Edit,
        Delete,
        vueMask,
    },
    data() {
        return {
            optionsUan: {
                placeholder: "xx-xxx-xxx-xxx",
            },
            optionsPhone: {
                placeholder: "03xx-xxxxxxx",
            },
            countWordsLength: 0,
            countAddressLength: 0,
            templates: [],
            terminals: [],
            addForm: {
                terminal: 0,
            },
            dataEdit: {},
            loading: false,
            loadingEdit: false,
            validationErrors: [],
            formID: "ticket_template",
            editFormID: "edit_ticket_template",
            deleteFormID: "delete_ticket_template",
        };
    },
    async created() {
        await this.fetchTemplates();
    },

    methods: {
        clearForm:function(){
            this.addForm.terminal = 0;
            this.addForm.termsCondition = '';
            this.addForm.address = '';
            this.addForm.phoneNumber = '';
            this.addForm.uanNumber = '';
            this.countWordsLength = 0;
            this.countAddressLength = 0

        },
        uanFormat: function (string) {
            return (string.replace(/(\d{2})(\d{3})(\d{3})(\d{3})/, "$1-$2-$3-$4"));
        },
        phoneFormat: function (string) {
            return (string.replace(/(\d{4})(\d{7})/, "$1-$2"));
        },
        countWords: function (count, flag, maxvalue) {
            console.log(typeof maxvalue);
            if (flag == 'terms' && maxvalue == 140) {
                this.countWordsLength = count;
            }
            if (flag == 'address' && maxvalue == 45) {
                this.countAddressLength = count;
            }
            // swal({
            //     title: "OOPs !!!",
            //     text: "Characters Must be less then or equal to Max Value",
            //     icon: "error",
            //     timer: 2000,
            // });

        },
        async fetchTemplates() {
            $("#ticket_templates").DataTable().destroy();
            const resAllTerminals = await this.callApi("post", 'settings/tickets/terminals');
            if (resAllTerminals.status == 200) {
                this.terminals = resAllTerminals.data
            } else {
                console.log(resAllTerminals);
            }
            const resTicketTemplate = await this.callApi("post", 'settings/tickets');

            if (resTicketTemplate.status == 200) {
                this.templates = resTicketTemplate.data;
            }
            if (resTicketTemplate.status == 422) {
                console.log(resTicketTemplate)
            }
            setTimeout(function () {
                $("#ticket_templates").DataTable();
            }, 300);

        },

        async addTemplate() {

            if (this.addForm.terminal == '0') {
                return swal({
                    title: "Required !!!",
                    text: "Please Select any Terminal",
                    icon: "error",
                    timer: 2000,
                });
            }
            if (this.addForm.uanNumber == '' || typeof this.addForm.uanNumber == 'undefined') {
                return swal({
                    title: "Required !!!",
                    text: "UAN Number is Required",
                    icon: "error",
                    timer: 2000,
                });
            }
            // if (this.addForm.phoneNumber == '' || typeof this.addForm.phoneNumber == 'undefined') {
            //     return swal({
            //         title: "Required !!!",
            //         text: "Phone Number is Required",
            //         icon: "error",
            //         timer: 2000,
            //     });
            // }
            // if (this.addForm.address == '' || typeof this.addForm.address == 'undefined') {
            //     return swal({
            //         title: "Required !!!",
            //         text: "Address is Required",
            //         icon: "error",
            //         timer: 2000,
            //     });
            // }
            if (this.addForm.termsCondition == '' || typeof this.addForm.termsCondition == 'undefined') {
                return swal({
                    title: "Required !!!",
                    text: "Terms &Condition is Required",
                    icon: "error",
                    timer: 2000,
                });
            }
            this.loading = true;
            const resAddTemplate = await this.callApi("post", 'settings/tickets/store', this.addForm);
            if (resAddTemplate.status == 201) {
                this.loading = false;
                swal({
                    title: "Success !!",
                    text: "Template Added Successfully",
                    icon: "success",
                    timer: 2000,
                });
                this.clearForm();
                this.fetchTemplates();
            }
            if (resAddTemplate.status == 422) {
                this.loading = false;
                let errorContent = "";
                let count = 0;
                for (const key in resAddTemplate.data.errors) {
                    resAddTemplate.data.errors[key].forEach((element) => {
                        errorContent += (
                            (++count) + " - " + //creating serial no.
                            element + // main error
                            "\n" // creating new line
                        );
                    });
                    swal({
                        title: "Error",
                        text: errorContent,
                        icon: "error",
                        timer: 4000
                    });

                }
            }

        },

        async edit(template) {
            console.log(template)
            this.dataEdit = template;
        },

        async updateTemplate(){
            if (this.dataEdit.terminal_id == '0') {
                return swal({
                    title: "Required !!!",
                    text: "Please Select any Terminal",
                    icon: "error",
                    timer: 2000,
                });
            }
            if (this.dataEdit.uan == '' || typeof this.dataEdit.uan == 'undefined') {
                return swal({
                    title: "Required !!!",
                    text: "UAN Number is Required",
                    icon: "error",
                    timer: 2000,
                });
            }
            if (this.dataEdit.phone == '' || typeof this.dataEdit.phone == 'undefined') {
                return swal({
                    title: "Required !!!",
                    text: "Phone Number is Required",
                    icon: "error",
                    timer: 2000,
                });
            }
            if (this.dataEdit.address == '' || typeof this.dataEdit.address == 'undefined') {
                return swal({
                    title: "Required !!!",
                    text: "Address is Required",
                    icon: "error",
                    timer: 2000,
                });
            }
            if (this.dataEdit.terms_condition == '' || typeof this.dataEdit.terms_condition == 'undefined') {
                return swal({
                    title: "Required !!!",
                    text: "Terms &Condition is Required",
                    icon: "error",
                    timer: 2000,
                });
            }
            this.loadingEdit = true;
            const resEditTemplate = await this.callApi("post", 'settings/tickets/update', this.dataEdit);
            if(resEditTemplate.status == 200){
                this.loadingEdit = false;
                swal({
                    title: "Success",
                    text: "Template Update Successfully ",
                    icon: "success",
                    timer: 4000
                });
                this.fetchTemplates();
            }
            if(resEditTemplate.status == 422){
                this.loadingEdit = false;
                let errorContent = "";
                let count = 0;
                for (const key in resEditTemplate.data.errors) {
                    resEditTemplate.data.errors[key].forEach((element) => {
                        errorContent += (
                            (++count) + " - " + //creating serial no.
                            element + // main error
                            "\n" // creating new line
                        );
                    });
                    swal({
                        title: "Error",
                        text: errorContent,
                        icon: "error",
                        timer: 4000
                    });

                }
            }
        }

    },
    // computed: {
    //     ...mapGetters(['getDeletingObj'])
    // },
    // watch: {
    //     getDeletingObj(obj) {
    //         if (obj.isDeleted) {
    //             this.employees.splice(obj.index, 1)
    //             $("#employee_table").DataTable().destroy();
    //             this.fetchEmployees();
    //         }
    //     }
    // }
};
</script>
<style scoped>

</style>
