<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function vmshellpay_hkd_internalDefaults()
{
    return [
        'signType' => 'HMAC-SHA256',
        'apiBaseUrl' => 'https://vmshell.win',
        'terminal' => 'auto',
        'paymentScene' => 'auto',
        'orderPrefix' => 'WHMCS',
        'notifyMode' => 'compat',
        'exchangeRateTimeout' => '10',
        'exchangeRateMarkupPercent' => '0',
        'disputeDeptId' => '1',
        'disputeAdminUsername' => '',
        'disputeNotifyAdmins' => 'on',
        'notifyUrl' => '',
        'returnUrl' => '',
        'refundNotifyUrl' => '',
        'disputeNotifyUrl' => '',
    ];
}

function vmshellpay_hkd_applyInternalDefaults(array $params)
{
    return array_merge(vmshellpay_hkd_internalDefaults(), $params);
}

function vmshellpay_hkd_MetaData()
{
    return [
        'DisplayName' => 'VmShellPAY-HKD',
        'APIVersion' => '1.1',
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    ];
}

function vmshellpay_hkd_detectContactEmail()
{
    if (!class_exists('\\WHMCS\\Database\\Capsule')) {
        return '';
    }

    $capsuleClass = '\\WHMCS\\Database\\Capsule';

    try {
        if (!empty($_SESSION['adminid'])) {
            $email = $capsuleClass::table('tbladmins')->where('id', (int) $_SESSION['adminid'])->value('email');
            if (is_string($email) && trim($email) !== '') {
                return trim($email);
            }
        }
    } catch (Exception $e) {
    }

    foreach (['Email', 'SupportEmail', 'InvoicePayToEmail'] as $setting) {
        try {
            $value = $capsuleClass::table('tblconfiguration')->where('setting', $setting)->value('value');
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        } catch (Exception $e) {
        }
    }

    return '';
}

function vmshellpay_hkd_config()
{
    $defaultContactEmail = vmshellpay_hkd_detectContactEmail();

    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'VmShellPAY-HKD Gateway',
        ],
        'appId' => [
            'FriendlyName' => 'VmShell PAY AppId:',
            'Type' => 'text',
            'Size' => '40',
            'Description' => ' 下游商户 AppId',
        ],
        'appSecret' => [
            'FriendlyName' => 'VmShell PAY AppSecret:',
            'Type' => 'text',
            'Size' => '60',
            'Description' => ' 下游商户 AppSecret',
        ],
        'contactEmail' => [
            'FriendlyName' => '联系邮箱',
            'Type' => 'text',
            'Size' => '50',
            'Default' => $defaultContactEmail,
            'Description' => ' 默认读取当前登录 WHMCS 管理员邮箱',
        ],
        'disputeCenterUrl' => [
            'FriendlyName' => '平台争议中心链接',
            'Type' => 'text',
            'Size' => '100',
            'Default' => 'https://vmshell.win/disputes/{dispute_id}',
            'Description' => ' 默认值通常即可使用',
        ],
        'enableAlipayCN' => [
            'FriendlyName' => '支付宝.中国',
            'Type' => 'yesno',
            'Default' => 'on',
            'Description' => ' 勾选后前台展示“支付宝.中国”单选项',
        ],
        'enableAlipayHK' => [
            'FriendlyName' => '支付宝.香港',
            'Type' => 'yesno',
            'Default' => 'on',
            'Description' => ' 勾选后前台展示“支付宝.香港”单选项',
        ],
        'enableWechatPay' => [
            'FriendlyName' => '微信',
            'Type' => 'yesno',
            'Default' => 'on',
            'Description' => ' 勾选后前台展示“微信”单选项',
        ],
        'defaultPaymentMethod' => [
            'FriendlyName' => '默认支付方式',
            'Type' => 'dropdown',
            'Options' => 'alipay_hk,alipay_cn,wechat_pay',
            'Default' => 'alipay_cn',
            'Description' => ' 仅作为默认选中项使用',
        ],
        'currencyCode' => [
            'FriendlyName' => '收款货币种类',
            'Type' => 'dropdown',
            'Size' => '10',
            'Options' => 'USD,GBP,SGD,JPY',
            'Default' => 'USD',
            'Description' => ' 商户常用计价币种默认 USD；平台支付与退款结算固定为 HKD',
        ],
        'exchangeRateMode' => [
            'FriendlyName' => '汇率来源',
            'Type' => 'dropdown',
            'Options' => 'manual,api',
            'Default' => 'api',
            'Description' => ' 默认推荐 api；接口失败时自动回退手工汇率表',
        ],
        'exchangeRateApiUrl' => [
            'FriendlyName' => '自动汇率接口',
            'Type' => 'text',
            'Size' => '80',
            'Default' => 'https://api.frankfurter.dev/v2/rate/{from}/{to}',
            'Description' => ' 默认使用 Frankfurter；支持 {from} {to} {amount} 占位符',
        ],
        'manualExchangeRates' => [
            'FriendlyName' => '手工汇率表',
            'Type' => 'textarea',
            'Rows' => '6',
            'Cols' => '60',
            'Default' => "USD=7.80\nEUR=8.45\nGBP=9.95\nSGD=5.78\nJPY=0.053",
            'Description' => ' API 失败时自动回退；每行一个，例如 USD=7.80，表示 1 USD = 7.80 HKD',
        ],
        'feeRate' => [
            'FriendlyName' => '收款手续费比例:',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '5.5',
            'Description' => ' 仅做后台展示备注，例如 2.9 表示 2.9%',
        ],
        'refundFeePerTxn' => [
            'FriendlyName' => '退款手续费每笔',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '0.65',
            'Description' => ' 仅做后台展示备注，例如 0.30',
        ],
    ];
}

function vmshellpay_hkd_link($params)
{
    $params = vmshellpay_hkd_applyInternalDefaults($params);
    if (vmshellpay_hkd_isInvoiceSettled($params)) {
        return '<div style="max-width:380px;margin:18px 0 18px auto;padding:10px 12px;border:1px solid #dcfce7;border-radius:14px;background:#f0fdf4;color:#166534;font-size:12px;line-height:1.55;text-align:center;">该账单已支付，支付区域已自动收起。</div>';
    }
    if (vmshellpay_hkd_shouldForceInvoiceRedirect($params)) {
        return vmshellpay_hkd_renderInvoiceRedirect($params);
    }
    $methods = vmshellpay_hkd_getEnabledPaymentMethods($params);
    if (empty($methods)) {
        return vmshellpay_hkd_renderError('暂未启用任何支付方式，请联系商户管理员。');
    }

    $systemUrl = rtrim((string) ($params['systemurl'] ?? ''), '/');
    $checkoutUrl = $systemUrl . '/modules/gateways/vmshellpay_hkd/vmshellpay_checkout.php';
    $invoiceId = htmlspecialchars((string) $params['invoiceid'], ENT_QUOTES, 'UTF-8');
    $defaultMethod = vmshellpay_hkd_resolveDefaultPaymentMethod($params, $methods);

    $radios = '';
    foreach ($methods as $code => $label) {
        $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $checked = $code === $defaultMethod ? 'checked' : '';
        $hint = htmlspecialchars(vmshellpay_hkd_methodHint($code), ENT_QUOTES, 'UTF-8');
        $radios .= <<<HTML
<label class="vmshellpay-hkd-option">
  <span class="vmshellpay-hkd-option-main">
    <input type="radio" name="payment_method" value="{$safeCode}" {$checked}>
    <span class="vmshellpay-hkd-option-copy">
      <span class="vmshellpay-hkd-option-title">{$safeLabel}</span>
      <span class="vmshellpay-hkd-option-desc">{$hint}</span>
    </span>
  </span>
  <span class="vmshellpay-hkd-option-tag">可选</span>
</label>
HTML;
    }

    $safeCheckoutUrl = htmlspecialchars($checkoutUrl, ENT_QUOTES, 'UTF-8');
    $statusUrl = htmlspecialchars($systemUrl . '/modules/gateways/vmshellpay_hkd/vmshellpay_status.php', ENT_QUOTES, 'UTF-8');
    $containerId = 'vmshellpay-hkd-checkout-' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) $params['invoiceid']);
    $jsContainerId = json_encode($containerId);
    $jsStatusUrl = json_encode($systemUrl . '/modules/gateways/vmshellpay_hkd/vmshellpay_status.php');
    $jsInvoiceId = json_encode((string) $params['invoiceid']);
    return <<<HTML
