<?php
namespace App\Services\Mobile;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MobilePushGateway
{
    public function configured(): bool
    {
        return (bool) config('mobile_notifications.push_enabled') && (bool) config('mobile_notifications.project_id')
            && is_readable((string) config('mobile_notifications.credentials_file'));
    }
    private function bearer(): string
    {
        $file = (string) config('mobile_notifications.credentials_file');
        $project = (string) config('mobile_notifications.project_id');
        $credentials = json_decode(file_get_contents($file), true);
        if (!$credentials || ($credentials['project_id'] ?? '') !== $project || empty($credentials['private_key']) || empty($credentials['client_email'])) {
            throw new RuntimeException('push_configuration_invalid');
        }
        $key = 'mobile-fcm-oauth:' . hash('sha256', $project . $credentials['private_key']);
        return Cache::remember($key, 3000, function () use ($credentials) {
            $encode = function ($value) { return rtrim(strtr(base64_encode($value), '+/', '-_'), '='); };
            $now = time();
            $jwt = $encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])) . '.' . $encode(json_encode([
                'iss' => $credentials['client_email'], 'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600]));
            if (!openssl_sign($jwt, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) throw new RuntimeException('push_signing_failed');
            $response = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt . '.' . $encode($signature)]);
            if (!$response->successful() || !$response->json('access_token')) throw new RuntimeException('push_auth_failed');
            return $response->json('access_token');
        });
    }
    public function send(string $token, int $notificationId): string
    {
        // Lock-screen content deliberately excludes passenger, journey and booking information.
        $response = Http::withToken($this->bearer())->timeout(15)->post('https://fcm.googleapis.com/v1/projects/'
            . rawurlencode(config('mobile_notifications.project_id')) . '/messages:send', ['message' => [
                'fid' => $token, 'notification' => ['title' => 'Kainat Travels', 'body' => 'You have a new update. Open the app to read it.'],
                'data' => ['notification_id' => (string) $notificationId],
                'android' => ['ttl' => '86400s', 'notification' => ['tag' => 'kt-notice-' . $notificationId]],
                'apns' => ['headers' => ['apns-collapse-id' => 'kt-notice-' . $notificationId, 'apns-expiration' => (string) (time() + 86400)]],
            ]]);
        if ($response->successful()) return 'accepted';
        foreach ((array) $response->json('error.details', []) as $detail) {
            if (($detail['errorCode'] ?? '') === 'UNREGISTERED') return 'unregistered';
        }
        // Do not log provider bodies: they can contain device tokens or private configuration.
        return in_array($response->status(), [400, 404], true) ? 'invalid_message' : 'retry';
    }
}
