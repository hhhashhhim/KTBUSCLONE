<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            size: landscape;
            margin: 10px;
        }

        body {
            margin: 15px;
            font-family: Arial, sans-serif;
            font-size: 9pt;
        }

        h2,
        .customer-info {
            text-align: center;
        }

        .customer-info {
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
        }
    </style>
    <script type="text/javascript">
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
    <title>Card History</title>
</head>

<body>
    @php
        $totals = collect($history)->reduce(
            function ($totals, $ticket) {
                $totals['seat_fare'] += (float) ($ticket['seat_fare'] ?? 0);
                $totals['discount'] += (float) ($ticket['discount'] ?? 0);
                $totals['terminal_discount'] += (float) ($ticket['terminal_discount'] ?? 0);
                $totals['schedule_discount'] += (float) ($ticket['schedule_discount'] ?? 0);
                $totals['total_discount'] += (float) ($ticket['total_discount'] ?? 0);

                return $totals;
            },
            [
                'seat_fare' => 0,
                'discount' => 0,
                'terminal_discount' => 0,
                'schedule_discount' => 0,
                'total_discount' => 0,
            ],
        );
    @endphp
    <h2>Card History Report</h2>
    <div class="customer-info">
        <strong>Customer:</strong> {{ $customer->name ?? '-' }}
        |
        <strong>CNIC:</strong> {{ $customer->cnic ?? '-' }}
        |
        <strong>Contact:</strong> {{ $customer->contact ?? '-' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Customer Name</th>
                <th>Customer CNIC</th>
                <th>Customer Contact</th>
                <th>Invoice ID</th>
                <th>Schedule Date</th>
                <th>Schedule Time</th>
                <th>Seat No</th>
                <th>Seat Fare</th>
                <th>Discount</th>
                <th>Terminal Discount</th>
                <th>Schedule Discount</th>
                <th>Total Discount</th>
                <th>Route</th>
                <th>From City</th>
                <th>To City</th>
                <th>Terminal</th>
                <th>Booked Time</th>
                <th>Added By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($history as $ticket)
                <tr>
                    <td>{{ $ticket['customer_name'] ?? '-' }}</td>
                    <td>{{ $ticket['customer_cnic'] ?? '-' }}</td>
                    <td>{{ $ticket['customer_contact'] ?? '-' }}</td>
                    <td>{{ $ticket['invoice_id'] ?? '-' }}</td>
                    <td>{{ $ticket['schedule_date'] ?? '-' }}</td>
                    <td>{{ $ticket['schedule_time'] ?? '-' }}</td>
                    <td>{{ $ticket['seat_no'] ?? '-' }}</td>
                    <td>{{ $ticket['seat_fare'] ?? 0 }}</td>
                    <td>{{ $ticket['discount'] ?? 0 }}</td>
                    <td>{{ $ticket['terminal_discount'] ?? 0 }}</td>
                    <td>{{ $ticket['schedule_discount'] ?? 0 }}</td>
                    <td>{{ $ticket['total_discount'] ?? 0 }}</td>
                    <td>{{ $ticket['route_name'] ?? '-' }}</td>
                    <td>{{ $ticket['departure_city_name'] ?? '-' }}</td>
                    <td>{{ $ticket['destination_city_name'] ?? '-' }}</td>
                    <td>{{ $ticket['terminal_name'] ?? '-' }}</td>
                    <td>{{ $ticket['booked_time'] ?? '-' }}</td>
                    <td>{{ $ticket['added_by_name'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="16" style="text-align: center;">No card history found</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="7">Totals</th>
                <th>{{ number_format($totals['seat_fare'], 2) }}</th>
                <th>{{ number_format($totals['discount'], 2) }}</th>
                <th>{{ number_format($totals['terminal_discount'], 2) }}</th>
                <th>{{ number_format($totals['schedule_discount'], 2) }}</th>
                <th>{{ number_format($totals['total_discount'], 2) }}</th>
                <th colspan="6"></th>
            </tr>
        </tfoot>
    </table>
</body>

</html>
