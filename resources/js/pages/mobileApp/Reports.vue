<template>
    <div class="ma-panel">
        <div class="ma-panel-head"><div><h2>Mobile App Reports</h2><p class="ma-muted">Review app users, registrations, booking activity, account restrictions, sales and payments.</p></div><button v-if="options.permissions.reports_export" class="btn btn-outline-success" :disabled="loading || exporting || !data" @click="download"><i class="fas fa-file-csv mr-1"></i>{{ exporting ? 'Exporting…' : 'Export filtered report' }}</button></div>
        <MobileFilters id="ma-reports" v-model="filters" :options="options" dates :environments="usesEnvironment" terminals routes :search="userReport ? searchLabel : ''" :statuses="userReport ? accountStatuses : []" :busy="loading" @apply="load(1,true)">
            <div class="ma-field"><label for="ma-report-type">Report</label><select id="ma-report-type" class="form-control" v-model="filters.report" @change="reportChanged"><optgroup label="Booking & payment reports"><option v-for="report in reports.filter(item => !item.userReport)" :key="report.value" :value="report.value">{{ report.label }}</option></optgroup><optgroup v-if="options.permissions.users_view" label="App user reports"><option v-for="report in reports.filter(item => item.userReport)" :key="report.value" :value="report.value">{{ report.label }}</option></optgroup></select></div>
            <div v-if="userReport" class="ma-field"><label for="ma-report-verification">Mobile verification</label><select id="ma-report-verification" class="form-control" v-model="filters.verification"><option value="">All verification states</option><option value="verified">Verified</option><option value="unverified">Unverified</option></select></div>
        </MobileFilters>
        <p class="ma-note m-3">{{ selectedReport?.hint }} Select your filters, then click Apply filters.</p>
        <div v-if="error" class="ma-error m-3" role="alert">{{ error }} <button class="btn btn-outline-danger btn-sm ml-2" @click="load()">Retry</button></div>
        <div v-if="exportError" class="ma-error m-3" role="alert">{{ exportError }}</div>
        <template v-if="data">
            <div class="mx-3 mt-3"><h3>{{ appliedTitle }}</h3><p class="ma-muted">{{ applied.from }} to {{ applied.to }}</p></div>
            <p v-if="data.note" class="ma-note m-3">{{ data.note }}</p>
            <div v-if="data.summary" class="ma-kpis m-3"><article class="ma-kpi" v-for="(value,key) in data.summary" :key="key"><span>{{ label(key) }}</span><strong>{{ ['confirmed_sales','recorded_refunds'].includes(key) ? money(value) : number(value) }}</strong></article></div>
        </template>
        <div class="ma-table-wrap"><table class="ma-table"><thead><tr><th v-for="column in data?.columns || []" :key="column.key">{{ column.label }}</th><th v-if="!data?.columns?.length">Report</th></tr></thead><tbody>
            <tr v-if="loading"><td :colspan="data?.columns?.length || 1" class="ma-empty" role="status">Generating report…</td></tr>
            <template v-else><tr v-for="(item,index) in data?.items || []" :key="index"><td v-for="column in data.columns" :key="column.key">{{ format(item[column.key],column.key) }}</td></tr><tr v-if="!data?.items?.length && !error"><td :colspan="data?.columns?.length || 1" class="ma-empty"><i class="fas fa-chart-bar"></i>No report records match these filters.</td></tr></template>
        </tbody></table></div>
        <MobilePagination :pagination="data?.pagination" :busy="loading" @change="load" />
    </div>
</template>
<script>
import MobileFilters from './MobileFilters.vue';
import MobilePagination from './MobilePagination.vue';
import { request,today,label,number,money,displayTime,formatCnic } from './mobileApp';

