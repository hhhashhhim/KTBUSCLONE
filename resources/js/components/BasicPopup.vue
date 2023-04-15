<template>

    <!-- Modal -->
    <div class="modal fade" :id="formID" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><span v-html="heading"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" @click="close()">
                                <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                  
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
                    <slot></slot>
                </div>
                
                <div class="modal-footer bg-whitesmoke br">
                    <slot name="button"></slot>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" @click="close()">Close</button>
                </div>
            </div>
        </div>
    </div>

</template>
<script>
import { watch } from '@vue/runtime-core'
export default {
    props:{
        heading:String,
        errors:Array,
        success:String,
        formID:String
    },
    methods:{
        close(){
            $(`#${this.formID}`).click();
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
