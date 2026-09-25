<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Support\ReportFilterScope;
use App\Support\TerminalSaleStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TerminalTicketActivityController extends Controller
{
    public function show(Request $request)
    {
        return response()->json($this->details($request));
    }

    public function print(Request $request)
    {
        return view('reports.terminalTicketActivity', $this->details($request));
    }

    public function details(Request $request): array
    {
        abort_unless(checkForSubmenu('terminal-sale'), 403);
        $input = $request->validate(['invoice_id' => ['required', 'regex:/^[0-9]{1,20}$/']]);
        $company = Auth::user()->company_id;
        // Lookup is independent of report filters, but never of the user's access scope.
        $scope = new Request();
        $terminal = ReportFilterScope::terminalId($scope, 'terminal-sale-terminal-filter');
        $routes = ReportFilterScope::routeIds($scope, 'terminal-sale-route-filter');
        $user = ReportFilterScope::terminalUserId($scope, 'terminal-sale-user-filter');
        $tickets = Ticket::withTrashed()->where('company_id', $company)
            ->where('invoice_id', $input['invoice_id'])
            ->when($terminal, fn ($q) => $q->where('terminal_id', $terminal))
            ->when($routes !== null, fn ($q) => $q->whereIn('route_id', $routes ?? []))
            ->when($user, fn ($q) => $q->where('updated_by', $user))
            ->with(['customer' => fn ($q) => $q->withTrashed()->select('id', 'name', 'cnic', 'contact'),
                'terminal' => fn ($q) => $q->withTrashed()->select('id', 'name'),
                'route' => fn ($q) => $q->withTrashed()->select('id', 'name'),
                'busClass' => fn ($q) => $q->withTrashed()->select('id', 'name'),
                'bus' => fn ($q) => $q->withTrashed()->select('id', 'bus_number')])
            ->orderBy('id')->limit(501)->get();
        abort_if($tickets->isEmpty(), 404, 'No ticket found for this invoice within your permitted access.');
        abort_if($tickets->count() > 500, 422, 'This invoice has too many historical records to display. Please contact support.');
        $ids = $tickets->pluck('id');
        $byId = $tickets->keyBy('id');
        $cancels = DB::table('booking_cancels')->where('company_id', $company)->whereIn('ticket_id', $ids)->orderBy('id')->get();
        $reschedules = DB::table('ticket_reschedules')->where('company_id', $company)
            ->where(fn ($q) => $q->whereIn('old_ticket_id', $ids)->orWhereIn('new_ticket_id', $ids))->orderBy('id')->get();
        $reservations = DB::table('ticket_advanced_bookeds')->where('company_id', $company)->whereIn('ticket_id', $ids)->get();
        $requests = DB::table('bookkaru_api_logs')->where('company_id', $company)
            ->where(fn ($q) => $q->where('normalized_invoice_id', $input['invoice_id'])->orWhere('invoice_id', $input['invoice_id']))
            ->orderBy('id')->get();
        $events = [];
        $add = function ($action, $at, $actor, $seat, $detail, $fallback = null) use (&$events) {
            $events[] = ['action' => $action, 'at' => $at ? (string) $at : null, 'actor_id' => $actor,
                'actor_fallback' => $fallback, 'seat' => (string) $seat, 'detail' => $detail];
        };
        $incoming = $reschedules->keyBy('new_ticket_id');
        $outgoing = $reschedules->keyBy('old_ticket_id');
        $rows = $tickets->map(function ($ticket) use ($cancels, $incoming, $outgoing, $add) {
            $cancel = $cancels->where('ticket_id', $ticket->id)->whereNull('deleted_at')->last();
            $ticket->setRelation('cancel_ticket', $cancel);
            $add($incoming->has($ticket->id) ? 'Ticket created by reschedule' : 'Ticket created',
                $ticket->created_at, $ticket->added_by, $ticket->seat_no, 'Ticket #' . $ticket->id);
            // This is explicitly a snapshot, not a claim that the last editor performed a specific action.
            if ($ticket->updated_at && (string) $ticket->updated_at !== (string) $ticket->created_at) {
                $add('Record last updated', $ticket->updated_at, null, $ticket->seat_no,
                    'Latest saved timestamp only; the specific change and its actor are not recorded here.');
            }
            return [
                'id' => $ticket->id, 'seat' => $ticket->seat_no, 'status' => TerminalSaleStatus::label($ticket),
                'historical' => $outgoing->has($ticket->id), 'removed' => (bool) $ticket->deleted_at,
                'passenger' => data_get($ticket, 'customer.name'), 'cnic' => data_get($ticket, 'customer.cnic'),
                'contact' => data_get($ticket, 'customer.contact'), 'terminal' => data_get($ticket, 'terminal.name'),
                'route' => data_get($ticket, 'route.name'), 'bus_class' => data_get($ticket, 'busClass.name'),
                'bus' => data_get($ticket, 'bus.bus_number'), 'departure' => trim($ticket->schedule_date . ' ' . $ticket->schedule_time),
                'transaction_id' => $ticket->transaction_id, 'fare' => (float) $ticket->seat_fare,
                'discount' => (float) $ticket->discount, 'net_fare' => (float) $ticket->seat_fare - (float) $ticket->discount,
                'recorded_refund' => $ticket->refund_amount === null ? null : (float) $ticket->refund_amount,
                'remarks' => $ticket->remarks, 'cancellation_reason' => $cancel->reason ?? $ticket->refund_reason,
                'created_at' => (string) $ticket->created_at,
            ];
        })->values();
        foreach ($reservations as $reservation) {
            $add('Reservation recorded', $reservation->created_at, $reservation->added_by,
                $byId[$reservation->ticket_id]->seat_no, 'Advance booking record #' . $reservation->id);
        }
        foreach ($reschedules as $change) {
            $old = $byId->get($change->old_ticket_id);
            $new = $byId->get($change->new_ticket_id);
            $detail = 'From ' . ($old ? "seat {$old->seat_no}, {$old->schedule_date} {$old->schedule_time}" : 'a ticket outside this view')
                . ' to ' . ($new ? "seat {$new->seat_no}, {$new->schedule_date} {$new->schedule_time}" : 'a ticket outside this view');
            if ($change->reason) $detail .= '. Reason: ' . $change->reason;
            if ($change->deleted_at) $detail .= ' (Historical record marked deleted.)';
            $add('Rescheduled', $change->created_at, $change->added_by, $old->seat_no ?? $new->seat_no, $detail);
        }
        foreach ($cancels as $cancel) {
            $auto = strtolower(trim((string) $cancel->reason)) === 'auto cancel';
            $detail = 'Previous status: ' . ($cancel->type ?: 'Not recorded') . '. Reason: ' . ($cancel->reason ?: 'Not recorded');
            if ($cancel->deleted_at) $detail .= ' (Historical record marked deleted.)';
            $add($auto ? 'Reservation expired' : 'Cancelled', $cancel->created_at, $auto ? null : $cancel->added_by,
                $byId[$cancel->ticket_id]->seat_no, $detail, $auto ? 'System (automatic cancellation)' : null);
        }
        foreach ($requests as $log) {
            $payload = json_decode($log->request_payload ?? '{}', true) ?: [];
            $linked = $payload['ticket_ids'] ?? $payload['display_ticket_ids'] ?? [];
            $seats = json_decode($log->seat_numbers ?? '[]', true) ?: [];
            // Do not expose another seat's request on a partly permitted invoice.
            $visible = $linked ? $tickets->whereIn('id', $linked) : $tickets->filter(fn ($t) => in_array((string) $t->seat_no, array_map('strval', $seats), true));
            if ($visible->isEmpty()) continue;
            $seat = $visible->pluck('seat_no')->unique()->implode(', ');
            $add('Cancellation requested', $log->created_at, $payload['requested_by'] ?? null, $seat,
                'Request #' . $log->id . '; saved status: ' . ($log->status ?? 'Not recorded') . '. Reason: '
                    . ($payload['cancellation_reason'] ?? 'No reason recorded'), $payload['requested_by_name'] ?? null);
            foreach (['approved' => 'approved', 'rejected' => 'rejected', 'cancelled' => 'completed'] as $field => $label) {
                if (!empty($log->{$field . '_at'})) {
                    $add('Cancellation request ' . $label, $log->{$field . '_at'}, $log->{$field . '_by'} ?? null,
                        $seat, 'Request #' . $log->id . '; saved status: ' . ($log->status ?? 'Not recorded'));
                }
            }
        }
        $actors = DB::table('users')->where('company_id', $company)->whereIn('id', collect($events)->pluck('actor_id')->filter()->unique())->pluck('name', 'id');
        $events = collect($events)->map(function ($event) use ($actors) {
            $event['actor'] = $actors->get($event['actor_id']) ?: $event['actor_fallback'] ?: 'Not recorded';
            unset($event['actor_id'], $event['actor_fallback']);
            return $event;
        })->sortBy('at')->values();
        return ['invoice_id' => (string) $input['invoice_id'], 'tickets' => $rows, 'events' => $events,
            'note' => 'History shows saved records within your access. Older edits or payment confirmations may not have a separate audit record. Recorded refunds are stored amounts, not proof of payment. Rescheduled historical seats are not additional sales.'];
    }
}
