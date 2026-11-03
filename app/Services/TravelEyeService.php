<?php

namespace App\Services;

use App\Models\Hrm\Employee\Employee;
use App\Models\Schedule\TicketClosingMember;
use App\Models\Ticket;
use App\Models\TravelEyeApiLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TravelEyeService
{
    public function verifyTicket(int $ticketId): void
    {
        $ticket = Ticket::with([
            'terminal.city',
            'customer',
            'company',
            'departure_city',
            'destination_city',
            'bus',
        ])->find($ticketId);

        if (!$ticket || $ticket->type !== 'booked' || !$ticket->terminal) {
            return;
        }

        $terminal = $ticket->terminal;
        if ((int) $terminal->company_id !== (int) $ticket->company_id || !$this->terminalIsConfigured($terminal)) {
            return;
        }

        $endpoint = rtrim(config('services.travel_eye.base_url'), '/')
            . '/'
            . ltrim(config('services.travel_eye.verify_path'), '/');
        $payload = $this->buildVerifyPayload($ticket);
        $missingFields = $this->missingRequiredFields($payload);

        $apiLog = TravelEyeApiLog::firstOrNew(['ticket_id' => $ticket->id]);
        if ($apiLog->exists && $apiLog->status === 'success') {
            return;
        }

        $apiLog->fill([
            'terminal_id' => $terminal->id,
            'voucher' => $payload['voucher'],
            'endpoint' => $endpoint,
            'request_payload' => $this->safePayload($payload),
            'status' => $missingFields ? 'skipped' : 'pending',
            'attempts' => (int) $apiLog->attempts + 1,
            'last_attempt_at' => now(),
            'exception_message' => $missingFields
                ? 'Required Travel Eye fields are missing: ' . implode(', ', $missingFields)
                : null,
        ])->save();

        if ($missingFields) {
            Log::warning('Travel Eye verification skipped because required data is missing.', [
                'ticket_id' => $ticket->id,
                'terminal_id' => $terminal->id,
                'missing_fields' => $missingFields,
            ]);
            return;
        }

        try {
            // Match the successful Postman request: multipart form-data, not JSON.
            $request = Http::asMultipart()
                ->acceptJson()
                ->withOptions(['connect_timeout' => 5])
                ->timeout(max(1, (int) config('services.travel_eye.timeout', 15)));

            $response = $request->post($endpoint, $payload);
            $status = $response->json('status');
            $successful = $response->successful()
                && ($status === true || $status === 1 || (is_string($status) && in_array(strtolower(trim($status)), ['true', '1'], true)));

            $apiLog->update([
                'status' => $successful ? 'success' : 'failed',
                'http_status' => $response->status(),
                'response_payload' => $this->safeResponse($response->body(), $payload),
                'exception_message' => $successful ? null : ($response->successful()
                    ? 'Travel Eye did not confirm success. See the response payload.'
                    : 'Travel Eye returned HTTP ' . $response->status()),
            ]);

            if (!$successful) {
                Log::warning('Travel Eye verification request failed.', [
                    'ticket_id' => $ticket->id,
                    'terminal_id' => $terminal->id,
                    'http_status' => $response->status(),
                ]);
            }
        } catch (\Throwable $exception) {
            $apiLog->update([
                'status' => 'failed',
                'exception_message' => $this->safeResponse($exception->getMessage(), $payload),
            ]);

            Log::error('Travel Eye verification request encountered an exception.', [
                'ticket_id' => $ticket->id,
                'terminal_id' => $terminal->id,
                'exception' => get_class($exception),
            ]);
        }
    }

    private function terminalIsConfigured($terminal): bool
    {
        return !empty($terminal->smarteye_id)
            && !empty($terminal->smarteye_api);
    }

    private function buildVerifyPayload(Ticket $ticket): array
    {
        $terminal = $ticket->terminal;
        $customer = $ticket->customer;
        $driver = $this->driverFor($ticket);

        return [
            'usrName' => $terminal->smarteye_id,
            // The live Travel Eye endpoint authenticates with the value issued
            // in the Smarteye API column, not the web-account password.
            'usrPass' => $terminal->smarteye_api,
            'terminal' => optional($terminal->city)->name,
            'voucher' => $this->voucherFor($ticket),
            'from' => optional($ticket->departure_city)->name,
            'to' => optional($ticket->destination_city)->name,
            'departDateTime' => $this->departureDateTime($ticket),
            'busRegno' => optional($ticket->bus)->bus_number,
            'driverName' => optional($driver)->name,
            'driverPhone' => optional($driver)->contact,
            'passengerName' => optional($customer)->name,
            'cnic' => $this->digitsOnly(optional($customer)->cnic),
            'passengerPhone' => $this->digitsOnly(optional($customer)->contact),
            'seatno' => (string) $ticket->seat_no,
            'companyName' => optional($ticket->company)->name,
            'terminalName' => $terminal->name,
        ];
    }

    private function driverFor(Ticket $ticket)
    {
        if (!$ticket->ticket_closing_id) {
            return null;
        }

        $driverId = TicketClosingMember::where([
            'company_id' => $ticket->company_id,
            'ticket_closing_id' => $ticket->ticket_closing_id,
            'type' => 1,
        ])->value('user_id');

        return $driverId
            ? Employee::where('company_id', $ticket->company_id)->find($driverId)
            : null;
    }

    private function voucherFor(Ticket $ticket): string
    {
        // One remote record per ticket, even when an invoice contains multiple
        // passengers or the same seat is sold on two non-overlapping segments.
        // Stable on retries: never generate a random/new voucher for an attempt.
        return sprintf('KT-C%d-T%d', $ticket->company_id, $ticket->id);
    }

    private function departureDateTime(Ticket $ticket): ?string
    {
        $date = $ticket->date ?: $ticket->schedule_date;
        $time = $ticket->schedule_time ?: $ticket->schedule_time_exact;

        if (!$date || !$time) {
            return null;
        }

        try {
            return Carbon::parse($date . ' ' . $time)->format('Y-m-d H:i:s');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function missingRequiredFields(array $payload): array
    {
        $required = [
            'usrName',
            'usrPass',
            'voucher',
            'from',
            'to',
            'departDateTime',
            'passengerName',
            'cnic',
            'seatno',
        ];

        return array_values(array_filter($required, function ($field) use ($payload) {
            return !isset($payload[$field]) || trim((string) $payload[$field]) === '';
        }));
    }

    private function safePayload(array $payload): array
    {
        unset($payload['usrName'], $payload['usrPass']);

        return $payload;
    }

    private function digitsOnly($value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?: '';
    }

    private function limitResponse(string $value): string
    {
        return mb_substr($value, 0, 20000);
    }

    private function safeResponse(string $value, array $payload): string
    {
        foreach (['usrName', 'usrPass'] as $field) {
            $secret = (string) ($payload[$field] ?? '');
            if ($secret !== '') {
                $value = str_replace([$secret, rawurlencode($secret), urlencode($secret)], '[redacted]', $value);
            }
        }
        return $this->limitResponse($value);
    }
}
