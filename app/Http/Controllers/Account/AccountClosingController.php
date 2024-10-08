<?php

namespace App\Http\Controllers\Account;

use App\Models\Account\Account;
use App\Models\Account\AccountGroup;
use App\Models\Account\AccountHead;
use App\Models\Account\Bank;
use App\Models\Account\Cash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Routing\Controller as BaseController;

class AccountClosingController extends BaseController
{

    public function accountClosingUpdate(Request $request)
    {
        return 'helo';
    }
}