export default {
    components:{MobileFilters,MobilePagination}, props:{options:Object},
    data:()=>({
        filters:{from:today(),to:today(),environment:'live',report:'sales',terminal_id:'',route_id:'',search:'',status:'',verification:''},
        applied:null,data:null,loading:false,error:'',exportError:'',exporting:false,sequence:0,
        reports:[
            {value:'sales',label:'Daily Mobile Sales',hint:'Dates refer to booking creation.'},
            {value:'routes',label:'Route Performance',hint:'Dates refer to booking creation.'},
            {value:'passengers',label:'Passenger Activity',hint:'Dates refer to booking creation; includes passengers with bookings in the period.'},
            {value:'cancellations',label:'Cancellations & Refunds',hint:'Dates refer to booking creation; refunds are recorded liabilities, not proof of gateway payout.'},
            {value:'payments',label:'Payment Monitoring',hint:'Dates refer to payment creation.'},
            {value:'user_registrations',label:'User Registrations',userReport:true,hint:'Daily registration totals with current verification and restriction counts. Dates refer to registration.'},
            {value:'user_verification',label:'User Directory & Verification',userReport:true,hint:'Passenger CNIC, contact details and current verification status. Dates refer to registration.'},
            {value:'user_restrictions',label:'Blocked & Blacklisted Users',userReport:true,hint:'Currently restricted users. Dates refer to the last update to their account controls.'},
            {value:'user_bookings',label:'User Booking Activity',userReport:true,hint:'Bookings, seats, cancellations and confirmed sales per user, including zero-booking users. Dates refer to booking creation.'},
            {value:'users_without_bookings',label:'Users Without Bookings',userReport:true,hint:'Users with no visible bookings in the selected period and payment environment. This is not a measure of app usage.'},
        ],
    }),
    computed:{
        selectedReport(){return this.reports.find(report=>report.value===this.filters.report);},
        userReport(){return !!this.selectedReport?.userReport;},
        usesEnvironment(){return !this.userReport || ['user_bookings','users_without_bookings'].includes(this.filters.report);},
        accountStatuses(){return this.filters.report==='user_restrictions'?['blocked','blacklisted']:['active','blocked','blacklisted'];},
        searchLabel(){return this.options?.permissions?.users_sensitive?'Name, mobile, CNIC, email or passenger ID':'Name, mobile, email or passenger ID';},
        appliedTitle(){return this.reports.find(report=>report.value===this.applied?.report)?.label || 'Report';},
    },
    mounted(){
        const report=this.reports.find(item=>item.value===this.$route?.query?.report);
        if(report && (!report.userReport || this.options.permissions.users_view)){
            this.filters.report=report.value;
            if(report.userReport)this.filters.from=today().slice(0,8)+'01';
        }
        this.load(1,true);
    },
    beforeUnmount(){this.sequence++;},
    methods:{
        label,number,money,
        format(value,key){
            if(value===null||value===undefined||value==='')return '—';
            if(['confirmed_sales','recorded_refunds'].includes(key))return money(value);
            if(key==='cnic')return formatCnic(value);
            if(key.endsWith('_at'))return displayTime(value);
            if(['status','verification'].includes(key))return label(value);
            if(key==='requires_review')return Number(value)?'Yes':'No';
            if(Array.isArray(value))return value.join(', ');
            if(typeof value==='boolean')return value?'Yes':'No';
            return value;
        },
        reportChanged(){this.filters.status='';this.filters.verification='';this.filters.search='';},
        query(){
            const filters={...this.filters};
            if(!this.userReport){delete filters.search;delete filters.status;delete filters.verification;}
            if(!this.usesEnvironment)delete filters.environment;
            return filters;
        },
        async load(page=1,apply=false){
            if(apply||!this.applied)this.applied=this.query();
            const sequence=++this.sequence;this.loading=true;this.error='';this.exportError='';this.data=null;
            try{const data=await request(this,'get','reports',null,{...this.applied,page});if(sequence===this.sequence)this.data=data;}
            catch(error){if(sequence===this.sequence)this.error=error.message;}
            finally{if(sequence===this.sequence)this.loading=false;}
        },
        async download(){
            if(this.exporting||!this.applied)return;this.exporting=true;this.exportError='';
            try{
                const response=await this.callApi('get','mobile-app/reports/export',null,{params:this.applied,responseType:'blob',headers:{Accept:'text/csv'},validateStatus:()=>true});
                if(!response||response.status!==200){
                    let message='Unable to export this report. Please try again.';
                    if(response?.data?.text){try{message=JSON.parse(await response.data.text()).message||message;}catch(error){/* Do not download an error page as a report. */}}
                    throw new Error(message);
                }
                const url=URL.createObjectURL(response.data);const link=document.createElement('a');link.href=url;
                link.download=`mobile-${this.applied.report}-${this.applied.from}-${this.applied.to}.csv`;link.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
            }catch(error){this.exportError=error.message;}finally{this.exporting=false;}
        },
    },
};
</script>
