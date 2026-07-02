# VmShellPAY-WHMCS

VmShellPAY-HKD for WHMCS 8 是一个 WHMCS 第三方支付网关插件，用于在 WHMCS 账单页接入 VmShellPAY 下游商户收款能力。

插件支持支付宝中国、支付宝香港、微信支付方式选择，账单页内展示二维码，支付回调自动入账，后台退款，退款通知，争议通知，多币种发票自动换算为 HKD 结算。

## 功能

- WHMCS 8 第三方支付网关模块。
- 使用 VmShellPAY AppId / AppSecret 发起支付、查询订单和退款。
- 前台账单页展示支付方式单选项，二维码直接显示在当前账单页。
- 支付成功后通过异步回调和状态轮询同步 WHMCS 发票状态。
- 支持 USD、GBP、SGD、JPY 等发票币种自动换算为 HKD 结算。
- 支持自动汇率接口，默认使用 Frankfurter，失败时回退手工汇率表。
- 支持支付手续费、退款手续费记录。
- 支持退款通知与争议通知，争议可自动创建或更新 WHMCS 工单。
- 默认严格验证回调签名，降低伪造支付通知风险。

## 环境要求

- WHMCS 8.x。
- PHP 7.4 或更高版本，建议与当前 WHMCS 官方支持版本保持一致。
- PHP cURL、JSON、OpenSSL 扩展。
- 一个可公网访问的 HTTPS WHMCS 站点。
- VmShellPAY 商户 AppId 和 AppSecret。

## 目录结构

```text
modules/
  gateways/
    vmshellpay_hkd.php
    callback/
      vmshellpay_payment_notify_url.php
      vmshellpay_refund_notify_url.php
      vmshellpay_dispute_notify_url.php
    vmshellpay_hkd/
      core/module.php
      assets/
      notify/
      vmshellpay_checkout.php
      vmshellpay_return_url.php
      vmshellpay_status.php
      vmshellpay_exchange_rate.php
```

WHMCS 标准入口文件保留在 `modules/gateways/` 和 `modules/gateways/callback/`，主要实现集中在 `modules/gateways/vmshellpay_hkd/`。

## 安装

1. 下载本仓库源码或 Release 压缩包。
2. 将仓库里的 `modules` 目录上传到 WHMCS 根目录，保持目录结构不变。
3. 确认以下文件可以通过 HTTPS 访问：
   - `https://你的WHMCS域名/modules/gateways/callback/vmshellpay_payment_notify_url.php`
   - `https://你的WHMCS域名/modules/gateways/callback/vmshellpay_refund_notify_url.php`
   - `https://你的WHMCS域名/modules/gateways/callback/vmshellpay_dispute_notify_url.php`
   - `https://你的WHMCS域名/modules/gateways/vmshellpay_hkd/vmshellpay_return_url.php`
4. 进入 WHMCS 管理后台。
5. 打开 `System Settings -> Payments -> Payment Gateways`。
6. 在可用网关中启用 `VmShellPAY-HKD`。
7. 按下面的“后台配置”填写网关参数并保存。

## 后台配置

基础字段：

- `VmShell PAY AppId`：VmShellPAY 下游商户 AppId。
- `VmShell PAY AppSecret`：VmShellPAY 下游商户 AppSecret，请勿公开。
- `VmShell PAY API 地址`：默认 `https://vmshell.win`。
- `联系邮箱`：默认尝试读取当前 WHMCS 管理员或系统邮箱。
- `平台争议中心链接`：默认 `https://vmshell.win/disputes/{dispute_id}`。

回调字段：

- `支付通知 notify_url`：建议留空，插件会自动使用内置支付通知地址。
- `同步跳转 return_url`：建议留空，插件会自动使用内置返回页。
- `退款通知 refund_notify_url`：可填写默认退款通知地址。
- `争议通知 dispute_notify_url`：可填写默认争议通知地址。

支付方式：

- `支付宝.中国`：启用后前台展示 `alipay_cn`。
- `支付宝.香港`：启用后前台展示 `alipay_hk`。
- `微信`：启用后前台展示 `wechat_pay`。
- `默认支付方式`：推荐 `alipay_cn`，也可选择 `alipay_hk` 或 `wechat_pay`。

币种与汇率：

- `收款货币种类`：WHMCS 发票常用币种，默认 `USD`；实际提交给 VmShellPAY 的支付和退款金额为 HKD。
- `汇率来源`：推荐 `api`。
- `自动汇率接口`：默认 `https://api.frankfurter.dev/v2/rate/{from}/{to}`。
- `汇率接口 API Key`：默认 Frankfurter 不需要填写；自定义接口需要时再填。
- `汇率接口超时秒数`：默认 `10`。
- `汇率上浮比例`：默认 `0`。
- `手工汇率表`：自动汇率失败时兜底，每行一个汇率，例如 `USD=7.80`。

