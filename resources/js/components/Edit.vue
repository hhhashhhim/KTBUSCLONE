<template>

    <!-- Modal -->
    <div class="modal fade" id="edit-modal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="card card-success">
                        <div class="card-header d-flex justify-content-between">
                            <h4 class="modal-title">{{ heading }}</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-danger alert-dismissible fade show" role="alert" v-if="errors.length">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                    <span class="sr-only">Close</span>
                                </button>
                                <!-- {{ errors.length }} -->
                                <ul>
                                    <li v-for="(error,i) in errors" :key="i">{{ error.desc }}</li>
                                </ul>
                            </div>
                            <div class="alert alert-success alert-dismissible fade show" role="alert" v-if="success">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                    <span class="sr-only">Close</span>
                                </button>
                                {{ success }}
                            </div>
                            <slot></slot>
                        </div>
                    </div>
                </div>
                <!-- <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" @click="this.$parent.update()">Save</button>
                </div> -->
            </div>
        </div>
    </div>

</template>
<script>
export default {
    props:{
        heading:String,
        errors:Array,
        success:String,
        dataEdit:Object,
        formID:String
    },
    methods:{
        close(){
            alert('Reaching')
            $(`#${this.formID}`).modal('hide')
        }
    },
    watch:{
        success(newSuccess,oldSuccess){
            if (newSuccess!="") {
                swal('Success', newSuccess, 'success');
            }
        },
        errors(newError,oldError){
            if (newError!="" && newError!=[]) {
                swal('Error', 'Oops Something Went Wrong', 'error');
            }

        }
    }
}
</script>
