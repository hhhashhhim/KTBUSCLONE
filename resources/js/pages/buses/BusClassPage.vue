<template>
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="card card-primary">
                        <div class="card-header d-flex justify-content-between">
                            <h4>Bus Class</h4>
                            <div class="card-header-action">
                                <a
                                    v-if="
                                        checkForSubmenuButtons('add-bus-class')
                                    "
                                    href="#"
                                    data-toggle="modal"
                                    :data-target="'#' + formID"
                                    class="btn btn-primary"
                                    @click="clearForm()"
                                >
                                    Add Bus Class
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <transition name="fade">
                                <div
                                    class="alert alert-danger alert-dismissible fade show"
                                    role="alert"
                                    v-if="error"
                                >
                                    <button
                                        type="button"
                                        class="close"
                                        data-dismiss="alert"
                                        aria-label="Close"
                                        @click="error = !error"
                                    >
                                        <span aria-hidden="true">&times;</span>
                                        <span class="sr-only">Close</span>
                                    </button>
                                    Please Enter All Required Fields !!!
                                </div>
                            </transition>
                            <!-- Table -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table
                                                    class="table table-striped table-hover"
                                                    id="bus_class_table"
                                                >
                                                    <thead>
                                                        <tr>
                                                            <th>Sr No.</th>
                                                            <th>Name</th>
                                                            <th>Color</th>
                                                            <th>Status</th>
                                                            <th>Added By</th>
                                                            <th
                                                                v-if="
                                                                    checkForSubmenuButtons(
                                                                        'edit-bus-Class'
                                                                    ) ||
                                                                    checkForSubmenuButtons(
                                                                        'duplicate-bus-Class'
                                                                    ) ||
                                                                    checkForSubmenuButtons(
                                                                        'delete-bus-class'
                                                                    )
                                                                "
                                                            >
                                                                Action
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="(
                                                                busClass, i
                                                            ) in busClasses"
                                                            :key="i"
                                                        >
                                                            <td>{{ i + 1 }}</td>
                                                            <td>
                                                                {{
                                                                    busClass.name
                                                                }}
                                                            </td>
                                                            <td>
                                                                <div
                                                                    style="
                                                                        border-radius: 50%;
                                                                        height: 50px;
                                                                        width: 50px;
                                                                    "
                                                                    :style="{
                                                                        backgroundColor:
                                                                            busClass.color,
                                                                    }"
                                                                ></div>
                                                            </td>
                                                            <td>
                                                                {{
                                                                    busClass.is_active ==
                                                                    1
                                                                        ? "Active"
                                                                        : "InActive"
                                                                }}
                                                            </td>
                                                            <td>
                                                                {{
                                                                    busClass
                                                                        .added_by
                                                                        .name
                                                                }}
                                                            </td>
                                                            <td
                                                                v-if="
                                                                    checkForSubmenuButtons(
                                                                        'edit-bus-Class'
                                                                    ) ||
                                                                    checkForSubmenuButtons(
                                                                        'duplicate-bus-Class'
                                                                    ) ||
                                                                    checkForSubmenuButtons(
                                                                        'delete-bus-class'
                                                                    )
                                                                "
                                                            >
                                                                <button
                                                                    title="Duplicate Bus Class"
                                                                    v-if="
                                                                        checkForSubmenuButtons(
                                                                            'duplicate-bus-Class'
                                                                        )
                                                                    "
                                                                    @click="
                                                                        duplicate(
                                                                            busClass.id,
                                                                            i +
                                                                                1
                                                                        )
                                                                    "
                                                                    class="btn btn-info mx-1"
                                                                >
                                                                    <i
                                                                        class="fas fa-clone"
                                                                    ></i>
                                                                </button>
                                                                <button
                                                                    title="Edit Bus Class"
                                                                    v-if="
                                                                        checkForSubmenuButtons(
                                                                            'edit-bus-Class'
                                                                        )
                                                                    "
                                                                    :data-target="
                                                                        '#' +
                                                                        editFormID
                                                                    "
                                                                    data-toggle="modal"
                                                                    @click="
                                                                        edit(
                                                                            busClass
                                                                        )
                                                                    "
                                                                    class="btn btn-primary mx-1"
                                                                >
                                                                    <i
                                                                        class="far fa-edit"
                                                                    ></i>
                                                                </button>
                                                                <button
                                                                    v-if="
                                                                        checkForSubmenuButtons(
                                                                            'delete-bus-class'
                                                                        )
                                                                    "
                                                                    :data-target="
                                                                        '#' +
                                                                        hideFormID
                                                                    "
                                                                    @click="
                                                                        delId =
                                                                            busClass.id
                                                                    "
                                                                    data-toggle="modal"
                                                                    class="btn btn-danger"
                                                                >
                                                                    <i
                                                                        class="far fas fa-trash"
                                                                    ></i>
                                                                </button>
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
                :heading="'Add Bus Class'"
                :errors="this.validationErrors"
                :success="success"
                :formID="formID"
            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="name"
                            >Name<span class="text-danger ml-1">*</span></label
                        >
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter Bus Class Name"
                            id="name"
                            v-model="data.BusClassName"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="color"
                            >Color<span class="text-danger ml-1">*</span></label
                        >
                        <input
                            type="color"
                            class="form-control"
                            id="color"
                            v-model="data.BusClassColor"
                        />
                    </div>
                    <div class="form-group col-md-2">
                        <h5>Status</h5>
                        <div class="form-group d-flex align-items-center">
                            <label class="mt-4" for="active">Is Active</label>
                            <label class="colorinput mx-3 mt-3">
                                <span>
                                    <input
                                        type="checkbox"
                                        value="1"
                                        checked
                                        class="colorinput-input"
                                        @change="checkBox($event)"
                                    />
                                    <span
                                        class="colorinput-color bg-primary"
                                    ></span>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="name"
                            >No. of Rows<span class="text-danger ml-1"
                                >*</span
                            ></label
                        >
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter No. of Rows"
                            v-model="data.noOfRows"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-3">
                        <label for="name"
                            >No. of Cols<span class="text-danger ml-1"
                                >*</span
                            ></label
                        >
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter No. of Cols"
                            v-model="data.noOfCols"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-3 my-4 pt-2">
                        <button
                            type="button"
                            class="btn btn-block btn-primary"
                            @click="addFormGenerateMap"
                        >
                            Generate Seat Map
                        </button>
                    </div>
                </div>
                <div class="row mx-3 mainRow" v-if="isShowDiv">
                    <div class="form-group col-md-5 border py-3">
                        <tr
                            class="seat-img p-0 m-0 s"
                            v-for="(record, rowIndex) in data.seatMap"
                            :key="rowIndex"
                        >
                            <td
                                v-for="(col, colIndex) in record"
                                :key="colIndex"
                                :class="col.reserved ? 'selected border' : ''"
                            >
                                <img
                                    @click="changeStatus(rowIndex, colIndex)"
                                    :src="
                                        $store.state.api_url +
                                        'assets/img/buses/available_seat_img.gif'
                                    "
                                    alt=""
                                />
                            </td>
                        </tr>
                    </div>
                    <div
                        class="form-group col-md-5 border py-3"
                        style="border-spacing: 5px"
                    >
                        <tr
                            class="seat-img p-0 m-0"
                            v-for="(record, rowIndex) in data.seatMap"
                            :key="rowIndex"
                        >
                            <td
                                v-for="(col, colIndex) in record"
                                :key="colIndex"
                            >
                                <small
                                    class="font-weight-bold position-absolute text-dark"
                                    style="font-size: 10px !important"
                                    v-if="col.reserved"
                                    >{{
                                        this.data.seatMap[rowIndex][colIndex]
                                            .seatNo ?? "N/A"
                                    }}</small
                                >
                                <img
                                    :data-toggle="
                                        this.data.seatMap[rowIndex][colIndex]
                                            .reserved
                                            ? 'modal'
                                            : ''
                                    "
                                    :data-target="
                                        this.data.seatMap[rowIndex][colIndex]
                                            .reserved
                                            ? '#addSeatNumber'
                                            : ''
                                    "
                                    v-if="col.reserved"
                                    class="position-relative"
                                    :class="col.selected ? 'selected' : ''"
                                    :style="
                                        col.class ? checkClass(col.class) : ''
                                    "
                                    @click="selectSeat(rowIndex, colIndex)"
                                    :src="
                                        $store.state.api_url +
                                        'assets/img/buses/booked_seat_img.gif'
                                    "
                                    alt=""
                                />
                                <span v-else></span>
                            </td>
                        </tr>
                    </div>
                    <div class="col-md-2">
                        <div
                            class="my-2"
                            v-for="(seatClass, i) in allSeatClasses"
                            :key="i"
                        >
                            <div
                                class="circles mr-1 border shadow"
                                :style="{
                                    border:
                                        '2px solid ' +
                                        seatClass.color +
                                        ' !important',
                                }"
                            ></div>
                            <span class="text-wrap">{{ seatClass.name }}</span>
                        </div>
                        <button
                            class="btn btn-primary"
                            :data-toggle="checkAllSeatAssign ? 'modal' : ''"
                            :data-target="
                                checkAllSeatAssign ? '#setSeatClass' : ''
                            "
                            @click="resetAttributes()"
                        >
                            Set Attributes
                        </button>
                    </div>
                </div>
                <template v-slot:button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="addBusClass"
                        :disabled="loading"
                    >
                        {{ loading ? "Loading..." : "Add Bus Class" }}
                    </button>
                </template>
            </Add>

            <div
                class="modal fade"
                id="addSeatNumber"
                tabindex="-1"
                aria-labelledby="addSeatNumberLabel"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addSeatNumberLabel">
                                Add Seat Number
                            </h5>
                            <button
                                type="button"
                                class="close"
                                data-dismiss="modal"
                                aria-label="Close"
                                @click="closeSeat()"
                            >
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label
                                    >Seat Number
                                    <span class="text-danger ml-1"
                                        >*</span
                                    ></label
                                >
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="setSeatNumber.addSeatNO"
                                />
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-primary"
                                @click="
                                    assignSeatNumber(
                                        setSeatNumber.rowId,
                                        setSeatNumber.colId
                                    )
                                "
                            >
                                Assign Seat Number
                            </button>
                            <button
                                type="button"
                                class="btn btn-secondary"
                                data-dismiss="modal"
                                @click="closeSeat()"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Modal End -->
            <!--                    Modal for modify bus class-->
            <div
                class="modal fade"
                id="setSeatClass"
                tabindex="-1"
                aria-labelledby="staticBackdropLabel"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="card card-primary">
                                <div
                                    class="card-header d-flex justify-content-between"
                                >
                                    <h4 class="modal-title">Seat Detail</h4>
                                    <button
                                        type="button"
                                        class="close"
                                        data-dismiss="modal"
                                        aria-label="Close"
                                        @click="closeDetail()"
                                    >
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label for="seat_class"
                                                >Seat Class</label
                                            >
                                            <div
                                                class="float-right badge badge-primary mx-0 mb-1"
                                                style="cursor: pointer"
                                                data-toggle="modal"
                                                data-target="#addFareClass"
                                                @click="clearFareClassForm()"
                                            >
                                                Add Fare Class
                                            </div>
                                            <select
                                                class="form-control"
                                                v-model="seatModify.class"
                                            >
                                                <option value="0" selected>
                                                    Select Class
                                                </option>
                                                <option
                                                    v-for="(
                                                        fareClass, i
                                                    ) in fareClasses"
                                                    :key="i"
                                                    :value="fareClass.id"
                                                >
                                                    {{ fareClass.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="seat_type"
                                                >Seat Type</label
                                            >
                                            <select
                                                class="form-control"
                                                v-model="seatModify.type"
                                            >
                                                <option value="0" selected>
                                                    Select Type
                                                </option>
                                                <!--                                                <option value="reserved_for_female">-->
                                                <!--                                                    Reserved for Female-->
                                                <!--                                                </option>-->
                                                <option value="not_for_sale">
                                                    Not for Sale
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <button
                                                type="button"
                                                class="btn btn-block btn-primary"
                                                @click="addSeatData()"
                                                data-dismiss="modal"
                                                :disabled="loading"
                                            >
                                                {{
                                                    loading
                                                        ? "Loading..."
                                                        : "Add Seat Detail"
                                                }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--End Modal-->

            <div
                class="modal fade"
                id="addFareClass"
                tabindex="-1"
                aria-labelledby="exampleModalLabel"
                aria-hidden="true"
            >
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                Add Fare Class
                            </h5>
                            <button
                                type="button"
                                class="close"
                                data-dismiss="modal"
                                aria-label="Close"
                                @click="closeFare()"
                            >
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="name"
                                        >Name<span class="text-danger ml-1"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        type="text"
                                        class="form-control"
                                        placeholder="Enter Fare Class Name"
                                        id="name"
                                        v-model="addData.FareClassName"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-primary"
                                @click="saveFareClass()"
                                :disabled="loading"
                            >
                                {{ loading ? "Loading..." : "Save Fare Class" }}
                            </button>
                            <button
                                type="button"
                                class="btn btn-secondary"
                                data-dismiss="modal"
                                @click="closeFare()"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!--            Edit Model-->
            <Edit
                heading="Edit Bus Class"
                :errors="this.validationErrors"
                :success="success"
                :editForm="editFormID"
            >
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="SurchargeName"
                            >Name <span class="text-danger ml-1">*</span></label
                        >
                        <input
                            type="text"
                            class="form-control"
                            v-model="dataEdit.name"
                        />
                    </div>
                    <div class="form-group col-md-6">
                        <label for="color"
                            >Color<span class="text-danger ml-1">*</span></label
                        >
                        <input
                            type="color"
                            class="form-control"
                            id="color"
                            v-model="dataEdit.busClassColor"
                        />
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-2">
                        <h5>Status</h5>
                        <div class="form-group d-flex align-items-center">
                            <label class="mt-4" for="active">Is Active</label>
                            <label class="colorinput mx-3 mt-3">
                                <span>
                                    <input
                                        type="checkbox"
                                        class="colorinput-input"
                                        id="editCheckBox"
                                        @change="editCheckBox($event)"
                                        v-bind:checked="dataEdit.is_active == 1"
                                    />
                                    <span
                                        class="colorinput-color bg-primary"
                                    ></span>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-md-2">
                        <h5>Front Icons</h5>
                        <div class="form-group d-flex align-items-center">
                            <label class="mt-4" for="active">Show</label>
                            <label class="colorinput mx-3 mt-3">
                                <span>
                                    <input
                                        type="checkbox"
                                        class="colorinput-input"
                                        id="editCheckBox"
                                        @change="editCheckBoxFont($event)"
                                        v-bind:checked="dataEdit.front_icons == 1"
                                    />
                                    <span
                                        class="colorinput-color bg-primary"
                                    ></span>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="name"
                            >No. of Rows
                            <span class="text-danger ml-1">*</span></label
                        >
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter No. of Rows"
                            v-model="dataEdit.no_of_rows"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-2">
                        <label for="name"
                            >No. of Cols
                            <span class="text-danger ml-1">*</span></label
                        >
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Enter No. of Cols"
                            v-model="dataEdit.no_of_cols"
                            @keypress="isNumber($event)"
                        />
                    </div>
                    <div class="form-group col-md-3 my-4 pt-2">
                        <button
                            type="button"
                            class="btn btn-block btn-primary"
                            @click="editGenerateMap"
                        >
                            Generate Seat Map
                        </button>
                    </div>
                </div>
                <div class="row mx-1 mainRow">
                    <!--v-if="isShowEditDiv-->
                    <div class="form-group col-md-5 border py-3">
                        <tr
                            class="seat-img p-0 m-0"
                            v-for="(record, rowIndex) in dataEdit.seat_map"
                            :key="rowIndex"
                        >
                            <td
                                v-for="(col, colIndex) in record"
                                :key="colIndex"
                                :class="col.reserved ? 'selected border' : ''"
                            >
                                <img
                                    @click="
                                        changeEditStatus(rowIndex, colIndex)
                                    "
                                    :src="
                                        $store.state.api_url +
                                        'assets/img/buses/available_seat_img.gif'
                                    "
                                    alt=""
                                />
                            </td>
                        </tr>
                    </div>
                    <div
                        class="form-group col-md-5 border py-3"
                        style="border-spacing: 5px"
                    >
                        <tr
                            class="seat-img p-0 m-0"
                            v-for="(record, rowIndex) in dataEdit.seat_map"
                            :key="rowIndex"
                        >
                            <td
                                v-for="(col, colIndex) in record"
                                :key="colIndex"
                            >
                                <small
                                    class="font-weight-bold position-absolute text-dark"
                                    style="font-size: 10px !important"
                                    v-if="col.reserved"
                                    >{{ col.seatNo ?? "N/A" }}</small
                                >
                                <img
                                    data-toggle="modal"
                                    data-target="#setEditSeatClass"
                                    @click="getSeatDetails(rowIndex, colIndex)"
                                    :style="
                                        col.class ? checkClass(col.class) : ''
                                    "
                                    v-if="col.reserved"
                                    :src="
                                        $store.state.api_url +
                                        'assets/img/buses/booked_seat_img.gif'
                                    "
                                    alt=""
                                />
                                <span v-else></span>
                            </td>
                        </tr>
                    </div>
                    <div class="col-md-2">
                        <div
                            class="my-2"
                            v-for="(seatClass, i) in allSeatClasses"
                            :key="i"
                        >
                            <div
                                class="circles mr-1 border shadow"
                                :style="{
                                    border:
                                        '2px solid ' +
                                        seatClass.color +
                                        ' !important',
                                }"
                            ></div>
                            <span class="text-wrap">{{ seatClass.name }}</span>
                        </div>
                        <!-- <button class="btn btn-primary" data-toggle="modal" data-target="#setSeatClass"
                                @click="resetAttributes()">Set Attributes
                        </button> -->
                    </div>
                </div>
                <template v-slot:button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="updateBusClass"
                        :disabled="loading"
                    >
                        {{ loading ? "Loading..." : "Update Bus Class" }}
                    </button>
                </template>
            </Edit>

            <!--                    Modal for modify bus class-->
            <div
                class="modal fade"
                id="setEditSeatClass"
                tabindex="-1"
                aria-labelledby="staticBackdropLabel"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="card card-primary">
                                <div
                                    class="card-header d-flex justify-content-between"
                                >
                                    <h4 class="modal-title">
                                        Edit Seat Detail
                                    </h4>
                                    <button
                                        type="button"
                                        class="close"
                                        data-dismiss="modal"
                                        aria-label="Close"
                                        @click="closeEditSeat()"
                                    >
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="seat_class"
                                                >Seat Class</label
                                            >
                                            <select
                                                class="form-control"
                                                v-model="editSeatModify.class"
                                            >
                                                <option value="0" selected>
                                                    Select Class
                                                </option>
                                                <option
                                                    v-for="(
                                                        fareClass, i
                                                    ) in fareClasses"
                                                    :key="i"
                                                    :value="fareClass.id"
                                                >
                                                    {{ fareClass.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="seat_type"
                                                >Seat Type</label
                                            >
                                            <select
                                                class="form-control"
                                                v-model="editSeatModify.type"
                                            >
                                                <option value="0" selected>
                                                    Select Type
                                                </option>
                                                <!--                                                <option value="reserved_for_female">-->
                                                <!--                                                    Reserved for Female-->
                                                <!--                                                </option>-->
                                                <option value="not_for_sale">
                                                    Not for Sale
                                                </option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="seat_type"
                                                >Seat Type</label
                                            >
                                            <input
                                                type="text"
                                                class="form-control"
                                                v-model="editSeatModify.seatNo"
                                            />
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <button
                                                type="button"
                                                class="btn btn-block btn-primary"
                                                @click="
                                                    updateSeatDetail(
                                                        editSingleSeat.rowId,
                                                        editSingleSeat.colId
                                                    )
                                                "
                                                data-dismiss="modal"
                                            >
                                                Update Seat Data
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--End Modal-->

            <Hide
                :hideForm="hideFormID"
                confirmationMessage="Are You Sure You want To Delete This City ???"
            >
                <template v-slot:button>
                    <button
                        type="button"
                        class="btn btn-danger btn-block"
                        :disabled="loading"
                        @click="hideBusClass"
                    >
                        {{ loading ? "Loading..." : "Yes, I want to Delete" }}
                    </button>
                </template>
            </Hide>
        </div>
    </section>
</template>
<script>
import Add from "../../components/Add.vue";
import Edit from "../../components/Edit.vue";
import Hide from "../../components/Hide.vue";
import { mapGetters } from "vuex";

export default {
    name: "BusClassPage",
    components: {
        Add,
        Edit,
        Hide,
    },
    data() {
        return {
            busClasses: [],
            fareClasses: [],
            permissions: [],
            formID: "busClass_form",
            editFormID: "edit_busClass_form",
            hideFormID: "delete_busClass_form",
            validationErrors: [],
            seatModify: {
                class: 0,
                type: 0,
            },
            editSeatModify: {
                class: 0,
                type: 0,
                seatNo: "",
            },
            success: false,
            checkAllSeatAssign: false,
            addSeatNO: "",
            loading: false,
            error: false,
            isShowDiv: false,
            isShowEditDiv: false,
            cloneDone: false,
            BusClassName: "",
            updateSeatValue: [],
            editSingleSeat: [],
            allSeatClasses: [],
            setSeatNumber: [],
            uniqueSeatNumber: [],
            totalSeat: 0,
            delId: "",
            addData: {},
            data: {
                noOfRows: "",
                noOfCols: "",
                seatMap: [],
                isActive: 1,
                BusClassName: "",
                BusClassColor: "#000000",
            },
            dataEdit: {
                BusClassName: "",
                noOfRows: "",
                no_of_cols: "",
                seatMap: [],
            },
            selectedSeats: [],
        };
    },
    async created() {
        $(".modal").remove();
        await this.fetchBussClasses();

        this.permissions = this.$store.state.permissions;
        const currentRouteName = this.$route.name;
        if (currentRouteName == "booking-page") {
            window.addEventListener("keydown", this.enterKey);
            window.addEventListener("keydown", this.altM);
        } else {
            window.removeEventListener("keydown", this.enterKey);
            window.removeEventListener("keydown", this.altM);
        }
    },

    methods: {
        closeSeat() {
            $("#addSeatNumber").click();
        },
        closeDetail() {
            $("#setSeatClass").click();
        },
        closeFare() {
            $("#addFareClass").click();
        },
        closeEditSeat() {
            $("#setEditSeatClass").click();
        },
        resetAttributes: function () {
            let b = 0;
            this.data.seatMap.map((seat) => {
                for (let i = seat.length - 1; i >= 0; i--) {
                    if (
                        seat[i].seatNo == undefined &&
                        seat[i].reserved == true
                    ) {
                        b = 1;
                    }
                }
            });

            if (b == 1) {
                this.checkAllSeatAssign = false;
                return swal({
                    title: "Required !",
                    text: "Please assign all seat number first",
                    icon: "error",
                    timer: 2000,
                });
            }

            this.checkAllSeatAssign = true;

            this.seatModify.class = 0;
            this.seatModify.type = 0;
        },
        clearForm: function () {
            this.data = {};
            this.isShowDiv = false;
        },
        clearFareClassForm: function () {
            this.addData = {};
        },
        async fetchBussClasses() {
            const resBusClass = await this.callApi("post", "bus_classes");
            const resClass = await this.callApi(
                "post",
                "bus_classes/fare-class"
            );
            if (resBusClass.status == 200 && resClass.status == 200) {
                this.busClasses = resBusClass.data;
                this.allSeatClasses = resClass.data;
            } else {
                console.log(resBusClass);
            }
            const resFareClass = await this.callApi(
                "post",
                "bus_classes/fare-class"
            );
            if (resFareClass.status == 200) {
                this.fareClasses = resFareClass.data;
            } else {
                console.log(res);
            }
            setTimeout(function () {
                $("#bus_class_table").DataTable();
            }, 300);
        },
        isNumber: function (evt) {
            evt = evt ? evt : window.event;
            var charCode = evt.which ? evt.which : evt.keyCode;
            if (
                charCode > 31 &&
                (charCode < 48 || charCode > 57) &&
                charCode !== 46
            ) {
                evt.preventDefault();
            } else {
                return true;
            }
        },
        isAlphabet: function (evet) {
            if (!/[a-zA-Z\s]/.test(event.key)) {
                this.ignoredValue = event.key ? event.key : "";
                event.preventDefault();
            }
        },
        async saveFareClass() {
            this.loading = true;
            const resSaveFareClass = await this.callApi(
                "post",
                "bus_classes/storeFareClass",
                this.addData
            );
            if (resSaveFareClass.status == 201) {
                this.closeFare();
                swal({
                    title: "Success",
                    text: "Fare Class Added Successfully",
                    icon: "success",
                    timer: 2000,
                });
                this.loading = false;
                return await this.fetchBussClasses();
            } else {
                console.log(resSaveFareClass);
            }
        },
        addSeatData: function () {
            if (this.seatModify.class != 0 || this.seatModify.type != 0) {
                this.selectedSeats.map((seat) => {
                    seat = JSON.parse(seat);
                    let row = seat[0];
                    let col = seat[1];
                    this.data.seatMap[row][col].class =
                        this.seatModify.class ?? 0;
                    this.data.seatMap[row][col].type =
                        this.seatModify.type ?? 0;
                    delete this.data.seatMap[row][col].selected;
                });
                this.selectedSeats = [];
                this.closeDetail();
                return swal({
                    title: "Success",
                    text: "Seats Modified Successfully !!!!",
                    icon: "success",
                    timer: 2000,
                });
            } else {
                return swal({
                    title: "Required",
                    text: "Please Select Any Field For Seat Modification !!!!",
                    icon: "error",
                    timer: 2000,
                });
            }
        },
        assignSeatNumber: function (rowId, colId) {
            if (
                this.setSeatNumber.addSeatNO == "" ||
                typeof this.setSeatNumber.addSeatNO == "undefined"
            ) {
                return swal({
                    title: "Required !",
                    text: "Please Enter Seat Number",
                    icon: "error",
                    timer: 2000,
                });
            }
            // Check seat number should be unique
            if (
                this.uniqueSeatNumber.includes(
                    this.setSeatNumber.addSeatNO.toLowerCase()
                )
            ) {
                return swal({
                    title: "Required !",
                    text: "This seat number already taken",
                    icon: "error",
                    timer: 2000,
                });
            }

            this.uniqueSeatNumber.push(this.setSeatNumber.addSeatNO);
            this.data.seatMap[rowId][colId].seatNo =
                this.setSeatNumber.addSeatNO;
            this.closeSeat();
            swal({
                title: "Success",
                text: "Successfully Added Seat Number",
                icon: "success",
                timer: 2000,
            });
            this.setSeatNumber.addSeatNO = "";
        },
        selectSeat(row, col) {
            let index = this.selectedSeats.indexOf(JSON.stringify([row, col]));
            if (index != -1) {
                this.data.seatMap[row][col].selected = false;
                this.selectedSeats.splice(index, 1);
            } else {
                this.data.seatMap[row][col].selected = true;
                this.selectedSeats.push(JSON.stringify([row, col]));
            }
            this.setSeatNumber = {
                rowId: row,
                colId: col,
            };
        },
        checkClass(colorCode) {
            for (let i = 0; i < this.allSeatClasses.length; i++) {
                if (colorCode == this.allSeatClasses[i].id) {
                    return "border: 3px solid " + this.allSeatClasses[i].color;
                }
            }
        },
        getSeatDetails: function (rowId, colId) {
            this.editSeatModify = {
                class: this.dataEdit.seat_map[rowId][colId].class ?? 0,
                type: this.dataEdit.seat_map[rowId][colId].type ?? 0,
                seatNo: this.dataEdit.seat_map[rowId][colId].seatNo ?? "",
            };
            this.editSingleSeat = {
                rowId: rowId,
                colId: colId,
            };
        },

        updateSeatDetail: function (rowId, colId) {
            if (this.editSeatModify.class == 0) {
                return swal({
                    title: "required",
                    text: "Please Select Seat Class",
                    icon: "error",
                    timer: 2000,
                });
            }

            if (this.editSeatModify.seatNo == "") {
                return swal({
                    title: "required",
                    text: "Please Enter Seat No",
                    icon: "error",
                    timer: 2000,
                });
            }

            const singleSeatDetails = this.dataEdit.seat_map[rowId][colId];
            this.dataEdit.seat_map[rowId][colId] = {
                reserved: singleSeatDetails.reserved,
                seatNo: this.editSeatModify.seatNo,
                class: this.editSeatModify.class,
                type:
                    this.editSeatModify.type == "0"
                        ? parseInt(this.editSeatModify.type)
                        : this.editSeatModify.type,
            };

            // let seatNo = 0;
            // this.dataEdit.seat_map = this.dataEdit.seat_map.map((seat) => {
            //     for (let i = seat.length - 1; i >= 0; i--) {
            //         if (seat[i].reserved) {
            //             seat[i]["seatNo"] = ++seatNo;
            //         }
            //     }
            //     return seat;
            // });

            const seatNumbersSet = [];
            for (let i = 0; i < this.dataEdit.seat_map.length; i++) {
                const col = this.dataEdit.seat_map[i];
                for (let j = 0; j < col.length; j++) {
                    const obj = col[j];
                    const seatNo = obj.seatNo;
                    if (seatNo !== undefined) {
                        seatNo = seatNo.toString();
                        if (seatNumbersSet.includes(seatNo)) {
                            console.log(
                                `Duplicate seat number found: ${seatNo}`
                            );
                            return swal({
                                title: "required",
                                text: `Duplicate Seat Number Allowed : ${seatNo}`,
                                icon: "error",
                                timer: 2000,
                            });
                        } else {
                            seatNumbersSet.push(seatNo);
                        }
                    }
                }
            }

            this.closeEditSeat();
            swal({
                title: "Success!",
                text:
                    "Seat Class Update Successfully to Seat Number " +
                    singleSeatDetails.seatNo,
                icon: "success",
                timer: 2000,
            });
        },

        async hideBusClass() {
            this.loading = true;
            const resHide = await this.callApi("post", "bus_classes/hide", {
                id: this.delId,
            });
            if (resHide.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Bus Class Deleted Successfully",
                    icon: "success",
                    timer: 2000,
                });
                this.loading = false;
                $("#bus_class_table").DataTable().destroy();
                await this.fetchBussClasses();
            } else {
                if (resHide.status == 422) {
                    this.loading = false;
                    for (const key in resHide.data.errors) {
                        resHide.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
                setTimeout(() => {
                    this.loading = false;
                }, 3000);
            }
        },

        changeStatus: function (row, col) {
            if (this.data.seatMap[row][col].reserved) {
                this.data.seatMap[row][col] = {
                    reserved: false,
                };
                this.totalSeat = this.totalSeat - 1;
            } else {
                this.data.seatMap[row][col] = {
                    reserved: true,
                };
                this.totalSeat = this.totalSeat + 1;
            }
        },
        changeEditStatus: function (row, col) {
            if (this.dataEdit.seat_map[row][col].reserved) {
                this.dataEdit.seat_map[row][col] = {
                    reserved: false,
                };
            } else {
                this.dataEdit.seat_map[row][col] = {
                    reserved: true,
                };
            }
        },
        addFormGenerateMap: function () {
            this.uniqueSeatNumber = [];
            this.validationErrors = [];
            let vm = this;
            if (typeof vm.data.noOfRows == "undefined")
                return swal({
                    title: "required",
                    text: "No of Rows Field is Required",
                    icon: "error",
                    timer: 2000,
                });
            if (typeof vm.data.noOfCols == "undefined")
                return swal({
                    title: "required",
                    text: "No of Cols is Required",
                    icon: "error",
                    timer: 2000,
                });
            if (vm.data.noOfRows <= 17) {
                if (vm.data.noOfCols <= 9) {
                    let arr,
                        count = 0;
                    var map = new Array(parseInt(vm.data.noOfRows)); // creating rows
                    for (var i = 0; i < map.length; i++) {
                        map[i] = new Array(vm.data.noOfCols); // creating columns
                    }

                    for (var i = 0; i < vm.data.noOfRows; i++) {
                        for (var j = 0; j < vm.data.noOfCols; j++) {
                            count++;
                            map[i][j] = {
                                reserved: false,
                            };
                        }
                    }
                    this.isShowDiv = true;
                    return (this.data.seatMap = map);
                } else {
                    return swal({
                        title: "Limited",
                        text: "No of Cols must be less then or equal to 9",
                        icon: "error",
                        timer: 2000,
                    });
                }
            } else {
                return swal({
                    title: "Limited",
                    text: "No of Rows must be less then or equal to 17",
                    icon: "error",
                    timer: 2000,
                });
            }
        },
        editGenerateMap: function () {
            this.isShowEditDiv = true;
        },

        checkBox: function (e) {
            if (e.target.checked) {
                this.data.isActive = 1;
            } else {
                this.data.isActive = 0;
            }
        },
        editCheckBox: function (e) {
            if (e.target.checked) {
                this.dataEdit.is_active = 1;
            } else {
                this.dataEdit.is_active = 0;
            }
        },
        editCheckBoxFont: function (e) {
            if (e.target.checked) {
                this.dataEdit.front_icons = 1;
            } else {
                this.dataEdit.front_icons = 0;
            }
        },
        async addBusClass() {
            // console.log(this.data.seatMap);
            this.validationErrors = [];

            // validation for assign all class
            let b = 0;
            let c = 0;
            this.data.seatMap.map((seat) => {
                for (let i = seat.length - 1; i >= 0; i--) {
                    if (seat[i].reserved == true) {
                        c = 1;
                    }
                    if (
                        (seat[i].class == undefined ||
                            seat[i].class == "0" ||
                            seat[i].class == 0) &&
                        seat[i].reserved == true
                    ) {
                        b = 1;
                    }
                }
            });
            // console.log(this.data);

            if (c == 0) {
                return swal({
                    title: "Required !",
                    text: "Please Select Seats",
                    icon: "error",
                    timer: 2000,
                });
            }
            if (b == 1) {
                return swal({
                    title: "Required !",
                    text: "Please assign seat class first",
                    icon: "error",
                    timer: 2000,
                });
            }

            if (this.data.BusClassName === "")
                // swal('Required', 'Bus Class Name is Required', 'error')
                return swal({
                    title: "Required",
                    text: "Bus Class Name is Required",
                    icon: "error",
                    timer: 2000,
                });
            if (
                typeof this.data.noOfRows == "undefined" ||
                this.data.noOfRows == ""
            )
                return swal({
                    title: "Required ",
                    text: "Row Field is Required",
                    icon: "error",
                    timer: 2000,
                });
            if (
                typeof this.data.noOfCols == "undefined" ||
                this.data.noOfCols == ""
            )
                return swal({
                    title: "Required",
                    text: "Col Field is required",
                    icon: "error",
                    timer: 2000,
                });
            this.loading = true;
            const res = await this.callApi(
                "post",
                "bus_classes/store",
                this.data
            );
            if (res.status == 201) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Bus Class Added Successfully",
                    icon: "success",
                    timer: 2000,
                });
                $("#bus_class_table").DataTable().destroy();
                this.loading = false;
                await this.fetchBussClasses();
                this.data = {
                    busClassColor: "#000000",
                };
                this.isShowDiv = false;
                window.scrollTo(0, 0);
            } else {
                if (res.status == 422) {
                    this.loading = false;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            this.errorsArray(element, key);
                        });
                    }
                }
            }
        },

        async updateBusClass() {
            this.validationErrors = [];
            if (this.dataEdit.BusClassName === "")
                return swal({
                    title: "Required",
                    text: "Bus Class name is required",
                    icon: "error",
                    timer: 2000,
                });
            if (this.dataEdit.noOfRows === "0")
                return swal({
                    title: "Required",
                    text: "row Field is required",
                    icon: "error",
                    timer: 2000,
                });
            if (this.dataEdit.noOfCols === "0")
                return swal({
                    title: "Required",
                    text: "Col Field is Required",
                    icon: "error",
                    timer: 2000,
                });
            // let seatNo = 0;
            // this.dataEdit.seat_map = this.dataEdit.seat_map.map((seat) => {
            //     for (let i = seat.length - 1; i >= 0; i--) {
            //         if (seat[i].reserved) {
            //             seat[i]["seatNo"] = ++seatNo;
            //         }
            //     }
            //     return seat;
            // });

            this.loading = true;
            const res = await this.callApi(
                "post",
                "bus_classes/update",
                this.dataEdit
            );
            if (res.status == 200) {
                $(".modal").click();
                swal({
                    title: "Success",
                    text: "Bus Class Updated Successfully",
                    icon: "success",
                    timer: 2000,
                });
                $("#bus_class_table").DataTable().destroy();
                this.loading = false;
                await this.fetchBussClasses();
            } else {
                if (res.status == 422) {
                    this.dropScheduleButton = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            errorContent += ++count + " - " + element + "\n";
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                            timer: 2000,
                        });
                    }
                }
            }
        },
        async deleteModal(fare_class, i) {
            const deletingObj = {
                url: "bus_classes/delete",
                data: fare_class,
                index: i,
            };
            this.$store.commit("setDeleteObj", deletingObj);
        },
        edit(bus_class) {
            this.dataEdit = { ...bus_class, busClassColor: bus_class.color };
        },
        async duplicate(id, index) {
            this.cloneDone = true;
            const res = await this.callApi("post", "bus_classes/duplicate", {
                id: id,
            });
            if (res.status == 201) {
                swal({
                    title: "Success",
                    text: "Row # " + index + " Duplicated Successfully",
                    icon: "success",
                    timer: 2000,
                });
                this.cloneDone = false;
                $("#bus_class_table").DataTable().destroy();
                this.fetchBussClasses();
                this.data = {
                    busClassColor: "#000000",
                };
            } else {
                if (res.status == 422) {
                    this.cloneDone = false;
                    let errorContent = "";
                    let count = 0;
                    for (const key in res.data.errors) {
                        res.data.errors[key].forEach((element) => {
                            errorContent +=
                                ++count +
                                " - " + //creating serial no.
                                element + // main error
                                "\n"; // creating new line
                        });
                        swal({
                            title: "Error",
                            text: errorContent,
                            icon: "error",
                            timer: 2000,
                        });
                    }
                }
            }
        },
    },

    computed: {
        ...mapGetters(["getDeletingObj"]),
    },
    watch: {
        getDeletingObj(obj) {
            if (obj.isDeleted) {
                this.busClasses.splice(obj.index, 1);
                $("#bus_class_table").DataTable().destroy();
                this.fetchBussClasses();
            }
        },
    },
};
</script>
<style scoped>
.selected {
    background-color: rgba(127, 164, 31, 0.64) !important;
}

