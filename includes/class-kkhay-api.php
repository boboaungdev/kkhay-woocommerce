<?php
/**
 * K Khay API Client for WooCommerce
 *
 * @package Kkhay_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Kkhay_API
{
    private string $api_key;
    private string $base_url;
    private int $timeout;
    private bool $debug;

    public function __construct(string $api_key, string $base_url = 'https://api.kkhay.com', int $timeout = 30, bool $debug = false)
    {
        $this->api_key = trim($api_key);
        $this->base_url = rtrim(trim($base_url), '/');
        $this->timeout = $timeout;
        $this->debug = $debug;
    }

    private function get_endpoint_url(string $path): string
    {
        $clean_path = str_starts_with($path, '/') ? $path : '/' . $path;
        if (str_ends_with($this->base_url, '/api') || str_contains($this->base_url, 'api.')) {
            return $this->base_url . $clean_path;
        }
        return $this->base_url . '/api' . $clean_path;
    }

    /**
     * Send HTTP request using WordPress HTTP API.
     *
     * @param string $path
     * @param string $method
     * @param array|null $body
     * @return array
     * @throws Exception
     */
    public function request(string $path, string $method = 'GET', ?array $body = null): array
    {
        if (empty($this->api_key)) {
            throw new Exception(__('K Khay API Key is missing. Please configure your API key in WooCommerce settings.', 'kkhay-woocommerce'));
        }

        $url = $this->get_endpoint_url($path);

        $headers = [
            'Accept'       => 'application/json',
            'x-api-key'    => $this->api_key,
            'User-Agent'   => 'kkhay-woocommerce/1.0.0 (WordPress/' . get_bloginfo('version') . '; WooCommerce/' . (defined('WC_VERSION') ? WC_VERSION : 'unknown') . ')',
        ];

        $args = [
            'method'      => $method,
            'timeout'     => $this->timeout,
            'redirection' => 5,
            'httpversion' => '1.1',
            'headers'     => $headers,
            'sslverify'   => true,
        ];

        if ($body !== null) {
            $headers['Content-Type'] = 'application/json';
            $args['headers'] = $headers;
            $args['body'] = wp_json_encode($body);
        }

        if ($this->debug && function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $logger->debug(
                sprintf('K Khay API Request: %s %s | Body: %s', $method, $url, wp_json_encode($body)),
                ['source' => 'kkhay-woocommerce']
            );
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            if ($this->debug && function_exists('wc_get_logger')) {
                wc_get_logger()->error('K Khay Network Error: ' . $error_message, ['source' => 'kkhay-woocommerce']);
            }
            throw new Exception(sprintf(__('Network error communicating with K Khay: %s', 'kkhay-woocommerce'), $error_message));
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $raw_body    = wp_remote_retrieve_body($response);
        $decoded     = json_decode($raw_body, true);

        if ($this->debug && function_exists('wc_get_logger')) {
            wc_get_logger()->debug(
                sprintf('K Khay API Response [%d]: %s', $status_code, $raw_body),
                ['source' => 'kkhay-woocommerce']
            );
        }

        if ($status_code >= 400) {
            $msg = is_array($decoded) ? ($decoded['message'] ?? $decoded['error'] ?? 'API error') : $raw_body;
            throw new Exception(sprintf(__('K Khay API error (%d): %s', 'kkhay-woocommerce'), $status_code, $msg));
        }

        return is_array($decoded) ? $decoded : ['data' => $raw_body];
    }

    /**
     * Create an invoice for an order.
     *
     * @param array $payload
     * @return array
     * @throws Exception
     */
    public function create_invoice(array $payload): array
    {
        return $this->request('/v1/merchant/invoices', 'POST', $payload);
    }

    /**
     * Retrieve status and transaction details of an invoice.
     *
     * @param string $invoice_id
     * @return array
     * @throws Exception
     */
    public function get_invoice(string $invoice_id): array
    {
        return $this->request('/v1/merchant/invoices/' . rawurlencode(trim($invoice_id)), 'GET');
    }

    /**
     * Check gateway health.
     *
     * @return array
     * @throws Exception
     */
    public function check_health(): array
    {
        return $this->request('/health', 'GET');
    }
}

