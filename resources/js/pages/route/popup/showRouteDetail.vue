<template>
    <div>
        <!-- Modal -->
        <div class="modal fade" id="showDetails" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Route Fare Chart</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="card">
                            <div class="card-body" style="font-size:14px;">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                        <tr>
                                            <th>City From</th>
                                            <th>City To</th>
                                            <th>Economy Fare</th>
                                            <th>Business Fare</th>
                                            <th>Executive Fare</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr v-for="item in routeDetails" :key="item.id">
                                            <td>{{ item[0].city_from.name.toUpperCase() }}</td>
                                            <td>
                                                {{ item[0].city_to.name.toUpperCase() }}
                                            </td>
                                            <template v-for="(n,i) in 3" :key="i">
                                                <td v-if="item[i]">RS.{{ item[i].fare_details.fare }}</td>
                                                <td v-else>N/A</td>
                                            </template>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name: 'showRouteDetails',
    props: {
        routeDetails: Array
    },
    methods: {
        testFunction(item) {
            console.log(item);
        }
    }
}
</script>
