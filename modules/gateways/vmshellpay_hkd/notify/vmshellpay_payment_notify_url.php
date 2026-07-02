<?php

require_once __DIR__ . '/../../../../init.php';
require_once __DIR__ . '/../../vmshellpay_hkd.php';
require_once __DIR__ . '/../../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../../includes/invoicefunctions.php';

$gatewayModuleName = 'vmshellpay_hkd';
$gatewayParams = getGatewayVariables($gatewayModuleName);

if (!$gatewayParams['type']) {
    http_response_code(503);
    exit('Module Not Activated');
}

$payload = $_POST;
if (empty($payload)) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $payload = $json;
    }
}

$signType = !empty($payload['sign_type']) ? (string) $payload['sign_type'] : (string) $gatewayParams['signType'];
$strict = ($gatewayParams['notifyMode'] ?? 'compat') === 'strict';
$verified = vmshellpay_hkd_verify($payload, $gatewayParams['appSecret'], $signType);

if (!$verified && $strict) {
    logTransaction('VmShellPAY-HKD Callback', $payload, 'Invalid Signature');
    http_response_code(400);
    exit('invalid signature');
}

$invoiceId = vmshellpay_hkd_extractInvoiceId($payload);
$rateLock = vmshellpay_hkd_findRateLock($invoiceId);
$transId = vmshellpay_hkd_firstNonEmpty([
    vmshellpay_hkd_arrayGet($payload, 'transaction_id'),
    vmshellpay_hkd_arrayGet($payload, 'gateway_order_id'),
    vmshellpay_hkd_arrayGet($payload, 'order_id'),
]);
$amount = vmshellpay_hkd_firstNonEmpty([
    vmshellpay_hkd_arrayGet($payload, 'amount'),
    vmshellpay_hkd_arrayGet($payload, 'pay_amount'),
]);

$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['FriendlyName']);
checkCbTransID($transId);

$paymentSuccess = vmshellpay_hkd_isPaidCallback($payload);

logTransaction(
    'VmShellPAY-HKD Callback',
    [
        'verified' => $verified,
        'strict' => $strict,
        'payload' => $payload,
        'rate_lock' => $rateLock,
    ],
    $paymentSuccess ? 'Payment Success' : 'Payment Ignored'
);

if (!$paymentSuccess) {
    http_response_code(200);
    exit('ignored');
}

vmshellpay_hkd_updateRateLockByOrder(vmshellpay_hkd_arrayGet($payload, 'order_id'), [
    'transaction_id' => $transId,
    'payment_fee_hkd' => vmshellpay_hkd_resolvePaymentFee($payload, $gatewayParams),
]);

addInvoicePayment(
    $invoiceId,
    $transId,
    vmshellpay_hkd_resolveInvoicePaymentAmount($payload, $rateLock, $amount),
    vmshellpay_hkd_resolveWhmcsFeeAmount($payload, $gatewayParams, $rateLock),
    $gatewayModuleName
);

http_response_code(200);
exit('success');

function vmshellpay_hkd_isPaidCallback(array $payload)
{
    $status = strtolower((string) vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($payload, 'status'),
        vmshellpay_hkd_arrayGet($payload, 'trade_status'),
        vmshellpay_hkd_arrayGet($payload, 'pay_status'),
    ]));

    return in_array($status, ['success', 'paid', 'pay_success', 'succeeded'], true)
        || vmshellpay_hkd_arrayGet($payload, 'success') === true
        || vmshellpay_hkd_arrayGet($payload, 'success') === 1
        || vmshellpay_hkd_arrayGet($payload, 'success') === '1';
}

function vmshellpay_hkd_resolvePaymentFee(array $payload, array $gatewayParams)
{
    $callbackFee = vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($payload, 'fee'),
        vmshellpay_hkd_arrayGet($payload, 'fees'),
        vmshellpay_hkd_arrayGet($payload, 'service_fee'),
    ]);
    if ($callbackFee !== null && $callbackFee !== '') {
        return round((float) $callbackFee, 2);
    }

    $amount = vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($payload, 'amount'),
        vmshellpay_hkd_arrayGet($payload, 'pay_amount'),
    ]);
    $feeRate = (float) ($gatewayParams['feeRate'] ?? 0);
    if ((float) $amount <= 0 || $feeRate <= 0) {
        return 0.00;
    }

    return round(((float) $amount) * ($feeRate / 100), 2);
}

function vmshellpay_hkd_resolveInvoicePaymentAmount(array $payload, $rateLock = null, $fallbackAmount = 0)
{
    if ($rateLock && !empty($rateLock['original_amount'])) {
        return round((float) $rateLock['original_amount'], 2);
    }
    return round((float) $fallbackAmount, 2);
}

function vmshellpay_hkd_resolveWhmcsFeeAmount(array $payload, array $gatewayParams, $rateLock = null)
{
    $feeHkd = vmshellpay_hkd_resolvePaymentFee($payload, $gatewayParams);
    if (!$rateLock) {
        return $feeHkd;
    }

    $originalCurrency = strtoupper((string) ($rateLock['original_currency'] ?? 'HKD'));
    if ($originalCurrency === 'HKD') {
        return $feeHkd;
    }
    return 0.00;
}
