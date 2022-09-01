<template>
<div>
    <!-- Modal -->
<div class="modal fade" id="showDetails" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                    <div class="card-body">
                       <div class="table-responsive">
                       <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>City From</th>
                                <th>City To</th>
                                <th>Terminals</th>
                                <th>Business Fare</th>
                                <th>Executive Fare</th>
                                <th>Ecomony Fare</th>
                            </tr>
                        </thead>
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
   name : 'showRouteDetails'
}
</script>
