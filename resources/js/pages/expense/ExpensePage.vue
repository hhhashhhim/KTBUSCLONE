<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary ">
                        <div class="card-header">
                            <h4>Expenses</h4>
                            <!-- <div class="card-header-action">
                                <a href="#" data-toggle="modal" :data-target="'#'+formID" @click="clearForm()" class="btn btn-primary">
                                    Add New Category
                                </a>
                            </div> -->
                        </div>
                        <div class="card-body">
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">

                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                    <tr>
                                                        <th>Category</th>
                                                        <th>Description</th>
                                                        <th>Amount</th>
                                                        <th>Invoice number</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr v-for="(i,index) in loop" :key="index">
                                                        <td>
                                                            <!-- {{ items[0] ? items[0].price : '' }} -->
                                                            <select class="form-control rounded-0" @change="saveRow($event,'first',index)" :value="postData.category[index]" :disabled="editAble">
                                                                <option value="" selected>Select Food </option>
                                                                <option v-for="(category, i) in categories" :value="category.id" :key="i" >
                                                                    {{ category.name }}
                                                                </option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" @keyup="saveRow($event,'second',index)" :value="postData.description[index]" :disabled="editAble"/>
                                                        </td>
                                                        <td>
                                                            <input type="number" min="0" class="form-control" @keyup="saveRow($event,'third',index)" :value="postData.amount[index]" :disabled="editAble"/>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control" @keyup="saveRow($event,'fourth',index)" :value="postData.invoice[index]" :disabled="editAble"/>
                                                        </td>
                                                        <td v-if="!editAble">
                                                            <button class="btn btn-outline-primary mx-2" @click="addRow">Add</button>
                                                            <button class="btn btn-outline-danger" @click="removeRow($event,index)" v-if="index != 0">Remove</button>
                                                        </td>
                                                        <td v-else></td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                                <div class="d-flex justify-content-end">
                                                    <button type="button" class="btn btn-outline-success mr-4" @click="add" :disabled="loading" v-if="!editAble">{{loading ? 'Loading...' : 'Save' }}
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary mr-4" @click="editAble=false" :disabled="loading" v-else>Edit
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary mr-4" @click="editAble=true" v-if="!editAble && postData.category.length != 0">Cancel
                                                </button>
                                                </div>
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
            <!-- <Add
            heading="Add New Category"
            :errors="this.validationErrors"
            :success="success"
            :formID="formID"
            >
                <div class="form-group">
                    <label for="name">Name <span class="text-danger ml-1">*</span></label>
                    <input type="text" class="form-control" placeholder="Enter Category Name" v-model="data.name">
                </div>
                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="add">{{ loading ? 'Loading...': 'Add New Category' }}</button>
                </template>
            </Add> -->

            <!-- Add Modal -->
            <!-- <Edit
            heading="Edit Category Name"
            :errors="this.validationErrors"
            :success="success"
            :editForm="editFormID"
            >
                <div class="form-group">
                    <label for="name">Name <span class="text-danger ml-1">*</span></label>
                    <input type="text" class="form-control" placeholder="Enter Category Name" v-model="dataEdit.name">
                </div>

                <template v-slot:button>
                    <button type="button" class="btn btn-primary" :disabled="loading" @click="update">{{ loading ? 'Loading...': 'Update Category' }}</button>
                </template>
            </Edit> -->

            <!-- Add Modal -->
            <!-- <Delete :deleteForm="deleteFormID" confirmationMessage="Are You Sure You want To Delete This City ???" /> -->

        </div>
    </section>


</template>

<script>
// import Add from '../../components/Add.vue';
// import Edit from '../../components/Edit.vue';
// import Delete from '../../components/Delete.vue';
import {mapGetters} from 'vuex';

