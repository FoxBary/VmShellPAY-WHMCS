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

if (!$verified) {
    logTransaction(
        'VmShellPAY-HKD Dispute Notify',
        [
            'verified' => false,
            'payload' => $payload,
        ],
        'Dispute Notification Signature Failed'
    );
    http_response_code(400);
    exit('invalid signature');
}

$invoiceId = vmshellpay_hkd_extractInvoiceId($payload);
$rateLock = vmshellpay_hkd_findRateLock($invoiceId);
$platformDisputeId = vmshellpay_hkd_firstNonEmpty([
    vmshellpay_hkd_arrayGet($payload, 'dispute_id'),
    vmshellpay_hkd_arrayGet($payload, 'platform_dispute_id'),
    vmshellpay_hkd_arrayGet($payload, 'id'),
]);

$context = [
    'platform_dispute_id' => (string) $platformDisputeId,
    'invoice_id' => (int) $invoiceId,
    'whmcs_user_id' => (int) vmshellpay_hkd_firstNonEmpty([
        isset($rateLock['invoice_id']) ? vmshellpay_hkd_extractWhmcsUserIdFromRateLock($rateLock) : null,
        vmshellpay_hkd_arrayGet($payload, 'whmcs_user_id'),
    ]),
    'order_id' => (string) vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($payload, 'order_id'),
        $rateLock['order_id'] ?? null,
    ]),
    'transaction_id' => (string) vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($payload, 'transaction_id'),
        $rateLock['transaction_id'] ?? null,
    ]),
    'customer_email' => (string) vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($payload, 'customer_email'),
        vmshellpay_hkd_arrayGet($payload, 'email'),
        vmshellpay_hkd_arrayGet($payload, 'payer_email'),
        vmshellpay_hkd_extractCustomerEmailFromRateLock($rateLock),
    ]),
    'original_currency' => (string) vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($payload, 'original_currency'),
        $rateLock['original_currency'] ?? null,
    ]),
    'original_amount' => (string) vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($payload, 'original_amount'),
        $rateLock['original_amount'] ?? null,
    ]),
    'dispute_amount_hkd' => (string) vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($payload, 'dispute_amount_hkd'),
        vmshellpay_hkd_arrayGet($payload, 'amount_hkd'),
        vmshellpay_hkd_arrayGet($payload, 'amount'),
    ]),
    'dispute_reason' => (string) vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($payload, 'dispute_reason'),
        vmshellpay_hkd_arrayGet($payload, 'reason'),
        vmshellpay_hkd_arrayGet($payload, 'type'),
    ]),
    'platform_status' => (string) vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($payload, 'status'),
        vmshellpay_hkd_arrayGet($payload, 'dispute_status'),
    ]),
    'platform_message' => (string) vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($payload, 'message'),
        vmshellpay_hkd_arrayGet($payload, 'remark'),
        vmshellpay_hkd_arrayGet($payload, 'note'),
    ]),
];
$context['dispute_center_url'] = vmshellpay_hkd_buildDisputeCenterUrl($gatewayParams, $context);
$ticketId = vmshellpay_hkd_openOrUpdateDisputeTicket($gatewayParams, $context);
$context['ticket_id'] = $ticketId;

vmshellpay_hkd_sendAdminDisputeNotification($gatewayParams, $context, $ticketId);

logTransaction(
    'VmShellPAY-HKD Dispute Notify',
    [
        'verified' => true,
        'payload' => $payload,
        'rate_lock' => $rateLock,
        'dispute_context' => $context,
    ],
    'Dispute Notification Processed'
);

http_response_code(200);
exit('success');
