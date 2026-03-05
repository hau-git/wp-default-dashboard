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

        if (!empty($options['hardening_disable_rest_users'])) {
            add_filter('rest_endpoints', [$this, 'disable_rest_users_endpoint']);
        }

        if (!empty($options['hardening_security_headers'])) {
            add_action('send_headers', [$this, 'add_security_headers']);
        }

        if (!empty($options['hardening_remove_version_assets'])) {
            add_filter('script_loader_src', [$this, 'remove_version_from_url'], 15);
            add_filter('style_loader_src', [$this, 'remove_version_from_url'], 15);
        }

        if (!empty($options['hardening_disable_app_passwords'])) {
            add_filter('wp_is_application_passwords_available', '__return_false');
        }

        if (!empty($options['hardening_disable_pingbacks'])) {
            add_filter('xmlrpc_methods', [$this, 'disable_pingback_xmlrpc_methods']);
            add_filter('pings_open', '__return_false');
            add_action('pre_ping', [$this, 'disable_self_ping']);
        }

        if (!empty($options['hardening_disable_emoji'])) {
            $this->disable_emoji();
        }

        if (!empty($options['hardening_disable_jquery_migrate'])) {
            add_action('wp_default_scripts', [$this, 'disable_jquery_migrate']);
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

    public function disable_rest_users_endpoint(array $endpoints): array {
        if (!is_user_logged_in()) {
            unset($endpoints['/wp/v2/users']);
            unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
        }
        return $endpoints;
    }

    public function add_security_headers(): void {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }

    public function remove_version_from_url(string $src): string {
        if (str_contains($src, 'ver=')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }

    public function disable_pingback_xmlrpc_methods(array $methods): array {
        unset($methods['pingback.ping'], $methods['pingback.extensions.getPingbacks']);
        return $methods;
    }

    public function disable_self_ping(array &$links): void {
        $home = home_url();
        foreach ($links as $key => $link) {
            if (str_starts_with($link, $home)) {
                unset($links[$key]);
            }
        }
    }

    protected function disable_emoji(): void {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        add_filter('tiny_mce_plugins', [$this, 'disable_emoji_tinymce']);
        add_filter('wp_resource_hints', [$this, 'disable_emoji_dns_prefetch'], 10, 2);
    }

    public function disable_emoji_tinymce(array $plugins): array {
        return array_diff($plugins, ['wpemoji']);
    }

    public function disable_emoji_dns_prefetch(array $urls, string $relation_type): array {
        if ($relation_type === 'dns-prefetch') {
            $urls = array_filter($urls, static fn($url) => !str_contains((string) $url, 'https://s.w.org'));
        }
        return $urls;
    }

    public function disable_jquery_migrate(\WP_Scripts $scripts): void {
        if (!is_admin() && isset($scripts->registered['jquery'])) {
            $scripts->registered['jquery']->deps = array_diff(
                $scripts->registered['jquery']->deps ?? [],
                ['jquery-migrate']
            );
        }
    }
}
