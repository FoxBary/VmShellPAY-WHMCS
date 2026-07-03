<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../vmshellpay_hkd.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

header('Content-Type: application/json; charset=utf-8');

$gatewayModuleName = 'vmshellpay_hkd';
$gatewayParams = getGatewayVariables($gatewayModuleName);
$invoiceId = isset($_GET['invoiceid']) ? (int) $_GET['invoiceid'] : 0;
$orderId = isset($_GET['order_id']) ? trim((string) $_GET['order_id']) : '';

if (!$gatewayParams['type'] || $invoiceId <= 0 || $orderId === '') {
    http_response_code(400);
    echo json_encode(['paid' => false, 'message' => 'invalid request']);
    exit;
}

$response = vmshellpay_hkd_queryOrder($gatewayParams, $orderId);
$rateLock = vmshellpay_hkd_findRateLock($invoiceId);
$paid = false;
$transId = '';
$redirectUrl = rtrim((string) ($gatewayParams['systemurl'] ?? ''), '/') . '/viewinvoice.php?id=' . $invoiceId;

if (vmshellpay_hkd_isInvoiceMarkedPaid($invoiceId)) {
    echo json_encode([
        'paid' => true,
        'transaction_id' => '',
        'redirect_url' => $redirectUrl,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($response['ok']) {
    $data = $response['json'];
    if (isset($data['data']) && is_array($data['data'])) {
        $data = $data['data'];
    }
    if (vmshellpay_hkd_isPaidStatus($data)) {
        $paid = true;
        $transId = (string) vmshellpay_hkd_resolveCanonicalTransactionId($data, $rateLock);
        $amount = vmshellpay_hkd_firstNonEmpty([
            vmshellpay_hkd_arrayGet($data, 'amount'),
            vmshellpay_hkd_arrayGet($data, 'pay_amount'),
        ]);
        if ($transId !== '' && !vmshellpay_hkd_shouldSkipPaymentApply($invoiceId)) {
            try {
                checkCbTransID($transId);
                vmshellpay_hkd_updateRateLockByOrder($orderId, [
                    'transaction_id' => $transId,
                    'payment_fee_hkd' => vmshellpay_hkd_resolvePaymentFee($data, $gatewayParams),
                ]);
                addInvoicePayment(
                    $invoiceId,
                    $transId,
                    vmshellpay_hkd_resolveInvoicePaymentAmount($data, $rateLock, $amount),
                    vmshellpay_hkd_resolveWhmcsFeeAmount($data, $gatewayParams, $rateLock),
                    $gatewayModuleName
                );
            } catch (Exception $e) {
                logTransaction('VmShellPAY-HKD Status Poll', ['exception' => $e->getMessage(), 'order_id' => $orderId], 'Duplicate or Invalid Transaction');
            }
        }
    }
}

if (!$paid && vmshellpay_hkd_isInvoiceMarkedPaid($invoiceId)) {
    $paid = true;
}

echo json_encode([
    'paid' => $paid,
    'transaction_id' => $transId,
    'redirect_url' => $redirectUrl,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
