<template>
    <div>
        <section class="section">
            <h2>Today Overview <button v-if="loading" class="btn btn-secondary btn-progress">abcd</button></h2>
            <div class="row ">
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <div class="card">
                        <div class="card-statistic-4">
                            <div class="align-items-center justify-content-between">
                                <div class="row ">
                                    <div class="col-md-8 pr-0 pt-3">
                                        <div class="card-content">
                                            <h5 class="font-15">Confirmed Booking</h5>
                                            <h2 class="mb-3 font-18">{{ cart.today_confirm??"---" }}</h2>
                                            <p class="mb-0"><span class="col-green">Last Day</span> {{cart.last_confirm??"---"}}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4 pl-0">
                                        <div class="banner-img">
                                            <img :src="$store.state.main_url + 'assets/img/banner/1.png'" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <div class="card">
                        <div class="card-statistic-4">
                            <div class="align-items-center justify-content-between">
                                <div class="row ">
                                    <div class="col-md-8 pr-0 pt-3">
                                        <div class="card-content">
                                            <h5 class="font-15">Reserved Booking</h5>
                                            <h2 class="mb-3 font-18">{{cart.today_reserve??"---"}}</h2>
                                            <p class="mb-0"><span class="col-green">Last Day</span> {{cart.last_reserve??"---"}}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4 pl-0">
                                        <div class="banner-img">
                                            <img :src="$store.state.main_url + 'assets/img/banner/2.png'" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <div class="card">
                        <div class="card-statistic-4">
                            <div class="align-items-center justify-content-between">
                                <div class="row ">
                                    <div class="col-md-8 pr-0 pt-3">
                                        <div class="card-content">
                                            <h5 class="font-15">Canceled Booking</h5>
                                            <h2 class="mb-3 font-18">{{cart.today_cancel??"---"}}</h2>
                                            <p class="mb-0"><span class="col-green">Last Day</span> {{cart.last_cancel??"---"}}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4 pl-0">
                                        <div class="banner-img">
                                            <img :src="$store.state.main_url + 'assets/img/banner/3.png'" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <div class="card">
                        <div class="card-statistic-4">
                            <div class="align-items-center justify-content-between">
                                <div class="row ">
                                    <div class="col-md-8 pr-0 pt-3">
                                        <div class="card-content">
                                            <h5 class="font-15">Overissued Booking</h5>
                                            <h2 class="mb-3 font-18">{{cart.today_overissue??"---"}}</h2>
                                            <p class="mb-0"><span class="col-green">Last Day</span> {{cart.last_overissue??"---"}}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4 pl-0">
                                        <div class="banner-img">
                                            <img :src="$store.state.main_url + 'assets/img/banner/4.png'" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <div class="card">
                        <div class="card-statistic-4">
                            <div class="align-items-center justify-content-between">
                                <div class="row ">
                                    <div class="col-md-8 pr-0 pt-3">
                                        <div class="card-content">
                                            <h5 class="font-15">New Customers</h5>
                                            <h2 class="mb-3 font-18">{{cart.today_new_customers??"---"}}</h2>
                                            <p class="mb-0"><span class="col-green">Last Day</span> {{cart.last_new_customers??"---"}}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4 pl-0">
                                        <div class="banner-img">
                                            <img :src="$store.state.main_url + 'assets/img/banner/4.png'" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <div class="card">
                        <div class="card-statistic-4">
                            <div class="align-items-center justify-content-between">
                                <div class="row ">
                                    <div class="col-md-8 pr-0 pt-3">
                                        <div class="card-content">
                                            <h5 class="font-15">Repeated Customers</h5>
                                            <h2 class="mb-3 font-18">{{cart.today_old_customers??"---"}}</h2>
                                            <p class="mb-0"><span class="col-green">Last Day</span> {{cart.last_old_customers??"---"}}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4 pl-0">
                                        <div class="banner-img">
                                            <img :src="$store.state.main_url + 'assets/img/banner/3.png'" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <div class="card">
                        <div class="card-statistic-4">
                            <div class="align-items-center justify-content-between">
                                <div class="row ">
                                    <div class="col-md-8 pr-0 pt-3">
                                        <div class="card-content">
                                            <h5 class="font-15">Total Discount</h5>
                                            <h2 class="mb-3 font-18">{{cart.today_discount??"---"}}</h2>
                                            <p class="mb-0"><span class="col-green">Last Day</span> {{cart.last_discount??"---"}}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4 pl-0">
                                        <div class="banner-img">
                                            <img :src="$store.state.main_url + 'assets/img/banner/2.png'" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <div class="card">
                        <div class="card-statistic-4">
                            <div class="align-items-center justify-content-between">
                                <div class="row ">
                                    <div class="col-md-8 pr-0 pt-3">
                                        <div class="card-content">
                                            <h5 class="font-15">Net Sales</h5>
                                            <h2 class="mb-3 font-18">{{cart.today_sale??"---"}}</h2>
                                            <p class="mb-0"><span class="col-green">Last Day</span> {{cart.last_sale??"---"}}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4 pl-0">
                                        <div class="banner-img">
                                            <img :src="$store.state.main_url + 'assets/img/banner/1.png'" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Today Schedules</h4>
                            <div class="card-header-form">
                                <form>
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Search">
                                        <div class="input-group-btn">
                                            <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <tr>
                                        <th>Name</th>
                                        <th>Booking</th>
                                        <th>Schedule Date</th>
                                        <th>Departure</th>
                                        <th>Booking Progress</th>
                                        <th>Status</th>
                                    </tr>
                                    <tr v-for="(schedule, index) in schedules" :key="index">
                                        <td>{{ schedule.name }}</td>
                                        <td>{{ schedule.booked_seats }}</td>
                                        <td>{{ schedule.schedule_date }}</td>
                                        <td>{{ schedule.departure_time }}</td>
                                        <td class="align-middle">
                                            <div class="progress-text">{{ schedule.progress }}%</div>
                                            <div class="progress" data-height="6">
                                                <div 
                                                    class="progress-bar bg-success" 
                                                    :style="{ width: schedule.progress + '%' }"
                                                ></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="badge" :class="schedule.drop ? 'badge-danger' : 'badge-success'">
                                                {{ schedule.drop ? 'Drop' : 'Active' }}
                                            </div>
                                        </td>
                                    </tr>

                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Projects Payments</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Client Name</th>
                                            <th>Date</th>
                                            <th>Payment Method</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>1</td>
                                            <td>John Doe</td>
                                            <td>11-08-2018</td>
                                            <td>NEFT</td>
                                            <td>$258</td>
                                        </tr>
                                        <tr>
                                            <td>2</td>
                                            <td>Cara Stevens
                                            </td>
                                            <td>15-07-2018</td>
                                            <td>PayPal</td>
                                            <td>$125</td>
                                        </tr>
                                        <tr>
                                            <td>3</td>
                                            <td>
                                                Airi Satou
                                            </td>
                                            <td>25-08-2018</td>
                                            <td>RTGS</td>
                                            <td>$287</td>
                                        </tr>
                                        <tr>
                                            <td>4</td>
                                            <td>
                                                Angelica Ramos
                                            </td>
                                            <td>01-05-2018</td>
                                            <td>CASH</td>
                                            <td>$170</td>
                                        </tr>
                                        <tr>
                                            <td>5</td>
                                            <td>
                                                Ashton Cox
                                            </td>
                                            <td>18-04-2018</td>
                                            <td>NEFT</td>
                                            <td>$970</td>
                                        </tr>
                                        <tr>
                                            <td>6</td>
                                            <td>
                                                John Deo
                                            </td>
                                            <td>22-11-2018</td>
                                            <td>PayPal</td>
                                            <td>$854</td>
                                        </tr>
                                        <tr>
                                            <td>7</td>
                                            <td>
                                                Hasan Basri
                                            </td>
                                            <td>07-09-2018</td>
                                            <td>Cash</td>
                                            <td>$128</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->
        </section>
    </div>
</template>
<script>
export default {
    data() {
        return {
            loading: true,
            cart: {},
            schedules: {},
        }
    },
    created() {
        this.fetchData();
    },
    methods: {

        async fetchData() {
            this.loading = true;
            const res = await this.callApi("post", 'terminals/dashboard/data');
            if (res.status == 200) {
                this.cart = res.data.cart;
                this.schedules = res.data.schedules;
            }
            if (res.status == 403) {
                window.history.back();
                return swal({
                    title: "OOPS!!!!!",
                    text: "ACCESS DENIED",
                    icon: "error",
                    timer: 2000
                });
            }
            this.loading = false;
            setTimeout(function () {
                $("#city_table").DataTable();
            }, 300);
        },
    }
};
</script>
