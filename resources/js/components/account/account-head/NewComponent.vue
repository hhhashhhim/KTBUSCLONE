<template>
    <div
        class="modal fade"
        id="newHead"
        tabindex="-1"
        role="dialog"
        aria-labelledby="myLargeModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 960px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">
                        New Ledger
                    </h5>
                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close"
                    >
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label>Tier 1 <span class="text-danger">*</span></label>
                            <select2 v-model="addData.first_level" :options="firstLevel" @select="getSecondLevel(addData.first_level)"
                            :settings="{ settingOption: value, settingOption: value, width: '100%' }" 
                                />
                        </div>
                        <div class="form-group col-md-3">
                            <label>Tier 2 <span class="text-danger">*</span></label>
                            <select2 v-model="addData.second_level" :options="secondLevel"  @select="getThirdLevel(addData.second_level)"
                            :settings="{ settingOption: value, settingOption: value, width: '100%' }" 
                                />
                        </div>
                        <div class="form-group col-md-3">
                            <label>Tier 3 <span class="text-danger">*</span></label>
                            <select2 v-model="addData.third_level" :options="thirdLevel" @select="getFourthLevel(addData.third_level)"
                            :settings="{ settingOption: value, settingOption: value, width: '100%' }" 
                                />
                        </div>
                        <div class="form-group col-md-3">
                            <label>Tier 4 <span class="text-danger">*</span></label>
                            <select2 v-model="addData.fourth_level" :options="fourthLevel"
                            :settings="{ settingOption: value, settingOption: value, width: '100%' }" 
                                />
                        </div>
                        <div class="form-group col-md-12">
                            <label>Ledger Name <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" v-model="addData.name">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-whitesmoke br">
                    <button type="button" class="btn btn-primary" :class="{ 'disabled btn-progress': btnLoading }" @click=add()>Save</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
  props: ['btnLoading', 'addData', 'firstLevel'],
  data() {
        return {
            secondLevel: [],
            thirdLevel: [],
            fourthLevel: [],
        };
    },
  methods: {
    add() {
      this.$emit('add');
    },
    async getSecondLevel(id) {
        const res = await this.callApi("get", 'accounts/'+id+'/second');
        if (res.status == 200) {
            this.secondLevel = res.data.secondLevel
        }
    },
    async getThirdLevel(id) {
        const res = await this.callApi("get", 'accounts/groups/'+id+'/third');
        if (res.status == 200) {
            this.thirdLevel = res.data.thirdLevel
        }
    },
    async getFourthLevel(id) {
        const res = await this.callApi("get", 'accounts/groups/'+id+'/fourth');
        if (res.status == 200) {
            this.fourthLevel = res.data.fourthLevel
        }
    },
  }
}
</script>