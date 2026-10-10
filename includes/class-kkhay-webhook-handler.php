<?php
/**
 * K Khay Webhook & IPN Handler for WooCommerce
 *
 * @package Kkhay_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kkhay_Webhook_Handler
{
    /**
     * Gateway instance.
     *
     * @var object
     */
    private $gateway;

    public function __construct($gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Handle incoming webhook requests.
     */
    public function handle(): void
    {
        $raw_body  = file_get_contents('php://input');
        $signature = '';

        if (isset($_SERVER['HTTP_X_KKHAY_SIGNATURE'])) {
            $signature = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_KKHAY_SIGNATURE']));
        } elseif (isset($_SERVER['HTTP_X_SIGNATURE'])) {
            $signature = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_SIGNATURE']));
        }

        if (empty($raw_body)) {
            $this->respond(400, 'Empty payload');
        }

        $secret = $this->gateway->get_ipn_secret();

        if (!empty($secret)) {
            if (empty($signature)) {
                $this->gateway->log('Webhook rejected: Missing signature header.');
                $this->respond(401, 'Missing signature header');
            }

            $computed = hash_hmac('sha256', $raw_body, $secret);
            if (!hash_equals($computed, trim($signature))) {
                $this->gateway->log('Webhook rejected: Invalid signature.');
                $this->respond(403, 'Invalid signature');
            }
        }

        $event = json_decode($raw_body, true);
        if (!is_array($event)) {
            $this->respond(400, 'Invalid JSON');
        }

        $this->gateway->log('Webhook received event: ' . ($event['event'] ?? 'unknown'));

        $this->process_event($event);

        $this->respond(200, 'Event processed');
    }

    /**
     * Process validated webhook event.
     *
     * @param array $event
     */
    private function process_event(array $event): void
    {
        $event_type = $event['event'] ?? '';
        $data       = $event['data'] ?? $event;

        $invoice_id = $data['id'] ?? $data['invoiceId'] ?? null;
        $order_id   = $data['orderId'] ?? $data['order_id'] ?? null;

        // Locate order either by order ID or stored invoice ID
        $order = null;
        if (!empty($order_id)) {
            $order = wc_get_order($order_id);
        }

        if (!$order && !empty($invoice_id)) {
            // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            $orders = wc_get_orders([
                'limit'        => 1,
                'meta_key'     => '_kkhay_invoice_id',
                'meta_value'   => sanitize_text_field($invoice_id),
                'meta_compare' => '=',
            ]);
            // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            if (!empty($orders)) {
                $order = $orders[0];
            }
        }

        if (!$order) {
            $this->gateway->log(sprintf('Order not found for Invoice ID: %s / Order ID: %s', $invoice_id, $order_id));
            return;
        }

        switch ($event_type) {
            case 'invoice.paid':
            case 'invoice.completed':
                $this->handle_paid($order, $data);
                break;

            case 'invoice.expired':
                $this->handle_expired($order, $data);
                break;

            case 'invoice.cancelled':
                $this->handle_cancelled($order, $data);
                break;

            case 'invoice.partial':
                $this->handle_partial($order, $data);
                break;

            default:
                $this->gateway->log('Unhandled event type: ' . $event_type);
                break;
        }
    }

    private function handle_paid(WC_Order $order, array $data): void
    {
        if ($order->is_paid()) {
            $this->gateway->log(sprintf('Order #%d is already marked paid.', $order->get_id()));
            return;
        }

        $tx_hash   = $data['txHash'] ?? $data['tx_hash'] ?? '';
        $pay_token = $data['payToken'] ?? $data['pay_token'] ?? '';
        $network   = $data['payNetwork'] ?? $data['pay_network'] ?? '';
        $amount    = $data['payAmount'] ?? $data['pay_amount'] ?? '';

        /* translators: 1: Token symbol, 2: Blockchain network, 3: Amount, 4: Token symbol */
        $note = sprintf(esc_html__('K Khay: Payment confirmed via %1$s on %2$s. Amount: %3$s %4$s.', 'kkhay'), esc_html(strtoupper($pay_token)), esc_html(strtoupper($network)), esc_html($amount), esc_html(strtoupper($pay_token)));

        if (!empty($tx_hash)) {
            /* translators: %s: Blockchain transaction hash */
            $note .= ' ' . sprintf(esc_html__('Tx Hash: %s', 'kkhay'), esc_html($tx_hash));
            $order->update_meta_data('_kkhay_tx_hash', $tx_hash);
        }

        $order->add_order_note($note);

        $target_status = $this->gateway->get_completed_order_status();
        if ($target_status === 'completed') {
            $order->payment_complete($tx_hash);
            $order->update_status('completed', esc_html__('Order auto-completed after crypto settlement.', 'kkhay'));
        } else {
            $order->payment_complete($tx_hash);
        }

        $this->gateway->log(sprintf('Order #%d marked paid successfully.', $order->get_id()));
    }

    private function handle_expired(WC_Order $order, array $data): void
    {
        if ($order->has_status(['pending', 'on-hold'])) {
            $order->update_status('cancelled', esc_html__('K Khay: Crypto invoice expired without payment.', 'kkhay'));
            $this->gateway->log(sprintf('Order #%d marked cancelled (invoice expired).', $order->get_id()));
        }
    }

    private function handle_cancelled(WC_Order $order, array $data): void
    {
        if ($order->has_status(['pending', 'on-hold'])) {
            $order->update_status('cancelled', esc_html__('K Khay: Invoice was cancelled.', 'kkhay'));
            $this->gateway->log(sprintf('Order #%d marked cancelled.', $order->get_id()));
        }
    }

    private function handle_partial(WC_Order $order, array $data): void
    {
        $paid   = $data['payAmount'] ?? $data['paidAmount'] ?? '0';
        $token  = $data['payToken'] ?? '';
        /* translators: 1: Paid amount, 2: Token symbol */
        $order->add_order_note(sprintf(esc_html__('K Khay Alert: Underpayment detected. Customer paid %1$s %2$s. Please check with customer before fulfilling.', 'kkhay'), esc_html($paid), esc_html(strtoupper($token))));
        $this->gateway->log(sprintf('Order #%d underpaid warning recorded.', $order->get_id()));
    }

    private function respond(int $code, string $message): void
    {
        status_header($code);
        header('Content-Type: application/json; charset=utf-8');
        echo wp_json_encode(['status' => $code < 400 ? 'success' : 'error', 'message' => $message]);
        exit;
    }
}

// Backward compatibility alias
if (!class_exists('WC_Kkhay_Webhook_Handler')) {
    class_alias('Kkhay_Webhook_Handler', 'WC_Kkhay_Webhook_Handler');
}
