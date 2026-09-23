<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCompanyToTerminalCancellationRequests extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('bookkaru_api_logs', 'company_id')) {
            Schema::table('bookkaru_api_logs', function (Blueprint $table) {
                // No default for new requests: ownership must come from trusted data.
                $table->unsignedBigInteger('company_id')->nullable();
                $table->index(['company_id', 'status', 'id'], 'cancellation_company_status_id');
            });
        }

        // All historical requests belong to company 1, as confirmed by the owner.
        DB::table('bookkaru_api_logs')->whereNull('company_id')->update(['company_id' => 1]);

        // Older requests may only reference an invoice/seat. Resolve them once,
        // not on every page view. Keep approval's original ticket_ids untouched.
        DB::table('bookkaru_api_logs')->orderBy('id')->chunkById(100, function ($logs) {
            $legacy = collect();
            foreach ($logs as $log) {
                $payload = json_decode($log->request_payload ?? '', true) ?: [];
                $response = json_decode($log->response_payload ?? '', true) ?: [];
                $ids = $payload['ticket_ids'] ?? [];
                $ids = $ids ?: collect(data_get($response, 'data.refunds', []))->pluck('ticket_id')->filter()->all();
                if ($ids || array_key_exists('display_ticket_ids', $payload)) {
                    continue;
                }
                $log->payload = $payload;
                $legacy->push($log);
            }
            foreach ($legacy->groupBy('company_id') as $companyId => $companyLogs) {
                $invoiceIds = $companyLogs->map(fn ($log) => $log->normalized_invoice_id ?: $log->invoice_id)->filter()->unique()->all();
                $tickets = $invoiceIds ? DB::table('tickets')->where('company_id', $companyId)
                    ->whereIn('invoice_id', $invoiceIds)
                    ->orderByRaw('deleted_at IS NULL DESC')->orderByDesc('id')
                    ->get(['id', 'invoice_id', 'seat_no'])->groupBy('invoice_id') : collect();
                foreach ($companyLogs as $log) {
                    $seats = array_map(fn ($seat) => trim((string) $seat), json_decode($log->seat_numbers ?? '[]', true) ?: []);
                    $ids = $tickets->get($log->normalized_invoice_id ?: $log->invoice_id, collect())
                        ->filter(fn ($ticket) => in_array(trim((string) $ticket->seat_no), $seats, true))
                        ->unique('seat_no')->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
                    $payload = array_merge($log->payload, ['display_ticket_ids' => $ids]);
                    // Do not alter updated_at: it is used as a decision-date fallback.
                    DB::table('bookkaru_api_logs')->where('id', $log->id)->update(['request_payload' => json_encode($payload)]);
                }
            }
        });
    }

    public function down()
    {
        Schema::table('bookkaru_api_logs', function (Blueprint $table) {
            $table->dropIndex('cancellation_company_status_id');
            $table->dropColumn('company_id');
        });
    }
}