export default {
    name:"expense",
    components:{
        // Add,
        // Edit,
        // Delete,
    },
    data(){
        return {
            validationErrors: [],
            editAble: true,
            categories: [],
            loading : false,
            formID:'expense_form',
            editFormID:'edit_expense_form',
            // deleteFormID:'delete_city_form',
            postData : {
                ticket_merge_id: "",
                category: [],
                description: [],
                amount: [],
                invoice: [],
            },
            // dataEdit:{
            //     id:"",
            //     name:"",
            // },
            // delId:"",
            success:false,
            errors:false,
            loop: 1,
        }
    },
    async created(){
        await this.fetchData();
        await this.existingExpenses();
        setTimeout(function(){
            $("#expense_table").DataTable();
        }, 300);
    },
    methods:{
        clearForm : function(){
            this.data = {};
        },
        async fetchData() {
            const res = await this.callApi("post",'expenses/categories');
            if (res.status == 200) {
                this.categories=res.data;
            }

            this.postData.ticket_merge_id = this.$route.params.id;
        },
        async existingExpenses() {
            const res = await this.callApi("post",'expenses',{ticket_merge_id : this.postData.ticket_merge_id});
            if (res.status == 200) {
                const expenses = res.data;
                if(expenses != "")
                {
                    this.loop = expenses.length;
                    for(var i = 0; i < expenses.length; i++)
                    {
                        this.postData.category.push(expenses[i].expense_category_id);
                        this.postData.description.push(expenses[i].description);
                        this.postData.amount.push(expenses[i].amount);
                        this.postData.invoice.push(expenses[i].invoice);
                    }
                }
                else
                {
                    this.loop = 1;
                    this.editAble = false;
                }
            }
        },
        saveRow(event,fieldName,index) {
            // const getRowNumber = event.target.parentElement.parentElement.rowIndex;
            if(fieldName == "first")
            {
                this.postData.category[index] = event.target.value;
            }
            if(fieldName == "second")
            {
                this.postData.description[index] = event.target.value;
            }
            if(fieldName == "third")
            {
                this.postData.amount[index] = event.target.value;
            }
            if(fieldName == "fourth")
            {
                this.postData.invoice[index] = event.target.value;
            }
        },
        addRow() {
            this.loop++;
        },
        removeRow(event,index) {
            this.postData.category.splice(index, 1);
            this.postData.description.splice(index, 1);
            this.postData.amount.splice(index, 1);
            this.postData.invoice.splice(index, 1);
            this.loop--;
        },
        async add() {

        // validation for empty data
        if(!this.postData.ticket_merge_id || this.postData.category.length == 0 || this.postData.description.length == 0 ||
            this.postData.amount.length == 0 || this.postData.invoice.length == 0)
        {
            return swal({
                title: "Error",
                text: "Please Fill All Field",
                icon: "error",
                timer: 4000
            });
        }

        // check if any index is empty or null in object
        for(var i = 0; i < this.postData.category.length; i++)
        {
            if(!this.postData.category[i] || !this.postData.description[i] || !this.postData.amount[i] || !this.postData.invoice[i])
            {
                return swal({
                    title: "Error",
                    text: "Please Fill All Field Or Remove Extra",
                    icon: "error",
                    timer: 2000
                });
            }
        }


        this.loading = true;
        const res = await this.callApi("post", "expenses/store", this.postData);
        if (res.status === 200) {
            this.loading = false;
            // $('#expense').DataTable().destroy();
            this.postData.category = [];
            this.postData.description = [];
            this.postData.amount = [];
            this.postData.invoice = [];
            this.loop = 0;
            this.editAble = true;
        swal({
                title: "Success",
                text: "Expense Saved",
                icon: "success",
                timer: 2000
            });
            await this.fetchData();
            await this.existingExpenses();
            this.loading = false;
        }
        else {
            this.loading = false;
            if (res.status == 422) {
                let errorContent = "";
                let count = 0;
                for (const key in res.data.errors) {
                    res.data.errors[key].forEach((element) => {
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
    },
    computed:{
        ...mapGetters(['getDeletingObj'])
    },
    watch:{
        getDeletingObj(obj){
            if (obj.isDeleted) {
                this.cities.splice(obj.index,1)
                $("#expense_table").DataTable().destroy();
                this.fetchData();
                this.existingExpenses();
            }
        }
    }
}
</script>
