<template>

    <section class="section">
        <div class="section-body">

            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Cities</h4>
                            <div class="card-header-action">
                                <a href="#" data-toggle="modal" :data-target="'#'+formID" @click="clearForm()" class="btn btn-primary">
                                    Add New City
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
                                                <table class="table table-striped table-hover" id="city_table">
                                                    <thead>
                                                        <tr>
                                                            <th>Sr No.</th>
                                                            <th>Name</th>
                                                            <th>Added By </th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(city,i) in cities" :key="i">
                                                            <td>{{ i+1 }}</td>
                                                            <td>{{ city.name }}</td>
                                                            <td>{{ city.added_by.name }}</td>
                                                            <td>
                                                                <button :data-target="'#' + editFormID" data-toggle="modal" @click="edit(city)" class=" text-light btn btn-primary mx-1">
                                                                    <i class="far fa-edit"></i>
                                                                </button>
                                                                <button :data-target="'#'+ deleteFormID" data-toggle="modal" @click="deleteModal(city,i)" class=" text-light btn btn-danger">
                                                                    <i class="far fa-trash-alt"></i>
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
            heading="Add New City"
            :errors="this.validationErrors"
            :success="success"
            :formID="formID"
            >
                <div class="form-group">
                    <label for="name">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" placeholder="Enter City Name" v-model="data.name">
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :class="loading?'disabled':''" @click="add">{{ loading ? 'Loading...': 'Add New City' }}</button>
                </template>
            </Add>

            <!-- Add Modal -->
            <Edit
            heading="Edit City Name"
            :errors="this.validationErrors"
            :success="success"
            :editForm="editFormID"
            >
                <div class="form-group">
                    <label for="name">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" placeholder="Enter City Name" v-model="dataEdit.name">
                </div>

                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :class="loading?'disabled':''" @click="update">{{ loading ? 'Loading...': 'Update City' }}</button>
                </template>
            </Edit>

            <!-- Add Modal -->
            <Delete :deleteForm="deleteFormID" confirmationMessage="Are You Sure You want To Delete This City ???" />

        </div>
    </section>


</template>

<script>
import Add from '../../components/Add.vue';
import Edit from '../../components/Edit.vue';
import Delete from '../../components/Delete.vue';
import { mapGetters } from 'vuex';

export default {
    name:"city",
    components:{
        Add,
        Edit,
        Delete,
    },
    data(){
        return {
            validationErrors: [],
            cities: [],
            loading : false,
            formID:'city_form',
            editFormID:'edit_city_form',
            deleteFormID:'delete_city_form',
            data:{
                name:"",
            },
            dataEdit:{
                id:"",
                name:"",
            },
            delId:"",
            success:false,
            errors:false,
        }
    },
    async created(){
        await this.fetchCities();
    },
    methods:{
        clearForm : function(){
            this.data = {};
        },
        async fetchCities() {
            const resCity = await this.callApi("post",'cities');
            if (resCity.status==200) {
                this.cities=resCity.data
            }
            setTimeout(function(){
                $("#city_table").DataTable();
            }, 50); //Time before execution
             //Time before execution
        },
        async add(){
            this.validationErrors = []
            if(this.data.name == "")
                // return this.errorsArray("City Name is Required","Name");
            // swal('Required','City Name is Required','error')
              return swal({
                    title: "Required",
                    text: "City Name is required",
                    icon: "error",
                   timer: 2000
                });
            //this.loading = true
            const res = await this.callApi("post",'cities/store',this.data);
            if (res.status == 200) {
                // this.success="City Created Successfully Named as " + res.data.name;
               swal({
                    title: "Success",
                    text: "City Created Succesfuly Named as  " + res.data.name,
                    icon: "success",
                   timer: 2000
                });

                //this.loading = false
                // swal('Success', 'City Added Successfully', 'success');
                // await this.fetchCities();
                // this.cities.unshift(res.data);
                this.cities.push(res.data);
                this.data.name = "";
                setTimeout(function(){
                    this.success = "";
                    this.data = "";
                },300)
            }
            else {
                if (res.status == 422) {
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                setTimeout(function () {
                  //this.loading = false
                }, 2000);
            }
        },
        edit( city ){
            let newCity = city
            this.dataEdit = newCity;
        },
        async update(){
            this.validationErrors=[]
            if(this.dataEdit.name=="")
                // return this.errorsArray("City Name is Required","Name");
                // swal('Required','City Name is Required','error')
              return swal({
                    title: "Required",
                    text: "city Name is required ",
                    icon: "error",
                   timer: 2000
                });
            //this.loading = true
            const resEdit = await this.callApi("post",'cities/update', this.dataEdit);
            if (resEdit.status==200) {
                // swal('Success', 'City Updated Successfully', 'success');
               swal({
                    title: "Success",
                    text: "City updated Successfully",
                    icon: "success",
                   timer: 2000
                });
                //this.loading = false
                await this.fetchCities();
                this.dataEdit.name = this.dataEdit.company_id ="";
                setTimeout(() => {
                    this.success=""
                    $('#edit-modal').modal('hide')
                }, 3000);
            }
            else{
                if (res.status == 422) {
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                setTimeout(() => {
                    //this.loading = false
                }, 3000);
            }
        },
        async deleteModal( city,i ){
            const deletingObj = {
                url:"cities/delete",
                data:city,
                index:i,
            }
            this.$store.commit("setDeleteObj",deletingObj);
        },
    },
    computed:{
        ...mapGetters(['getDeletingObj'])
    },
    watch:{
        getDeletingObj(obj){
            if (obj.isDeleted) {
                this.cities.splice(obj.index,1)
                this.fetchCities();
            }
        }
    }
}
</script>
