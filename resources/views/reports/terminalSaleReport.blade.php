@php
    $visibleColumnsLookup = array_fill_keys($visibleColumns ?? [], true);

    $ticketCount = $tickets->count();
    $totalSale = 0;
    $totalRefund = 0;
    $totalCancellationCharges = 0;
    $totalCommission = 0;
    $totalNetCash = 0;
    $totalSeatCommission = 0;
    $countedTerminalCommissionGroups = [];
@endphp

<!DOCTYPE html>
<html>

<head>
    <style>
        .cancelled-tag { display: inline-block; margin-top: 3px; padding: 2px 5px; border: 1px solid #c62828; border-radius: 3px; color: #a51d2d; font-size: 7pt; font-weight: bold; }
        .reservation-tag { display: inline-block; margin-top: 3px; padding: 2px 5px; border: 1px solid #b7791f; border-radius: 3px; color: #805b10; font-size: 7pt; font-weight: bold; }
        .report-filter-summary { margin: 12px 0; border: 1px solid #aaa; }
        .report-filter-summary td { padding: 7px 10px; text-align: left; vertical-align: top; font-size: 9pt; }
        @page {
            transform: rotate(-90deg);
            padding: 0;
            margin: 10px;
        }

        body {
            margin: 40px 30px;
            font-size: 6pt;
            font-family: Verdana, Arial, sans-serif;
        }

        .companyName {
            font-weight: 800;
            font-size: 18pt;
            text-transform: uppercase;
            text-align: center;
        }

        table {
            font-size: 10pt;
            border-collapse: collapse;
            width: 100%;
        }
    </style>

    <title>Terminal Sales Report</title>
</head>

<body>
    <div style="border: 2px solid black; padding: 15px 3px 5px 3px;">
        <div class="companyName">Terminal Sales Report</div>
        <table class="report-filter-summary">
            <tr>
                <td colspan="2"><strong>Terminal:</strong> {{ $reportFilters['terminal'] ?? 'Not specified' }}</td>
            </tr>
            <tr>
                <td style="width: 50%;"><strong>From:</strong> {{ $reportFilters['from'] ?? 'Not specified' }}</td>
                <td style="width: 50%;"><strong>To:</strong> {{ $reportFilters['to'] ?? 'Not specified' }}</td>
            </tr>
            @if (!empty($reportFilters['status']))
                <tr><td colspan="2"><strong>Included entries:</strong> {{ $reportFilters['status'] }}</td></tr>
            @endif
        </table>

        <table border="2" style="text-align: center;">
            <thead>
                <tr>
                    @if (!empty($visibleColumnsLookup['bus_time']))
                        <th>Bus Time</th>
                    @endif
                    @if (!empty($visibleColumnsLookup['bus_number']))
                        <th>Bus No</th>
                    @endif
                    @if (!empty($visibleColumnsLookup['bus_class']))
                        <th>Bus Class</th>
                    @endif
                    @if (!empty($visibleColumnsLookup['route']))
                        <th>Route</th>
                    @endif
                    @if (!empty($visibleColumnsLookup['passenger_name']))
                        <th>Name</th>
                    @endif
                    @if (!empty($visibleColumnsLookup['passenger_cnic']))
                        <th>Cnic</th>
                    @endif
                    @if (!empty($visibleColumnsLookup['passenger_contact']))
                        <th>Contact</th>
                    @endif
                    @if (!empty($visibleColumnsLookup['seat_no']))
                        <th>Seat No</th>
                    @endif
                    @if (!empty($visibleColumnsLookup['invoice_id']))
                        <th>Invoice</th>
                    @endif
                    @if (!empty($visibleColumnsLookup['transaction_id']))
                        <th>Transaction id</th>
                    @endif
                    @if (!empty($visibleColumnsLookup['terminal_name']))
                        <th>Terminal</th>
                    @endif
                    @if (!empty($visibleColumnsLookup['status']))
                        <th>Status</th>
                    @endif
                    @if (!empty($visibleColumnsLookup['action_by']))
                        <th>Action By</th>
                    @endif
                    @if (!empty($visibleColumnsLookup['sale']))
                        <th>Sale</th>
                    @endif

                    @if (!empty($visibleColumnsLookup['refund']))
                        <th>Refund</th>
                    @endif
                    @if (!empty($visibleColumnsLookup['cancellation_charges']))
                        <th>Cancellation Charges</th>
                    @endif

                    @if (!empty($visibleColumnsLookup['terminal_commission']))
                        <th>Terminal Commission</th>
                    @endif

                    @if (!empty($visibleColumnsLookup['seat_commission']))
                        <th>Seat Commission</th>
                    @endif

                    @if (!empty($visibleColumnsLookup['net_cash']))
                        <th>Net Cash</th>
                    @endif
                </tr>
            </thead>

            <tbody>
                @foreach ($tickets as $single)
                    @php
                        $isUnpaidCancellation = \App\Support\TerminalSaleStatus::isUnpaidCancellation($single);
                        $cancellationLabel = \App\Support\TerminalSaleStatus::label($single);
                        $isPaidCancellation = $single->type === 'canceled' && !$isUnpaidCancellation;
                        $sale =
                            $single->type !== 'canceled' ? (float) $single->seat_fare - (float) $single->discount : 0;

                        $cancellationCharges =
                            $isPaidCancellation
                                ? ((float) $single->seat_fare - (float) $single->discount) *
                                    ((float) ($single->cancel_ticket->percentage ?? 0) / 100)
                                : 0;
                        $refund =
                            $isPaidCancellation
                                ? max((float) $single->seat_fare - (float) $single->discount - $cancellationCharges, 0)
                                : 0;

                        // ✅ FIXED COMMISSION LOGIC
                        $terminalCommission = $single->type !== 'canceled' ? (float) ($single->comsn ?? 0) : 0;

                        $seatCommission = $single->type !== 'canceled' ? (float) ($single->seat_commission ?? 0) : 0;

                        $netCash =
                            $single->type === 'canceled'
                                ? $cancellationCharges
                                : $sale - $seatCommission;

                        // totals
                        $totalSale += $sale;
                        $totalRefund += $refund;
                        $totalCancellationCharges = ($totalCancellationCharges ?? 0) + $cancellationCharges;
                        $terminalCommissionKey = implode('_', [
                            $single->terminal_id,
                            $single->bus_id,
                            $single->schedule_date,
                            $single->schedule_time_exact ?: $single->schedule_time,
                        ]);

                        if (
                            $single->type !== 'canceled' &&
                            !in_array($terminalCommissionKey, $countedTerminalCommissionGroups, true)
                        ) {
                            $totalCommission += $terminalCommission;
                            $countedTerminalCommissionGroups[] = $terminalCommissionKey;
                        }
                        $totalSeatCommission += $seatCommission;
                        $totalNetCash = $totalSale - $totalCommission - $totalSeatCommission + $totalCancellationCharges;
                    @endphp

                    <tr>
                        @if (!empty($visibleColumnsLookup['bus_time']))
                            <td>{{ $single->schedule_date }}<br>{{ $single->schedule_time }}</td>
                        @endif

                        @if (!empty($visibleColumnsLookup['bus_number']))
                            <td>{{ $single->bus->bus_number ?? 'N/A' }}</td>
                        @endif

                        @if (!empty($visibleColumnsLookup['bus_class']))
                            <td>{{ $single->busClass->name ?? '' }}</td>
                        @endif

                        @if (!empty($visibleColumnsLookup['route']))
                            <td>{{ $single->route->name ?? '' }} ({{ $single->route->via ?? '' }})</td>
                        @endif

                        @if (!empty($visibleColumnsLookup['passenger_name']))
                            <td>{{ $single->customer->name ?? '' }}</td>
                        @endif

                        @if (!empty($visibleColumnsLookup['passenger_cnic']))
                            <td>{{ $single->customer->cnic ?? '' }}</td>
                        @endif

                        @if (!empty($visibleColumnsLookup['passenger_contact']))
                            <td>{{ $single->customer->contact ?? '' }}</td>
                        @endif

                        @if (!empty($visibleColumnsLookup['seat_no']))
                            <td>{{ $single->seat_no }}</td>
                        @endif

                        @if (!empty($visibleColumnsLookup['invoice_id']))
                            <td>{{ $single->invoice_id }}
                                @if ($single->type === 'canceled')
                                    <br><span class="{{ $isUnpaidCancellation ? 'reservation-tag' : 'cancelled-tag' }}">{{ $cancellationLabel }}</span>
                                @endif
                            </td>
                        @endif

                        @if (!empty($visibleColumnsLookup['transaction_id']))
                            <td>{{ $single->transaction_id ?? 'N/A' }}</td>
                        @endif

                        @if (!empty($visibleColumnsLookup['terminal_name']))
                            <td>{{ $single->terminal->name ?? '' }}</td>
                        @endif

                        @if (!empty($visibleColumnsLookup['status']))
                            <td>
                                @if ($single->type === 'canceled')
                                    <span class="{{ $isUnpaidCancellation ? 'reservation-tag' : 'cancelled-tag' }}">{{ $cancellationLabel }}</span>
                                @else
                                    {{ $single->type }}
                                @endif
                            </td>
                        @endif

                        @if (!empty($visibleColumnsLookup['action_by']))
                            <td>{{ $single->updated_name->name ?? 'N/A' }}</td>
                        @endif

                        @if (!empty($visibleColumnsLookup['sale']))
                            <td>{{ $sale }}</td>
                        @endif

                        @if (!empty($visibleColumnsLookup['refund']))
                            <td>{{ $refund }}</td>
                        @endif
                        @if (!empty($visibleColumnsLookup['cancellation_charges']))
                            <td>{{ $cancellationCharges }}</td>
                        @endif
                        @if (!empty($visibleColumnsLookup['terminal_commission']))
                            <td>
                                {{ $single->type !== 'canceled' ? ($terminalCommission > 0 ? $terminalCommission : 'N/A') : 0 }}
                            </td>
                        @endif

                        @if (!empty($visibleColumnsLookup['seat_commission']))
                            <td>
                                {{ $single->type !== 'canceled' ? ($seatCommission > 0 ? $seatCommission : 'N/A') : 0 }}
                            </td>
                        @endif

                        @if (!empty($visibleColumnsLookup['net_cash']))
                            <td>{{ $netCash }}</td>
                        @endif
                    </tr>
                @endforeach

                {{-- TOTAL ROW --}}
                <tr>
                   @if (!empty($visibleColumnsLookup['bus_time']))
    <th></th>
@endif

@if (!empty($visibleColumnsLookup['bus_number']))
    <th></th>
@endif

@if (!empty($visibleColumnsLookup['bus_class']))
    <th></th>
@endif

@if (!empty($visibleColumnsLookup['route']))
    <th></th>
@endif

@if (!empty($visibleColumnsLookup['passenger_name']))
    <th></th>
@endif

@if (!empty($visibleColumnsLookup['passenger_cnic']))
    <th></th>
@endif

@if (!empty($visibleColumnsLookup['passenger_contact']))
    <th></th>
@endif

                    @if (!empty($visibleColumnsLookup['seat_no']))
                        <th>{{ $ticketCount }}</th>
                    @endif

                    @if (!empty($visibleColumnsLookup['invoice_id']))
                        <th></th>
                    @endif

                    @if (!empty($visibleColumnsLookup['transaction_id']))
                        <th></th>
                    @endif

                    @if (!empty($visibleColumnsLookup['terminal_name']))
                        <th></th>
                    @endif

                    @if (!empty($visibleColumnsLookup['status']))
                        <th></th>
                    @endif

                    @if (!empty($visibleColumnsLookup['action_by']))
                        <th></th>
                    @endif

                    @if (!empty($visibleColumnsLookup['sale']))
                        <th>{{ $totalSale }}</th>
                    @endif

                    @if (!empty($visibleColumnsLookup['refund']))
                        <th>{{ $totalRefund }}</th>
                    @endif
                    @if (!empty($visibleColumnsLookup['cancellation_charges']))
                        <th>{{ $totalCancellationCharges ?? 0 }}</th>
                    @endif

                    @if (!empty($visibleColumnsLookup['terminal_commission']))
                        <th>{{ $totalCommission }}</th>
                    @endif

                    @if (!empty($visibleColumnsLookup['seat_commission']))
                        <th>{{ $totalSeatCommission ?? 0 }}</th>
                    @endif

                    @if (!empty($visibleColumnsLookup['net_cash']))
                        <th>{{ $totalNetCash }}</th>
                    @endif
                </tr>

            </tbody>
        </table>
    </div>
</body>

</html>
