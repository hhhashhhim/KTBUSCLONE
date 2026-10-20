<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function authenticate($request, array $guards)
    {
        parent::authenticate($request, $guards);
        $user = $this->auth->user();
        if (($user instanceof \App\Models\PendingPassengerRegistration || $user instanceof \App\Models\PassengerAccount)
            && !$request->is('api/mobile/v1/*')) {
            abort(403, 'Passenger tokens are only valid for the mobile API.');
        }
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
