<?php
/**
 * Plugin Name: WP Default
 * Plugin URI:  https://github.com/hau-git/wp-default-dashboard
 * Description: A clean, standardized WordPress admin baseline — dashboard cleanup, branding, and light hardening.
 * Version:     1.0.1
 * Author:      Marc Probst
 * Author URI:  https://github.com/hau-git
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpd
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 */

defined('ABSPATH') || exit;

if (defined('WPD_VERSION')) {
    return;
}

define('WPD_VERSION', '1.0.1');
define('WPD_PLUGIN_FILE', __FILE__);
define('WPD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPD_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WPD_OPTION_KEY', 'wpd_options');
define('WPD_MIN_PHP', '8.1');

if (version_compare(PHP_VERSION, WPD_MIN_PHP, '<')) {
    add_action('admin_notices', static function (): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html(
                sprintf(
                    __('WP Default requires PHP %s or higher. You are running PHP %s.', 'wpd'),
                    WPD_MIN_PHP,
                    PHP_VERSION
                )
            )
        );
    });
    return;
}

require_once WPD_PLUGIN_DIR . 'includes/helpers.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-plugin.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-settings.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-admin.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-dashboard.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-branding.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-hardening.php';
require_once WPD_PLUGIN_DIR . 'includes/class-wpd-updater.php';

register_activation_hook(__FILE__, static function (): void {
    if (false === get_option(WPD_OPTION_KEY)) {
        require_once WPD_PLUGIN_DIR . 'includes/helpers.php';
        add_option(WPD_OPTION_KEY, wpd_get_defaults(), '', 'no');
    }
});

add_filter('plugin_action_links_' . WPD_PLUGIN_BASENAME, static function (array $links): array {
    $settings_url  = admin_url('options-general.php?page=wpd-settings');
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url($settings_url),
        esc_html__('Settings', 'wpd')
    );
    array_unshift($links, $settings_link);
    return $links;
});

add_action('plugins_loaded', static function (): void {
    load_plugin_textdomain('wpd', false, dirname(plugin_basename(__FILE__)) . '/languages');

    $plugin = WPD_Plugin::get_instance();
    $plugin->init();
});