.seat-img img,
.seat-img span {
    height: 40px;
    width: 40px;
    display: inline-block;
    cursor: pointer;
}

.circles {
    width: 30px;
    height: 30px;
    -moz-border-radius: 25px;
    -webkit-border-radius: 25px;
    border-radius: 50px;
    display: inline-block;
    box-sizing: content-box;
}

[tooltip] {
    position: relative; /* opinion 1 */
}

/* Applies to all tooltips */
[tooltip]::before,
[tooltip]::after {
    text-transform: none; /* opinion 2 */
    font-size: 0.9em; /* opinion 3 */
    line-height: 1;
    user-select: none;
    pointer-events: none;
    position: absolute;
    display: none;
    opacity: 0;
}

[tooltip]::before {
    content: "";
    border: 5px solid transparent; /* opinion 4 */
    z-index: 1001; /* absurdity 1 */
}

[tooltip]::after {
    content: attr(tooltip); /* magic! */

    /* most of the rest of this is opinion */
    font-family: Helvetica, sans-serif;
    text-align: center;

    /*
      Let the content set the size of the tooltips
      but this will also keep them from being obnoxious
      */
    min-width: 3em;
    max-width: 21em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding: 1ch 1.5ch;
    border-radius: 0.3ch;
    box-shadow: 0 1em 2em -0.5em rgba(0, 0, 0, 0.35);
    background: #333;
    color: #fff;
    z-index: 1000; /* absurdity 2 */
}

