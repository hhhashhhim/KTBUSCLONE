<?php

namespace App\Services\Mobile;

use App\Models\MobilePayment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MobilePaymentGateway
{
    public function checkoutHostAllowed(): bool
    {
        $url = (string) config('app.url');
        if (parse_url($url, PHP_URL_SCHEME) === 'https') { return true; }
        return app()->environment(['local', 'testing'])
            && config('mobile_payments.environment') === 'sandbox'
            && parse_url($url, PHP_URL_SCHEME) === 'http'
            && in_array(parse_url($url, PHP_URL_HOST), ['127.0.0.1', 'localhost', '10.0.2.2'], true);
    }

    public function configured(string $method): bool
    {
        $required = [
            'jazzcash' => ['merchant_id', 'password', 'integrity_salt', 'checkout_url', 'status_url'],
            'bank_alfalah' => ['merchant_id', 'store_id', 'merchant_hash', 'username', 'password', 'key1', 'key2', 'base_url'],
        ];
        if (!in_array(config('mobile_payments.environment'), ['sandbox', 'live'], true) || !isset($required[$method])) { return false; }
        foreach ($required[$method] as $key) {
            if (!is_string(config("mobile_payments.$method.$key")) || trim(config("mobile_payments.$method.$key")) === '') {
                return false;
            }
        }
        try {
            foreach ($method === 'jazzcash' ? ['checkout_url', 'status_url'] : ['base_url'] as $key) {
                $this->endpoint($method, $key);
            }
        } catch (RuntimeException $e) { return false; }
        return $method !== 'bank_alfalah' || (
            strlen(config('mobile_payments.bank_alfalah.key1')) === 16 &&
            strlen(config('mobile_payments.bank_alfalah.key2')) === 16
        );
    }

    public function assertTransactionsAllowed(): void
    {
        if (config('mobile_payments.preview_only')) {
            throw new RuntimeException('Payment preview only. No booking or payment will be submitted.', 409);
        }
    }

    public function checkout(MobilePayment $payment): array
    {
        $this->assertTransactionsAllowed();
        if ($payment->environment !== config('mobile_payments.environment') || !$this->configured($payment->method)) {
            throw new RuntimeException('This payment gateway is not configured.', 503);
        }
        $returnUrl = route('mobile.payments.return', ['payment' => $payment->public_id]);
        $config = config('mobile_payments.' . $payment->method);
        if ($payment->method === 'jazzcash') {
            $fields = [
                'pp_Version' => '1.1', 'pp_TxnType' => 'MWALLET', 'pp_Language' => 'EN',
                'pp_MerchantID' => $config['merchant_id'], 'pp_Password' => $config['password'],
                'pp_TxnRefNo' => $payment->transaction_reference,
                'pp_Amount' => (string) $payment->amount_minor, 'pp_TxnCurrency' => $payment->currency,
                'pp_TxnDateTime' => $payment->created_at->copy()->timezone('Asia/Karachi')->format('YmdHis'),
                'pp_BillReference' => (string) $payment->invoice_id, 'pp_Description' => 'Kainat Travels ticket',
                'pp_TxnExpiryDateTime' => $payment->expires_at->copy()->timezone('Asia/Karachi')->format('YmdHis'),
                'pp_ReturnURL' => $returnUrl, 'pp_SubMerchantID' => '', 'pp_BankID' => '', 'pp_ProductID' => '',
                'ppmpf_1' => '', 'ppmpf_2' => '', 'ppmpf_3' => '', 'ppmpf_4' => '', 'ppmpf_5' => '',
            ];
            $fields['pp_SecureHash'] = $this->jazzHash($fields);
            return ['action' => $this->endpoint('jazzcash', 'checkout_url'), 'fields' => $fields];
        }

        // Preserve the merchant template's field order when encrypting the map.
        $handshake = [
            'HS_RequestHash' => '', 'HS_IsRedirectionRequest' => '0', 'HS_ChannelId' => '1001',
            'HS_ReturnURL' => $returnUrl, 'HS_MerchantId' => $config['merchant_id'],
            'HS_StoreId' => $config['store_id'], 'HS_MerchantHash' => $config['merchant_hash'],
            'HS_MerchantUsername' => $config['username'], 'HS_MerchantPassword' => $config['password'],
            'HS_TransactionReferenceNumber' => $payment->transaction_reference,
        ];
        $handshake['HS_RequestHash'] = $this->alfalahHash($handshake);
        $response = $this->decode($this->http()->asForm()->post(
            $this->endpoint('bank_alfalah', 'base_url') . '/HS/HS/HS', $handshake
        )->throw()->json());
        if (!in_array($response['success'] ?? null, [true, 'true'], true) || empty($response['AuthToken'])) {
            throw new RuntimeException('Bank Alfalah could not start checkout. Please try again.', 502);
        }
        $fields = [
            'AuthToken' => $response['AuthToken'], 'RequestHash' => '', 'ChannelId' => '1001',
            'Currency' => $payment->currency, 'IsBIN' => '0', 'ReturnURL' => $returnUrl,
            'MerchantId' => $config['merchant_id'], 'StoreId' => $config['store_id'],
            'MerchantHash' => $config['merchant_hash'], 'MerchantUsername' => $config['username'],
            'MerchantPassword' => $config['password'], 'TransactionTypeId' => '3',
            'TransactionReferenceNumber' => $payment->transaction_reference,
            'TransactionAmount' => number_format($payment->amount_minor / 100, 2, '.', ''),
        ];
        $fields['RequestHash'] = $this->alfalahHash($fields);
        return ['action' => $this->endpoint('bank_alfalah', 'base_url') . '/SSO/SSO/SSO', 'fields' => $fields];
    }

    // A browser redirect is only a signal to inquire. Never accept its amount or success flag.
    public function paid(MobilePayment $payment): bool
    {
        $this->assertTransactionsAllowed();
        if ($payment->environment !== config('mobile_payments.environment') || !$this->configured($payment->method)) {
            throw new RuntimeException('Payment verification is temporarily unavailable.', 503);
        }
        $config = config('mobile_payments.' . $payment->method);
        if ($payment->method === 'jazzcash') {
            $fields = ['pp_MerchantID' => $config['merchant_id'], 'pp_Password' => $config['password'],
                'pp_TxnRefNo' => $payment->transaction_reference];
            $fields['pp_SecureHash'] = $this->jazzHash($fields);
            $data = $this->decode($this->http()->post($this->endpoint('jazzcash', 'status_url'), $fields)->throw()->json());
            // Status-inquiry responses on the existing orchestrator use these detailed fields.
            $detail = (string) ($data['pp_PaymentResponseCode'] ?? '');
            $status = (string) ($data['pp_Status'] ?? '');
            $paid = ($data['pp_ResponseCode'] ?? '') === '000'
                && ($detail === '121' || $status === 'Completed')
                && ($status === '' || $status === 'Completed')
                && ($detail === '' || in_array($detail, ['121', '000'], true));
            if (!$paid) { return false; }
            if (isset($data['pp_SecureHash']) && !hash_equals($this->jazzHash($data), strtolower((string) $data['pp_SecureHash']))) {
                throw new RuntimeException('Payment verification failed.', 502);
            }
            $matches = (string) ($data['pp_TxnRefNo'] ?? '') === $payment->transaction_reference
                && ctype_digit((string) ($data['pp_Amount'] ?? ''))
                && (int) $data['pp_Amount'] === $payment->amount_minor;
            foreach (['pp_MerchantID' => $config['merchant_id'], 'pp_TxnCurrency' => $payment->currency,
                'pp_BillReference' => (string) $payment->invoice_id] as $key => $expected) {
                if (isset($data[$key]) && (string) $data[$key] !== $expected) { $matches = false; }
            }
        } else {
            $url = $this->endpoint('bank_alfalah', 'base_url') . '/HS/api/IPN/OrderStatus/'
                . rawurlencode($config['merchant_id']) . '/' . rawurlencode($config['store_id'])
                . '/' . rawurlencode($payment->transaction_reference);
            $data = $this->decode($this->http()->get($url)->throw()->json());
            if (($data['ResponseCode'] ?? '') !== '00' || ($data['TransactionStatus'] ?? '') !== 'Paid') { return false; }
            $matches = (string) ($data['TransactionReferenceNumber'] ?? '') === $payment->transaction_reference
                && (string) ($data['MerchantId'] ?? '') === $config['merchant_id']
                && (string) ($data['StoreId'] ?? '') === $config['store_id']
                && preg_match('/^\d+(\.\d{1,2})?$/', (string) ($data['TransactionAmount'] ?? ''))
                && (int) round((float) $data['TransactionAmount'] * 100) === $payment->amount_minor;
        }
        if (!$matches) { throw new RuntimeException('The gateway payment does not match this booking.', 409); }
        return true;
    }

    public function jazzHash(array $fields): string
    {
        unset($fields['pp_SecureHash']);
        ksort($fields, SORT_STRING);
        $salt = (string) config('mobile_payments.jazzcash.integrity_salt');
        $values = array_filter($fields, function ($value, $key) {
            return strpos($key, 'pp') === 0 && is_scalar($value) && (string) $value !== '';
        }, ARRAY_FILTER_USE_BOTH);
        return hash_hmac('sha256', $salt . (count($values) ? '&' . implode('&', $values) : ''), $salt);
    }

    private function alfalahHash(array $fields): string
    {
        $parts = [];
        foreach ($fields as $key => $value) { $parts[] = $key . '=' . $value; }
        $encrypted = openssl_encrypt(implode('&', $parts), 'AES-128-CBC',
            config('mobile_payments.bank_alfalah.key1'), OPENSSL_RAW_DATA, config('mobile_payments.bank_alfalah.key2'));
        if ($encrypted === false) { throw new RuntimeException('Could not prepare checkout.', 503); }
        return base64_encode($encrypted);
    }

    private function endpoint(string $method, string $key): string
    {
        $url = rtrim((string) config("mobile_payments.$method.$key"), '/');
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $sandbox = config('mobile_payments.environment') === 'sandbox';
        $allowed = $method === 'jazzcash'
            ? ($sandbox ? ['sandbox.jazzcash.com.pk'] : ['payments.jazzcash.com.pk', 'onlinepayments.jazzcash.com.pk'])
            : ($sandbox ? ['sandbox.bankalfalah.com'] : ['payments.bankalfalah.com']);
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || !in_array($host, $allowed, true)
            || parse_url($url, PHP_URL_USER) || parse_url($url, PHP_URL_PASS)
            || parse_url($url, PHP_URL_PORT) || parse_url($url, PHP_URL_FRAGMENT)) {
            throw new RuntimeException('Invalid payment gateway URL.', 503);
        }
        return $url;
    }

    private function http()
    {
        return Http::timeout(15)->withOptions(['connect_timeout' => 5, 'allow_redirects' => false]);
    }

    private function decode($data): array
    {
        if (is_string($data)) { $data = json_decode($data, true); }
        if (!is_array($data)) { throw new RuntimeException('The payment gateway returned an invalid response.', 502); }
        return $data;
    }
}
