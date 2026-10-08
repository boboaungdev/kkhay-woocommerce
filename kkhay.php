<?php
/**
 * Plugin Name: K Khay – Crypto Payments for WooCommerce (USDT, USDC, BNB, ETH)
 * Plugin URI: https://kkhay.com
 * Description: Accept non-custodial and custodial crypto payments (USDT, USDC, BNB, ETH on BSC, Polygon, Arbitrum, Base, Ethereum) directly in your WooCommerce store with zero chargebacks.
 * Version: 1.0.0
 * Author: Bo Bo
 * Author URI: https://github.com/boboaungdev
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: kkhay
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.3
 *
 * @package Kkhay
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KKHAY_VERSION', '1.0.0');
define('KKHAY_PLUGIN_FILE', __FILE__);
define('KKHAY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('KKHAY_PLUGIN_URL', plugin_dir_url(__FILE__));

// Backward compatibility constants
define('KKHAY_WOOCOMMERCE_VERSION', KKHAY_VERSION);
define('KKHAY_WOOCOMMERCE_PLUGIN_FILE', KKHAY_PLUGIN_FILE);
define('KKHAY_WOOCOMMERCE_PLUGIN_PATH', KKHAY_PLUGIN_PATH);
define('KKHAY_WOOCOMMERCE_PLUGIN_URL', KKHAY_PLUGIN_URL);

/**
 * Declare HPOS (High-Performance Order Storage) and Blocks compatibility.
 */
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

/**
 * Initialize K Khay Gateway once WooCommerce is loaded.
 */
add_action('plugins_loaded', 'kkhay_woocommerce_init', 11);

function kkhay_woocommerce_init(): void
{
    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'kkhay_woocommerce_missing_wc_notice');
        return;
    }

    // Load plugin dependencies
    require_once KKHAY_WOOCOMMERCE_PLUGIN_PATH . 'includes/class-kkhay-api.php';
    require_once KKHAY_WOOCOMMERCE_PLUGIN_PATH . 'includes/class-kkhay-webhook-handler.php';
    require_once KKHAY_WOOCOMMERCE_PLUGIN_PATH . 'includes/class-wc-gateway-kkhay.php';

    // Register gateway with WooCommerce
    add_filter('woocommerce_payment_gateways', 'kkhay_woocommerce_add_gateway');

    // Register action links on plugins page
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'kkhay_woocommerce_plugin_action_links');

    // Load text domain for translations
    load_plugin_textdomain('kkhay', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

/**
 * Register K Khay gateway class.
 *
 * @param array $gateways
 * @return array
 */
function kkhay_woocommerce_add_gateway(array $gateways): array
{
    $gateways[] = 'WC_Gateway_Kkhay';
    return $gateways;
}

/**
 * Register WooCommerce Blocks checkout support.
 */
add_action('woocommerce_blocks_loaded', function () {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once KKHAY_WOOCOMMERCE_PLUGIN_PATH . 'includes/class-kkhay-blocks-support.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $registry) {
            $registry->register(new WC_Kkhay_Blocks_Support());
        }
    );
});

/**
 * Add settings shortcut link to plugins table.
 *
 * @param array $links
 * @return array
 */
function kkhay_woocommerce_plugin_action_links(array $links): array
{
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=kkhay')),
        __('Settings', 'kkhay-woocommerce')
    );
    array_unshift($links, $settings_link);
    return $links;
}

/**
 * Notice when WooCommerce is not installed or active.
 */
function kkhay_woocommerce_missing_wc_notice(): void
{
    echo '<div class="notice notice-error"><p>' .
        esc_html__('K Khay Sovereign Crypto Gateway requires WooCommerce to be installed and active.', 'kkhay-woocommerce') .
        '</p></div>';
}

