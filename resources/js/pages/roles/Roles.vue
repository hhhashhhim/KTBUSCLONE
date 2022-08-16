<template>

    <section class="section">
        <div class="section-body">

            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-success">
                        <div class="card-header">
                            <h4>Roles</h4>
                            <div class="card-header-action">
                                <a href="#add-modal" data-toggle="modal" class="btn btn-success">
                                    Add New
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
                                                <table class="table table-striped table-hover" id="edit_loc">
                                                    <thead>
                                                        <tr>
                                                            <th>Sr No.</th>
                                                            <th>Name</th>
                                                            <th v-if="$store.state.user.is_super_admin==1">Company Name</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(role,i) in roles" :key="i">
                                                            <td>{{ i+1 }}</td>
                                                            <td>{{ role.name }}</td>
                                                            <td v-if="$store.state.user.is_super_admin==1">{{ role.company?role.company.name:"Not Found" }}</td>
                                                            <td>
                                                                <router-link :to="{name: 'role.permission', params: { id:role.id }}" class="btn btn-success">
                                                                    <i class="fas fa-user-shield"></i>
                                                                </router-link>    
                                                                <a href="#edit-modal" data-toggle="modal" @click="edit(role)" class="btn btn-warning mx-1">
                                                                    <i class="far fa-edit"></i>
                                                                </a>
                                                                <a href="#delete-modal" data-toggle="modal" @click="deleteModal(role,i)" class="btn btn-danger">
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
                </div>
            </div>

            <!-- Add Modal -->
            <Add 
            heading="New Role"
            :errors="this.validationErrors"
            :success="success"
            >
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" placeholder="Enter Name" id="name" v-model="data.name">
                </div>
                <div class="form-group" v-if="$store.state.user.is_super_admin==1">
                    <label for="company">Company</label>
                    <select name="comapany" id="company" class="form-control" v-model="data.company_id">
                        <option value="">Select Company</option>
                        <option v-for="(company,i) in companies" :key="i" :value="company.id">{{ company.name }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-block btn-success" @click="add">Add Role</button>
                </div>
            </Add>

            <!-- Add Modal -->
            <Edit 
            heading="Edit Role"
            :errors="this.validationErrors"
            :success="success"
            >
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" placeholder="Enter Name" id="name" v-model="dataEdit.name">
                </div>
                <div class="form-group" v-if="$store.state.user.is_super_admin==1">
                    <label for="company">Company</label>
                    <select name="comapany" id="company" class="form-control" v-model="dataEdit.company_id">
                        <option value="">Select Company</option>
                        <option v-for="(company,i) in companies" :key="i" :value="company.id">{{ company.name }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-block btn-success" @click="update">Update Role</button>
                </div>
            </Edit>

            <!-- Add Modal -->
            <Delete confirmationMessage="Are You Sure You want To Delete This Role ???" />
            
        </div>
    </section>


</template>

<script>
import Add from '../../components/Add.vue';
import Edit from '../../components/Edit.vue';
import Delete from '../../components/Delete.vue';
import { mapGetters } from 'vuex';

export default {
    name:"Role",
    components:{
        Add,
        Edit,
        Delete,
    },
    data(){
        return {
            roles:[],
            companies:[],
            data:{
                name:"",
                company_id:""
            },
            dataEdit:{
                id:"",
                name:"",
                company_id:"",
            },
            delId:"",
            success:false,
        }
    },
    async created(){
        const companyRes = await this.callApi("post", "/company");
        if (companyRes.status==200){
            this.companies = companyRes.data;
        }
        const res = await this.callApi("post",'/role',{name:this.data.name});
        if (res.status==200) {
            this.roles=res.data
        }
        else{
            console.log(res);
        }
    },
    methods:{
        async add(){
            this.validationErrors=[]
            if(this.data.name=="") return this.errorsArray("Role Name is Required","Name");
            if(this.data.company_id=="" && $store.state.user.is_super_admin==1) return this.errorsArray("Company is Required","Company");
            const res = await this.callApi("post",'/role/store',this.data);
            if (res.status==200) {
                this.success="Role Created Successfully";
                this.roles.unshift(res.data);
                this.data.name = this.data.company_id = "";
                setTimeout(() => {
                    this.success=""
                }, 3000);
            }
            else{
                if (res.status==422) {
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach(element => {
                            this.errorsArray(element,key)
                        });
                    }
                }
            }
        },
        edit( role ){
            this.dataEdit = role;
        },
        async update(){
            
            this.validationErrors=[]
            if(this.dataEdit.name=="") return this.errorsArray("Role Name is Required","Name");
            if(this.dataEdit.company_id=="" && $store.state.user.is_super_admin==1) return this.errorsArray("Company is Required","Company");
            const res = await this.callApi("post",'/role/update',this.dataEdit);
            if (res.status==201) {
                this.success="Role Updated Successfully";
                const res = await this.callApi("post",'/role',{name:this.data.name});
                if (res.status==200) {
                    this.roles=res.data
                }
                this.dataEdit.name = this.dataEdit.company_id ="";
                setTimeout(() => {
                    this.success=""
                }, 3000);
            }
            else{
                if (res.status==422) {
                    console.log();
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach(element => {
                            this.errorsArray(element,key)
                        });
                    }
                }
            }
        },
        async deleteModal( role,i ){
            const deletingObj = {
                url:"/role/delete",
                data:role,
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
            console.log(obj);
            if (obj.isDeleted) {
                this.roles.splice(obj.index,1)
            }
        }
    }
}
</script>