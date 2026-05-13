@php($visibleColumnsLookup = array_fill_keys($visibleColumns ?? [], true))

<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 10px;
        }

        body {
            margin: 15px;
            font-size: 9px;
            font-family: Verdana, Arial, sans-serif;
        }

        .companyName {
            font-weight: 700;
            font-size: 18px;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 5px;
        }

        .reportTitle {
            font-weight: 700;
            font-size: 14px;
            text-align: center;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        th {
            background: #f2f2f2;
            font-size: 9px;
            padding: 6px 4px;
            border: 1px solid #000;
            text-align: center;
        }

        td {
            font-size: 8px;
            padding: 5px 4px;
            border: 1px solid #000;
            text-align: center;
            word-wrap: break-word;
        }

        .wrapper {
            border: 2px solid #000;
            padding: 10px;
        }

        .badge-reschedule {
            color: #0d6efd;
            font-weight: bold;
        }

        .badge-booked {
            color: green;
            font-weight: bold;
        }

        .badge-canceled {
            color: red;
            font-weight: bold;
        }

        .badge-advance {
            color: orange;
            font-weight: bold;
        }
    </style>

    <title>Reschedule Report</title>
</head>

<body>

<div class="wrapper">

    <div class="companyName">
        Kainat Travels
    </div>

    <div class="reportTitle">
        Reschedule Report
    </div>

    <table>
        <thead>
        <tr>

            @if(!empty($visibleColumnsLookup['terminal_name']))
                <th>Terminal</th>
            @endif

            @if(!empty($visibleColumnsLookup['passenger_name']))
                <th>Passenger</th>
            @endif

            @if(!empty($visibleColumnsLookup['passenger_contact']))
                <th>Contact</th>
            @endif

            @if(!empty($visibleColumnsLookup['passenger_cnic']))
                <th>CNIC</th>
            @endif

            @if(!empty($visibleColumnsLookup['status']))
                <th>Status</th>
            @endif

            @if(!empty($visibleColumnsLookup['current_status']))
                <th>Current Status</th>
            @endif

            @if(!empty($visibleColumnsLookup['from_bus_time']))
                <th>From Bus Time</th>
            @endif

            @if(!empty($visibleColumnsLookup['to_bus_time']))
                <th>To Bus Time</th>
            @endif

            @if(!empty($visibleColumnsLookup['reschedule_from']))
                <th>Reschedule From</th>
            @endif

            @if(!empty($visibleColumnsLookup['reschedule_to']))
                <th>Reschedule To</th>
            @endif

            @if(!empty($visibleColumnsLookup['from_seat']))
                <th>From Seat</th>
            @endif

            @if(!empty($visibleColumnsLookup['to_seat']))
                <th>To Seat</th>
            @endif

            @if(!empty($visibleColumnsLookup['old_fare']))
                <th>Old Fare</th>
            @endif

            @if(!empty($visibleColumnsLookup['new_fare']))
                <th>New Fare</th>
            @endif

            @if(!empty($visibleColumnsLookup['remarks']))
                <th>Remarks</th>
            @endif

            @if(!empty($visibleColumnsLookup['reschedule_by']))
                <th>Reschedule By</th>
            @endif

            @if(!empty($visibleColumnsLookup['reschedule_time']))
                <th>Reschedule Time</th>
            @endif

        </tr>
        </thead>

        <tbody>

        @forelse($tickets as $single)

            <tr>

                @if(!empty($visibleColumnsLookup['terminal_name']))
                    <td>
                        {{ $single->terminal_name ?: 'N/A' }}
                    </td>
                @endif

                @if(!empty($visibleColumnsLookup['passenger_name']))
                    <td>
                        {{ $single->passenger_name }}
                    </td>
                @endif

                @if(!empty($visibleColumnsLookup['passenger_contact']))
                    <td>
                        {{ $single->passenger_contact }}
                    </td>
                @endif

                @if(!empty($visibleColumnsLookup['passenger_cnic']))
                    <td>
                        {{ $single->passenger_cnic }}
                    </td>
                @endif

                @if(!empty($visibleColumnsLookup['status']))
                    <td>
                        <span class="
                            @if($single->type == 'reschedule')
                                badge-reschedule
                            @elseif($single->type == 'booked')
                                badge-booked
                            @elseif($single->type == 'canceled')
                                badge-canceled
                            @endif
                        ">
                            {{ ucfirst($single->type) }}
                        </span>
                    </td>
                @endif

                @if(!empty($visibleColumnsLookup['current_status']))
                    <td>
                        @if($single->new_type == 'advance-seat')
                            <span class="badge-advance">
                                Advance Booking
                            </span>
                        @else
                            {{ ucfirst($single->new_type) }}
                        @endif
                    </td>
                @endif

                @if(!empty($visibleColumnsLookup['from_bus_time']))
                    <td>
                        {{ $single->old_bus_time }}
                    </td>
                @endif

                @if(!empty($visibleColumnsLookup['to_bus_time']))
                    <td>
                        {{ $single->new_bus_time }}
                    </td>
                @endif

                @if(!empty($visibleColumnsLookup['reschedule_from']))
                    <td>
                        {{ $single->old_departure }} -
                        {{ $single->old_destination }}
                    </td>
                @endif

                @if(!empty($visibleColumnsLookup['reschedule_to']))
                    <td>
                        {{ $single->new_departure }} -
                        {{ $single->new_destination }}
                    </td>
                @endif

                @if(!empty($visibleColumnsLookup['from_seat']))
                    <td>
                        {{ $single->old_seat }}
                    </td>
                @endif

                @if(!empty($visibleColumnsLookup['to_seat']))
                    <td>
                        {{ $single->new_seat }}
                    </td>
                @endif

                @if(!empty($visibleColumnsLookup['old_fare']))
                    <td>
                        {{ number_format($single->old_fare) }}
                    </td>
                @endif

                @if(!empty($visibleColumnsLookup['new_fare']))
                    <td>
                        {{ number_format($single->new_fare) }}
                    </td>
                @endif

                @if(!empty($visibleColumnsLookup['remarks']))
                    <td>
                        {{ $single->reason }}
                    </td>
                @endif

                @if(!empty($visibleColumnsLookup['reschedule_by']))
                    <td>
                        {{ $single->reschedule_by }}
                    </td>
                @endif

                @if(!empty($visibleColumnsLookup['reschedule_time']))
                    <td>
                        {{ $single->reschedule_time }}
                    </td>
                @endif

            </tr>

        @empty

            <tr>
                <td colspan="20">
                    No Record Found
                </td>
            </tr>

        @endforelse

        </tbody>
    </table>

</div>

</body>
</html>
