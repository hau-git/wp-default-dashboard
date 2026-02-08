<?php

defined('ABSPATH') || exit;

class WPD_Branding {

    protected WPD_Plugin $plugin;

    public function __construct(WPD_Plugin $plugin) {
        $this->plugin = $plugin;
    }

    public function register(): void {
        $options = wpd_get_options();

        if (!empty($options['enable_login_branding'])) {
            add_action('login_enqueue_scripts', [$this, 'enqueue_login_styles']);
            add_filter('login_headerurl', [$this, 'login_logo_url']);
            add_filter('login_headertext', [$this, 'login_logo_title']);
        }

        if (!empty($options['enable_admin_branding'])) {
            add_filter('admin_footer_text', [$this, 'admin_footer_text']);
            add_action('admin_bar_menu', [$this, 'admin_bar_link'], 100);
        }
    }

    public function enqueue_login_styles(): void {
        wp_enqueue_style(
            'wpd-login',
            WPD_PLUGIN_URL . 'assets/login.css',
            [],
            WPD_VERSION
        );

        $options = wpd_get_options();
        $css     = '';

        if (!empty($options['login_logo_url'])) {
            $css .= sprintf(
                '#login h1 a, .login h1 a { background-image: url(%s); background-size: contain; width: 320px; height: 80px; }',
                esc_url($options['login_logo_url'])
            );
        }

        if (!empty($options['login_bg_color'])) {
            $css .= sprintf(
                'body.login { background-color: %s; }',
                esc_attr($options['login_bg_color'])
            );
        }

        if (!empty($options['login_bg_image'])) {
            $css .= sprintf(
                'body.login { background-image: url(%s); background-size: cover; background-position: center; }',
                esc_url($options['login_bg_image'])
            );
        }

        if (!empty($css)) {
            wp_add_inline_style('wpd-login', $css);
        }
    }

    public function login_logo_url(): string {
        $url = wpd_get_option('login_logo_link', '');
        return !empty($url) ? $url : home_url('/');
    }

    public function login_logo_title(): string {
        $title = wpd_get_option('login_logo_title', '');
        return !empty($title) ? $title : get_bloginfo('name');
    }

    public function admin_footer_text(string $text): string {
        $custom = wpd_get_option('admin_footer_text', '');
        if (!empty($custom)) {
            return wp_kses_post($custom);
        }
        return $text;
    }

    public function admin_bar_link(\WP_Admin_Bar $wp_admin_bar): void {
        $options = wpd_get_options();
        $label   = $options['admin_bar_link_label'] ?? '';
        $url     = $options['admin_bar_link_url'] ?? '';

        if (empty($label) || empty($url)) {
            return;
        }

        $wp_admin_bar->add_node([
            'id'    => 'wpd-branding-link',
            'title' => esc_html($label),
            'href'  => esc_url($url),
            'meta'  => ['target' => '_blank'],
        ]);
    }
}