费用：

- `收款手续费比例`：用于 WHMCS 财务记录和日志，例如 `2.9` 表示 2.9%。
- `退款手续费每笔`：用于记录每笔退款手续费，单位按 HKD 处理。

高级字段：

- `签名方式`：推荐 `HMAC-SHA256`。
- `终端类型`：默认 `auto`。
- `支付场景`：默认 `auto`。
- `订单号前缀`：默认 `WHMCS`，最终格式类似 `WHMCS_1024_1782958200`。
- `回调验签策略`：生产环境必须使用 `strict`。`compat` 仅建议在旧接口联调排查时短暂使用。
- `争议工单部门 ID`：收到争议通知时创建或更新工单的部门 ID。
- `争议通知管理员用户名`：WHMCS `localAPI` 调用上下文，留空使用默认上下文。
- `争议通知管理员`：启用后收到争议通知时触发管理员通知。

## VmShellPAY 平台回调地址

如果 VmShellPAY 平台需要手动配置回调地址，请使用：

```text
支付通知:
https://你的WHMCS域名/modules/gateways/callback/vmshellpay_payment_notify_url.php

退款通知:
https://你的WHMCS域名/modules/gateways/callback/vmshellpay_refund_notify_url.php

争议通知:
https://你的WHMCS域名/modules/gateways/callback/vmshellpay_dispute_notify_url.php
```

同步跳转地址通常由插件发起支付时自动提交。需要手动排查时可参考：

```text
https://你的WHMCS域名/modules/gateways/vmshellpay_hkd/vmshellpay_return_url.php
```

## 使用流程

1. 客户打开 WHMCS 发票页。
2. 选择 `支付宝.中国`、`支付宝.香港` 或 `微信`。
3. 插件调用 VmShellPAY 创建 HKD 结算订单。
4. WHMCS 发票页内显示支付二维码或跳转到接口返回的收银台地址。
5. 客户完成支付。
6. VmShellPAY 调用支付通知地址。
7. 插件验签成功后调用 WHMCS `addInvoicePayment` 自动入账。
8. 如果客户先回到 WHMCS 页面，返回页和状态轮询会主动查询订单状态，降低回调延迟导致的未即时入账。

## 退款

在 WHMCS 后台对已支付发票发起退款时，插件会：

- 查找支付时锁定的汇率记录。
- 将退款金额换算为 HKD。
- 调用 VmShellPAY 退款接口。
- 记录退款手续费。
- 退款通知到达后同步退款交易流水。

## 争议通知

争议通知验签成功后，插件会尽量从通知 payload 和本地汇率锁定记录中提取：

- VmShellPAY 争议 ID。
- WHMCS 发票 ID。
- WHMCS 客户 ID 和邮箱。
- 平台订单号和交易号。
- 争议金额、原因、状态和平台备注。

随后插件会尝试创建或更新 WHMCS 工单，并按配置通知管理员。

## 汇率调试

插件提供一个 JSON 调试页：

```text
https://你的WHMCS域名/modules/gateways/vmshellpay_hkd/vmshellpay_exchange_rate.php?from=USD&to=HKD&amount=100
```

返回内容包含 `success`、`rate`、`settlement_amount`、`source` 和 `message`，便于排查自动汇率接口与手工汇率兜底。

## 日志与排错

WHMCS 后台可在 `Billing -> Gateway Log` 查看插件日志。

常见问题：

- 下单失败：检查 AppId、AppSecret、API 地址、商户应用状态、IP 白名单和支付方式权限。
- 支付后未入账：检查支付通知 URL 是否公网可访问，查看 Gateway Log 中的签名验证结果。
- 签名失败：确认 AppSecret 与 VmShellPAY 平台一致，生产环境不要关闭 `strict`。
- 汇率失败：检查自动汇率接口连通性，或确认手工汇率表存在对应币种。
- 退款失败：确认原支付交易存在、可退款余额充足，并查看 VmShellPAY 平台退款结果。

## 数据表

插件会在需要时自动创建 `mod_vmshellpay_hkd_rate_locks`，用于保存发票原币种、HKD 结算金额、汇率、订单号和手续费等信息。该表用于支付、退款、通知和争议处理的审计追踪。

卸载插件时建议保留该表用于财务追溯；确需删除前请先备份数据库。

## 升级

1. 备份当前 WHMCS 文件和数据库。
2. 上传新版 `modules` 目录覆盖旧文件。
3. 进入 WHMCS 支付网关配置页保存一次配置。
4. 创建一张测试发票，完成下单、支付通知、状态查询和退款联调。

## 开发检查

本仓库不依赖 Composer。发布前可运行 PHP 语法检查：

```bash
find modules -name '*.php' -print0 | xargs -0 -n1 php -l
```

## 许可证

本项目使用 MIT License 开源。