<div id="{$containerId}" class="vmshellpay-hkd-shell">
  <style>
    #{$containerId}.vmshellpay-hkd-shell{width:100%;max-width:380px;margin:18px 0 18px auto;padding:0;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
    #{$containerId} .vmshellpay-hkd-panel{border:1px solid #e7ecf3;border-radius:20px;overflow:hidden;background:#fff;box-shadow:0 12px 28px rgba(15,23,42,.07)}
    #{$containerId} .vmshellpay-hkd-body{padding:12px 14px 14px;background:#f8fafc}
    #{$containerId} .vmshellpay-hkd-form{display:grid;gap:8px}
    #{$containerId} .vmshellpay-hkd-options{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}
    #{$containerId} .vmshellpay-hkd-option{display:flex;align-items:center;justify-content:center;gap:6px;padding:9px 6px;border:1px solid #dbe4f0;border-radius:12px;background:#fff;cursor:pointer;transition:border-color .18s ease, box-shadow .18s ease}
    #{$containerId} .vmshellpay-hkd-option:hover{border-color:#93c5fd;box-shadow:0 6px 14px rgba(37,99,235,.08)}
    #{$containerId} .vmshellpay-hkd-option-main{display:flex;align-items:center;justify-content:center;gap:6px;min-width:0}
    #{$containerId} .vmshellpay-hkd-option input[type=radio]{width:14px;height:14px;margin:0;flex:0 0 auto}
    #{$containerId} .vmshellpay-hkd-option-copy{display:block;min-width:0}
    #{$containerId} .vmshellpay-hkd-option-title{font-size:12px;font-weight:600;color:#0f172a;white-space:nowrap}
    #{$containerId} .vmshellpay-hkd-option-desc{display:none}
    #{$containerId} .vmshellpay-hkd-option-tag{display:none}
    #{$containerId} [data-vmshellpay-result]{margin-top:10px;transition:opacity .22s ease,transform .22s ease}
    #{$containerId} [data-vmshellpay-result].is-hiding{opacity:0;transform:translateY(4px)}
    #{$containerId} .vmshellpay-hkd-paid-note{display:flex;align-items:center;justify-content:center;gap:8px;padding:12px 14px;border:1px solid #d1fae5;border-radius:14px;background:linear-gradient(180deg,#ffffff 0%,#f0fdf4 100%);color:#166534;font-size:12px;line-height:1.5;box-shadow:0 10px 24px rgba(34,197,94,.08)}
    #{$containerId} .vmshellpay-hkd-paid-dot{width:18px;height:18px;border-radius:999px;background:#22c55e;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex:0 0 auto}
    @media (max-width:767px){
      #{$containerId}.vmshellpay-hkd-shell{max-width:none;margin:16px 0}
      #{$containerId} .vmshellpay-hkd-options{grid-template-columns:1fr}
      #{$containerId} .vmshellpay-hkd-option{justify-content:flex-start;padding:10px 12px}
      #{$containerId} .vmshellpay-hkd-option-desc{display:block;font-size:11px;color:#64748b}
    }
  </style>
  <div class="vmshellpay-hkd-panel">
    <div class="vmshellpay-hkd-body">
      <form method="post" action="{$safeCheckoutUrl}" data-vmshellpay-inline="1" class="vmshellpay-hkd-form">
        <input type="hidden" name="invoiceid" value="{$invoiceId}">
        <div class="vmshellpay-hkd-options">{$radios}</div>
      </form>
      <div data-vmshellpay-result></div>
    </div>
  </div>
</div>
<script>
(function(){
  var root = document.getElementById({$jsContainerId});
  if (!root) return;
  var form = root.querySelector('form[data-vmshellpay-inline="1"]');
  var result = root.querySelector('[data-vmshellpay-result]');
  var radios = root.querySelectorAll('input[name="payment_method"]');
  var pollTimer = null;
  var redirecting = false;
  if (!form || !result) return;
  if (form.dataset.bound === '1') return;
  form.dataset.bound = '1';
  function stopPolling() {
    if (pollTimer) {
      clearInterval(pollTimer);
      pollTimer = null;
    }
  }
  function showPaidState(redirectUrl) {
    if (redirecting) return;
    redirecting = true;
    stopPolling();
    result.classList.add('is-hiding');
    window.setTimeout(function(){
      result.innerHTML = '<div class="vmshellpay-hkd-paid-note"><span class="vmshellpay-hkd-paid-dot">✓</span><span>支付已确认，正在返回账单页…</span></div>';
      result.classList.remove('is-hiding');
      window.setTimeout(function(){
        window.location.href = redirectUrl || ('/viewinvoice.php?id=' + encodeURIComponent({$jsInvoiceId}));
      }, 900);
    }, 180);
  }
  function startPolling(orderId) {
    stopPolling();
    if (!orderId) return;
    pollTimer = setInterval(function(){
      var url = {$jsStatusUrl} + '?invoiceid=' + encodeURIComponent({$jsInvoiceId}) + '&order_id=' + encodeURIComponent(orderId) + '&_=' + Date.now();
      fetch(url, { credentials: 'same-origin' })
        .then(function(response){ return response.json(); })
        .then(function(data){
          if (data && data.paid) {
            showPaidState(data.redirect_url);
          }
        })
        .catch(function(){});
    }, 4000);
  }
  function loadPayment(methodValue) {
    result.innerHTML = '<div style="padding:12px 14px;border:1px solid #dbe3f0;border-radius:14px;background:#fff;color:#334155;font-size:12px;line-height:1.6;">正在获取支付二维码，请稍候...</div>';
    var data = new FormData(form);
    if (methodValue) data.set('payment_method', methodValue);
    data.append('ajax', '1');
    fetch(form.action, {
      method: 'POST',
      body: data,
      credentials: 'same-origin'
    }).then(function(response){
      return response.text();
    }).then(function(html){
      result.innerHTML = html;
      var paidOrderInput = result.querySelector('input[data-vmshellpay-order-id]');
      if (paidOrderInput && paidOrderInput.value) {
        startPolling(paidOrderInput.value);
      }
    }).catch(function(){
      result.innerHTML = '<div style="padding:12px 14px;border:1px solid #fecaca;border-radius:14px;background:#fff1f2;color:#be123c;font-size:12px;line-height:1.6;">支付二维码获取失败，请稍后再试。</div>';
    });
  }
  form.addEventListener('submit', function(event){
    event.preventDefault();
  });
  document.addEventListener('submit', function(event){
    if (!form) return;
    if (event.target === form) {
      event.preventDefault();
      var checked = form.querySelector('input[name="payment_method"]:checked');
      loadPayment(checked ? checked.value : '');
    }
  }, true);
  Array.prototype.forEach.call(radios, function(radio){
    radio.addEventListener('change', function(){
      if (radio.checked) {
        loadPayment(radio.value);
      }
    });
  });
  var initial = form.querySelector('input[name="payment_method"]:checked');
  loadPayment(initial ? initial.value : '');
})();
</script>
HTML;
}

