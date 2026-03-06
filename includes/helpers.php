<?php

defined('ABSPATH') || exit;

function wpd_get_options(): array {
    // Cache per request. WordPress always redirects after saving options, so
    // the cache is always fresh at the start of the next page load.
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $defaults = wpd_get_defaults();
    $stored   = get_option(WPD_OPTION_KEY, []);
    if (!is_array($stored)) {
        $stored = [];
    }
    $cache = wp_parse_args($stored, $defaults);

    return $cache;
}

function wpd_get_defaults(): array {
    $defaults = [
        'enable_dashboard_manager'    => true,
        'disabled_dashboard_widgets'  => [],

        'enable_top_banner'           => false,
        'top_banner_headline'         => '',
        'top_banner_intro'            => '',
        'top_banner_columns'          => [
            ['content' => '', 'button_label' => '', 'button_url' => ''],
            ['content' => '', 'button_label' => '', 'button_url' => ''],
            ['content' => '', 'button_label' => '', 'button_url' => ''],
            ['content' => '', 'button_label' => '', 'button_url' => ''],
        ],

        'enable_posttypes_banner'     => false,
        'posttypes_selected'          => [],

        'enable_login_branding'       => false,
        'login_logo_url'              => '',
        'login_logo_link'             => '',
        'login_logo_title'            => '',
        'login_bg_color'              => '',
        'login_bg_image'              => '',

        'enable_admin_branding'       => false,
        'admin_footer_text'           => '',
        'admin_bar_link_label'        => '',
        'admin_bar_link_url'          => '',
        'admin_bar_greeting'          => 'Moin,',

        'enable_hardening'                 => false,
        'hardening_disable_xmlrpc'         => true,
        'hardening_author_enum'            => 'off',
        'hardening_remove_rsd'             => true,
        'hardening_remove_wlw'             => true,
        'hardening_remove_generator'       => true,
        'hardening_hide_editor'            => false,
        'hardening_disable_rest_users'     => false,
        'hardening_security_headers'       => false,
        'hardening_remove_version_assets'  => false,
        'hardening_disable_app_passwords'  => false,
        'hardening_disable_pingbacks'      => false,
        'hardening_disable_emoji'          => false,
        'hardening_disable_jquery_migrate' => false,

        'admin_environment'                => 'off',
        'admin_environment_live_url'       => '',
        'admin_environment_stage_url'      => '',
        'admin_hide_update_notices'        => false,
        'enable_maintenance_mode'          => false,
        'admin_hide_menu_items'            => [],

        'admin_color_scheme'               => '',
        'admin_primary_color'              => '',
        'admin_accent_color'               => '',
        'admin_bar_bg_color'               => '',
        'admin_bar_text_color'             => '',
        'admin_menu_text_color'            => '',
        'login_button_color'               => '',
        'login_link_color'                 => '',

        'enable_updater'              => false,
        'updater_mode'                => 'off',
        'updater_github_repo'         => '',
        'updater_github_token'        => '',
        'updater_custom_url'          => '',

        'delete_data_on_uninstall'    => false,
    ];

    return apply_filters('wpd_options_defaults', $defaults);
}

function wpd_update_options(array $options): bool {
    return update_option(WPD_OPTION_KEY, $options);
}

function wpd_get_option(string $key, mixed $fallback = null): mixed {
    $options = wpd_get_options();
    return $options[$key] ?? $fallback;
}

function wpd_is_admin_user(): bool {
    return current_user_can('manage_options');
}
