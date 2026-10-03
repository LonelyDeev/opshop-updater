<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Shetabit\Multipay\Abstracts\Driver;
use Shetabit\Multipay\Contracts\ReceiptInterface;
use Shetabit\Multipay\Exceptions\PurchaseFailedException;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Invoice;
use Shetabit\Multipay\Receipt;
use Shetabit\Multipay\RedirectionForm;
use Shetabit\Multipay\Request;

/**
 * درایور سامان SEP — نسخه REST (بدون SOAP)
 *
 * چرا؟ درگاه قدیمی سامان (payment.aspx) به SoapClient و WSDL وابسته است که
 * روی بسیاری از هاست‌های اشتراکی با خطای «Couldn't load from …WSDL» شکست
 * می‌خورد. سامان SEP پنل «آسان پرداخت» خود REST توکنی دارد:
 *   ۱) POST {apiGetToken}       → دریافت Token
 *   ۲) ریدایرکت به {apiPaymentUrl} با Token + RedirectUrl
 *   ۳) POST {apiVerificationUrl} → تأیید تراکنش با Token + RefNum
 *
 * تنظیمات (config/payment.php → drivers.sep):
 *   terminalId, apiGetToken, apiPaymentUrl, apiVerificationUrl, callbackUrl
 */
class SepRest extends Driver
{
    public function __construct(Invoice $invoice, $settings)
    {
        $this->invoice($invoice);
        $this->settings = (object) $settings;
    }

    /* ---------------------------------------------------------------- */

    public function purchase()
    {
        $fields = [
            'action'      => 'token',
            'TerminalId'  => (int) $this->settings->terminalId,
            'Amount'      => $this->invoice->getAmount() * 10, // ریال
            'ResNum'      => (string) $this->invoice->getUuid(),
            'RedirectUrl' => (string) $this->settings->callbackUrl,
            'CellNumber'  => (string) ($this->invoice->getDetails()['mobile'] ?? ''),
        ];

        try {
            $response = Http::asJson()
                ->timeout(30)
                ->connectTimeout(15)
                ->post($this->settings->apiGetToken, $fields);
        } catch (\Throwable $e) {
            throw new PurchaseFailedException('ارتباط با درگاه سامان برقرار نشد: ' . $e->getMessage());
        }

        $json = $response->json();

        $ok    = (bool) (data_get($json, 'StatusIsSuccessful', false));
        $token = (string) (data_get($json, 'Token', ''));

        if (!$ok || $token === '') {
            $msg = data_get($json, 'Message')
                ?? data_get($json, 'errorDesc')
                ?? ($response->successful() ? 'پاسخ نامعتبر از درگاه سامان' : 'خطای HTTP ' . $response->status());

            throw new PurchaseFailedException('دریافت توکن پرداخت سامان ناموفق بود: ' . $msg);
        }

        $this->invoice->transactionId($token);

        return $this->invoice->getTransactionId();
    }

    public function pay(): RedirectionForm
    {
        return $this->redirectWithForm(
            $this->settings->apiPaymentUrl,
            [
                'Token'       => $this->invoice->getTransactionId(),
                'RedirectUrl' => (string) $this->settings->callbackUrl,
            ],
            'POST'
        );
    }

    public function verify(): ReceiptInterface
    {
        $token  = (string) ($this->invoice->getTransactionId() ?: Request::input('Token'));
        $refNum = (string) Request::input('RefNum', '');

        if ($token === '') {
            throw new InvalidPaymentException('توکن تراکنش سامان یافت نشد.');
        }

        try {
            $response = Http::asJson()
                ->timeout(30)
                ->connectTimeout(15)
                ->post($this->settings->apiVerificationUrl, [
                    'Token'  => $token,
                    'RefNum' => $refNum,
                ]);
        } catch (\Throwable $e) {
            throw new InvalidPaymentException('ارتباط با سرور تأیید سامان برقرار نشد: ' . $e->getMessage());
        }

        $json = $response->json();

        if (!data_get($json, 'StatusIsSuccessful', false)) {
            $msg = data_get($json, 'errorDesc')
                ?? data_get($json, 'Message')
                ?? 'تأیید تراکنش سامان ناموفق بود.';

            throw new InvalidPaymentException($msg);
        }

        // اگر درگاه مبلغ تأییدشده را برگردانده، تطابق مبلغ را چک می‌کنیم (ریال)
        $verifiedAmount = (int) (data_get($json, 'data.Amount', data_get($json, 'Amount', 0)));
        if ($verifiedAmount > 0 && $verifiedAmount !== $this->invoice->getAmount() * 10) {
            throw new InvalidPaymentException('مبلغ تراکنش تأییدشده با مبلغ فاکتور برابر نیست.');
        }

        $receipt = new Receipt('sep', $refNum ?: $token);
        $receipt->detail([
            'traceNo'      => data_get($json, 'data.TraceNo', Request::input('TraceNo')),
            'referenceNo'  => data_get($json, 'data.RRN', Request::input('RRN')),
            'transactionId' => $refNum,
            'cardNo'       => data_get($json, 'data.SecurePan', Request::input('SecurePan')),
        ]);

        return $receipt;
    }
}