function vmshellpay_hkd_createOrderForMethod($params, $paymentMethod)
{
    $params = vmshellpay_hkd_applyInternalDefaults($params);
    $orderId = vmshellpay_hkd_buildOrderId($params);
    $invoiceCurrency = strtoupper(trim((string) ($params['currency'] ?? 'HKD')));
    $invoiceAmount = round((float) $params['amount'], 2);
    $paymentTitle = vmshellpay_hkd_limitText((string) $params['description'], 64);
    $paymentBody = vmshellpay_hkd_limitText((string) $params['description'], 128);
    $fx = vmshellpay_hkd_resolveExchangeRate($params, $invoiceCurrency, 'HKD', $invoiceAmount);
    if (!$fx['success']) {
        return ['ok' => false, 'html' => vmshellpay_hkd_renderError('汇率换算失败，暂时无法发起支付。', $fx['message'])];
    }

    $notifyUrl = trim((string) ($params['notifyUrl'] ?? ''));
    if ($notifyUrl === '') {
        $notifyUrl = $params['systemurl'] . '/modules/gateways/callback/vmshellpay_payment_notify_url.php';
    }
    $returnUrl = trim((string) ($params['returnUrl'] ?? ''));
    if ($returnUrl === '') {
        $returnUrl = $params['systemurl']
            . '/modules/gateways/vmshellpay_hkd/vmshellpay_return_url.php?invoiceid=' . rawurlencode((string) $params['invoiceid'])
            . '&order_id=' . rawurlencode((string) $orderId);
    }

    $payload = [
        'app_id' => trim($params['appId']),
        'method' => $paymentMethod,
        'payment_method' => $paymentMethod,
        'order_id' => $orderId,
        'amount' => number_format($fx['settlement_amount'], 2, '.', ''),
        'currency' => 'HKD',
        'customer_email' => (string) $params['clientdetails']['email'],
        'customer_name' => trim(($params['clientdetails']['firstname'] ?? '') . ' ' . ($params['clientdetails']['lastname'] ?? '')),
        'customer_phone' => (string) ($params['clientdetails']['phonenumber'] ?? ''),
        'subject' => $paymentTitle,
        'body' => $paymentBody,
        'remark' => 'WHMCS Invoice #' . $params['invoiceid'],
        'notify_url' => $notifyUrl,
        'return_url' => $returnUrl,
        'terminal' => $params['terminal'],
        'payment_scene' => $params['paymentScene'],
        'direct' => '1',
        'client_ip' => vmshellpay_hkd_getClientIp(),
        'timestamp' => (string) time(),
        'nonce' => bin2hex(random_bytes(8)),
        'ext_param' => json_encode([
            'invoice_id' => (string) $params['invoiceid'],
            'whmcs_user_id' => (string) $params['clientdetails']['userid'],
            'contact_email' => (string) ($params['contactEmail'] ?? ''),
            'refund_notify_url' => (string) ($params['refundNotifyUrl'] ?? ''),
            'dispute_notify_url' => (string) ($params['disputeNotifyUrl'] ?? ''),
            'original_amount' => number_format($invoiceAmount, 2, '.', ''),
            'original_currency' => $invoiceCurrency,
            'settlement_amount_hkd' => number_format($fx['settlement_amount'], 2, '.', ''),
            'exchange_rate' => (string) $fx['rate'],
            'rate_source' => (string) $fx['source'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ];

    vmshellpay_hkd_saveRateLock([
        'invoice_id' => (int) $params['invoiceid'],
        'order_id' => $orderId,
        'transaction_id' => null,
        'original_currency' => $invoiceCurrency,
        'original_amount' => $invoiceAmount,
        'settlement_currency' => 'HKD',
        'settlement_amount' => $fx['settlement_amount'],
        'exchange_rate' => $fx['rate'],
        'rate_source' => $fx['source'],
        'payment_fee_hkd' => 0,
        'refund_fee_hkd' => vmshellpay_hkd_calculateRefundFee($params['refundFeePerTxn'] ?? 0),
        'ext_json' => json_encode(['stage' => 'created'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);

    $payload['sign_type'] = strtoupper($params['signType']) === 'MD5' ? 'MD5' : 'HMAC-SHA256';
    $payload['signature'] = vmshellpay_hkd_sign($payload, $params['appSecret'], $payload['sign_type']);

    $response = vmshellpay_hkd_request($params['apiBaseUrl'], '/api/v1/pay.php', $payload);

    logTransaction(
        'VmShellPAY-HKD',
        [
            'request' => $payload,
            'response' => $response['body'],
            'http_code' => $response['http_code'],
            'fx' => $fx,
        ],
        $response['ok'] ? 'Request Successful' : 'Request Failed'
    );

    if (!$response['ok']) {
        $error = vmshellpay_hkd_extractApiError($response);
        return ['ok' => false, 'html' => vmshellpay_hkd_renderError('下单未成功，请检查 AppId/AppSecret、应用状态、IP 白名单、支付通道权限和支付方式配置。', $error)];
    }

    $data = $response['json'];
    if (isset($data['data']) && is_array($data['data'])) {
        $data = array_merge($data, $data['data']);
    }
    if (!vmshellpay_hkd_isGatewayAccepted($response['json'])) {
        $error = vmshellpay_hkd_extractApiError($response);
        return ['ok' => false, 'html' => vmshellpay_hkd_renderError('支付通道返回失败。', $error)];
    }
    $checkoutUrl = vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($data, 'checkout_url'),
        vmshellpay_hkd_arrayGet($data, 'payment_url'),
        vmshellpay_hkd_arrayGet($data, 'pay_url'),
        vmshellpay_hkd_arrayGet($data, 'mobile_h5_url'),
    ]);
    $qrCodeUrl = vmshellpay_hkd_arrayGet($data, 'qr_code_url');
    $qrContent = vmshellpay_hkd_arrayGet($data, 'qr_content');
    $message = vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($data, 'message'),
        vmshellpay_hkd_arrayGet($data, 'msg'),
        '请使用下方方式完成支付',
    ]);

    if (!$checkoutUrl && !$qrCodeUrl && !$qrContent) {
        return ['ok' => false, 'html' => vmshellpay_hkd_renderError('支付接口已返回响应，但未提供可跳转链接或二维码内容。请检查支付通道返回参数。', $message)];
    }

    $displayName = trim($params['displayName']) ?: 'VmShellPAY-HKD';
    $systemUrl = rtrim((string) ($params['systemurl'] ?? ''), '/');
    $logoUrl = htmlspecialchars($systemUrl . '/modules/gateways/vmshellpay_hkd/assets/vmshell_secure_icon.jpg', ENT_QUOTES, 'UTF-8');
    $safeCheckoutUrl = htmlspecialchars((string) $checkoutUrl, ENT_QUOTES, 'UTF-8');
    $safeQrCodeUrl = htmlspecialchars((string) $qrCodeUrl, ENT_QUOTES, 'UTF-8');
    $safeQrContent = htmlspecialchars((string) $qrContent, ENT_QUOTES, 'UTF-8');

    $actionBlock = '';
    if ($qrContent && !$qrCodeUrl) {
        $safeQrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=' . rawurlencode($qrContent);
    }

    $qrBlock = '';
    if ($safeQrCodeUrl) {
        $qrBlock = '<div style="margin-top:4px;text-align:center;">'
            . '<div style="display:flex;align-items:center;justify-content:center;gap:6px;margin:0 auto 8px;line-height:1;">'
            . '<img src="' . $logoUrl . '" alt="Security" style="width:16px;height:16px;border-radius:4px;object-fit:cover;display:block;">'
            . '<span style="font-size:10px;color:#64748b;">Power By </span>'
            . '<a href="https://vmshell.win/" target="_blank" rel="noopener" style="font-size:11px;font-weight:800;text-decoration:none;background:linear-gradient(90deg,#16a34a 0%,#0ea5e9 25%,#7c3aed 50%,#f59e0b 75%,#ef4444 100%);-webkit-background-clip:text;background-clip:text;color:transparent;">VmShellPAY</a>'
            . '</div>'
            . '<img src="' . $safeQrCodeUrl . '" alt="QR Code" style="max-width:236px;width:100%;border-radius:14px;border:1px solid #dbe3f0;background:#fff;padding:10px;">'
            . '</div>';
    }

    $html = <<<HTML
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:14px;box-shadow:0 8px 22px rgba(15,23,42,.05);">
  <input type="hidden" data-vmshellpay-order-id value="{$orderId}">
  {$qrBlock}
</div>
HTML;
    return ['ok' => true, 'html' => $html];
}

function vmshellpay_hkd_refund($params)
{
    $params = vmshellpay_hkd_applyInternalDefaults($params);
    $refundId = vmshellpay_hkd_buildRefundId($params);
    $refundFee = vmshellpay_hkd_calculateRefundFee($params['refundFeePerTxn'] ?? 0);
    $rateLock = vmshellpay_hkd_findRateLock((int) $params['invoiceid'], (string) $params['transid']);
    $refundOriginalAmount = round((float) $params['amount'], 2);
    $refundHkdAmount = vmshellpay_hkd_convertRefundToHkd($refundOriginalAmount, $rateLock);
    $payload = [
        'app_id' => trim($params['appId']),
        'order_id' => !empty($rateLock['order_id']) ? $rateLock['order_id'] : $params['transid'],
        'refund_id' => $refundId,
        'refund_amount' => number_format($refundHkdAmount, 2, '.', ''),
        'reason' => 'WHMCS refund invoice #' . $params['invoiceid'],
        'currency' => 'HKD',
        'timestamp' => (string) time(),
        'nonce' => bin2hex(random_bytes(8)),
        'sign_type' => strtoupper($params['signType']) === 'MD5' ? 'MD5' : 'HMAC-SHA256',
    ];
    $payload['signature'] = vmshellpay_hkd_sign($payload, $params['appSecret'], $payload['sign_type']);

    $response = vmshellpay_hkd_request($params['apiBaseUrl'], '/api/v1/refund.php', $payload);

    logTransaction(
        'VmShellPAY-HKD Refund',
        [
            'request' => $payload,
            'response' => $response['body'],
            'http_code' => $response['http_code'],
            'rate_lock' => $rateLock,
            'refund_original_amount' => $refundOriginalAmount,
            'refund_hkd_amount' => $refundHkdAmount,
        ],
        $response['ok'] ? 'Refund Request Successful' : 'Refund Request Failed'
    );

    if (!$response['ok']) {
        return [
            'status' => 'error',
            'rawdata' => $response['body'],
            'message' => '退款接口请求失败',
        ];
    }

    $json = $response['json'];
    $success = vmshellpay_hkd_isSuccess($json);
    if ($success) {
        $refundTransId = vmshellpay_hkd_firstNonEmpty([
            vmshellpay_hkd_arrayGet($json, 'refund_id'),
            $refundId,
        ]);
        if ($refundFee > 0) {
            vmshellpay_hkd_recordRefundFeeTransaction($params, $refundTransId, $refundFee);
        }
        vmshellpay_hkd_updateRateLockByInvoice((int) $params['invoiceid'], [
            'refund_fee_hkd' => $refundFee,
        ]);
        return [
            'status' => 'success',
            'transid' => $refundTransId,
            'rawdata' => $json,
            'fee' => $refundFee,
        ];
    }

    return [
        'status' => 'declined',
        'rawdata' => $json,
        'declinereason' => vmshellpay_hkd_firstNonEmpty([
            vmshellpay_hkd_arrayGet($json, 'message'),
            vmshellpay_hkd_arrayGet($json, 'msg'),
            '退款失败',
        ]),
    ];
}

function vmshellpay_hkd_request($baseUrl, $path, array $payload)
{
    $baseUrl = rtrim(trim($baseUrl), '/');
    $url = $baseUrl . $path;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $body = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $json = json_decode((string) $body, true);
    $ok = !$curlError && $httpCode >= 200 && $httpCode < 300 && is_array($json);

    return [
        'ok' => $ok,
        'body' => $body,
        'json' => is_array($json) ? $json : [],
        'http_code' => $httpCode,
        'curl_error' => $curlError,
    ];
}

function vmshellpay_hkd_sign(array $payload, $secret, $signType)
{
    $secret = (string) $secret;
    $canonical = vmshellpay_hkd_canonicalString($payload);

    if (strtoupper($signType) === 'MD5') {
        $final = $canonical === '' ? 'key=' . $secret : $canonical . '&key=' . $secret;
        return strtolower(md5($final));
    }

    return strtolower(hash_hmac('sha256', $canonical, $secret));
}

function vmshellpay_hkd_verify(array $payload, $secret, $signType)
{
    $signature = '';
    if (isset($payload['signature'])) {
        $signature = (string) $payload['signature'];
        unset($payload['signature']);
    } elseif (isset($payload['sign'])) {
        $signature = (string) $payload['sign'];
        unset($payload['sign']);
    }

    if ($signature === '') {
        return false;
    }

    $expected = vmshellpay_hkd_sign($payload, $secret, $signType);
    return hash_equals(strtolower($expected), strtolower($signature));
}

function vmshellpay_hkd_canonicalString(array $payload)
{
    $filtered = [];
    foreach ($payload as $key => $value) {
        if (in_array($key, ['signature', 'sign'], true)) {
            continue;
        }
        if ($value === null || $value === '') {
            continue;
        }
        $filtered[$key] = (string) $value;
    }
    ksort($filtered);

    $parts = [];
    foreach ($filtered as $key => $value) {
        $parts[] = $key . '=' . $value;
    }
    return implode('&', $parts);
}

function vmshellpay_hkd_getClientIp()
{
    $candidates = [];
    $headerOrder = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_TRUE_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_X_CLIENT_IP',
        'REMOTE_ADDR',
    ];

    foreach ($headerOrder as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }
        $rawValue = trim((string) $_SERVER[$key]);
        if ($rawValue === '') {
            continue;
        }
        if ($key === 'HTTP_X_FORWARDED_FOR') {
            foreach (explode(',', $rawValue) as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $candidates[] = $part;
                }
            }
            continue;
        }
        $candidates[] = $rawValue;
    }

    foreach ($candidates as $candidate) {
        if (!filter_var($candidate, FILTER_VALIDATE_IP)) {
            continue;
        }
        if (vmshellpay_hkd_isPublicIp($candidate)) {
            return $candidate;
        }
    }

    foreach ($candidates as $candidate) {
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            return $candidate;
        }
    }

    return '127.0.0.1';
}

