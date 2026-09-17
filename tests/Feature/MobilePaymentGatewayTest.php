<?php

namespace Tests\Feature;

use App\Models\MobilePayment;
use App\Services\Mobile\MobilePaymentGateway;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class MobilePaymentGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('mobile_payments.preview_only', false);
        config()->set('mobile_payments.environment', 'sandbox');
        config()->set('mobile_payments.jazzcash', [
            'merchant_id' => 'test-merchant', 'password' => 'test-password', 'integrity_salt' => 'test-salt',
            'checkout_url' => 'https://sandbox.jazzcash.com.pk/merchantform',
            'status_url' => 'https://sandbox.jazzcash.com.pk/status',
        ]);
        config()->set('mobile_payments.bank_alfalah', [
            'merchant_id' => '123', 'store_id' => '000456', 'merchant_hash' => 'test-hash',
            'username' => 'test-user', 'password' => 'test-password',
            'key1' => '1234567890123456', 'key2' => 'abcdefghijklmnop',
            'base_url' => 'https://sandbox.bankalfalah.com',
        ]);
    }

    public function test_preview_blocks_all_gateway_requests_even_with_configured_credentials()
    {
        Http::fake();
        config()->set('mobile_payments.preview_only', true);
        $gateway = new MobilePaymentGateway();
        foreach (['jazzcash', 'bank_alfalah'] as $method) {
            foreach (['checkout', 'paid'] as $operation) {
                try { $gateway->$operation($this->payment($method)); $this->fail('Preview must block gateway use'); }
                catch (RuntimeException $e) { $this->assertSame(409, $e->getCode()); }
            }
        }
        Http::assertNothingSent();
    }

    public function test_missing_credentials_or_untrusted_gateway_hosts_disable_checkout()
    {
        $gateway = new MobilePaymentGateway();
        $this->assertTrue($gateway->configured('jazzcash'));
        config()->set('mobile_payments.jazzcash.status_url', 'https://sandbox.jazzcash.com.pk.attacker.test/status');
        $this->assertFalse($gateway->configured('jazzcash'));
        config()->set('mobile_payments.bank_alfalah.key1', 'invalid');
        $this->assertFalse($gateway->configured('bank_alfalah'));
        $this->assertFalse($gateway->configured('easypaisa'));
    }

    public function test_jazzcash_wallet_checkout_includes_signed_bank_and_product_fields()
    {
        Http::fake();
        $gateway = new MobilePaymentGateway();
        $form = $gateway->checkout($this->payment('jazzcash'));
        $fields = $form['fields'];
        $this->assertSame('https://sandbox.jazzcash.com.pk/merchantform', $form['action']);
        $this->assertSame('MWALLET', $fields['pp_TxnType']);
        $this->assertSame('TBANK', $fields['pp_BankID']);
        $this->assertSame('RETL', $fields['pp_ProductID']);
        $this->assertSame('250000', $fields['pp_Amount']);
        $this->assertSame('test-merchant', $fields['pp_MerchantID']);
        $this->assertSame(route('mobile.payments.return', ['payment' => 'payment-test']), $fields['pp_ReturnURL']);
        $this->assertSame($gateway->jazzHash($fields), $fields['pp_SecureHash']);
        // Missing routing fields must also change the signature: they cannot be
        // injected into the HTML after signing the incomplete request.
        $incomplete = array_merge($fields, ['pp_BankID' => '', 'pp_ProductID' => '']);
        $this->assertNotSame($gateway->jazzHash($incomplete), $fields['pp_SecureHash']);
        Http::assertNothingSent();
    }

    public function test_jazzcash_status_inquiry_uses_stored_reference_and_verifies_amount()
    {
        Http::fake(['*' => Http::response($this->jazzResponse())]);
        $this->assertTrue((new MobilePaymentGateway())->paid($this->payment('jazzcash')));
        Http::assertSent(function ($request) {
            return $request->url() === 'https://sandbox.jazzcash.com.pk/status'
                && $request['pp_TxnRefNo'] === 'TTEST123' && strlen($request['pp_SecureHash']) === 64;
        });
    }

    /** @dataProvider jazzMismatches */
    public function test_jazzcash_mismatched_payment_is_never_accepted(array $override)
    {
        Http::fake(['*' => Http::response(array_merge($this->jazzResponse(), $override))]);
        $this->expectException(RuntimeException::class);
        (new MobilePaymentGateway())->paid($this->payment('jazzcash'));
    }

    public static function jazzMismatches(): array
    {
        return [[['pp_Amount' => '249999']], [['pp_TxnRefNo' => 'OTHER']],
            [['pp_MerchantID' => 'other-merchant']], [['pp_TxnCurrency' => 'USD']],
            [['pp_BillReference' => '999']], [['pp_SecureHash' => str_repeat('0', 64)]]];
    }

    public function test_jazzcash_gateway_success_without_completed_payment_stays_pending()
    {
        Http::fake(['*' => Http::response(['pp_ResponseCode' => '000', 'pp_Status' => 'Pending', 'pp_PaymentResponseCode' => '124'])]);
        $this->assertFalse((new MobilePaymentGateway())->paid($this->payment('jazzcash')));
    }

    public function test_alfalah_decodes_double_encoded_response_and_checks_order_amount_and_merchant()
    {
        Http::fake(['*' => Http::response(json_encode(json_encode($this->alfalahResponse())))]);
        $this->assertTrue((new MobilePaymentGateway())->paid($this->payment('bank_alfalah')));
        Http::assertSent(function ($request) {
            return $request->url() === 'https://sandbox.bankalfalah.com/HS/api/IPN/OrderStatus/123/000456/TTEST123';
        });
    }

    /** @dataProvider alfalahMismatches */
    public function test_alfalah_mismatched_payment_is_never_accepted(array $override)
    {
        Http::fake(['*' => Http::response(array_merge($this->alfalahResponse(), $override))]);
        $this->expectException(RuntimeException::class);
        (new MobilePaymentGateway())->paid($this->payment('bank_alfalah'));
    }

    public static function alfalahMismatches(): array
    {
        return [[['TransactionAmount' => '2499.99']], [['TransactionAmount' => '2500evil']],
            [['TransactionReferenceNumber' => 'OTHER']], [['MerchantId' => '999']], [['StoreId' => '999']]];
    }

    public function test_alfalah_handshake_produces_a_server_signed_card_checkout()
    {
        Http::fake(['*' => Http::response(['success' => 'true', 'AuthToken' => 'test-token', 'ReturnURL' => 'https://attacker.test'])]);
        $form = (new MobilePaymentGateway())->checkout($this->payment('bank_alfalah'));
        $this->assertSame('https://sandbox.bankalfalah.com/SSO/SSO/SSO', $form['action']);
        $this->assertSame('2500.00', $form['fields']['TransactionAmount']);
        $this->assertSame('3', $form['fields']['TransactionTypeId']);
        $this->assertStringNotContainsString('attacker.test', $form['fields']['ReturnURL']);
        $plaintext = openssl_decrypt(base64_decode($form['fields']['RequestHash']), 'AES-128-CBC',
            '1234567890123456', OPENSSL_RAW_DATA, 'abcdefghijklmnop');
        $this->assertStringContainsString('TransactionReferenceNumber=TTEST123&TransactionAmount=2500.00', $plaintext);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://sandbox.bankalfalah.com/HS/HS/HS'
                && $request['HS_TransactionReferenceNumber'] === 'TTEST123';
        });
    }

    public function test_conflicting_jazzcash_status_is_not_confirmed()
    {
        Http::fake(['*' => Http::response(array_merge($this->jazzResponse(), ['pp_Status' => 'Failed']))]);
        $this->assertFalse((new MobilePaymentGateway())->paid($this->payment('jazzcash')));
    }

    public function test_sandbox_mode_rejects_live_gateway_hosts()
    {
        config()->set('mobile_payments.jazzcash.checkout_url', 'https://onlinepayments.jazzcash.com.pk/merchantform');
        config()->set('mobile_payments.bank_alfalah.base_url', 'https://payments.bankalfalah.com');
        $gateway = new MobilePaymentGateway();
        $this->assertFalse($gateway->configured('jazzcash'));
        $this->assertFalse($gateway->configured('bank_alfalah'));
    }

    public function test_payment_from_another_environment_cannot_contact_a_gateway()
    {
        Http::fake();
        $payment = $this->payment('jazzcash');
        $payment->environment = 'live';
        try {
            (new MobilePaymentGateway())->paid($payment);
            $this->fail('A live payment was accepted in sandbox mode.');
        } catch (RuntimeException $e) {
            Http::assertNothingSent();
        }
    }

    public function test_http_checkout_is_allowed_only_for_local_sandbox()
    {
        $gateway = new MobilePaymentGateway();
        config()->set('app.url', 'http://10.0.2.2:8000');
        $this->assertTrue($gateway->checkoutHostAllowed());
        config()->set('mobile_payments.environment', 'live');
        $this->assertFalse($gateway->checkoutHostAllowed());
        config()->set('mobile_payments.environment', 'sandbox');
        config()->set('app.url', 'http://example.com');
        $this->assertFalse($gateway->checkoutHostAllowed());
    }

    private function payment(string $method): MobilePayment
    {
        return new MobilePayment(['environment' => 'sandbox', 'public_id' => 'payment-test', 'invoice_id' => 42, 'method' => $method,
            'transaction_reference' => 'TTEST123', 'amount_minor' => 250000, 'currency' => 'PKR',
            'created_at' => Carbon::now(), 'expires_at' => Carbon::now()->addMinutes(10)]);
    }

    private function jazzResponse(): array
    {
        return ['pp_ResponseCode' => '000', 'pp_PaymentResponseCode' => '121', 'pp_Status' => 'Completed',
            'pp_TxnRefNo' => 'TTEST123', 'pp_Amount' => '250000', 'pp_MerchantID' => 'test-merchant',
            'pp_TxnCurrency' => 'PKR', 'pp_BillReference' => '42'];
    }

    private function alfalahResponse(): array
    {
        return ['ResponseCode' => '00', 'TransactionStatus' => 'Paid', 'TransactionReferenceNumber' => 'TTEST123',
            'TransactionAmount' => '2500.00', 'MerchantId' => '123', 'StoreId' => '000456'];
    }
}
