<table border="2" style="text-align: center;">
    <thead>
        <tr>
            <th width="200px">Bus Time</th>
            <th>Terminal Name</th>
            <th>Route</th>
            <th>Transaction #</th>
            <th>Invoice</th>
            <th>Cancel By</th>
            <th>Seat No</th>
            <th>Type</th>
            <th>Passenger Name</th>
            <th>Cell NO</th>
            <th>Cnic NO</th>
            <th>Total Fare</th>
            <th>Cancellation Percentage</th>
            <th>Amount Refund</th>
            <th>Cancellation Charges</th>
            <th>Remarks</th>
            <th width="200px">Cancellation Date</th>
        </tr>
    </thead>

    <tbody>
        @foreach($tickets as $single)
            <tr>
                <td>{{ $single->bus_time }}</td>
                <td>{{ $single->terminal_name }}</td>
                <td>{{ $single->route_name }}</td>
                <td>{{ $single->transaction_id ?? 'N/A' }}</td>
                <td>{{ $single->invoice_id }}</td>
                <td>{{ $single->cancel_by }}</td>
                <td>{{ $single->seat_no }}</td>
                <td>{{ $single->type }}</td>
                <td>{{ $single->passenger_name }}</td>
                <td>{{ $single->passenger_contact }}</td>
                <td>{{ $single->passenger_cnic }}</td>
                <td>{{ $single->total_fare }}</td>
                <td>{{ $single->cancel_percentage }} %</td>
                <td>{{ $single->amount_refund }}</td>
                <td>{{ $single->cancelation_charges }}</td>
                <td>{{ $single->cancel_reason }}</td>
                <td>{{ $single->cancel_date }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