function vmshellpay_hkd_isPublicIp($ip)
{
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) !== false;
}

function vmshellpay_hkd_renderError($title, $detail = '')
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeDetail = htmlspecialchars($detail, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<div style="background:#fff7ed;border:1px solid #fdba74;border-radius:16px;padding:14px;box-shadow:0 8px 22px rgba(15,23,42,.05);">
  <div style="font-size:15px;font-weight:700;color:#9a3412;">支付发起失败</div>
  <div style="margin-top:6px;color:#7c2d12;font-size:12px;line-height:1.65;">{$safeTitle}</div>
  <div style="margin-top:4px;color:#9a3412;font-size:11px;line-height:1.6;">{$safeDetail}</div>
  </div>
HTML;
}

function vmshellpay_hkd_extractApiError(array $response)
{
    $json = is_array($response['json'] ?? null) ? $response['json'] : [];
    $data = isset($json['data']) && is_array($json['data']) ? $json['data'] : [];

    $parts = [];
    $message = vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($data, 'errMsg'),
        vmshellpay_hkd_arrayGet($json, 'msg'),
        vmshellpay_hkd_arrayGet($json, 'message'),
        vmshellpay_hkd_arrayGet($json, 'error'),
        $response['curl_error'] ?? null,
    ]);
    $code = vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($data, 'errCode'),
        vmshellpay_hkd_arrayGet($json, 'code'),
        !empty($response['http_code']) ? 'HTTP ' . (int) $response['http_code'] : null,
    ]);

    if ($code !== null && $code !== '') {
        $parts[] = '代码: ' . $code;
    }
    if ($message !== null && $message !== '') {
        $parts[] = '原因: ' . $message;
    }

    return $parts ? implode(' | ', $parts) : '平台未返回更详细的错误信息。';
}

function vmshellpay_hkd_isGatewayAccepted(array $responseJson)
{
    $code = (string) vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($responseJson, 'code'),
        isset($responseJson['data']) && is_array($responseJson['data']) ? vmshellpay_hkd_arrayGet($responseJson['data'], 'code') : null,
    ]);
    if ($code !== '' && !in_array($code, ['0', 'SUCCESS', 'success'], true)) {
        return false;
    }

    $data = isset($responseJson['data']) && is_array($responseJson['data']) ? $responseJson['data'] : $responseJson;
    $errCode = (string) vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($data, 'errCode'),
        vmshellpay_hkd_arrayGet($responseJson, 'errCode'),
    ]);

    return $errCode === '';
}

function vmshellpay_hkd_isInvoiceSettled(array $params)
{
    $status = strtolower(trim((string) ($params['status'] ?? '')));
    return in_array($status, ['paid', 'refunded'], true);
}

function vmshellpay_hkd_isInvoiceMarkedPaid($invoiceId)
{
    $capsuleClass = vmshellpay_hkd_getCapsule();
    if (!$capsuleClass || (int) $invoiceId <= 0) {
        return false;
    }

    try {
        $status = $capsuleClass::table('tblinvoices')->where('id', (int) $invoiceId)->value('status');
        return in_array(strtolower(trim((string) $status)), ['paid', 'refunded'], true);
    } catch (Exception $e) {
        logTransaction('VmShellPAY-HKD Invoice Status', ['exception' => $e->getMessage(), 'invoice_id' => (int) $invoiceId], 'Lookup Failed');
        return false;
    }
}

function vmshellpay_hkd_shouldForceInvoiceRedirect(array $params)
{
    $scriptName = strtolower((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $requestUri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));

    if (strpos($scriptName, 'viewinvoice.php') !== false) {
        return false;
    }

    if (strpos($scriptName, 'clientarea.php') !== false && strpos($requestUri, 'action=addfunds') !== false) {
        return true;
    }

    if (strpos($scriptName, 'cart.php') !== false && strpos($requestUri, 'a=complete') !== false) {
        return true;
    }

    return false;
}

