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
$verified = vmshellpay_hkd_verify($payload, $gatewayParams['appSecret'], $signType);
$invoiceId = vmshellpay_hkd_extractInvoiceId($payload);
$rateLock = vmshellpay_hkd_findRateLock($invoiceId);
$refundTransId = vmshellpay_hkd_firstNonEmpty([
    vmshellpay_hkd_arrayGet($payload, 'refund_id'),
    vmshellpay_hkd_arrayGet($payload, 'refund_transaction_id'),
    vmshellpay_hkd_arrayGet($payload, 'transaction_id'),
]);

logTransaction(
    'VmShellPAY-HKD Refund Notify',
    [
        'verified' => $verified,
        'payload' => $payload,
        'invoice_id' => $invoiceId,
        'refund_trans_id' => $refundTransId,
    ],
    $verified ? 'Refund Notification Received' : 'Refund Notification Signature Failed'
);

if ($verified && (int) $invoiceId > 0 && $refundTransId) {
    try {
        $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['FriendlyName']);
        vmshellpay_hkd_recordRefundTransaction($invoiceId, $refundTransId, $payload, $rateLock);
    } catch (Exception $e) {
        logTransaction('VmShellPAY-HKD Refund Notify', ['exception' => $e->getMessage(), 'payload' => $payload], 'Refund Transaction Sync Failed');
    }
}

http_response_code($verified ? 200 : 400);
exit($verified ? 'success' : 'invalid signature');
