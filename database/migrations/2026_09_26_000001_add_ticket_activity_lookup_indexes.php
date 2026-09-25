<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTicketActivityLookupIndexes extends Migration
{
    private function indexes(): array
    {
        return [
            ['tickets', ['company_id', 'invoice_id'], 'tickets_company_invoice_activity_idx'],
            ['booking_cancels', ['company_id', 'ticket_id'], 'booking_cancels_company_ticket_activity_idx'],
            ['ticket_reschedules', ['company_id', 'old_ticket_id'], 'reschedules_company_old_activity_idx'],
            ['ticket_reschedules', ['company_id', 'new_ticket_id'], 'reschedules_company_new_activity_idx'],
            ['ticket_advanced_bookeds', ['company_id', 'ticket_id'], 'advanced_company_ticket_activity_idx'],
        ];
    }

    public function up()
    {
        foreach ($this->indexes() as [$table, $columns, $name]) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
        }
    }

    public function down()
    {
        foreach (array_reverse($this->indexes()) as [$table, $columns, $name]) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
        }
    }
}
