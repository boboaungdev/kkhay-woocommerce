<?php
/**
 * Main K Khay Payment Gateway Class for WooCommerce
 *
 * @package Kkhay_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Kkhay extends WC_Payment_Gateway
{
    private WC_Kkhay_API $api;
    private WC_Logger $logger;

    public function __construct()
    {
        $this->id                 = 'kkhay';
        $this->icon               = apply_filters('woocommerce_kkhay_icon', KKHAY_WOOCOMMERCE_PLUGIN_URL . 'assets/images/kkhay-badge.svg');
        $this->has_fields         = false;
        $this->method_title       = __('K Khay Crypto Gateway', 'kkhay-woocommerce');
        $this->method_description = __('Accept sovereign, non-custodial crypto payments (USDT, USDC, BNB, ETH on BSC, Polygon, Arbitrum, Base, Ethereum) directly to your self-hosted or custodial K Khay gateway.', 'kkhay-woocommerce');

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        // Assign user configuration properties
        $this->title               = $this->get_option('title', __('Crypto (USDT, USDC, BNB, ETH via K Khay)', 'kkhay-woocommerce'));
        $this->description         = $this->get_option('description', __('Pay securely with cryptocurrency across multiple blockchains. Fast, zero chargebacks, and instant confirmation.', 'kkhay-woocommerce'));
        $this->enabled             = $this->get_option('enabled', 'no');
        $this->api_key             = $this->get_option('api_key', '');
        $this->ipn_secret          = $this->get_option('ipn_secret', '');
        $this->base_url            = $this->get_option('base_url', 'https://api.kkhay.com');
        $this->order_status_paid   = $this->get_option('order_status_paid', 'processing');
        $this->debug               = 'yes' === $this->get_option('debug', 'no');

        // Instantiate API client
        $this->api = new WC_Kkhay_API($this->api_key, $this->base_url, 30, $this->debug);

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_api_wc_gateway_kkhay', [$this, 'handle_webhook']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Define admin settings form fields.
     */
    public function init_form_fields(): void
    {
        $webhook_url = WC()->api_request_url('wc_gateway_kkhay');

        $this->form_fields = [
            'enabled' => [
                'title'       => __('Enable/Disable', 'kkhay-woocommerce'),
                'type'        => 'checkbox',
                'label'       => __('Enable K Khay Crypto Payment Gateway', 'kkhay-woocommerce'),
                'default'     => 'no',
            ],
            'title' => [
                'title'       => __('Title', 'kkhay-woocommerce'),
                'type'        => 'text',
                'description' => __('Payment method title displayed to customers during checkout.', 'kkhay-woocommerce'),
                'default'     => __('Crypto (USDT, USDC, BNB, ETH via K Khay)', 'kkhay-woocommerce'),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __('Description', 'kkhay-woocommerce'),
                'type'        => 'textarea',
                'description' => __('Payment method description displayed to customers during checkout.', 'kkhay-woocommerce'),
                'default'     => __('Pay securely with cryptocurrency across multiple blockchains. Fast, zero chargebacks, and instant settlement.', 'kkhay-woocommerce'),
            ],
            'api_key' => [
                'title'       => __('Merchant API Key', 'kkhay-woocommerce'),
                'type'        => 'password',
                'description' => __('Your secret merchant API key (starts with kkhay_live_ or kkhay_test_). Retrieve from your K Khay dashboard.', 'kkhay-woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ],
            'ipn_secret' => [
                'title'       => __('Webhook / IPN Secret', 'kkhay-woocommerce'),
                'type'        => 'password',
                'description' => __('Secret key used to verify HMAC-SHA256 signatures of incoming payment notifications.', 'kkhay-woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ],
            'base_url' => [
                'title'       => __('Gateway API Base URL', 'kkhay-woocommerce'),
                'type'        => 'text',
                'description' => __('Default is https://api.kkhay.com. Override only if self-hosting your K Khay gateway instance.', 'kkhay-woocommerce'),
                'default'     => 'https://api.kkhay.com',
                'desc_tip'    => true,
            ],
            'order_status_paid' => [
                'title'       => __('Order Status on Payment', 'kkhay-woocommerce'),
                'type'        => 'select',
                'description' => __('Status assigned to the order once crypto payment is confirmed on-chain.', 'kkhay-woocommerce'),
                'default'     => 'processing',
                'options'     => [
                    'processing' => __('Processing (Recommended for physical goods)', 'kkhay-woocommerce'),
                    'completed'  => __('Completed (Ideal for digital / downloadable items)', 'kkhay-woocommerce'),
                ],
            ],
            'webhook_url_info' => [
                'title'       => __('Instant Payment Notification (IPN) URL', 'kkhay-woocommerce'),
                'type'        => 'title',
                'description' => sprintf(
                    /* translators: %s: webhook url */
                    __('Copy and paste this URL into your K Khay Merchant Dashboard under Webhook Settings:<br><code>%s</code>', 'kkhay-woocommerce'),
                    esc_url($webhook_url)
                ),
            ],
            'debug' => [
                'title'       => __('Debug Logging', 'kkhay-woocommerce'),
                'type'        => 'checkbox',
                'label'       => __('Log API requests and webhook events to WooCommerce Status Logs', 'kkhay-woocommerce'),
                'default'     => 'no',
                'description' => __('Logs can be viewed in WooCommerce &rarr; Status &rarr; Logs.', 'kkhay-woocommerce'),
            ],
        ];
    }

    /**
     * Render payment fields at checkout.
     */
    public function payment_fields(): void
    {
        if ($this->description) {
            echo '<div class="kkhay-payment-description">' . wp_kses_post(wpautop(wptexturize($this->description))) . '</div>';
        }

        echo '<div class="kkhay-supported-crypto">';
        echo '<span class="kkhay-crypto-pill">USDT</span>';
        echo '<span class="kkhay-crypto-pill secondary">USDC</span>';
        echo '<span class="kkhay-crypto-pill accent">BNB</span>';
        echo '<span class="kkhay-crypto-pill secondary">ETH</span>';
        echo '<span class="kkhay-crypto-pill">BSC / Polygon / Arbitrum / Base</span>';
        echo '</div>';
        echo '<div class="kkhay-instant-badge">⚡ Instant On-Chain Verification</div>';
    }

    /**
     * Enqueue CSS styling for checkout.
     */
    public function enqueue_assets(): void
    {
        if (is_checkout() || is_checkout_pay_page()) {
            wp_enqueue_style(
                'kkhay-woocommerce',
                KKHAY_WOOCOMMERCE_PLUGIN_URL . 'assets/css/kkhay.css',
                [],
                KKHAY_WOOCOMMERCE_VERSION
            );
        }
    }

    /**
     * Process order payment and redirect to K Khay Hosted Checkout.
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            wc_add_notice(__('Unable to find order. Please try again.', 'kkhay-woocommerce'), 'error');
            return ['result' => 'fail'];
        }

        try {
            $amount   = (float) $order->get_total();
            $currency = $order->get_currency();

            $webhook_url = WC()->api_request_url('wc_gateway_kkhay');
            $return_url  = $this->get_return_url($order);
            $cancel_url  = $order->get_cancel_order_url();

            $payload = [
                'priceAmount'   => $amount,
                'priceCurrency' => strtoupper($currency),
                'orderId'       => (string) $order->get_id(),
                'title'         => sprintf(__('Order #%s on %s', 'kkhay-woocommerce'), $order->get_order_number(), get_bloginfo('name')),
                'description'   => sprintf(__('Payment for order #%s (%d item(s))', 'kkhay-woocommerce'), $order->get_order_number(), $order->get_item_count()),
                'redirectUrl'   => $return_url,
                'cancelUrl'     => $cancel_url,
                'ipnUrl'        => $webhook_url,
                'metadata'      => [
                    'source'           => 'woocommerce',
                    'order_id'         => $order->get_id(),
                    'order_key'        => $order->get_order_key(),
                    'customer_email'   => $order->get_billing_email(),
                    'customer_name'    => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                ],
            ];

            $this->log(sprintf('Creating invoice for Order #%d: Total %s %s', $order->get_id(), $amount, $currency));

            $response = $this->api->create_invoice($payload);
            $invoice  = $response['data'] ?? $response;

            $invoice_id = $invoice['id'] ?? $invoice['invoiceId'] ?? null;
            $hosted_url = $invoice['hostedUrl'] ?? $invoice['hosted_url'] ?? $invoice['checkoutUrl'] ?? null;

            if (empty($invoice_id) || empty($hosted_url)) {
                throw new Exception(__('Invalid invoice response received from K Khay gateway.', 'kkhay-woocommerce'));
            }

            // Store metadata on order
            $order->update_meta_data('_kkhay_invoice_id', $invoice_id);
            $order->update_meta_data('_kkhay_hosted_url', $hosted_url);
            $order->save();

            // Set order status to pending payment
            $order->update_status('pending', sprintf(__('Awaiting K Khay crypto payment. Invoice ID: %s', 'kkhay-woocommerce'), $invoice_id));

            // Reduce cart stock
            wc_reduce_stock_levels($order->get_id());

            // Clear cart
            WC()->cart->empty_cart();

            $this->log(sprintf('Order #%d created invoice %s, redirecting to %s', $order->get_id(), $invoice_id, $hosted_url));

            return [
                'result'   => 'success',
                'redirect' => $hosted_url,
            ];
        } catch (Exception $e) {
            $this->log('Payment error: ' . $e->getMessage(), 'error');
            wc_add_notice(sprintf(__('Payment failed: %s', 'kkhay-woocommerce'), $e->getMessage()), 'error');
            return ['result' => 'fail'];
        }
    }

    /**
     * Handle incoming webhooks.
     */
    public function handle_webhook(): void
    {
        $handler = new WC_Kkhay_Webhook_Handler($this);
        $handler->handle();
    }

    public function get_ipn_secret(): string
    {
        return $this->ipn_secret;
    }

    public function get_completed_order_status(): string
    {
        return $this->order_status_paid;
    }

    /**
     * Internal logging helper.
     */
    public function log(string $message, string $level = 'info'): void
    {
        if ($this->debug && function_exists('wc_get_logger')) {
            wc_get_logger()->log($level, $message, ['source' => 'kkhay-woocommerce']);
        }
    }
}

