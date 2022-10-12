@extends('super-admin.app')
@section('content')
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h4>Permissions</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between">
                                            <h4>{{ role . name }}</h4>
                                            <button class="btn btn-primary" @click="save">SAVE</button>
                                        </div>
                                        <div class="card-body">


                                            <div class="alert alert-success alert-dismissible fade show" role="alert"
                                                v-if="success">
                                                <button type="button" class="close" data-dismiss="alert"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                    <span class="sr-only">Close</span>
                                                </button>
                                                {{ success }}
                                            </div>

                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover" id="edit_loc">
                                                    <thead>
                                                        <tr>
                                                            <th>Sr No.</th>
                                                            <th>Module Name</th>
                                                            <th>Permissions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>1</td>
                                                            <td>HRM</td>
                                                            <td>
                                                                <label class="colorinput mx-3">
                                                                    <input :checked="permission[i]" type="checkbox"
                                                                        :value="true" class="colorinput-input"
                                                                        v-model="permission[i]" />
                                                                    <span class="colorinput-color bg-primary"></span>
                                                                    <span style="position:relative;top:-10px;left:5px;">
                                                                        </span>
                                                                </label>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
