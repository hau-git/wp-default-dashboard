<?php

defined('ABSPATH') || exit;

class WPD_Hardening {

    protected WPD_Plugin $plugin;

    public function __construct(WPD_Plugin $plugin) {
        $this->plugin = $plugin;
    }

    public function register(): void {
        $options = wpd_get_options();

        if (empty($options['enable_hardening'])) {
            return;
        }

        if (!empty($options['hardening_disable_xmlrpc'])) {
            add_filter('xmlrpc_enabled', '__return_false');
            add_filter('wp_headers', [$this, 'remove_xmlrpc_header']);
        }

        $author_enum = $options['hardening_author_enum'] ?? 'off';
        if ($author_enum !== 'off') {
            add_action('template_redirect', [$this, 'block_author_enumeration']);
        }

        if (!empty($options['hardening_remove_rsd'])) {
            remove_action('wp_head', 'rsd_link');
        }

        if (!empty($options['hardening_remove_wlw'])) {
            remove_action('wp_head', 'wlw_manifest_link');
        }

        if (!empty($options['hardening_remove_generator'])) {
            remove_action('wp_head', 'wp_generator');
            add_filter('the_generator', '__return_empty_string');
        }

        if (!empty($options['hardening_hide_editor'])) {
            add_action('admin_menu', [$this, 'hide_editor_menus'], 999);
        }
    }

    public function remove_xmlrpc_header(array $headers): array {
        unset($headers['X-Pingback']);
        return $headers;
    }

    public function block_author_enumeration(): void {
        if (!isset($_GET['author']) || is_admin()) {
            return;
        }

        $options     = wpd_get_options();
        $author_enum = $options['hardening_author_enum'] ?? 'off';

        if ($author_enum === '404') {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
            return;
        }

        if ($author_enum === 'redirect') {
            wp_safe_redirect(home_url('/'), 301);
            exit;
        }
    }

    public function hide_editor_menus(): void {
        remove_submenu_page('themes.php', 'theme-editor.php');
        remove_submenu_page('plugins.php', 'plugin-editor.php');
    }
}
