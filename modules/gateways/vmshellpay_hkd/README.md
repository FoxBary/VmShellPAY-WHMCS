# VmShellPAY-HKD Module

This directory contains the implementation files for the `VmShellPAY-HKD` WHMCS gateway module.

For complete installation, configuration, callback, refund, dispute, exchange-rate, troubleshooting, and upgrade instructions, see the repository root `README.md`.

## Important Paths

- Main WHMCS gateway entry: `modules/gateways/vmshellpay_hkd.php`
- Core module implementation: `modules/gateways/vmshellpay_hkd/core/module.php`
- Inline checkout page: `modules/gateways/vmshellpay_hkd/vmshellpay_checkout.php`
- Return page: `modules/gateways/vmshellpay_hkd/vmshellpay_return_url.php`
- Status polling endpoint: `modules/gateways/vmshellpay_hkd/vmshellpay_status.php`
- Exchange-rate test endpoint: `modules/gateways/vmshellpay_hkd/vmshellpay_exchange_rate.php`
- Payment notify wrapper: `modules/gateways/callback/vmshellpay_payment_notify_url.php`
- Refund notify wrapper: `modules/gateways/callback/vmshellpay_refund_notify_url.php`
- Dispute notify wrapper: `modules/gateways/callback/vmshellpay_dispute_notify_url.php`

## Production Notes

- Keep `AppSecret` private.
- Use HTTPS for all WHMCS and callback URLs.
- Keep manual exchange rates configured as fallback even when automatic exchange rates are enabled.
- Test desktop QR payment, mobile Alipay China launch, WeChat embedded-browser warning, payment callback, return query, refund, and dispute notification before enabling the gateway for all users.
