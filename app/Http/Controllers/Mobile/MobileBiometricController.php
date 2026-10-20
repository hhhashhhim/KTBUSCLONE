<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mobile\Concerns\RespondsWithMobileApi;
use App\Http\Resources\Mobile\PassengerResource;
use App\Models\PassengerAccount;
use App\Services\Mobile\MobileBiometricSessions;
use Illuminate\Http\Request;
use RuntimeException;

class MobileBiometricController extends Controller
{
    use RespondsWithMobileApi;

    public function enroll(Request $request, MobileBiometricSessions $sessions)
    {
        $request->validate(['password' => 'required|string|max:1024']);
        if (!$request->user() instanceof PassengerAccount) { return $this->failure('Passenger sign-in required.', [], 403); }
        try {
            return $this->success($sessions->enroll($request->user(), $request->user()->currentAccessToken(), $request->password),
                'Quick login enabled.');
        } catch (RuntimeException $e) { return $this->failure($e->getMessage(), [], $this->exceptionStatus($e)); }
    }

    public function renew(Request $request, MobileBiometricSessions $sessions)
    {
        $request->validate(['refresh_token' => ['required', 'string', 'regex:/^[a-f0-9]{96}$/']]);
        try {
            $data = $sessions->renew($request->refresh_token);
            $data['passenger'] = (new PassengerResource($data['passenger']))->resolve();
            return $this->success($data, 'Signed in successfully.');
        } catch (RuntimeException $e) { return $this->failure($e->getMessage(), [], $this->exceptionStatus($e)); }
    }

    public function disable(Request $request, MobileBiometricSessions $sessions)
    {
        if (!$request->user() instanceof PassengerAccount) { return $this->failure('Passenger sign-in required.', [], 403); }
        try {
            $sessions->disable($request->user(), $request->user()->currentAccessToken());
            return $this->success(null, 'Quick login disabled.');
        } catch (RuntimeException $e) { return $this->failure($e->getMessage(), [], $this->exceptionStatus($e)); }
    }
}
