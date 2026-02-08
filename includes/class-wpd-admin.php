<?php

defined('ABSPATH') || exit;

class WPD_Admin {

    protected WPD_Plugin $plugin;

    public function __construct(WPD_Plugin $plugin) {
        $this->plugin = $plugin;
    }

    public function register(): void {
        add_action('admin_init', [$this, 'on_admin_init']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function on_admin_init(): void {
        if (!wpd_is_admin_user()) {
            return;
        }
        do_action('wpd_admin_init');
    }

    public function enqueue_assets(string $hook): void {
        if (!wpd_is_admin_user()) {
            return;
        }

        if ($hook === 'settings_page_wpd-settings') {
            wp_enqueue_media();
            wp_enqueue_style(
                'wpd-admin',
                WPD_PLUGIN_URL . 'assets/admin.css',
                [],
                WPD_VERSION
            );
            wp_enqueue_script(
                'wpd-admin',
                WPD_PLUGIN_URL . 'assets/admin.js',
                ['jquery'],
                WPD_VERSION,
                true
            );
        }

        if ($hook === 'index.php') {
            wp_enqueue_style(
                'wpd-dashboard',
                WPD_PLUGIN_URL . 'assets/admin.css',
                [],
                WPD_VERSION
            );
        }
    }
}
