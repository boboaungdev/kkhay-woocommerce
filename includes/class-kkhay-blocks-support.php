<?php
/**
 * WooCommerce Blocks Support for K Khay Gateway
 *
 * @package Kkhay_WooCommerce
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if (!defined('ABSPATH')) {
    exit;
}

final class Kkhay_Blocks_Support extends AbstractPaymentMethodType
{
    protected $name = 'kkhay';
    private $gateway;

    public function initialize(): void
    {
        $this->settings = get_option('woocommerce_kkhay_settings', []);
        $gateways       = WC()->payment_gateways->payment_gateways();
        $this->gateway  = $gateways['kkhay'] ?? new Kkhay_WC_Gateway();
    }

    public function is_active(): bool
    {
        return !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
    }

    public function get_payment_method_script_handles(): array
    {
        return [];
    }

    public function get_payment_method_data(): array
    {
        return [
            'title'       => $this->get_setting('title', __('Crypto (USDT, USDC, BNB, ETH via K Khay)', 'kkhay')),
            'description' => $this->get_setting('description', __('Pay securely with cryptocurrency across multiple blockchains.', 'kkhay')),
            'icon'        => KKHAY_WOOCOMMERCE_PLUGIN_URL . 'assets/images/kkhay-badge.svg',
            'supports'    => ['products'],
        ];
    }
}

// Backward compatibility alias
if (!class_exists('WC_Kkhay_Blocks_Support')) {
    class_alias('Kkhay_Blocks_Support', 'WC_Kkhay_Blocks_Support');
}
