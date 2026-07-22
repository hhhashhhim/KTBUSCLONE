<?php

namespace App\Models\Setting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketLabelSetting extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function surchargeLabelForCompany($companyId): string
    {
        $label = static::where('company_id', $companyId)->value('surcharge_label');

        return filled($label) ? trim($label) : 'Surcharge';
    }
}
