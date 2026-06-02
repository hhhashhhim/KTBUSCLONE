<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoyaltyCardHistoryController extends Controller
{
    public function customerCardHistory($customerId): JsonResponse
    {
        $customer = DB::table('customers')
            ->where('id', $customerId)
            ->first();

        $cardAssigns = DB::table('card_assigns')
            ->where('customer_id', $customerId)
            ->get();

        if (!$customer && $cardAssigns->isEmpty()) {
            return response()->json([
                'history' => [],
                'matching_customer_ids' => [],
            ]);
        }

        $matchingCustomerIds = $this->matchingCustomerIds(
            $this->identifierValues($customer, $cardAssigns, 'cnic', 'cnic'),
            $this->identifierValues($customer, $cardAssigns, 'contact', 'phone')
        );

        if (empty($matchingCustomerIds)) {
            return response()->json([
                'history' => [],
                'matching_customer_ids' => [],
            ]);
        }

        $history = DB::table('tickets')
            ->leftJoin('customers', 'customers.id', '=', 'tickets.customer_id')
            ->whereIn('tickets.customer_id', $matchingCustomerIds)
            ->where('tickets.discount_type', 'card')
            ->orderByDesc('tickets.id')
            ->select($this->historyColumns())
            ->get();

        return response()->json([
            'history' => $history,
            'matching_customer_ids' => $matchingCustomerIds,
        ]);
    }

    private function matchingCustomerIds(array $cnics, array $contacts): array
    {
        $cnics = $this->filledValues($cnics);
        $contacts = $this->filledValues($contacts);
        $cleanCnics = $this->cleanValues($cnics);
        $cleanContacts = $this->cleanValues($contacts);

        if (empty($cnics) && empty($contacts) && empty($cleanCnics) && empty($cleanContacts)) {
            return [];
        }

        return DB::table('customers')
            ->where(function ($query) use ($cnics, $contacts, $cleanCnics, $cleanContacts) {
                if (!empty($cnics)) {
                    $query->orWhereIn('customers.cnic', $cnics);
                }

                if (!empty($cleanCnics)) {
                    $query->orWhereIn(DB::raw("REPLACE(REPLACE(customers.cnic, '-', ''), ' ', '')"), $cleanCnics);
                }

                if (!empty($contacts)) {
                    $query->orWhereIn('customers.contact', $contacts);
                }

                if (!empty($cleanContacts)) {
                    $query->orWhereIn(DB::raw("REPLACE(REPLACE(customers.contact, '-', ''), ' ', '')"), $cleanContacts);
                }
            })
            ->pluck('customers.id')
            ->unique()
            ->values()
            ->all();
    }

    private function historyColumns(): array
    {
        return [
            'tickets.id',
            'tickets.customer_id',
            'customers.name as customer_name',
            'customers.cnic as customer_cnic',
            'customers.contact as customer_contact',
            $this->ticketColumn('booking_no'),
            $this->ticketColumn('invoice_id'),
            $this->ticketColumn('transaction_id'),
            $this->ticketColumn('schedule_date'),
            $this->ticketColumn('schedule_time'),
            $this->ticketColumn('seat_no'),
            $this->ticketColumn('seat_fare'),
            $this->ticketColumn('discount'),
            $this->ticketColumn('terminal_discount'),
            $this->ticketColumn('schedule_discount'),
            $this->ticketColumn('points_usage'),
            $this->ticketColumn('discount_type'),
            $this->ticketColumn('booked_time', 'time'),
            'tickets.created_at',
        ];
    }

    private function ticketColumn(string $column, ?string $fallbackColumn = null)
    {
        if (Schema::hasColumn('tickets', $column)) {
            return "tickets.{$column}";
        }

        if ($fallbackColumn && Schema::hasColumn('tickets', $fallbackColumn)) {
            return "tickets.{$fallbackColumn} as {$column}";
        }

        return DB::raw("NULL as {$column}");
    }

    private function filledValue(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function identifierValues($customer, $cardAssigns, string $customerColumn, string $cardAssignColumn): array
    {
        $values = [];

        if ($customer) {
            $values[] = $customer->{$customerColumn} ?? null;
        }

        foreach ($cardAssigns as $cardAssign) {
            $values[] = $cardAssign->{$cardAssignColumn} ?? null;
        }

        return $values;
    }

    private function filledValues(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => $this->filledValue($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function cleanValues(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => $this->digitsOnly($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function digitsOnly(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits === '' ? null : $digits;
    }
}
