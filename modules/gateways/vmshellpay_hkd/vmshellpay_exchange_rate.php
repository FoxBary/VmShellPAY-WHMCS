<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../vmshellpay_hkd.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';

$gatewayParams = getGatewayVariables('vmshellpay_hkd');

$from = isset($_GET['from']) ? strtoupper(trim((string) $_GET['from'])) : 'USD';
$to = isset($_GET['to']) ? strtoupper(trim((string) $_GET['to'])) : 'HKD';
$amount = isset($_GET['amount']) ? (float) $_GET['amount'] : 1.00;

$result = vmshellpay_hkd_resolveExchangeRate($gatewayParams, $from, $to, $amount);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => $result['success'],
    'from' => $from,
    'to' => $to,
    'amount' => round($amount, 2),
    'rate' => $result['rate'],
    'settlement_amount' => $result['settlement_amount'],
    'source' => $result['source'],
    'message' => $result['message'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
