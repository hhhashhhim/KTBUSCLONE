<?php

namespace App\Models\CallCenter;

use App\Models\User;
use App\Models\Terminal;
use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    protected $table = 'call_center_calls';
    protected $guarded = [];
    protected $appends = ['reference'];
    protected $casts = ['shift' => 'integer', 'seat_count' => 'integer'];

    public function getReferenceAttribute() { return 'CC-' . $this->id; }
    public function agent() { return $this->belongsTo(User::class, 'agent_id')->select(['id', 'name']); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to')->select(['id', 'name']); }
    public function resolver() { return $this->belongsTo(User::class, 'resolved_by')->select(['id', 'name']); }
    public function terminal() { return $this->belongsTo(Terminal::class)->select(['id', 'name']); }
}
