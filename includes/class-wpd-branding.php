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

        // Custom admin bar greeting — intercept at translation level so it
        // reliably replaces "Howdy" regardless of WP version or hook order.
        add_filter('gettext', [$this, 'filter_admin_bar_greeting'], 10, 3);

        // Admin color scheme — force for all users
        if (!empty($options['admin_color_scheme'])) {
            add_filter('get_user_option_admin_color', [$this, 'force_admin_color_scheme']);
        }

        // Custom admin colors via CSS variables
        $has_custom_colors = !empty($options['admin_primary_color'])
            || !empty($options['admin_accent_color'])
            || !empty($options['admin_bar_bg_color'])
            || !empty($options['admin_bar_text_color'])
            || !empty($options['admin_menu_text_color']);
        if ($has_custom_colors) {
            add_action('admin_head', [$this, 'inject_custom_admin_colors']);
            add_action('wp_head',    [$this, 'inject_custom_admin_colors']);
        }

        // Environment indicator in admin bar.
        // Register hooks when the manual setting is on OR when URLs are
        // configured so that auto-detection (detect_environment) can fire.
        $env_manual = $options['admin_environment'] ?? 'off';
        $has_live   = !empty($options['admin_environment_live_url']);
        $has_stage  = !empty($options['admin_environment_stage_url']);
        if ($env_manual !== 'off' || $has_live || $has_stage) {
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

    public function filter_admin_bar_greeting(string $translation, string $text, string $domain): string {
        // Only intercept core WP's "Howdy, %s" admin bar greeting.
        if ($text !== 'Howdy, %s' || $domain !== 'default') {
            return $translation;
        }
        $greeting = wpd_get_option('admin_bar_greeting', '');
        return !empty($greeting) ? esc_html($greeting) . ' %s' : $translation;
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
        $primary   = wpd_get_option('admin_primary_color', '');
        $accent    = wpd_get_option('admin_accent_color', '');
        $bar_bg    = wpd_get_option('admin_bar_bg_color', '');
        $bar_text  = wpd_get_option('admin_bar_text_color', '');
        $menu_text = wpd_get_option('admin_menu_text_color', '');

        if (empty($primary) && empty($accent) && empty($bar_bg) && empty($bar_text) && empty($menu_text)) {
            return;
        }

        echo '<style id="wpd-custom-admin-colors">';

        if (!empty($primary)) {
            $p = esc_attr($primary);
            echo ":root { --wp-admin-theme-color: {$p}; }";
            echo "#adminmenu, #adminmenuback, #adminmenuwrap { background: {$p}; }";
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

        if (!empty($bar_bg)) {
            $b = esc_attr($bar_bg);
            echo "#wpadminbar { background: {$b}; }";
            echo "#wpadminbar .quicklinks .menupop ul, #wpadminbar .quicklinks .menupop ul li { background: {$b}; }";
        }

        if (!empty($bar_text)) {
            $t = esc_attr($bar_text);
            echo "#wpadminbar .ab-item, #wpadminbar .ab-empty-item, #wpadminbar > #wp-toolbar span.ab-label, #wpadminbar > #wp-toolbar span.noticon { color: {$t}; }";
            echo "#wpadminbar #adminbarsearch:before { color: {$t}; }";
        }

        if (!empty($menu_text)) {
            $m = esc_attr($menu_text);
            echo "#adminmenu a, #adminmenu .wp-menu-name { color: {$m}; }";
            echo "#adminmenu .wp-submenu a { color: {$m}; opacity: 0.85; }";
        }

        echo '</style>';
    }

    /**
     * Detect the active environment by comparing home_url() against the
     * configured Live / Staging URLs. Falls back to the manually saved setting.
     */
    private function detect_environment(): string {
        $options   = wpd_get_options();
        $live_url  = trailingslashit(strtolower($options['admin_environment_live_url']  ?? ''));
        $stage_url = trailingslashit(strtolower($options['admin_environment_stage_url'] ?? ''));
        $current   = trailingslashit(strtolower(home_url()));

        if ($live_url && $current === $live_url) {
            return 'live';
        }
        if ($stage_url && $current === $stage_url) {
            return 'stage';
        }

        return wpd_get_option('admin_environment', 'off');
    }

    public function add_environment_indicator(\WP_Admin_Bar $wp_admin_bar): void {
        $env = $this->detect_environment();
        if ($env === 'off') {
            return;
        }

        $options  = wpd_get_options();
        $is_live  = ($env === 'live');

        $current_label = $is_live ? __('Live', 'wpd')    : __('Staging', 'wpd');
        $other_label   = $is_live ? __('Staging', 'wpd') : __('Live', 'wpd');
        $current_class = $is_live ? 'wpd-env-live'       : 'wpd-env-stage';
        $other_class   = $is_live ? 'wpd-env-stage'      : 'wpd-env-live';
        $switch_url    = $is_live
            ? ($options['admin_environment_stage_url'] ?? '')
            : ($options['admin_environment_live_url']  ?? '');

        // Top-bar badge: "Umgebung: [Staging ▾]"
        $title = sprintf(
            '%s: <span class="wpd-env-badge %s">%s <span class="wpd-env-arrow">&#9660;</span></span>',
            esc_html__('Umgebung', 'wpd'),
            esc_attr($current_class),
            esc_html($current_label)
        );

        $wp_admin_bar->add_node([
            'id'     => 'wpd-environment',
            'parent' => 'top-secondary',
            'title'  => $title,
            'href'   => '#',
            'meta'   => [
                'class'   => 'wpd-env-indicator',
                'onclick' => 'return false;',
            ],
        ]);

        // Dropdown header
        $wp_admin_bar->add_node([
            'id'     => 'wpd-env-header',
            'parent' => 'wpd-environment',
            'title'  => esc_html__('UMGEBUNG WECHSELN', 'wpd'),
            'href'   => false,
            'meta'   => ['class' => 'wpd-env-menu-header'],
        ]);

        // Current environment row (not clickable)
        $wp_admin_bar->add_node([
            'id'     => 'wpd-env-current',
            'parent' => 'wpd-environment',
            'title'  => sprintf(
                '<span class="wpd-env-dot %s"></span><span class="wpd-env-name">%s</span><span class="wpd-env-status-label">%s</span>',
                esc_attr($current_class),
                esc_html($current_label),
                esc_html__('aktuell', 'wpd')
            ),
            'href'   => false,
            'meta'   => ['class' => 'wpd-env-row wpd-env-row--current'],
        ]);

        // Switch environment row (clickable if URL is set)
        $wp_admin_bar->add_node([
            'id'     => 'wpd-env-switch',
            'parent' => 'wpd-environment',
            'title'  => sprintf(
                '<span class="wpd-env-dot %s"></span><span class="wpd-env-name">%s</span>%s',
                esc_attr($other_class),
                esc_html($other_label),
                $switch_url
                    ? '<span class="wpd-env-switch-label">&rarr; ' . esc_html__('wechseln', 'wpd') . '</span>'
                    : '<span class="wpd-env-status-label">' . esc_html__('keine URL', 'wpd') . '</span>'
            ),
            'href'   => $switch_url ? esc_url($switch_url) : false,
            'meta'   => array_filter([
                'class'  => 'wpd-env-row wpd-env-row--switch',
                'target' => $switch_url ? '_blank' : null,
            ]),
        ]);
    }

    public function inject_environment_styles(): void {
        echo '<style id="wpd-env-styles">
            /* ── Top-bar trigger ─────────────────────────────── */
            #wpadminbar #wp-admin-bar-wpd-environment > .ab-item {
                display: flex !important;
                align-items: center !important;
                gap: 6px !important;
                height: 32px !important;
                padding: 0 10px !important;
                line-height: 32px !important;
                color: #c3c4c7 !important;
                font-size: 13px !important;
                background: transparent !important;
                box-sizing: border-box !important;
                text-decoration: none !important;
                cursor: default !important;
            }
            #wpadminbar #wp-admin-bar-wpd-environment > .ab-item:focus {
                color: #c3c4c7 !important;
                background: transparent !important;
            }
            .wpd-env-badge {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                padding: 3px 10px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 700;
                color: #fff;
                letter-spacing: 0.02em;
                line-height: 1.4;
            }
            .wpd-env-arrow { font-size: 9px; opacity: 0.8; line-height: 1; }
            .wpd-env-badge.wpd-env-live  { background: #00a32a; }
            .wpd-env-badge.wpd-env-stage { background: #d63638; }

            /* ── Dropdown panel ──────────────────────────────── */
            #wpadminbar .wpd-env-indicator .ab-sub-wrapper {
                min-width: 230px;
            }
            #wpadminbar .wpd-env-indicator .ab-submenu {
                min-width: 230px;
                background: #2c3338 !important;
                padding: 0 !important;
                border-radius: 0 0 4px 4px;
                box-shadow: 0 4px 12px rgba(0,0,0,.35);
            }

            /* ── Section header ──────────────────────────────── */
            #wpadminbar #wp-admin-bar-wpd-env-header > .ab-item {
                color: #8c8f94 !important;
                font-size: 10px !important;
                font-weight: 700 !important;
                letter-spacing: 0.1em !important;
                text-transform: uppercase;
                padding: 12px 14px 8px !important;
                cursor: default;
                pointer-events: none;
                background: transparent !important;
            }

            /* ── Env rows ────────────────────────────────────── */
            #wpadminbar .wpd-env-row > .ab-item {
                display: flex !important;
                align-items: center;
                gap: 10px;
                padding: 9px 14px !important;
                color: #c3c4c7 !important;
                font-size: 13px !important;
                background: transparent !important;
                transition: background 0.1s;
            }
            #wpadminbar .wpd-env-row--current > .ab-item {
                cursor: default;
                pointer-events: none;
            }
            #wpadminbar .wpd-env-row--switch > .ab-item:hover {
                background: #3c434a !important;
            }

            /* ── Dots ────────────────────────────────────────── */
            .wpd-env-dot {
                display: inline-block;
                width: 10px;
                height: 10px;
                border-radius: 50%;
                flex-shrink: 0;
            }
            .wpd-env-dot.wpd-env-live  { background: #00a32a; }
            .wpd-env-dot.wpd-env-stage { background: #d63638; }

            /* ── Row labels ──────────────────────────────────── */
            .wpd-env-name         { flex: 1; }
            .wpd-env-status-label { color: #8c8f94; font-size: 12px; }
            .wpd-env-switch-label { color: #00a32a; font-size: 12px; font-weight: 600; }
        </style>';
    }
}
