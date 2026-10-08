=== K Khay Sovereign Crypto Gateway for WooCommerce ===
Contributors: boboaungdev, kkhay
Donate link: https://kkhay.com
Tags: crypto, payment gateway, woocommerce, usdt, usdc, bnb, ethereum, bitcoin, bsc, polygon, arbitrum, base
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Accept non-custodial and custodial crypto payments (USDT, USDC, BNB, ETH on BSC, Polygon, Arbitrum, Base, Ethereum) in WooCommerce.

== Description ==

**K Khay Sovereign Crypto Gateway for WooCommerce** is the official payment gateway for **[K Khay](https://kkhay.com)**.

Accept payments directly to your wallet in stablecoins and popular cryptocurrencies with **zero chargebacks**, **instant on-chain verification**, and **frictionless checkout**.

### 🌟 Key Features

* **Multi-Chain Stablecoins**: Accept USDT & USDC across BNB Smart Chain (BSC), Polygon, Arbitrum, Base, and Ethereum mainnet.
* **Native Tokens**: Accept BNB and ETH directly.
* **Non-Custodial & Sovereign**: Receive payments directly into your own wallets — no third-party freezes or chargebacks.
* **Hosted Checkout Experience**: Seamless, mobile-responsive checkout UI with dynamic QR codes, real-time blockchain monitoring, and multi-network selectors.
* **Instant Payment Notifications (IPN)**: Cryptographically secured webhooks (HMAC-SHA256) update WooCommerce order statuses instantly upon on-chain settlement.
* **High-Performance Order Storage (HPOS)**: 100% compatible with modern WooCommerce HPOS database tables and Gutenberg Checkout Blocks.
* **Partial Payment Detection**: Flags underpaid orders to alert store administrators before fulfillment.
* **Self-Hosted Gateway Friendly**: Fully configurable base URL allows connecting to your private self-hosted K Khay gateway instance or the cloud gateway.

== Installation ==

1. Upload the `kkhay-woocommerce` folder to the `/wp-content/plugins/` directory, or upload the `kkhay-woocommerce.zip` file directly via **Plugins &rarr; Add New &rarr; Upload Plugin** in your WordPress admin.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **WooCommerce &rarr; Settings &rarr; Payments**.
4. Click on **K Khay Crypto Gateway** to configure settings.
5. Enter your **Merchant API Key** and **Webhook / IPN Secret** from your [K Khay Dashboard](https://kkhay.com).
6. Copy the displayed IPN URL into your K Khay Dashboard under Webhook Settings.
7. Save changes and start accepting crypto payments!

== Frequently Asked Questions ==

= Does this plugin support WooCommerce Blocks checkout? =
Yes! K Khay fully supports both the classic WooCommerce checkout template and the new Gutenberg Cart & Checkout Blocks.

= Does this support High-Performance Order Storage (HPOS)? =
Yes. K Khay declares official compatibility with HPOS custom order tables.

= Are there any chargebacks with crypto? =
No. Blockchain payments are irreversible, completely eliminating fraudulent chargebacks.

= Where do I get my API Key? =
You can generate your merchant API keys and IPN webhook secret from your [K Khay Merchant Dashboard](https://kkhay.com).

== Changelog ==

= 1.0.0 =
* Initial release of the official K Khay Crypto Payment Gateway for WooCommerce.
* Support for USDT, USDC, BNB, ETH on BSC, Polygon, Arbitrum, Base, and Ethereum.
* Full compatibility with WooCommerce HPOS and Blocks Checkout.
* HMAC-SHA256 cryptographic webhook verification.

