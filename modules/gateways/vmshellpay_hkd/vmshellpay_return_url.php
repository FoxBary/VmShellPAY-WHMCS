<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../vmshellpay_hkd.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

$gatewayModuleName = 'vmshellpay_hkd';
$gatewayParams = getGatewayVariables($gatewayModuleName);

$invoiceId = isset($_GET['invoiceid']) ? (int) $_GET['invoiceid'] : 0;
$orderId = isset($_GET['order_id']) ? trim((string) $_GET['order_id']) : '';

if (!$gatewayParams['type'] || $invoiceId <= 0 || $orderId === '') {
    header('Location: ' . ($gatewayParams['systemurl'] ?? '') . '/viewinvoice.php?id=' . $invoiceId);
    exit;
}

if (vmshellpay_hkd_isInvoiceMarkedPaid($invoiceId)) {
    header('Location: ' . rtrim((string) ($gatewayParams['systemurl'] ?? ''), '/') . '/viewinvoice.php?id=' . $invoiceId);
    exit;
}

$response = vmshellpay_hkd_queryOrder($gatewayParams, $orderId);
$rateLock = vmshellpay_hkd_findRateLock($invoiceId);

logTransaction(
    'VmShellPAY-HKD Return Query',
    [
        'invoiceid' => $invoiceId,
        'order_id' => $orderId,
        'response' => $response['body'],
        'http_code' => $response['http_code'],
        'rate_lock' => $rateLock,
    ],
    $response['ok'] ? 'Query Successful' : 'Query Failed'
);

if ($response['ok']) {
    $data = $response['json'];
    if (isset($data['data']) && is_array($data['data'])) {
        $data = $data['data'];
    }

    if (vmshellpay_hkd_isPaidStatus($data)) {
        $transId = vmshellpay_hkd_resolveCanonicalTransactionId($data, $rateLock);
        $amount = vmshellpay_hkd_firstNonEmpty([
            vmshellpay_hkd_arrayGet($data, 'amount'),
            vmshellpay_hkd_arrayGet($data, 'pay_amount'),
        ]);

        if ($transId && !vmshellpay_hkd_shouldSkipPaymentApply($invoiceId)) {
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
                logTransaction('VmShellPAY-HKD Return Query', ['exception' => $e->getMessage()], 'Duplicate or Invalid Transaction');
            }
        }
    }
}

if (!vmshellpay_hkd_isInvoiceMarkedPaid($invoiceId)) {
    logTransaction('VmShellPAY-HKD Return Query', ['invoiceid' => $invoiceId, 'order_id' => $orderId], 'Invoice Still Unpaid After Return');
}

$systemUrl = rtrim((string) ($gatewayParams['systemurl'] ?? ''), '/');
header('Location: ' . $systemUrl . '/viewinvoice.php?id=' . $invoiceId);
exit;