function vmshellpay_hkd_renderInvoiceRedirect(array $params)
{
    $systemUrl = rtrim((string) ($params['systemurl'] ?? ''), '/');
    $invoiceId = (int) ($params['invoiceid'] ?? 0);
    $invoiceUrl = $systemUrl . '/viewinvoice.php?id=' . $invoiceId . '&payopen=vmshellpay';
    $safeInvoiceUrl = htmlspecialchars($invoiceUrl, ENT_QUOTES, 'UTF-8');
    $jsInvoiceUrl = json_encode($invoiceUrl);

    return <<<HTML
<div style="max-width:380px;margin:18px 0 18px auto;padding:12px 14px;border:1px solid #dbeafe;border-radius:16px;background:linear-gradient(180deg,#ffffff 0%,#eff6ff 100%);color:#1e3a8a;font-size:12px;line-height:1.6;text-align:center;box-shadow:0 10px 24px rgba(59,130,246,.08);">
  正在返回账单页并加载支付二维码...
</div>
<script>
(function(){
  var target = {$jsInvoiceUrl};
  if (!target) return;
  window.setTimeout(function(){
    window.location.replace(target);
  }, 120);
})();
</script>
<noscript>
  <meta http-equiv="refresh" content="0;url={$safeInvoiceUrl}">
  <div style="max-width:380px;margin:10px 0 0 auto;text-align:center;font-size:12px;">
    <a href="{$safeInvoiceUrl}" style="color:#2563eb;text-decoration:none;font-weight:600;">点击返回账单页支付</a>
  </div>
</noscript>
HTML;
}

function vmshellpay_hkd_methodHint($code)
{
    switch ((string) $code) {
        case 'alipay_cn':
            return '支付宝中国钱包扫码';
        case 'alipay_hk':
            return '支付宝香港钱包扫码';
        case 'wechat_pay':
            return '微信钱包扫码支付';
        default:
            return '请选择合适的支付方式';
    }
}

function vmshellpay_hkd_arrayGet(array $data, $key, $default = null)
{
    return array_key_exists($key, $data) ? $data[$key] : $default;
}

function vmshellpay_hkd_firstNonEmpty(array $values)
{
    foreach ($values as $value) {
        if ($value !== null && $value !== '') {
            return $value;
        }
    }
    return null;
}

function vmshellpay_hkd_isSuccess(array $data)
{
    $status = strtolower((string) vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($data, 'status'),
        vmshellpay_hkd_arrayGet($data, 'state'),
        vmshellpay_hkd_arrayGet($data, 'trade_status'),
    ]));

    $success = vmshellpay_hkd_arrayGet($data, 'success');
    return $success === true
        || $success === 1
        || $success === '1'
        || in_array($status, ['success', 'paid', 'succeeded', 'ok'], true);
}

function vmshellpay_hkd_queryOrder(array $gatewayParams, $orderId)
{
    $gatewayParams = vmshellpay_hkd_applyInternalDefaults($gatewayParams);
    $payload = [
        'app_id' => trim((string) $gatewayParams['appId']),
        'order_id' => (string) $orderId,
        'sign_type' => strtoupper((string) $gatewayParams['signType']) === 'MD5' ? 'MD5' : 'HMAC-SHA256',
        'timestamp' => (string) time(),
        'nonce' => bin2hex(random_bytes(8)),
    ];
    $payload['signature'] = vmshellpay_hkd_sign($payload, (string) $gatewayParams['appSecret'], $payload['sign_type']);

    return vmshellpay_hkd_request((string) $gatewayParams['apiBaseUrl'], '/api/v1/query.php', $payload);
}

function vmshellpay_hkd_extractInvoiceId(array $payload)
{
    $extParam = vmshellpay_hkd_arrayGet($payload, 'ext_param');
    if (is_string($extParam) && $extParam !== '') {
        $decoded = json_decode($extParam, true);
        if (is_array($decoded) && !empty($decoded['invoice_id'])) {
            return (int) $decoded['invoice_id'];
        }
    }

    $remark = (string) vmshellpay_hkd_arrayGet($payload, 'remark');
    if (preg_match('/Invoice\s*#(\d+)/i', $remark, $matches)) {
        return (int) $matches[1];
    }

    $orderId = (string) vmshellpay_hkd_arrayGet($payload, 'order_id');
    if (preg_match('/^[A-Za-z0-9_-]+_(\d+)_\d+$/', $orderId, $matches)) {
        return (int) $matches[1];
    }

    return 0;
}

function vmshellpay_hkd_isPaidStatus(array $data)
{
    $status = strtolower((string) vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($data, 'status'),
        vmshellpay_hkd_arrayGet($data, 'trade_status'),
        vmshellpay_hkd_arrayGet($data, 'pay_status'),
    ]));

    return in_array($status, ['success', 'paid', 'pay_success', 'succeeded'], true)
        || vmshellpay_hkd_arrayGet($data, 'success') === true
        || vmshellpay_hkd_arrayGet($data, 'success') === 1
        || vmshellpay_hkd_arrayGet($data, 'success') === '1';
}

function vmshellpay_hkd_calculateRefundFee($refundFeePerTxn)
{
    $refundFeePerTxn = (float) $refundFeePerTxn;
    if ($refundFeePerTxn <= 0) {
        return 0.00;
    }
    return round($refundFeePerTxn, 2);
}

function vmshellpay_hkd_recordRefundFeeTransaction(array $params, $refundTransId, $refundFee)
{
    if (!function_exists('localAPI')) {
        return;
    }

    $postData = [
        'paymentmethod' => 'vmshellpay_hkd',
        'invoiceid' => (int) $params['invoiceid'],
        'transid' => $refundTransId . '-FEE',
        'description' => 'VmShellPAY-HKD Refund Fee for Invoice #' . $params['invoiceid'],
        'amountin' => 0,
        'amountout' => 0,
        'fees' => $refundFee,
        'allowduplicatetransid' => true,
    ];

    $result = localAPI('AddTransaction', $postData);
    logTransaction(
        'VmShellPAY-HKD Refund Fee',
        [
            'request' => $postData,
            'response' => $result,
        ],
        isset($result['result']) && $result['result'] === 'success' ? 'Transaction Recorded' : 'Transaction Record Failed'
    );
}

function vmshellpay_hkd_recordRefundTransaction($invoiceId, $refundTransId, array $payload, $rateLock = null)
{
    if (!function_exists('localAPI') || (int) $invoiceId <= 0 || trim((string) $refundTransId) === '') {
        return;
    }

    $refundAmount = vmshellpay_hkd_resolveRefundInvoiceAmount($payload, $rateLock);
    $postData = [
        'paymentmethod' => 'vmshellpay_hkd',
        'invoiceid' => (int) $invoiceId,
        'transid' => (string) $refundTransId,
        'description' => 'VmShellPAY-HKD Refund for Invoice #' . (int) $invoiceId,
        'amountin' => 0,
        'amountout' => $refundAmount,
        'fees' => 0,
        'allowduplicatetransid' => true,
    ];

    $result = localAPI('AddTransaction', $postData);
    logTransaction(
        'VmShellPAY-HKD Refund Transaction',
        [
            'request' => $postData,
            'response' => $result,
            'payload' => $payload,
        ],
        isset($result['result']) && $result['result'] === 'success' ? 'Refund Transaction Recorded' : 'Refund Transaction Record Failed'
    );
}

function vmshellpay_hkd_resolveRefundInvoiceAmount(array $payload, $rateLock = null)
{
    $refundAmount = vmshellpay_hkd_firstNonEmpty([
        vmshellpay_hkd_arrayGet($payload, 'refund_amount'),
        vmshellpay_hkd_arrayGet($payload, 'amount'),
        vmshellpay_hkd_arrayGet($payload, 'refundAmount'),
    ]);
    $refundAmount = round((float) $refundAmount, 2);

    if (!$rateLock) {
        return $refundAmount;
    }

    $originalCurrency = strtoupper((string) ($rateLock['original_currency'] ?? 'HKD'));
    $rate = (float) ($rateLock['exchange_rate'] ?? 1);
    if ($originalCurrency !== 'HKD' && $rate > 0 && $refundAmount > 0) {
        return round($refundAmount / $rate, 2);
    }

    return $refundAmount;
}

