<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../vmshellpay_hkd.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';

$gatewayParams = getGatewayVariables('vmshellpay_hkd');
$invoiceId = isset($_REQUEST['invoiceid']) ? (int) $_REQUEST['invoiceid'] : 0;
$paymentMethod = isset($_REQUEST['payment_method']) ? trim((string) $_REQUEST['payment_method']) : '';

if (!$gatewayParams['type'] || $invoiceId <= 0) {
    http_response_code(400);
    exit('Invalid request');
}

$params = vmshellpay_hkd_buildRuntimeParams($gatewayParams, $invoiceId);
if (!$params) {
    http_response_code(404);
    exit('Invoice not found');
}

$methods = vmshellpay_hkd_getEnabledPaymentMethods($params);
if (!isset($methods[$paymentMethod])) {
    $paymentMethod = vmshellpay_hkd_resolveDefaultPaymentMethod($params, $methods);
}

$result = vmshellpay_hkd_createOrderForMethod($params, $paymentMethod);
if (!empty($_REQUEST['ajax'])) {
    echo $result['html'];
    exit;
}
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}
$systemUrl = rtrim((string) ($gatewayParams['systemurl'] ?? ''), '/');
$invoiceUrl = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
$title = 'VmShellPAY Checkout';
$bodyHtml = $result['html'];
$statusUrl = $systemUrl . '/modules/gateways/vmshellpay_hkd/vmshellpay_status.php';
$safeInvoiceUrl = htmlspecialchars($invoiceUrl, ENT_QUOTES, 'UTF-8');
$safeStatusUrl = htmlspecialchars($statusUrl, ENT_QUOTES, 'UTF-8');
$jsInvoiceId = json_encode((string) $invoiceId);
$jsStatusUrl = json_encode($statusUrl);
echo <<<HTML
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$title}</title>
  <style>
    body{margin:0;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f8fafc;color:#0f172a}
    .vms-shell{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:28px 16px}
    .vms-wrap{width:min(100%,420px)}
    .vms-back{display:inline-flex;align-items:center;gap:6px;margin-bottom:12px;color:#64748b;text-decoration:none;font-size:12px}
    .vms-card{background:#fff;border:1px solid #e2e8f0;border-radius:20px;padding:14px;box-shadow:0 12px 30px rgba(15,23,42,.08)}
    .vms-card{transition:opacity .22s ease,transform .22s ease}
    .vms-card.is-hiding{opacity:0;transform:translateY(4px)}
    .vms-paid-note{display:flex;align-items:center;justify-content:center;gap:8px;padding:12px 14px;border:1px solid #d1fae5;border-radius:14px;background:linear-gradient(180deg,#ffffff 0%,#f0fdf4 100%);color:#166534;font-size:12px;line-height:1.5;box-shadow:0 10px 24px rgba(34,197,94,.08)}
    .vms-paid-dot{width:18px;height:18px;border-radius:999px;background:#22c55e;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex:0 0 auto}
  </style>
</head>
<body>
  <div class="vms-shell">
    <div class="vms-wrap">
      <a class="vms-back" href="{$safeInvoiceUrl}">返回账单页</a>
      <div class="vms-card" id="vmshellpay-standalone-root">{$bodyHtml}</div>
    </div>
  </div>
  <script>
    (function(){
      var root = document.getElementById('vmshellpay-standalone-root');
      if (!root) return;
      var input = root.querySelector('input[data-vmshellpay-order-id]');
      if (!input || !input.value) return;
      var orderId = input.value;
      var paid = false;
      function showPaidState(redirectUrl) {
        if (paid) return;
        paid = true;
        root.classList.add('is-hiding');
        window.setTimeout(function(){
          root.innerHTML = '<div class=\"vms-paid-note\"><span class=\"vms-paid-dot\">✓</span><span>支付已确认，正在返回账单页…</span></div>';
          root.classList.remove('is-hiding');
          window.setTimeout(function(){
            window.location.href = redirectUrl || ('/viewinvoice.php?id=' + encodeURIComponent({$jsInvoiceId}));
          }, 900);
        }, 180);
      }
      setInterval(function(){
        fetch({$jsStatusUrl} + '?invoiceid=' + encodeURIComponent({$jsInvoiceId}) + '&order_id=' + encodeURIComponent(orderId) + '&_=' + Date.now(), {
          credentials: 'same-origin'
        }).then(function(response){
          return response.json();
        }).then(function(data){
          if (data && data.paid) {
            showPaidState(data.redirect_url);
          }
        }).catch(function(){});
      }, 4000);
    })();
  </script>
</body>
</html>
HTML;
exit;
