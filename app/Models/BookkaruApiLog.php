<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookkaruApiLog extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $appends = ['current_status'];

    protected $casts = [
        'invoice_id' => 'integer',
        'normalized_invoice_id' => 'integer',
        'approved_by' => 'integer',
        'rejected_by' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'seat_numbers' => 'array',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'success' => 'boolean',
        'duplicate' => 'boolean',
        'unauthorized' => 'boolean',
    ];

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by', 'id');
    }

    public function getCurrentStatusAttribute(): string
    {
        if ($this->status) {
            return $this->status;
        }

        if ($this->cancellation_status === 'cancelled') {
            return 'cancelled';
        }

        if ($this->approval_status === 'rejected') {
            return 'rejected';
        }

        if ($this->approval_status === 'approved' && $this->cancellation_status === 'failed') {
            return 'failed';
        }

        if ($this->approval_status === 'approved') {
            return 'approved';
        }

        return 'pending';
    }
}