function vmshellpay_hkd_resolveExchangeRate(array $gatewayParams, $fromCurrency, $toCurrency, $amount)
{
    $gatewayParams = vmshellpay_hkd_applyInternalDefaults($gatewayParams);
    $fromCurrency = strtoupper(trim((string) $fromCurrency));
    $toCurrency = strtoupper(trim((string) $toCurrency));
    $amount = round((float) $amount, 2);

    if ($fromCurrency === $toCurrency) {
        return [
            'success' => true,
            'rate' => 1.0,
            'source' => 'identity',
            'settlement_amount' => $amount,
            'message' => '',
        ];
    }

    $mode = strtolower(trim((string) ($gatewayParams['exchangeRateMode'] ?? 'manual')));
    $markup = (float) ($gatewayParams['exchangeRateMarkupPercent'] ?? 0);
    $rate = null;
    $source = '';
    $message = '';

    if ($mode === 'api') {
        $apiResult = vmshellpay_hkd_fetchExchangeRateFromApi($gatewayParams, $fromCurrency, $toCurrency, $amount);
        if ($apiResult['success']) {
            $rate = (float) $apiResult['rate'];
            $source = 'api';
        } else {
            $message = $apiResult['message'];
        }
    }

    if ($rate === null) {
        $manualRate = vmshellpay_hkd_getManualExchangeRate($gatewayParams, $fromCurrency, $toCurrency);
        if ($manualRate !== null) {
            $rate = (float) $manualRate;
            $source = $mode === 'api' ? 'manual_fallback' : 'manual';
        }
    }

    if ($rate === null || $rate <= 0) {
        return [
            'success' => false,
            'rate' => null,
            'source' => '',
            'settlement_amount' => 0,
            'message' => $message !== '' ? $message : '未找到可用汇率，请检查手工汇率表或汇率接口配置',
        ];
    }

    if ($markup > 0) {
        $rate = $rate * (1 + ($markup / 100));
        $source .= '_markup';
    }

    return [
        'success' => true,
        'rate' => round($rate, 8),
        'source' => $source,
        'settlement_amount' => round($amount * $rate, 2),
        'message' => '',
    ];
}

function vmshellpay_hkd_limitText($text, $maxLength)
{
    $text = trim((string) $text);
    if ($text === '') {
        return 'WHMCS Order';
    }

    $text = preg_replace('/\s+/u', ' ', str_replace(["\r", "\n", "\t"], ' ', $text));
    $maxLength = max(1, (int) $maxLength);

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text, 'UTF-8') > $maxLength) {
            return rtrim(mb_substr($text, 0, $maxLength, 'UTF-8'));
        }
        return $text;
    }

    if (strlen($text) > $maxLength) {
        return rtrim(substr($text, 0, $maxLength));
    }

    return $text;
}

function vmshellpay_hkd_fetchExchangeRateFromApi(array $gatewayParams, $fromCurrency, $toCurrency, $amount)
{
    $apiUrl = trim((string) ($gatewayParams['exchangeRateApiUrl'] ?? ''));
    if ($apiUrl === '') {
        return ['success' => false, 'message' => '未配置汇率查询接口'];
    }

    $url = str_replace(
        ['{from}', '{to}', '{amount}'],
        [rawurlencode($fromCurrency), rawurlencode($toCurrency), rawurlencode((string) $amount)],
        $apiUrl
    );
    if ($url === $apiUrl) {
        $separator = strpos($apiUrl, '?') === false ? '?' : '&';
        $url .= $separator . http_build_query([
            'from' => $fromCurrency,
            'to' => $toCurrency,
            'amount' => $amount,
        ]);
    }

    $headers = ['Accept: application/json'];
    $apiKey = trim((string) ($gatewayParams['exchangeRateApiKey'] ?? ''));
    if ($apiKey !== '') {
        $headers[] = 'Authorization: Bearer ' . $apiKey;
        $headers[] = 'X-API-Key: ' . $apiKey;
    }

    $timeout = max(3, (int) ($gatewayParams['exchangeRateTimeout'] ?? 10));
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => min($timeout, 10),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $body = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError || $httpCode < 200 || $httpCode >= 300) {
        return ['success' => false, 'message' => $curlError !== '' ? $curlError : '汇率接口返回异常 HTTP ' . $httpCode];
    }

    $json = json_decode((string) $body, true);
    if (!is_array($json)) {
        return ['success' => false, 'message' => '汇率接口未返回有效 JSON'];
    }

    $candidates = [
        vmshellpay_hkd_arrayGet($json, 'rate'),
        vmshellpay_hkd_arrayGet($json, 'exchange_rate'),
        isset($json['data']) && is_array($json['data']) ? vmshellpay_hkd_arrayGet($json['data'], 'rate') : null,
        isset($json['data']) && is_array($json['data']) ? vmshellpay_hkd_arrayGet($json['data'], 'exchange_rate') : null,
        isset($json['result']) && is_array($json['result']) ? vmshellpay_hkd_arrayGet($json['result'], 'rate') : null,
    ];

    foreach ($candidates as $candidate) {
        if ($candidate !== null && $candidate !== '' && (float) $candidate > 0) {
            return ['success' => true, 'rate' => (float) $candidate];
        }
    }

    return ['success' => false, 'message' => '汇率接口 JSON 中未找到 rate / exchange_rate'];
}

function vmshellpay_hkd_getManualExchangeRate(array $gatewayParams, $fromCurrency, $toCurrency)
{
    if (strtoupper($fromCurrency) === strtoupper($toCurrency)) {
        return 1.0;
    }

    $map = vmshellpay_hkd_parseManualExchangeRates((string) ($gatewayParams['manualExchangeRates'] ?? ''));
    $directKey = strtoupper($fromCurrency) . '_' . strtoupper($toCurrency);
    if (isset($map[$directKey]) && (float) $map[$directKey] > 0) {
        return (float) $map[$directKey];
    }

    if (strtoupper($toCurrency) === 'HKD' && isset($map[strtoupper($fromCurrency)])) {
        return (float) $map[strtoupper($fromCurrency)];
    }

    $reverseKey = strtoupper($toCurrency) . '_' . strtoupper($fromCurrency);
    if (isset($map[$reverseKey]) && (float) $map[$reverseKey] > 0) {
        return 1 / (float) $map[$reverseKey];
    }

    return null;
}

function vmshellpay_hkd_parseManualExchangeRates($raw)
{
    $lines = preg_split('/\r\n|\r|\n/', (string) $raw);
    $map = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = array_map('trim', explode('=', $line, 2));
        } elseif (strpos($line, ':') !== false) {
            list($key, $value) = array_map('trim', explode(':', $line, 2));
        } else {
            continue;
        }
        if ($key !== '' && $value !== '' && is_numeric($value)) {
            $map[strtoupper($key)] = (float) $value;
        }
    }
    return $map;
}

function vmshellpay_hkd_convertRefundToHkd($refundOriginalAmount, $rateLock = null)
{
    $refundOriginalAmount = round((float) $refundOriginalAmount, 2);
    if (!$rateLock) {
        return $refundOriginalAmount;
    }

    $rate = isset($rateLock['exchange_rate']) ? (float) $rateLock['exchange_rate'] : 1.0;
    if ($rate <= 0) {
        $rate = 1.0;
    }
    return round($refundOriginalAmount * $rate, 2);
}

function vmshellpay_hkd_getCapsule()
{
    if (!class_exists('\\WHMCS\\Database\\Capsule')) {
        return null;
    }
    return '\\WHMCS\\Database\\Capsule';
}

