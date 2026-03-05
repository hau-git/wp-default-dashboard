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

        $options = wpd_get_options();

        // Hide update nag / notices for non-admins
        if (!empty($options['admin_hide_update_notices'])) {
            add_action('admin_init', [$this, 'hide_update_notices_for_non_admins']);
        }

        // Maintenance mode — intercept frontend for non-logged-in visitors
        if (!empty($options['enable_maintenance_mode'])) {
            add_action('template_redirect', [$this, 'maintenance_mode']);
        }

        // Hide admin menu items for non-admins
        if (!empty($options['admin_hide_menu_items'])) {
            add_action('admin_menu', [$this, 'hide_admin_menu_items'], 999);
        }
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

    public function hide_update_notices_for_non_admins(): void {
        if (wpd_is_admin_user()) {
            return;
        }
        add_filter('pre_site_transient_update_core', '__return_null');
        add_filter('pre_site_transient_update_plugins', '__return_null');
        add_filter('pre_site_transient_update_themes', '__return_null');
        remove_action('admin_notices', 'update_nag', 3);
        remove_action('admin_notices', 'maintenance_nag', 10);
    }

    public function maintenance_mode(): void {
        if (is_admin() || is_user_logged_in()) {
            return;
        }
        wp_die(
            '<h1 style="font-family:sans-serif;">Wartungsarbeiten</h1><p style="font-family:sans-serif;">Die Website wird gerade gewartet. Bitte versuche es später erneut.</p>',
            'Wartungsarbeiten',
            ['response' => 503]
        );
    }

    public function hide_admin_menu_items(): void {
        if (wpd_is_admin_user()) {
            return;
        }
        $items = wpd_get_option('admin_hide_menu_items', []);
        foreach ($items as $slug) {
            remove_menu_page(sanitize_text_field($slug));
        }
    }
}