/* Make the tooltips respond to hover */
[tooltip]:hover::before,
[tooltip]:hover::after {
    display: block;
}

/* don't show empty tooltips */
[tooltip=""]::before,
[tooltip=""]::after {
    display: none !important;
}

/* FLOW: UP */
[tooltip]:not([flow])::before,
[tooltip][flow^="up"]::before {
    bottom: 100%;
    border-bottom-width: 0;
    border-top-color: #333;
}

[tooltip]:not([flow])::after,
[tooltip][flow^="up"]::after {
    bottom: calc(100% + 5px);
}

[tooltip]:not([flow])::before,
[tooltip]:not([flow])::after,
[tooltip][flow^="up"]::before,
[tooltip][flow^="up"]::after {
    left: 50%;
    transform: translate(-50%, -0.5em);
}

/* FLOW: DOWN */

/* KEYFRAMES */
@keyframes tooltips-vert {
    to {
        opacity: 0.9;
        transform: translate(-50%, 0);
    }
}

@keyframes tooltips-horz {
    to {
        opacity: 0.9;
        transform: translate(0, -50%);
    }
}

/* FX All The Things */
[tooltip]:not([flow]):hover::before,
[tooltip]:not([flow]):hover::after,
[tooltip][flow^="up"]:hover::before,
[tooltip][flow^="up"]:hover::after {
    animation: tooltips-vert 300ms ease-out forwards;
}

/* UNRELATED to tooltips */

#mytolltip {
    flex: 1 1 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

#mytolltip div {
    text-align: center;
    color: #353539;
}

#mytolltip span {
    padding: 0.5em 1em;
    margin: 0.5em;
    display: inline-block;
    background: #dedede;
}
</style>