function vmshellpay_hkd_ensureRateLockTable()
{
    $capsuleClass = vmshellpay_hkd_getCapsule();
    if (!$capsuleClass) {
        return false;
    }
    try {
        if (!$capsuleClass::schema()->hasTable('mod_vmshellpay_hkd_rate_locks')) {
            $capsuleClass::schema()->create('mod_vmshellpay_hkd_rate_locks', function ($table) {
                $table->increments('id');
                $table->integer('invoice_id')->nullable()->index();
                $table->string('order_id', 120)->nullable()->unique();
                $table->string('transaction_id', 120)->nullable()->index();
                $table->string('original_currency', 10)->default('HKD');
                $table->decimal('original_amount', 18, 2)->default(0);
                $table->string('settlement_currency', 10)->default('HKD');
                $table->decimal('settlement_amount', 18, 2)->default(0);
                $table->decimal('exchange_rate', 18, 8)->default(1);
                $table->string('rate_source', 50)->nullable();
                $table->decimal('payment_fee_hkd', 18, 2)->default(0);
                $table->decimal('refund_fee_hkd', 18, 2)->default(0);
                $table->text('ext_json')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }
        return true;
    } catch (Exception $e) {
        logTransaction('VmShellPAY-HKD Rate Lock', ['exception' => $e->getMessage()], 'Create Table Failed');
        return false;
    }
}

function vmshellpay_hkd_saveRateLock(array $data)
{
    $capsuleClass = vmshellpay_hkd_getCapsule();
    if (!$capsuleClass || !vmshellpay_hkd_ensureRateLockTable()) {
        return false;
    }

    $now = date('Y-m-d H:i:s');
    $payload = [
        'invoice_id' => (int) ($data['invoice_id'] ?? 0),
        'order_id' => isset($data['order_id']) ? (string) $data['order_id'] : null,
        'transaction_id' => isset($data['transaction_id']) ? (string) $data['transaction_id'] : null,
        'original_currency' => (string) ($data['original_currency'] ?? 'HKD'),
        'original_amount' => (float) ($data['original_amount'] ?? 0),
        'settlement_currency' => (string) ($data['settlement_currency'] ?? 'HKD'),
        'settlement_amount' => (float) ($data['settlement_amount'] ?? 0),
        'exchange_rate' => (float) ($data['exchange_rate'] ?? 1),
        'rate_source' => isset($data['rate_source']) ? (string) $data['rate_source'] : null,
        'payment_fee_hkd' => (float) ($data['payment_fee_hkd'] ?? 0),
        'refund_fee_hkd' => (float) ($data['refund_fee_hkd'] ?? 0),
        'ext_json' => isset($data['ext_json']) ? (string) $data['ext_json'] : null,
        'updated_at' => $now,
    ];

    try {
        $existing = null;
        if (!empty($payload['order_id'])) {
            $existing = $capsuleClass::table('mod_vmshellpay_hkd_rate_locks')->where('order_id', $payload['order_id'])->first();
        }
        if ($existing) {
            $capsuleClass::table('mod_vmshellpay_hkd_rate_locks')->where('id', $existing->id)->update($payload);
        } else {
            $payload['created_at'] = $now;
            $capsuleClass::table('mod_vmshellpay_hkd_rate_locks')->insert($payload);
        }
        return true;
    } catch (Exception $e) {
        logTransaction('VmShellPAY-HKD Rate Lock', ['exception' => $e->getMessage(), 'payload' => $payload], 'Save Failed');
        return false;
    }
}

function vmshellpay_hkd_findRateLock($invoiceId, $transactionId = '')
{
    $capsuleClass = vmshellpay_hkd_getCapsule();
    if (!$capsuleClass || !vmshellpay_hkd_ensureRateLockTable()) {
        return null;
    }
    try {
        $query = $capsuleClass::table('mod_vmshellpay_hkd_rate_locks');
        if ($transactionId !== '') {
            $row = $query->where('transaction_id', $transactionId)->first();
            if ($row) {
                return (array) $row;
            }
        }
        if ((int) $invoiceId > 0) {
            $row = $capsuleClass::table('mod_vmshellpay_hkd_rate_locks')
                ->where('invoice_id', (int) $invoiceId)
                ->orderBy('id', 'desc')
                ->first();
            if ($row) {
                return (array) $row;
            }
        }
    } catch (Exception $e) {
        logTransaction('VmShellPAY-HKD Rate Lock', ['exception' => $e->getMessage()], 'Find Failed');
    }
    return null;
}

function vmshellpay_hkd_updateRateLockByOrder($orderId, array $updates)
{
    $capsuleClass = vmshellpay_hkd_getCapsule();
    if (!$capsuleClass || !vmshellpay_hkd_ensureRateLockTable() || !$orderId) {
        return false;
    }
    $updates['updated_at'] = date('Y-m-d H:i:s');
    try {
        $capsuleClass::table('mod_vmshellpay_hkd_rate_locks')->where('order_id', (string) $orderId)->update($updates);
        return true;
    } catch (Exception $e) {
        logTransaction('VmShellPAY-HKD Rate Lock', ['exception' => $e->getMessage(), 'order_id' => $orderId], 'Update By Order Failed');
        return false;
    }
}

function vmshellpay_hkd_updateRateLockByInvoice($invoiceId, array $updates)
{
    $capsuleClass = vmshellpay_hkd_getCapsule();
    if (!$capsuleClass || !vmshellpay_hkd_ensureRateLockTable() || (int) $invoiceId <= 0) {
        return false;
    }
    $updates['updated_at'] = date('Y-m-d H:i:s');
    try {
        $capsuleClass::table('mod_vmshellpay_hkd_rate_locks')->where('invoice_id', (int) $invoiceId)->update($updates);
        return true;
    } catch (Exception $e) {
        logTransaction('VmShellPAY-HKD Rate Lock', ['exception' => $e->getMessage(), 'invoice_id' => $invoiceId], 'Update By Invoice Failed');
        return false;
    }
}

function vmshellpay_hkd_extractWhmcsUserIdFromRateLock(array $rateLock)
{
    $capsuleClass = vmshellpay_hkd_getCapsule();
    if (!$capsuleClass || empty($rateLock['invoice_id'])) {
        return null;
    }
    try {
        $row = $capsuleClass::table('tblinvoices')->where('id', (int) $rateLock['invoice_id'])->first();
        if ($row && isset($row->userid)) {
            return (int) $row->userid;
        }
    } catch (Exception $e) {
        logTransaction('VmShellPAY-HKD Dispute', ['exception' => $e->getMessage()], 'Lookup Invoice User Failed');
    }
    return null;
}

function vmshellpay_hkd_extractCustomerEmailFromRateLock($rateLock = null)
{
    if (!$rateLock || empty($rateLock['invoice_id'])) {
        return null;
    }
    $capsuleClass = vmshellpay_hkd_getCapsule();
    if (!$capsuleClass) {
        return null;
    }
    try {
        $invoice = $capsuleClass::table('tblinvoices')->where('id', (int) $rateLock['invoice_id'])->first();
        if (!$invoice || empty($invoice->userid)) {
            return null;
        }
        $client = $capsuleClass::table('tblclients')->where('id', (int) $invoice->userid)->first();
        if ($client && !empty($client->email)) {
            return (string) $client->email;
        }
    } catch (Exception $e) {
        logTransaction('VmShellPAY-HKD Dispute', ['exception' => $e->getMessage()], 'Lookup Customer Email Failed');
    }
    return null;
}

function vmshellpay_hkd_buildDisputeCenterUrl(array $gatewayParams, array $context)
{
    $template = trim((string) ($gatewayParams['disputeCenterUrl'] ?? ''));
    if ($template === '') {
        return '';
    }
    return str_replace(
        ['{dispute_id}', '{order_id}', '{invoice_id}'],
        [
            rawurlencode((string) ($context['platform_dispute_id'] ?? '')),
            rawurlencode((string) ($context['order_id'] ?? '')),
            rawurlencode((string) ($context['invoice_id'] ?? '')),
        ],
        $template
    );
}

function vmshellpay_hkd_sendAdminDisputeNotification(array $gatewayParams, array $context, $ticketId = null)
{
    $gatewayParams = vmshellpay_hkd_applyInternalDefaults($gatewayParams);
    if (empty($gatewayParams['disputeNotifyAdmins']) || !function_exists('localAPI')) {
        return;
    }

    $subject = 'VmShellPAY-HKD 争议提醒';
    $message = '收到一条新的争议或争议状态更新。' . "\n"
        . '关联发票：#' . ($context['invoice_id'] ?? '-') . "\n"
        . '终端用户邮箱：' . ($context['customer_email'] ?? '-') . "\n"
        . '当前状态：' . ($context['platform_status'] ?? '-') . "\n"
        . '争议原因：' . ($context['dispute_reason'] ?? '-') . "\n"
        . '争议金额(HKD)：' . ($context['dispute_amount_hkd'] ?? '-') . "\n"
        . '工单ID：' . ($ticketId ?: '-') . "\n"
        . '处理入口：' . ($context['dispute_center_url'] ?? '-');

    $payload = [
        'identifier' => 'vmshellpay_hkd_dispute_' . ($context['platform_dispute_id'] ?? uniqid()),
        'title' => $subject,
        'message' => $message,
    ];
    $result = localAPI('TriggerNotificationEvent', $payload, (string) ($gatewayParams['disputeAdminUsername'] ?? ''));
    if (!isset($result['result']) || $result['result'] !== 'success') {
        localAPI('SendAdminEmail', [
            'type' => 'support',
            'customsubject' => $subject,
            'custommessage' => nl2br($message),
        ], (string) ($gatewayParams['disputeAdminUsername'] ?? ''));
    }
}

function vmshellpay_hkd_openOrUpdateDisputeTicket(array $gatewayParams, array $context)
{
    $gatewayParams = vmshellpay_hkd_applyInternalDefaults($gatewayParams);
    if (!function_exists('localAPI')) {
        return null;
    }

    $subject = 'VmShellPAY-HKD 争议通知 - 发票 #' . ($context['invoice_id'] ?? '-');
    $message = vmshellpay_hkd_buildDisputeTicketMessage($context);
    $adminUsername = (string) ($gatewayParams['disputeAdminUsername'] ?? '');
    $existing = !empty($context['existing_ticket_id']) ? (string) $context['existing_ticket_id'] : '';
    if ($existing === '') {
        $existing = vmshellpay_hkd_findExistingDisputeTicket($context, $adminUsername);
    }

    if ($existing !== '') {
        $replyPayload = [
            'ticketid' => $existing,
            'message' => $message,
            'status' => 'Answered',
        ];
        if ($adminUsername !== '') {
            $replyPayload['adminusername'] = $adminUsername;
        }
        $reply = localAPI('AddTicketReply', $replyPayload, $adminUsername);
        if (isset($reply['result']) && $reply['result'] === 'success') {
            return $existing;
        }
    }

    $openPayload = [
        'deptid' => (int) ($gatewayParams['disputeDeptId'] ?? 1),
        'subject' => $subject,
        'message' => $message,
        'priority' => 'High',
        'name' => 'VmShellPAY-HKD Dispute Bot',
        'email' => (string) ($gatewayParams['contactEmail'] ?: 'noreply@localhost'),
        'admin' => true,
        'markdown' => true,
        'preventClientClosure' => true,
    ];
    if (!empty($context['whmcs_user_id'])) {
        $openPayload['clientid'] = (int) $context['whmcs_user_id'];
    }
    $open = localAPI('OpenTicket', $openPayload, $adminUsername);

    if (isset($open['result']) && $open['result'] === 'success' && !empty($open['id'])) {
        return (string) $open['id'];
    }

    logTransaction('VmShellPAY-HKD Dispute', ['open_ticket_response' => $open, 'context' => $context], 'Open Ticket Failed');
    return null;
}

function vmshellpay_hkd_buildDisputeTicketMessage(array $context)
{
    $lines = [
        '# VmShellPAY-HKD 争议通知',
        '',
        '> 请优先通过下方平台处理链接进入 VmShellPAY 争议中心处理。',
        '',
        '| 字段 | 内容 |',
        '| --- | --- |',
        '| 关联发票 | #' . ($context['invoice_id'] ?? '-') . ' |',
        '| 终端用户邮箱 | ' . ($context['customer_email'] ?? '-') . ' |',
        '| 原始账单金额 | ' . ($context['original_amount'] ?? '-') . ' ' . ($context['original_currency'] ?? '') . ' |',
        '| 争议金额(HKD) | ' . ($context['dispute_amount_hkd'] ?? '-') . ' |',
        '| 当前状态 | ' . ($context['platform_status'] ?? '-') . ' |',
        '| 争议原因 | ' . ($context['dispute_reason'] ?? '-') . ' |',
        '| 处理说明 | ' . ($context['platform_message'] ?? '-') . ' |',
        '',
        '## 处理说明',
        '',
        '请前往 VmShellPAY 平台争议中心处理，本工单仅用于 WHMCS 内部通知、提醒和跟进。',
        '',
        '- 平台处理链接：' . ($context['dispute_center_url'] ?? '-'),
        '- 建议先核对终端用户邮箱、订单信息和争议原因，再进入平台处理。',
    ];
    return implode("\n", $lines);
}

function vmshellpay_hkd_findExistingDisputeTicket(array $context, $adminUsername = '')
{
    if (!function_exists('localAPI') || empty($context['platform_dispute_id'])) {
        return '';
    }

    $subject = 'VmShellPAY-HKD 争议通知 - 发票 #' . ($context['invoice_id'] ?? '-');
    $result = localAPI('GetTickets', [
        'subject' => $subject,
        'limitnum' => 5,
        'ignore_dept_assignments' => true,
    ], (string) $adminUsername);

    if (!isset($result['result']) || $result['result'] !== 'success' || empty($result['tickets']['ticket'])) {
        return '';
    }

    $tickets = $result['tickets']['ticket'];
    if (isset($tickets['id'])) {
        return (string) $tickets['id'];
    }
    if (is_array($tickets)) {
        foreach ($tickets as $ticket) {
            if (!empty($ticket['id'])) {
                return (string) $ticket['id'];
            }
        }
    }

    return '';
}

function vmshellpay_hkd_buildOrderId(array $params)
{
    $prefix = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $params['orderPrefix']);
    return $prefix . '_' . $params['invoiceid'] . '_' . time();
}

function vmshellpay_hkd_getEnabledPaymentMethods(array $params)
{
    $methods = [];
    if (!empty($params['enableAlipayCN'])) {
        $methods['alipay_cn'] = '支付宝.中国';
    }
    if (!empty($params['enableAlipayHK'])) {
        $methods['alipay_hk'] = '支付宝.香港';
    }
    if (!empty($params['enableWechatPay'])) {
        $methods['wechat_pay'] = '微信';
    }
    return $methods;
}

function vmshellpay_hkd_resolveDefaultPaymentMethod(array $params, array $methods)
{
    $default = (string) ($params['defaultPaymentMethod'] ?? 'alipay_hk');
    if (isset($methods[$default])) {
        return $default;
    }
    $keys = array_keys($methods);
    return $keys ? $keys[0] : 'alipay_hk';
}

function vmshellpay_hkd_buildRuntimeParams(array $gatewayParams, $invoiceId)
{
    $gatewayParams = vmshellpay_hkd_applyInternalDefaults($gatewayParams);
    $capsuleClass = vmshellpay_hkd_getCapsule();
    if (!$capsuleClass || (int) $invoiceId <= 0) {
        return null;
    }

    try {
        $invoice = $capsuleClass::table('tblinvoices')->where('id', (int) $invoiceId)->first();
        if (!$invoice) {
            return null;
        }

        $client = null;
        if (!empty($invoice->userid)) {
            $client = $capsuleClass::table('tblclients')->where('id', (int) $invoice->userid)->first();
        }

        $lineItem = $capsuleClass::table('tblinvoiceitems')
            ->where('invoiceid', (int) $invoiceId)
            ->orderBy('id', 'asc')
            ->first();

        $systemUrl = rtrim((string) ($gatewayParams['systemurl'] ?? ''), '/');
        $description = $lineItem && !empty($lineItem->description)
            ? (string) $lineItem->description
            : 'WHMCS Invoice #' . $invoiceId;

        $currencyCode = (string) ($gatewayParams['currencyCode'] ?? 'USD');
        if (!empty($invoice->currency) && is_numeric($invoice->currency)) {
            $currencyRow = $capsuleClass::table('tblcurrencies')->where('id', (int) $invoice->currency)->first();
            if ($currencyRow && !empty($currencyRow->code)) {
                $currencyCode = (string) $currencyRow->code;
            }
        } elseif (!empty($invoice->currency)) {
            $currencyCode = (string) $invoice->currency;
        }

        return array_merge($gatewayParams, [
            'invoiceid' => (int) $invoiceId,
            'amount' => (float) $invoice->total,
            'currency' => $currencyCode,
            'description' => $description,
            'returnurl' => $systemUrl . '/viewinvoice.php?id=' . (int) $invoiceId,
            'systemurl' => $systemUrl,
            'clientdetails' => [
                'userid' => $client->id ?? $invoice->userid ?? 0,
                'email' => $client->email ?? '',
                'firstname' => $client->firstname ?? '',
                'lastname' => $client->lastname ?? '',
                'phonenumber' => $client->phonenumber ?? '',
            ],
        ]);
    } catch (Exception $e) {
        logTransaction('VmShellPAY-HKD Runtime Params', ['exception' => $e->getMessage(), 'invoice_id' => $invoiceId], 'Build Params Failed');
        return null;
    }
}

function vmshellpay_hkd_buildRefundId(array $params)
{
    return 'RF_' . $params['invoiceid'] . '_' . time();
}
