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

        // Custom admin bar greeting
        $greeting = $options['admin_bar_greeting'] ?? '';
        if (!empty($greeting)) {
            add_filter('gettext', [$this, 'custom_admin_bar_greeting'], 10, 3);
        }

        // Admin color scheme — force for all users
        if (!empty($options['admin_color_scheme'])) {
            add_filter('get_user_option_admin_color', [$this, 'force_admin_color_scheme']);
        }

        // Custom admin colors via CSS variables
        if (!empty($options['admin_primary_color']) || !empty($options['admin_accent_color'])) {
            add_action('admin_head', [$this, 'inject_custom_admin_colors']);
        }

        // Environment indicator in admin bar
        $env = $options['admin_environment'] ?? 'off';
        if ($env !== 'off') {
            add_action('admin_bar_menu', [$this, 'add_environment_indicator'], 5);
            add_action('admin_head', [$this, 'inject_environment_styles']);
            add_action('wp_head', [$this, 'inject_environment_styles']);
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

        if (!empty($options['login_button_color'])) {
            $btn = esc_attr($options['login_button_color']);
            $css .= ".wp-core-ui .button-primary { background: {$btn}; border-color: {$btn}; }";
            $css .= ".wp-core-ui .button-primary:hover, .wp-core-ui .button-primary:focus { background: {$btn}; border-color: {$btn}; filter: brightness(0.9); }";
        }

        if (!empty($options['login_link_color'])) {
            $lnk = esc_attr($options['login_link_color']);
            $css .= ".login #nav a, .login #backtoblog a, .login .privacy-policy-page-link a { color: {$lnk}; }";
            $css .= ".login #nav a:hover, .login #backtoblog a:hover { color: {$lnk}; text-decoration: underline; }";
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

    public function custom_admin_bar_greeting(string $translation, string $text, string $domain): string {
        if ($domain !== 'default') {
            return $translation;
        }
        if ($text !== 'Howdy, %s') {
            return $translation;
        }
        $greeting = wpd_get_option('admin_bar_greeting', '');
        if (!empty($greeting)) {
            return $greeting;
        }
        return $translation;
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

    public function force_admin_color_scheme(): string {
        return wpd_get_option('admin_color_scheme', '');
    }

    public function inject_custom_admin_colors(): void {
        $primary = wpd_get_option('admin_primary_color', '');
        $accent  = wpd_get_option('admin_accent_color', '');

        if (empty($primary) && empty($accent)) {
            return;
        }

        echo '<style id="wpd-custom-admin-colors">';

        if (!empty($primary)) {
            $p = esc_attr($primary);
            echo ":root { --wp-admin-theme-color: {$p}; }";
            echo "#adminmenu, #adminmenuback, #adminmenuwrap { background: {$p}; }";
            echo "#adminmenu a { color: rgba(255,255,255,0.85); }";
            echo "#adminmenu li.current a.menu-top, #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu { background: rgba(0,0,0,0.15); }";
            echo "#adminmenu .wp-submenu { background: rgba(0,0,0,0.2); }";
            echo "#adminmenu .wp-submenu a { color: rgba(255,255,255,0.7); }";
            echo "#adminmenu .wp-submenu a:hover, #adminmenu .wp-submenu li.current a { color: #fff; }";
        }

        if (!empty($accent)) {
            $a = esc_attr($accent);
            echo ".wp-core-ui .button-primary { background: {$a}; border-color: {$a}; }";
            echo ".wp-core-ui .button-primary:hover, .wp-core-ui .button-primary:focus { background: {$a}; border-color: {$a}; filter: brightness(0.9); box-shadow: none; }";
        }

        echo '</style>';
    }

    public function add_environment_indicator(\WP_Admin_Bar $wp_admin_bar): void {
        $env = wpd_get_option('admin_environment', 'off');
        if ($env === 'off') {
            return;
        }

        $options  = wpd_get_options();
        $is_live  = ($env === 'live');
        $badge_label = $is_live ? __('Live', 'wpd') : __('Staging', 'wpd');
        $badge_class = $is_live ? 'wpd-env-live' : 'wpd-env-stage';

        $title = sprintf(
            '%s: <span class="wpd-env-badge %s">%s</span>',
            esc_html__('Umgebung', 'wpd'),
            esc_attr($badge_class),
            esc_html($badge_label)
        );

        $wp_admin_bar->add_node([
            'id'     => 'wpd-environment',
            'parent' => 'top-secondary',
            'title'  => $title,
            'href'   => false,
            'meta'   => ['class' => 'wpd-env-indicator'],
        ]);

        if ($is_live) {
            $switch_url   = $options['admin_environment_stage_url'] ?? '';
            $switch_label = __('Staging öffnen', 'wpd');
        } else {
            $switch_url   = $options['admin_environment_live_url'] ?? '';
            $switch_label = __('Live öffnen', 'wpd');
        }

        if (!empty($switch_url)) {
            $wp_admin_bar->add_node([
                'id'     => 'wpd-environment-switch',
                'parent' => 'wpd-environment',
                'title'  => esc_html($switch_label),
                'href'   => esc_url($switch_url),
                'meta'   => ['target' => '_blank'],
            ]);
        }
    }

    public function inject_environment_styles(): void {
        echo '<style id="wpd-env-styles">
            #wpadminbar .wpd-env-indicator > .ab-item {
                display: flex;
                align-items: center;
                gap: 6px;
                height: 32px;
                padding: 0 8px;
                color: #c3c4c7 !important;
                font-size: 13px;
            }
            .wpd-env-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 700;
                color: #fff;
                line-height: 18px;
                letter-spacing: 0.03em;
            }
            .wpd-env-badge.wpd-env-live { background: #00a32a; }
            .wpd-env-badge.wpd-env-stage { background: #d63638; }
            #wpadminbar .wpd-env-indicator .ab-submenu { min-width: 140px; }
        </style>';
    }
}
