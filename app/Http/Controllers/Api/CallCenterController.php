<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallCenter\CallLog;
use App\Models\Customer;
use App\Models\Terminal;
use App\Models\User;
use App\Support\CallCenterScope as Scope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CallCenterController extends Controller
{
    private const CATEGORIES = ['GI' => 'General Information', 'BK' => 'Confirmed Booking', 'C' => 'Complaint', 'FU' => 'Follow-up', 'RF' => 'Refund', 'RS' => 'Reschedule'];
    private const RESPONSES = ['agreed' => 'Agreed', 'declined' => 'Declined', 'not_reached' => 'No answer'];
    private const STATUSES = ['new' => 'New', 'in_progress' => 'In progress', 'waiting' => 'Waiting', 'closed' => 'Closed'];

    private function authorizeAccess(Request $request, ?string $action = null): void
    {
        abort_unless($request->user()->company_id && Scope::allows($request->user(), 'call-center'), 403, 'Call center access denied.');
        if ($action) { abort_unless(Scope::allows($request->user(), 'call-center-' . $action), 403, 'Your role does not allow this call center action.'); }
    }

    private function authorizeRead(Request $request, CallLog $call): void
    {
        abort_unless(Scope::allows($request->user(), 'call-center-view-calls')
            || ($call->category === 'C' && Scope::allows($request->user(), 'call-center-view-complaints')), 403, 'Call viewing permission required.');
    }

    private function response($data, int $status = 200)
    {
        return response()->json(['status' => true, 'data' => $data], $status);
    }

    private function now(): Carbon { return Carbon::now('Asia/Karachi'); }

    public function options(Request $request)
    {
        $this->authorizeAccess($request);
        $user = $request->user();
        $all = Scope::allows($user, 'call-center-all-terminals');
        $agents = User::with(['role', 'company'])->where('company_id', $user->company_id)->where('hide', 0)
            ->when(!$all, function ($q) use ($user) { $q->where('terminal_id', $user->terminal_id); })
            ->get()->filter(function ($agent) { return Scope::allows($agent, 'call-center'); })
            ->map(function ($agent) { return $agent->only(['id', 'name', 'terminal_id']); })->values();
        $terminals = Terminal::where('company_id', $user->company_id)->where('hide', 0)
            ->when(!$all, function ($q) use ($user) { $q->where('id', $user->terminal_id); })->get(['id', 'name']);
        return $this->response([
            'categories' => $this->choices(self::CATEGORIES), 'statuses' => $this->choices(self::STATUSES),
            'customer_responses' => $this->choices(self::RESPONSES),
            'shifts' => [1, 2, 3], 'agents' => $agents, 'terminals' => $terminals,
            'permissions' => $this->permissions($request),
            'current_user' => $user->only(['id', 'name', 'terminal_id']), 'timezone' => 'Asia/Karachi',
        ]);
    }

    private function permissions(Request $request): array
    {
        $permissions = ['view' => true];
        foreach (\App\Support\CallCenterPermissions::definitions() as $definition) {
            $key = str_replace('-', '_', substr($definition['name'], strlen('call-center-')));
            $permissions[$key] = Scope::allows($request->user(), $definition['name']);
        }
        $permissions['create'] = $permissions['create_calls'];
        $permissions['update'] = $permissions['edit_calls'];
        $permissions['reports'] = $permissions['shift_report'] || $permissions['daily_report'];
        $permissions['review'] = $permissions['review_reports'] && $permissions['supervise'];
        return $permissions;
    }

    private function choices(array $values): array
    {
        return collect($values)->map(function ($label, $value) { return compact('value', 'label'); })->values()->all();
    }

    public function passengers(Request $request)
    {
        $this->authorizeAccess($request, 'passenger-lookup');
        $request->validate(['search_by' => ['required', 'string', Rule::in(['phone', 'ticket', 'cnic'])], 'q' => 'required|string|min:1|max:50']);
        $search = trim($request->q);
        if ($request->search_by === 'ticket' && (!preg_match('/^[0-9]{1,18}$/', $search) || ltrim($search, '0') === '')) {
            throw ValidationException::withMessages(['q' => 'Enter a numeric ticket, booking or invoice number.']);
        }
        $tickets = Scope::tickets($request);
        $customers = Customer::where('company_id', $request->user()->company_id);
        if ($request->search_by === 'ticket') {
            $tickets->where(function ($q) use ($search) {
                $q->where('booking_no', $search)->orWhere('invoice_id', $search);
                if (ctype_digit($search)) { $q->orWhere('id', (int) $search); }
            });
            $customers->whereIn('id', (clone $tickets)->select('customer_id'));
        } else {
            $digits = preg_replace('/\D/', '', $search);
            if (($request->search_by === 'cnic' && strlen($digits) !== 13) || ($request->search_by === 'phone' && strlen($digits) < 10)) {
                throw ValidationException::withMessages(['q' => 'Enter a complete phone number or 13-digit CNIC.']);
            }
            $field = $request->search_by === 'cnic' ? 'cnic' : 'contact';
            $variants = [$digits];
            if ($field === 'contact') {
                if (strlen($digits) === 12 && substr($digits, 0, 2) === '92') { $variants[] = '0' . substr($digits, 2); }
                if (strlen($digits) === 11 && substr($digits, 0, 1) === '0') { $variants[] = '92' . substr($digits, 1); }
            }
            $customers->whereIn(DB::raw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE($field, '-', ''), ' ', ''), '+', ''), '(', ''), ')', '')"), $variants);
            // Find exact customer matches before examining their scoped tickets.
            // Otherwise MySQL can materialize the entire company's ticket history.
            $matchingIds = (clone $customers)->pluck('id');
            if ($matchingIds->isEmpty()) { return $this->response(['passengers' => []]); }
            $tickets->whereIn('customer_id', $matchingIds);
            $customers->whereIn('id', (clone $tickets)->select('customer_id'));
        }
        $rows = $customers->orderByDesc('id')->limit(20)->get()->map(function ($customer) use ($tickets) {
            $bookings = (clone $tickets)->where('customer_id', $customer->id)->with(['terminal:id,name', 'departure_city:id,name', 'destination_city:id,name'])
                ->orderByDesc('id')->limit(25)->get()->map(function ($ticket) {
                    $cancelled = $ticket->deleted_at !== null;
                    return array_merge($ticket->only(['id', 'invoice_id', 'booking_no', 'seat_no', 'type', 'schedule_date', 'schedule_time', 'seat_fare', 'terminal_id']), [
                        'terminal_name' => optional($ticket->terminal)->name,
                        'departure' => optional($ticket->departure_city)->name, 'destination' => optional($ticket->destination_city)->name,
                        'payment_status' => $ticket->type === 'canceled' ? 'cancelled' : (!$cancelled && $ticket->type === 'booked' ? 'recorded_booking' : 'unconfirmed'),
                        'can_link_booking' => !$cancelled && $ticket->type === 'booked',
                    ]);
                });
            return ['id' => $customer->id, 'name' => $customer->name, 'contact' => $customer->contact,
                'cnic_masked' => '*********' . substr(preg_replace('/\D/', '', $customer->cnic), -4), 'tickets' => $bookings];
        });
        return $this->response(['passengers' => $rows]);
    }

    public function index(Request $request)
    {
        $this->authorizeAccess($request);
        $viewCalls = Scope::allows($request->user(), 'call-center-view-calls');
        abort_unless($viewCalls || Scope::allows($request->user(), 'call-center-view-complaints'), 403, 'Call viewing permission required.');
        $this->validateFilters($request, false);
        $query = Scope::calls($request)->with(['agent', 'assignee', 'terminal', 'resolver']);
        if (!$viewCalls) { $query->where('category', 'C'); }
        $this->applyFilters($query, $request);
        if ($request->queue === 'awaiting_agreement') { $query->whereIn('category', ['RF', 'RS'])->where('customer_response', 'not_reached')->where('status', '!=', 'closed'); }
        if ($request->queue === 'today') { $query->where('call_date', $request->date ?: $this->now()->toDateString()); }
        if ($request->queue === 'callbacks_due') {
            $query->whereNotNull('callback_at')->where('callback_at', '<=', $this->now()->format('Y-m-d H:i:s'))->where('status', '!=', 'closed')->orderBy('callback_at');
        }
        if ($request->queue === 'open_complaints') { $query->where('category', 'C')->where('status', '!=', 'closed'); }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $digits = preg_replace('/\D/', '', $search);
                if (strlen($digits) >= 10 && preg_match('/^[0-9+()\-\s]+$/', $search)) {
                    $variants = [$digits];
                    if (substr($digits, 0, 2) === '92') { $variants[] = '0' . substr($digits, 2); }
                    if (substr($digits, 0, 1) === '0') { $variants[] = '92' . substr($digits, 1); }
                    $q->whereIn(DB::raw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(caller_phone, '-', ''), ' ', ''), '+', ''), '(', ''), ')', '')"), $variants);
                }
                elseif (preg_match('/^(?:CC-)?(\d+)$/i', $search, $match)) { $q->where('id', $match[1])->orWhere('caller_phone', $search); }
                else { $q->where('caller_name', 'like', '%' . addcslashes($search, '%_') . '%')->orWhere('external_reference', $search); }
            });
        }
        if ($request->boolean('pending_callbacks')) { $query->whereNotNull('callback_at')->where('status', '!=', 'closed'); }
        return $this->response($query->orderByDesc('called_at')->orderByDesc('id')->paginate(25));
    }

    public function history(Request $request)
    {
        $this->authorizeAccess($request, 'view-history');
        $request->validate(['customer_id' => 'nullable|integer|min:1', 'phone' => 'nullable|string|max:25']);
        $customerId = $request->customer_id;
        $phone = $request->phone;
        if ($customerId) {
            $customer = Customer::where('company_id', $request->user()->company_id)->whereIn('id', Scope::tickets($request)->where('customer_id', $customerId)->select('customer_id'))->findOrFail($customerId);
            $phone = $customer->contact;
        }
        $digits = preg_replace('/\D/', '', $phone ?? '');
        if (!$customerId && strlen($digits) < 10) { throw ValidationException::withMessages(['phone' => 'Enter a complete phone number.']); }
        $variants = [$digits];
        if (strlen($digits) === 12 && substr($digits, 0, 2) === '92') { $variants[] = '0' . substr($digits, 2); }
        if (strlen($digits) === 11 && substr($digits, 0, 1) === '0') { $variants[] = '92' . substr($digits, 1); }
        $query = Scope::calls($request)->with(['agent', 'assignee'])->where(function ($q) use ($customerId, $variants, $digits) {
            if ($customerId) { $q->where('customer_id', $customerId); }
            if (strlen($digits) >= 10) {
                $method = $customerId ? 'orWhereIn' : 'whereIn';
                $q->$method(DB::raw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(caller_phone, '-', ''), ' ', ''), '+', ''), '(', ''), ')', '')"), $variants);
            }
        });
        $open = (clone $query)->where('category', 'C')->where('status', '!=', 'closed');
        return $this->response([
            'recent_calls' => (clone $query)->orderByDesc('called_at')->orderByDesc('id')->limit(5)->get(),
            'open_complaints' => (clone $open)->orderByDesc('called_at')->orderByDesc('id')->limit(10)->get(),
            'open_complaint_count' => $open->count(),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $this->authorizeAccess($request);
        $call = Scope::calls($request)->with(['agent', 'assignee', 'terminal', 'resolver'])->findOrFail($id);
        $this->authorizeRead($request, $call);
        if (Scope::allows($request->user(), 'call-center-view-audit')) { $call->setAttribute('audit', DB::table('call_center_audits')->where('call_id', $call->id)->orderBy('id')->get()->map(function ($audit) use ($request) {
            $audit->changes = json_decode($audit->changes, true);
            $audit->actor = User::where('company_id', $request->user()->company_id)->find($audit->actor_id, ['id', 'name']);
            return $audit;
        })); }
        return $this->response($call);
    }

    public function store(Request $request)
    {
        $this->authorizeAccess($request, 'create-calls');
        $user = $request->user();
        abort_unless($user->terminal_id && Terminal::where('company_id', $user->company_id)->where('id', $user->terminal_id)->where('hide', 0)->exists(), 422, 'Assign an active terminal to this agent first.');
        $data = array_merge(['status' => 'new', 'seat_count' => 0], $this->validatedCall($request));
        $call = DB::transaction(function () use ($request, $data, $user) {
            $call = CallLog::create(array_merge($data, [
                'company_id' => $user->company_id, 'terminal_id' => $user->terminal_id, 'agent_id' => $user->id,
                'called_at' => $this->now()->format('Y-m-d H:i:s'), 'call_date' => $data['call_date'] ?? $this->now()->toDateString(),
            ]));
            $this->audit($call, $request, 'created', $call->getAttributes());
            $this->resolveParent($call, $request);
            return $call;
        });
        return $this->response($call->load(['agent', 'assignee', 'terminal']), 201);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeAccess($request, 'edit-calls');
        $call = DB::transaction(function () use ($request, $id) {
            $call = Scope::calls($request)->lockForUpdate()->findOrFail($id);
            $this->authorizeRead($request, $call);
            $before = $call->getAttributes();
            $call->fill($this->validatedCall($request, $call));
            $changes = [];
            foreach ($call->getDirty() as $key => $value) { $changes[$key] = ['from' => $before[$key] ?? null, 'to' => $value]; }
            if ($changes) { $call->save(); $this->audit($call, $request, 'updated', $changes); }
            $this->resolveParent($call, $request);
            return $call;
        });
        return $this->response($call->load(['agent', 'assignee', 'terminal']));
    }

    public function closeComplaint(Request $request, int $id)
    {
        $this->authorizeAccess($request, 'close-complaints');
        $request->validate(['resolution_remarks' => 'required|string|max:5000']);
        $call = DB::transaction(function () use ($request, $id) {
            $call = Scope::calls($request)->lockForUpdate()->findOrFail($id);
            abort_unless($call->category === 'C', 422, 'This call is not a complaint.');
            $this->finishComplaint($call, $request, $request->resolution_remarks);
            return $call;
        });
        return $this->response($call->load(['agent', 'assignee', 'terminal', 'resolver']));
    }

    private function finishComplaint(CallLog $call, Request $request, string $remarks, string $errorField = 'resolution_remarks', ?int $followupId = null): void
    {
        $this->authorizeAccess($request, 'view-complaints');
        $this->authorizeAccess($request, 'close-complaints');
        $remarks = trim($remarks);
        if ($remarks === '' || mb_strlen($remarks) > 5000) {
            throw ValidationException::withMessages([$errorField => 'Enter closing remarks (up to 5000 characters).']);
        }
        if ($call->status === 'closed') { return; }
        $before = $call->getAttributes();
        $call->fill(['status' => 'closed', 'callback_at' => null, 'resolution_remarks' => $remarks,
            'resolved_at' => $this->now()->format('Y-m-d H:i:s'), 'resolved_by' => $request->user()->id]);
        $changes = [];
        foreach ($call->getDirty() as $key => $value) { $changes[$key] = ['from' => $before[$key] ?? null, 'to' => $value]; }
        if ($followupId !== null) { $changes['resolved_by_call'] = $followupId; }
        $call->save();
        $this->audit($call, $request, 'closed', $changes);
    }

    private function validatedCall(Request $request, ?CallLog $existing = null): array
    {
        $required = $existing ? 'sometimes' : 'required';
        $rules = [
            'shift' => [$required, 'integer', 'between:1,3'], 'category' => [$required, 'string', Rule::in(array_keys(self::CATEGORIES))],
            'status' => ['sometimes', 'string', Rule::in(array_keys(self::STATUSES))], 'caller_name' => 'nullable|string|max:120',
            'caller_phone' => 'nullable|string|max:25|regex:/^[0-9+()\-\s]+$/', 'customer_id' => 'nullable|integer|min:1',
            'ticket_id' => 'nullable|integer|min:1', 'seat_count' => 'sometimes|integer|min:0|max:100', 'remarks' => 'nullable|string|max:5000',
            'assigned_to' => 'nullable|integer|min:1', 'callback_at' => 'nullable|date_format:Y-m-d H:i:s',
            'parent_call_id' => 'nullable|integer|min:1', 'external_reference' => 'nullable|string|max:100',
            'inquiry_topic' => ['nullable', Rule::in(['fare', 'timings', 'terminal'])],
            'complaint_from_city' => 'nullable|string|max:120', 'complaint_to_city' => 'nullable|string|max:120',
            'complaint_bus_time' => 'nullable|date_format:H:i', 'complaint_seat_number' => 'nullable|string|max:20',
            'customer_response' => ['nullable', Rule::in(array_keys(self::RESPONSES))],
            'change_seat_count' => 'nullable|integer|min:1|max:100',
            'proposed_refund_amount' => 'nullable|numeric|min:0|max:9999999999.99|regex:/^\d+(?:\.\d{1,2})?$/',
            'proposed_travel_date' => 'nullable|date_format:Y-m-d', 'proposed_bus_time' => 'nullable|date_format:H:i',
            'outcome' => ['sometimes', Rule::in(['finish', 'follow_up', 'record'])], 'resolve_parent' => 'sometimes|boolean',
        ];
        if (!$existing) { $rules['call_date'] = ['sometimes', 'date_format:Y-m-d', 'after_or_equal:' . $this->now()->subDay()->toDateString(), 'before_or_equal:' . $this->now()->toDateString()]; }
        $data = $request->validate($rules);
        $merged = array_merge($existing ? $existing->getAttributes() : ['status' => 'new'], $data);
        unset($data['outcome'], $data['resolve_parent']);
        if ($merged['category'] === 'FU' || (in_array($merged['category'], ['RF', 'RS'], true) && !empty($merged['parent_call_id']))) {
            $this->authorizeAccess($request, 'follow-up');
            $this->authorizeAccess($request, 'view-calls');
            if ($merged['category'] !== 'FU') { $this->authorizeAccess($request, 'edit-calls'); }
        }
        if ($existing && $existing->category === 'C' && $merged['category'] !== 'C') {
            $this->authorizeAccess($request, 'view-complaints');
            $this->authorizeAccess($request, 'close-complaints');
            if ($existing->status === 'closed') { $this->authorizeAccess($request, 'reopen-complaints'); }
            if ($existing->status !== 'closed' && ($merged['status'] === 'closed' || $request->outcome === 'finish')) {
                throw ValidationException::withMessages(['category' => 'Close the complaint with remarks before changing its category.']);
            }
        }
        // Linking passenger data is an action too: guessing an ID cannot bypass lookup permission.
        foreach (['ticket_id', 'customer_id'] as $field) {
            if (!empty($merged[$field]) && (int) $merged[$field] !== (int) ($existing->$field ?? 0)) {
                $inherited = in_array($merged['category'], ['FU', 'RF', 'RS'], true) && !empty($merged['parent_call_id'])
                    ? Scope::calls($request)->find($merged['parent_call_id']) : null;
                if (!$inherited || (int) $inherited->$field !== (int) $merged[$field]) { $this->authorizeAccess($request, 'passenger-lookup'); }
            }
        }
        $previousAssignee = (int) ($existing->assigned_to ?? 0);
        $nextAssignee = (int) ($merged['assigned_to'] ?? 0);
        if ($previousAssignee !== $nextAssignee &&
            (($previousAssignee && $previousAssignee !== (int) $request->user()->id) || ($nextAssignee && $nextAssignee !== (int) $request->user()->id))) {
            $this->authorizeAccess($request, 'assign-calls');
        }
        $oldCallback = $existing && $existing->callback_at ? $existing->callback_at : null;
        $newCallback = $merged['callback_at'] ?? null;
        // Finishing a call may clear its reminder; scheduling or rescheduling needs a separate grant.
        if ($request->outcome === 'follow_up' || ($newCallback != $oldCallback && !($request->outcome === 'finish' && !$newCallback))) {
            $this->authorizeAccess($request, 'manage-callbacks');
        }
        if ($merged['category'] !== 'GI') { $data['inquiry_topic'] = null; }
        if (!in_array($merged['category'], ['C', 'BK', 'RF', 'RS'], true)) {
            foreach (['complaint_from_city', 'complaint_to_city', 'complaint_bus_time', 'complaint_seat_number'] as $field) { $data[$field] = null; }
        }
        if ($request->outcome === 'record' && !in_array($merged['category'], ['C', 'RF', 'RS'], true)) {
            throw ValidationException::withMessages(['outcome' => 'Record is only available for complaints, refund calls and reschedule calls.']);
        }
        if ($request->outcome === 'finish' && $merged['category'] !== 'C') { $data['status'] = 'closed'; $data['callback_at'] = null; }
        if ($request->outcome === 'follow_up') {
            $request->validate(['callback_at' => 'required|date_format:Y-m-d H:i:s|after:' . $this->now()->format('Y-m-d H:i:s')]);
            if (strlen(preg_replace('/\D/', '', $merged['caller_phone'] ?? '')) < 10) {
                throw ValidationException::withMessages(['caller_phone' => 'Enter a phone number for the callback.']);
            }
            $data['status'] = 'waiting';
            $data['assigned_to'] = $merged['assigned_to'] ?? $request->user()->id;
        }
        if (in_array($merged['category'], ['RF', 'RS'], true)) {
            if (($existing && $existing->category !== $merged['category'] && !$request->filled('customer_response')) || empty($merged['customer_response'])) {
                throw ValidationException::withMessages(['customer_response' => 'Record whether the customer agreed, declined or did not answer.']);
            }
            $data['change_seat_count'] = $merged['change_seat_count'] ?? 1;
            $data['seat_count'] = 0;
            $data[$merged['category'] === 'RF' ? 'proposed_travel_date' : 'proposed_refund_amount'] = null;
            if ($merged['category'] === 'RF') { $data['proposed_bus_time'] = null; }
            // These are call outcomes, never a cancellation, payment or seat change.
            // No answer stays visible until the customer can be reached.
            if ($merged['customer_response'] === 'not_reached') {
                $data['status'] = $existing && $existing->status === 'closed' && $existing->customer_response === 'not_reached' && $request->outcome !== 'follow_up' ? 'closed' : 'waiting';
                $data['assigned_to'] = $data['assigned_to'] ?? $merged['assigned_to'] ?? $request->user()->id;
            } elseif ($request->outcome !== 'follow_up' && (!$existing || $request->filled('outcome') || $request->has('customer_response'))) {
                $data['status'] = 'closed'; $data['callback_at'] = null;
            }
        } else {
            foreach (['customer_response', 'change_seat_count', 'proposed_refund_amount', 'proposed_travel_date', 'proposed_bus_time'] as $field) { $data[$field] = null; }
        }
        if ($request->filled('outcome') && $merged['category'] === 'C' && !trim($merged['remarks'] ?? '')) {
            throw ValidationException::withMessages(['remarks' => 'Describe the complaint.']);
        }
        if ($merged['category'] === 'C') {
            if (($data['status'] ?? $merged['status']) === 'closed' && (!$existing || $existing->category !== 'C' || $existing->status !== 'closed')) {
                throw ValidationException::withMessages(['status' => 'Use Complaints to add closing remarks and close this complaint.']);
            }
            if ($existing && $existing->status === 'closed' && ($data['status'] ?? $merged['status']) !== 'closed') {
                $this->authorizeAccess($request, 'view-complaints');
                $this->authorizeAccess($request, 'reopen-complaints');
                $data['resolution_remarks'] = null; $data['resolved_at'] = null; $data['resolved_by'] = null;
            }
        }
        if ($request->boolean('resolve_parent') && ($merged['category'] !== 'FU' || $request->outcome !== 'finish')) {
            throw ValidationException::withMessages(['resolve_parent' => 'Finish a follow-up call to resolve its earlier call.']);
        }
        $merged = array_merge($merged, $data);
        if (!empty($merged['ticket_id'])) {
            $ticket = Scope::tickets($request)->find($merged['ticket_id']);
            if (!$ticket) { throw ValidationException::withMessages(['ticket_id' => 'Booking is outside your permitted scope.']); }
            if (!empty($merged['customer_id']) && (int) $merged['customer_id'] !== (int) $ticket->customer_id) {
                throw ValidationException::withMessages(['customer_id' => 'Passenger does not match the selected ticket.']);
            }
            $data['customer_id'] = $ticket->customer_id;
            if ($merged['category'] === 'BK') {
                // Updates to notes/status remain possible after the linked booking is later cancelled.
                $relink = !$existing || $existing->category !== 'BK' || (int) $existing->ticket_id !== (int) $ticket->id;
                if ($relink && ($ticket->type !== 'booked' || $ticket->deleted_at !== null)) {
                    throw ValidationException::withMessages(['ticket_id' => 'Choose an active confirmed booking to link.']);
                }
                if ($relink) {
                    $data['seat_count'] = $ticket->invoice_id ? Scope::tickets($request)->whereNull('deleted_at')->where('type', 'booked')->where('invoice_id', $ticket->invoice_id)->count() : 1;
                } else { $data['seat_count'] = $existing->seat_count; }
            }
        } elseif (!empty($merged['customer_id'])) {
            $visible = Customer::where('company_id', $request->user()->company_id)->where('id', $merged['customer_id'])
                ->whereIn('id', Scope::tickets($request)->select('customer_id'))->exists();
            if (!$visible) { throw ValidationException::withMessages(['customer_id' => 'Passenger is outside your permitted scope.']); }
        }
        if ($merged['category'] === 'BK' && empty($merged['ticket_id'])) {
            // Manual call logs have no verified seat count; unlinking clears the old snapshot.
            $data['seat_count'] = 0;
        }
        if (!empty($merged['parent_call_id'])) {
            $parent = Scope::calls($request)->find($merged['parent_call_id']);
            if (!$parent || ($existing && (int) $parent->id >= (int) $existing->id)) {
                throw ValidationException::withMessages(['parent_call_id' => 'Select a visible earlier call.']);
            }
            if (in_array($merged['category'], ['RF', 'RS'], true)) {
                if ($parent->category !== $merged['category']) {
                    throw ValidationException::withMessages(['parent_call_id' => 'Choose an earlier call for the same request type.']);
                }
                if ((!empty($parent->ticket_id) && (int) $parent->ticket_id !== (int) ($merged['ticket_id'] ?? 0))
                    || (!empty($parent->customer_id) && (int) $parent->customer_id !== (int) ($data['customer_id'] ?? $merged['customer_id'] ?? 0))) {
                    throw ValidationException::withMessages(['ticket_id' => 'Keep the passenger and booking from the earlier call.']);
                }
            }
        } elseif ($merged['category'] === 'FU') {
            throw ValidationException::withMessages(['parent_call_id' => 'Follow-up must link to an earlier call.']);
        }
        if (!empty($merged['assigned_to'])) {
            $agent = User::where('company_id', $request->user()->company_id)->where('hide', 0)->find($merged['assigned_to']);
            // Assignment must remain readable to the assignee without changing the call's owning terminal.
            $terminal = $existing ? $existing->terminal_id : $request->user()->terminal_id;
            if (!$agent || !Scope::allows($agent, 'call-center') || ((int) $agent->terminal_id !== (int) $terminal && !Scope::allows($agent, 'call-center-all-terminals'))) {
                throw ValidationException::withMessages(['assigned_to' => 'Choose an agent with access to this terminal.']);
            }
            if (!empty($merged['ticket_id'])) {
                $assigneeRequest = Request::create('/');
                $assigneeRequest->setUserResolver(function () use ($agent) { return $agent; });
                if (!Scope::tickets($assigneeRequest)->where('tickets.id', $merged['ticket_id'])->exists()) {
                    throw ValidationException::withMessages(['assigned_to' => 'Selected agent cannot access this booking.']);
                }
            }
        }
        if (!empty($merged['callback_at']) && empty($merged['assigned_to'])) {
            $data['assigned_to'] = $request->user()->id;
        }
        return $data;
    }

    private function resolveParent(CallLog $call, Request $request): void
    {
        $agreementFollowup = in_array($call->category, ['RF', 'RS'], true) && $call->parent_call_id
            && in_array($call->customer_response, array_keys(self::RESPONSES), true);
        if (!$request->boolean('resolve_parent') && !$agreementFollowup) { return; }
        $parent = Scope::calls($request)->lockForUpdate()->findOrFail($call->parent_call_id);
        if ($parent->status === 'closed') { return; }
        if ($agreementFollowup && ($parent->category !== $call->category || $parent->customer_response !== 'not_reached')) { return; }
        if ($parent->category === 'C') {
            $this->finishComplaint($parent, $request, $call->remarks ?? '', 'remarks', $call->id);
            return;
        }
        $this->authorizeAccess($request, 'edit-calls');
        if (in_array($parent->category, ['RF', 'RS'], true) && $parent->customer_response === 'not_reached' && !$agreementFollowup) {
            throw ValidationException::withMessages(['resolve_parent' => 'Open the refund or reschedule call and record the customer response first.']);
        }
        // A new attempt supersedes the unanswered attempt, preserving its response.
        $before = $parent->status;
        $callback = $parent->callback_at;
        $parent->update(['status' => 'closed', 'callback_at' => null]);
        $this->audit($parent, $request, 'updated', ['status' => ['from' => $before, 'to' => 'closed'], 'callback_at' => ['from' => $callback, 'to' => null], 'resolved_by_call' => $call->id]);
    }

    private function audit(CallLog $call, Request $request, string $action, array $changes): void
    {
        DB::table('call_center_audits')->insert(['call_id' => $call->id, 'actor_id' => $request->user()->id, 'action' => $action,
            'changes' => json_encode($changes), 'created_at' => $this->now()->format('Y-m-d H:i:s')]);
    }

    private function validateFilters(Request $request, bool $dateRequired = true): void
    {
        $request->validate(['date' => [$dateRequired ? 'required' : 'sometimes', 'date_format:Y-m-d'],
            'shift' => 'nullable|integer|between:1,3', 'terminal_id' => 'nullable|integer|min:0', 'agent_id' => 'nullable|integer|min:1',
            'category' => ['nullable', 'string', Rule::in(array_keys(self::CATEGORIES))], 'status' => ['nullable', 'string', Rule::in(array_keys(self::STATUSES))],
            'search' => 'nullable|string|max:100', 'page' => 'nullable|integer|min:1', 'pending_callbacks' => 'nullable|boolean',
            'queue' => $dateRequired ? 'prohibited' : ['nullable', Rule::in(['today', 'callbacks_due', 'open_complaints', 'awaiting_agreement', 'all'])]]);
    }

    private function applyFilters($query, Request $request): void
    {
        foreach (['date' => 'call_date', 'shift' => 'shift', 'category' => 'category', 'status' => 'status', 'agent_id' => 'agent_id'] as $input => $column) {
            if ($input === 'date' && in_array($request->queue, ['callbacks_due', 'open_complaints', 'awaiting_agreement'], true)) { continue; }
            if ($request->filled($input)) { $query->where($column, $request->input($input)); }
        }
    }

    private function reportData(Request $request): array
    {
        $query = Scope::calls($request, true);
        $this->applyFilters($query, $request);
        $calls = $query->with('agent')->orderBy('id')->get();
        $totals = array_fill_keys(array_keys(self::CATEGORIES), 0) + ['total' => 0, 'seats' => 0];
        $rows = [];
        foreach ($calls as $call) {
            $key = $call->agent_id . ':' . $call->shift;
            if (!isset($rows[$key])) { $rows[$key] = array_merge(['agent_id' => $call->agent_id, 'agent_name' => optional($call->agent)->name ?? 'Former agent', 'shift' => $call->shift], $totals); }
            $rows[$key][$call->category]++; $rows[$key]['total']++; $rows[$key]['seats'] += $call->category === 'BK' ? $call->seat_count : 0;
        }
        foreach ($rows as $row) { foreach ($totals as $key => $value) { $totals[$key] += $row[$key]; } }
        return ['rows' => array_values($rows), 'totals' => $totals, 'customer_agreements' => $this->customerAgreements($calls), 'calls' => $calls];
    }

    private function customerAgreements($calls): array
    {
        $result = [];
        foreach (['RF' => 'refund', 'RS' => 'reschedule'] as $category => $key) {
            $selected = $calls->where('category', $category);
            $agreed = $selected->where('customer_response', 'agreed');
            $result[$key] = ['calls' => $selected->count(), 'agreed' => $agreed->count(),
                'declined' => $selected->where('customer_response', 'declined')->count(),
                'not_reached' => $selected->where('customer_response', 'not_reached')->count(),
                'agreed_seats' => (int) $agreed->sum('change_seat_count')];
        }
        return $result;
    }

    public function summary(Request $request)
    {
        $this->authorizeAccess($request, 'shift-report'); $this->validateFilters($request);
        $request->validate(['category' => 'prohibited', 'status' => 'prohibited', 'agent_id' => 'prohibited']);
        $data = $this->reportData($request);
        $tokens = [];
        foreach ([1, 2, 3] as $shift) {
            $tokens[$shift] = $request->filled('shift') && (int) $request->shift !== $shift
                ? null : $this->fingerprintFrom($data['calls']->where('shift', $shift)->values());
        }
        $data['snapshot_tokens'] = (object) $tokens;
        $data['reviews'] = $this->reviews($request, false, $tokens);
        unset($data['calls']);
        return $this->response($data);
    }

    public function dailyReport(Request $request)
    {
        $this->authorizeAccess($request, 'daily-report'); $this->validateFilters($request);
        $request->validate(['shift' => 'prohibited', 'category' => 'prohibited', 'status' => 'prohibited', 'agent_id' => 'prohibited']);
        $data = $this->reportData($request);
        foreach (['C' => 'complaints', 'FU' => 'followups'] as $category => $name) {
            $calls = $data['calls']->where('category', $category);
            $closed = $calls->where('status', 'closed')->count();
            $data[$name] = ['closed' => $closed, 'pending' => $calls->count() - $closed];
        }
        $total = $data['totals']['total'];
        $data['ratios'] = ['booking' => $total ? round($data['totals']['BK'] / $total * 100, 2) : 0,
            'general_information' => $total ? round($data['totals']['GI'] / $total * 100, 2) : 0];
        $data['seat_movements'] = $this->seatMovements($request);
        $data['seat_movements_note'] = 'ERP events on the selected date, not call tallies. Shifted seats use reschedule audit records. Recorded refunds require a cancelled booked ticket with a positive recorded refund; settlement is not verified. Legacy refunds without a recorded amount are excluded.';
        $data['snapshot_token'] = $this->fingerprintFrom($data['calls'], $data['seat_movements']);
        $data['review'] = $this->reviews($request, true, [0 => $data['snapshot_token']])->first();
        unset($data['calls']);
        return $this->response($data);
    }

    private function seatMovements(Request $request): array
    {
        $tickets = Scope::tickets($request)->select('tickets.id');
        $rows = [];
        foreach (['shifted' => ['ticket_reschedules', 'old_ticket_id'], 'refunded' => ['booking_cancels', 'ticket_id']] as $kind => [$table, $ticketField]) {
            $query = DB::table($table . ' as events')->join('tickets', 'tickets.id', '=', 'events.' . $ticketField)
                ->leftJoin('terminals', 'terminals.id', '=', 'tickets.terminal_id')
                ->where('events.company_id', $request->user()->company_id)->whereNull('events.deleted_at')
                ->whereIn('tickets.id', clone $tickets)->whereDate('events.created_at', $request->date);
            if (!Scope::allows($request->user(), 'call-center-supervise')) { $query->where('events.added_by', $request->user()->id); }
            if ($kind === 'refunded') { $query->where('events.type', 'booked')->where('tickets.type', 'canceled')->whereNotNull('tickets.deleted_at')->where('tickets.refund_amount', '>', 0); }
            $counts = $query->select('tickets.terminal_id', 'terminals.name as terminal_name')->selectRaw('COUNT(DISTINCT tickets.id) as seats')
                ->groupBy('tickets.terminal_id', 'terminals.name')->get();
            foreach ($counts as $count) {
                if (!isset($rows[$count->terminal_id])) { $rows[$count->terminal_id] = ['terminal_id' => $count->terminal_id, 'terminal_name' => $count->terminal_name, 'shifted' => 0, 'refunded' => 0]; }
                $rows[$count->terminal_id][$kind] = (int) $count->seats;
            }
        }
        ksort($rows, SORT_NUMERIC);
        return array_values($rows);
    }

    private function fingerprint(Request $request, int $shift = 0): string
    {
        $calls = Scope::calls($request, true)->where('call_date', $request->date)
            ->when($shift, function ($q) use ($shift) { $q->where('shift', $shift); })->orderBy('id')->get();
        return $this->fingerprintFrom($calls, $shift ? null : $this->seatMovements($request));
    }

    private function fingerprintFrom($calls, ?array $movements = null): string
    {
        $payload = $calls->map(function ($call) { return $call->getAttributes(); })->all();
        if ($movements !== null) { $payload[] = $movements; }
        return hash('sha256', json_encode($payload));
    }

    private function reviews(Request $request, bool $daily, ?array $snapshots = null)
    {
        // Agent-only totals cannot display a supervisor's certification of wider totals.
        if (!Scope::allows($request->user(), 'call-center-supervise')) { return collect(); }
        $query = DB::table('call_center_reviews')->where('company_id', $request->user()->company_id)
            ->where('terminal_id', Scope::terminal($request) ?? 0)->where('date', $request->date);
        $daily ? $query->where('shift', 0) : $query->where('shift', '>', 0);
        if (!$daily && $request->filled('shift')) { $query->where('shift', $request->shift); }
        return $query->orderBy('shift')->get()->map(function ($review) use ($request, $snapshots) {
            $review->reviewer = User::where('company_id', $request->user()->company_id)->find($review->reviewed_by, ['id', 'name']);
            $snapshot = $snapshots === null ? $this->fingerprint($request, (int) $review->shift) : ($snapshots[(int) $review->shift] ?? null);
            $review->stale = $snapshot === null || !hash_equals($review->snapshot_hash, $snapshot);
            unset($review->snapshot_hash);
            return $review;
        });
    }

    public function reviewShift(Request $request) { return $this->saveReview($request, false); }
    public function reviewDaily(Request $request) { return $this->saveReview($request, true); }

    private function saveReview(Request $request, bool $daily)
    {
        $this->authorizeAccess($request, 'review-reports');
        $this->authorizeAccess($request, 'supervise');
        $this->authorizeAccess($request, $daily ? 'daily-report' : 'shift-report');
        $request->validate(['date' => 'required|date_format:Y-m-d|before_or_equal:' . $this->now()->toDateString(),
            'shift' => [$daily ? 'nullable' : 'required', 'integer', 'between:1,3'], 'terminal_id' => 'nullable|integer|min:0',
            'remarks' => 'nullable|string|max:5000', 'next_day_actions' => 'nullable|string|max:5000',
            'snapshot_token' => 'required|string|size:64']);
        $terminal = Scope::terminal($request) ?? 0;
        if ($terminal) { abort_unless(Terminal::where('company_id', $request->user()->company_id)->where('id', $terminal)->exists(), 422, 'Unknown terminal.'); }
        $shift = $daily ? 0 : (int) $request->shift;
        abort_unless(hash_equals($this->fingerprint($request, $shift), $request->snapshot_token), 409, 'Report changed. Reload it before reviewing.');
        DB::table('call_center_reviews')->updateOrInsert(['company_id' => $request->user()->company_id, 'terminal_id' => $terminal, 'date' => $request->date, 'shift' => $shift], [
            'remarks' => $request->remarks, 'next_day_actions' => $daily ? $request->next_day_actions : null,
            'reviewed_by' => $request->user()->id, 'reviewed_at' => $this->now()->format('Y-m-d H:i:s'),
            'snapshot_hash' => $request->snapshot_token,
        ]);
        return $this->response($this->reviews($request, $daily)->first());
    }
}
