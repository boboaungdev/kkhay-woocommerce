<?php
/**
 * K Khay API Client for WooCommerce
 *
 * @package Kkhay_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kkhay_API
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
        $clean_path = (0 === strpos($path, '/')) ? $path : '/' . $path;
        if (substr($this->base_url, -4) === '/api' || false !== strpos($this->base_url, 'api.')) {
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
            throw new Exception(esc_html__('K Khay API Key is missing. Please configure your API key in WooCommerce settings.', 'kkhay'));
        }

        $url = $this->get_endpoint_url($path);

        $headers = [
            'Accept'       => 'application/json',
            'x-api-key'    => $this->api_key,
            'User-Agent'   => 'kkhay/1.0.0 (WordPress/' . get_bloginfo('version') . '; WooCommerce/' . (defined('WC_VERSION') ? WC_VERSION : 'unknown') . ')',
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
                ['source' => 'kkhay']
            );
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            if ($this->debug && function_exists('wc_get_logger')) {
                wc_get_logger()->error('K Khay Network Error: ' . $error_message, ['source' => 'kkhay']);
            }
            /* translators: %s: Network error message details */
            throw new Exception(sprintf(esc_html__('Network error communicating with K Khay: %s', 'kkhay'), esc_html($error_message)));
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $raw_body    = wp_remote_retrieve_body($response);
        $decoded     = json_decode($raw_body, true);

        if ($this->debug && function_exists('wc_get_logger')) {
            wc_get_logger()->debug(
                sprintf('K Khay API Response [%d]: %s', $status_code, $raw_body),
                ['source' => 'kkhay']
            );
        }

        if ($status_code >= 400) {
            $msg = is_array($decoded) ? ($decoded['message'] ?? $decoded['error'] ?? 'API error') : $raw_body;
            /* translators: 1: HTTP status code, 2: API error message */
            throw new Exception(sprintf(esc_html__('K Khay API error (%1$d): %2$s', 'kkhay'), (int) $status_code, esc_html($msg)));
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

// Backward compatibility alias
if (!class_exists('WC_Kkhay_API')) {
    class_alias('Kkhay_API', 'WC_Kkhay_API');
}
