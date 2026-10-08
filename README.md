# K Khay Sovereign Crypto Gateway for WooCommerce 🛒⚡

Official WooCommerce payment gateway for the **[K Khay Sovereign Crypto Payment Gateway](https://kkhay.com)**.

Accept non-custodial and custodial crypto payments (USDT, USDC, BNB, ETH on BSC, Polygon, Arbitrum, Base, Ethereum) directly in your WooCommerce store with **zero chargebacks**, **instant on-chain verification**, and **high-performance order storage (HPOS)** compatibility.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![WooCommerce: Tested up to 9.x](https://img.shields.io/badge/WooCommerce-HPOS%20Ready-purple.svg)](https://woocommerce.com)
[![WordPress: 5.8+](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org)
[![PHP: 7.4+](https://img.shields.io/badge/PHP-7.4%2B-indigo.svg)](https://php.net)

---

## 🌟 Key Features

* **Multi-Chain Stablecoins**: Accept USDT & USDC across BNB Smart Chain (BSC), Polygon, Arbitrum, Base, and Ethereum.
* **Native Crypto**: Accept BNB and ETH directly.
* **Zero Chargebacks**: Blockchain settlement eliminates fraudulent chargebacks permanently.
* **Hosted Checkout Experience**: Seamless, responsive hosted checkout with live QR codes and multi-chain wallet selectors.
* **Instant Payment Notifications (IPN)**: Cryptographically secured webhooks (HMAC-SHA256) update WooCommerce order statuses in real-time.
* **HPOS Compatible**: 100% compliant with WooCommerce High-Performance Order Storage (`custom_order_tables`) and Gutenberg Cart & Checkout Blocks.
* **Self-Hosted Friendly**: Configurable Base URL allows connecting to private self-hosted K Khay gateway nodes or the cloud service.

---

## 📦 Installation

### Method 1: Upload via WordPress Admin (Recommended)
1. Download the latest `kkhay-woocommerce.zip` release.
2. In WordPress Admin, navigate to **Plugins &rarr; Add New &rarr; Upload Plugin**.
3. Choose the `.zip` file and click **Install Now**.
4. Click **Activate Plugin**.

### Method 2: Manual Installation via FTP / SSH
1. Clone or extract this repository into `/wp-content/plugins/kkhay-woocommerce/`:
   ```bash
   cd /path/to/wordpress/wp-content/plugins/
   git clone https://github.com/boboaungdev/kkhay-woocommerce.git
   ```
2. In WordPress Admin, go to **Plugins** and click **Activate** under **K Khay Sovereign Crypto Gateway for WooCommerce**.

---

## ⚙️ Configuration

1. In WordPress Admin, navigate to **WooCommerce &rarr; Settings &rarr; Payments**.
2. Click **K Khay Crypto Gateway** to access the settings panel.
3. Configure the following fields:
   - **Enable/Disable**: Check to activate the gateway on checkout.
   - **Title**: Payment method title shown to buyers (e.g. `Crypto (USDT, USDC, BNB, ETH via K Khay)`).
   - **Description**: Payment instructions shown when selected.
   - **Merchant API Key**: Paste your secret API key (`kkhay_live_...`) from your [K Khay Dashboard](https://kkhay.com).
   - **Webhook / IPN Secret**: Enter your webhook secret key used to verify cryptographic signatures.
   - **Gateway API Base URL**: Defaults to `https://api.kkhay.com` (change only if self-hosting your K Khay node).
   - **Order Status on Payment**: Choose `Processing` (standard) or `Completed` (digital downloads).
4. Copy the displayed **Instant Payment Notification (IPN) URL**:
   ```text
   https://yourstore.com/?wc-api=wc_gateway_kkhay
   ```
5. In your [K Khay Dashboard](https://kkhay.com), paste this URL into your **Webhook Settings**.
6. Click **Save Changes**.

---

## 🔒 Security & Verification

Incoming payment notifications are cryptographically verified using **HMAC-SHA256**:
- K Khay sends the SHA256 signature in the `x-kkhay-signature` HTTP header.
- The plugin calculates the expected HMAC signature using your configured `IPN Secret` and compares them using timing-attack resistant `hash_equals()`.
- Invalid or forged requests are immediately rejected with HTTP 401/403.

---

## 📄 License

MIT © [Bo Bo](https://github.com/boboaungdev) / [K Khay](https://kkhay.com)

