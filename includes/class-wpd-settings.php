<?php

defined('ABSPATH') || exit;

class WPD_Settings {

    protected WPD_Plugin $plugin;
    protected string $page_slug = 'wpd-settings';
    protected string $option_group = 'wpd_options_group';

    public function __construct(WPD_Plugin $plugin) {
        $this->plugin = $plugin;
    }

    public function register(): void {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_menu_page(): void {
        if (!wpd_is_admin_user()) {
            return;
        }

        add_options_page(
            __('WP Default', 'wpd'),
            __('WP Default', 'wpd'),
            'manage_options',
            $this->page_slug,
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void {
        register_setting($this->option_group, WPD_OPTION_KEY, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize_options'],
            'default'           => wpd_get_defaults(),
        ]);
    }

    public function get_page_slug(): string {
        return $this->page_slug;
    }

    public function get_current_tab(): string {
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';
        $valid_tabs = ['dashboard', 'branding', 'hardening', 'updates'];
        return in_array($tab, $valid_tabs, true) ? $tab : 'dashboard';
    }

    protected static function get_tab_fields(): array {
        return [
            'dashboard' => [
                'enable_dashboard_manager',
                'disabled_dashboard_widgets',
                'enable_top_banner',
                'top_banner_headline',
                'top_banner_intro',
                'top_banner_columns',
                'enable_posttypes_banner',
                'posttypes_selected',
                'delete_data_on_uninstall',
            ],
            'branding' => [
                'enable_login_branding',
                'login_logo_url',
                'login_logo_link',
                'login_logo_title',
                'login_bg_color',
                'login_bg_image',
                'enable_admin_branding',
                'admin_footer_text',
                'admin_bar_link_label',
                'admin_bar_link_url',
            ],
            'hardening' => [
                'enable_hardening',
                'hardening_disable_xmlrpc',
                'hardening_author_enum',
                'hardening_remove_rsd',
                'hardening_remove_wlw',
                'hardening_remove_generator',
                'hardening_hide_editor',
            ],
            'updates' => [
                'enable_updater',
                'updater_mode',
                'updater_github_repo',
                'updater_github_token',
                'updater_custom_url',
            ],
        ];
    }

    public function render_settings_page(): void {
        if (!wpd_is_admin_user()) {
            return;
        }

        $current_tab = $this->get_current_tab();
        $tabs = [
            'dashboard'  => __('Dashboard', 'wpd'),
            'branding'   => __('Branding', 'wpd'),
            'hardening'  => __('Hardening', 'wpd'),
            'updates'    => __('Updates', 'wpd'),
        ];

        $options = wpd_get_options();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP Default', 'wpd'); ?></h1>
            <p class="description"><?php esc_html_e('Customize your WordPress admin area, clean up the dashboard, and apply security hardening.', 'wpd'); ?></p>
            <nav class="nav-tab-wrapper">
                <?php foreach ($tabs as $slug => $label) : ?>
                    <a href="<?php echo esc_url(add_query_arg(['page' => $this->page_slug, 'tab' => $slug], admin_url('options-general.php'))); ?>"
                       class="nav-tab <?php echo $current_tab === $slug ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <form method="post" action="options.php">
                <?php settings_fields($this->option_group); ?>
                <input type="hidden" name="wpd_active_tab" value="<?php echo esc_attr($current_tab); ?>">
                <?php $this->render_tab($current_tab, $options); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    protected function render_tab(string $tab, array $options): void {
        switch ($tab) {
            case 'dashboard':
                $this->render_dashboard_tab($options);
                break;
            case 'branding':
                $this->render_branding_tab($options);
                break;
            case 'hardening':
                $this->render_hardening_tab($options);
                break;
            case 'updates':
                $this->render_updates_tab($options);
                break;
        }
    }

    protected function render_dashboard_tab(array $options): void {
        ?>
        <h2><?php esc_html_e('Dashboard Widget Manager', 'wpd'); ?></h2>
        <p class="description"><?php esc_html_e('Control which dashboard widgets are visible to all users. Disabled widgets will be completely removed from the dashboard.', 'wpd'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Enable Widget Manager', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[enable_dashboard_manager]"
                               value="1" <?php checked(!empty($options['enable_dashboard_manager'])); ?>>
                        <?php esc_html_e('Manage dashboard widgets globally for all users.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('When enabled, only checked widgets below will remain visible on the dashboard. Unchecked widgets are removed for all users.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Active Widgets', 'wpd'); ?></th>
                <td>
                    <?php
                    $available = WPD_Dashboard::get_available_dashboard_widgets();
                    $disabled  = WPD_Dashboard::normalize_disabled_widgets($options['disabled_dashboard_widgets'] ?? []);

                    if (empty($available)) {
                        echo '<p class="description">' . esc_html__('No dashboard widgets detected. Widgets are discovered automatically from WordPress.', 'wpd') . '</p>';
                    } else {
                        foreach ($available as $widget_id => $widget_data) {
                            $is_active = !isset($disabled[$widget_id]);
                            printf(
                                '<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="%s[active_dashboard_widgets][%s]" value="%s" %s> %s <code style="font-size:11px;color:#787c82;">%s</code></label>',
                                esc_attr(WPD_OPTION_KEY),
                                esc_attr($widget_id),
                                esc_attr($widget_data['context']),
                                checked($is_active, true, false),
                                esc_html($widget_data['title']),
                                esc_html($widget_id)
                            );
                        }
                        echo '<p class="description">' . esc_html__('Checked widgets remain visible. Uncheck a widget to remove it from the dashboard for all users.', 'wpd') . '</p>';
                    }
                    ?>
                </td>
            </tr>
        </table>

        <hr>
        <h2><?php esc_html_e('Top Banner', 'wpd'); ?></h2>
        <p class="description"><?php esc_html_e('Display a customizable information banner at the top of the dashboard. Use it for announcements, quick links, or onboarding content with up to 4 columns.', 'wpd'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Enable Top Banner', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[enable_top_banner]"
                               value="1" <?php checked(!empty($options['enable_top_banner'])); ?>>
                        <?php esc_html_e('Show a custom banner above the dashboard widgets.', 'wpd'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Headline', 'wpd'); ?></th>
                <td>
                    <input type="text" class="large-text"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[top_banner_headline]"
                           value="<?php echo esc_attr($options['top_banner_headline'] ?? ''); ?>">
                    <p class="description"><?php esc_html_e('The main heading displayed at the top of the banner.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Introduction', 'wpd'); ?></th>
                <td>
                    <textarea class="large-text" rows="3"
                              name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[top_banner_intro]"><?php echo esc_textarea($options['top_banner_intro'] ?? ''); ?></textarea>
                    <p class="description"><?php esc_html_e('A short introduction text shown below the headline.', 'wpd'); ?></p>
                </td>
            </tr>
            <?php
            $columns = $options['top_banner_columns'] ?? [];
            for ($i = 0; $i < 4; $i++) :
                $col = $columns[$i] ?? ['content' => '', 'button_label' => '', 'button_url' => ''];
                ?>
                <tr>
                    <th scope="row"><?php printf(esc_html__('Column %d', 'wpd'), $i + 1); ?></th>
                    <td>
                        <p><strong><?php esc_html_e('Content', 'wpd'); ?></strong></p>
                        <?php
                        wp_editor(
                            $col['content'] ?? '',
                            'wpd_col_' . $i,
                            [
                                'textarea_name' => WPD_OPTION_KEY . '[top_banner_columns][' . $i . '][content]',
                                'textarea_rows' => 5,
                                'media_buttons' => true,
                                'teeny'         => true,
                            ]
                        );
                        ?>
                        <p style="margin-top:8px;">
                            <label><?php esc_html_e('Button Label', 'wpd'); ?>
                                <input type="text" class="regular-text"
                                       name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[top_banner_columns][<?php echo $i; ?>][button_label]"
                                       value="<?php echo esc_attr($col['button_label'] ?? ''); ?>">
                            </label>
                        </p>
                        <p>
                            <label><?php esc_html_e('Button URL', 'wpd'); ?>
                                <input type="url" class="regular-text"
                                       name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[top_banner_columns][<?php echo $i; ?>][button_url]"
                                       value="<?php echo esc_url($col['button_url'] ?? ''); ?>">
                            </label>
                        </p>
                        <p class="description"><?php printf(esc_html__('Leave empty to hide column %d. Each column can have rich text content and an optional button.', 'wpd'), $i + 1); ?></p>
                    </td>
                </tr>
            <?php endfor; ?>
        </table>

        <hr>
        <h2><?php esc_html_e('Post Types Banner', 'wpd'); ?></h2>
        <p class="description"><?php esc_html_e('Show quick-access buttons on the dashboard for creating new content. Each button links directly to the "Add New" screen of the selected post type and uses its native WordPress icon and label.', 'wpd'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Enable Post Types Banner', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[enable_posttypes_banner]"
                               value="1" <?php checked(!empty($options['enable_posttypes_banner'])); ?>>
                        <?php esc_html_e('Show post type quick-access buttons on the dashboard.', 'wpd'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Visible Post Types', 'wpd'); ?></th>
                <td>
                    <?php
                    $all_post_types = get_post_types(['public' => true, 'show_ui' => true], 'objects');
                    unset($all_post_types['attachment']);
                    $selected_pts = $options['posttypes_selected'] ?? [];

                    if (empty($all_post_types)) {
                        echo '<p class="description">' . esc_html__('No public post types found.', 'wpd') . '</p>';
                    } else {
                        foreach ($all_post_types as $pt) {
                            $is_selected = empty($selected_pts) || in_array($pt->name, $selected_pts, true);
                            printf(
                                '<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="%s[posttypes_selected][]" value="%s" %s> %s <code style="font-size:11px;color:#787c82;">%s</code></label>',
                                esc_attr(WPD_OPTION_KEY),
                                esc_attr($pt->name),
                                checked($is_selected, true, false),
                                esc_html($pt->labels->name ?? $pt->label),
                                esc_html($pt->name)
                            );
                        }
                        echo '<p class="description">' . esc_html__('Select which post types to show in the banner. If none are checked, all public post types will be displayed.', 'wpd') . '</p>';
                    }
                    ?>
                </td>
            </tr>
        </table>

        <hr>
        <h2><?php esc_html_e('Data', 'wpd'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Delete Data on Uninstall', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[delete_data_on_uninstall]"
                               value="1" <?php checked(!empty($options['delete_data_on_uninstall'])); ?>>
                        <?php esc_html_e('Remove all plugin settings when the plugin is deleted.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('When enabled, all WP Default options will be permanently removed from the database upon plugin deletion. Deactivating the plugin does not delete data.', 'wpd'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    protected function render_branding_tab(array $options): void {
        ?>
        <h2><?php esc_html_e('Login Branding', 'wpd'); ?></h2>
        <p class="description"><?php esc_html_e('Customize the appearance of the WordPress login page with your own logo, colors, and background image.', 'wpd'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Enable Login Branding', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[enable_login_branding]"
                               value="1" <?php checked(!empty($options['enable_login_branding'])); ?>>
                        <?php esc_html_e('Customize the WordPress login screen.', 'wpd'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Logo', 'wpd'); ?></th>
                <td>
                    <input type="text" class="regular-text wpd-media-url" id="wpd_login_logo_url"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[login_logo_url]"
                           value="<?php echo esc_url($options['login_logo_url'] ?? ''); ?>">
                    <button type="button" class="button wpd-media-upload" data-target="#wpd_login_logo_url">
                        <?php esc_html_e('Select Image', 'wpd'); ?>
                    </button>
                    <?php if (!empty($options['login_logo_url'])) : ?>
                        <p><img src="<?php echo esc_url($options['login_logo_url']); ?>" style="max-width:200px;height:auto;margin-top:8px;"></p>
                    <?php endif; ?>
                    <p class="description"><?php esc_html_e('Replaces the WordPress logo on the login page. Recommended size: 320×80 pixels.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Logo Link URL', 'wpd'); ?></th>
                <td>
                    <input type="url" class="regular-text"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[login_logo_link]"
                           value="<?php echo esc_url($options['login_logo_link'] ?? ''); ?>">
                    <p class="description"><?php esc_html_e('The URL the logo links to. Defaults to wordpress.org if left empty.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Logo Title', 'wpd'); ?></th>
                <td>
                    <input type="text" class="regular-text"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[login_logo_title]"
                           value="<?php echo esc_attr($options['login_logo_title'] ?? ''); ?>">
                    <p class="description"><?php esc_html_e('The tooltip text when hovering over the logo. Defaults to "Powered by WordPress" if left empty.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Background Color', 'wpd'); ?></th>
                <td>
                    <input type="text" class="regular-text"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[login_bg_color]"
                           value="<?php echo esc_attr($options['login_bg_color'] ?? ''); ?>"
                           placeholder="#ffffff">
                    <p class="description"><?php esc_html_e('Hex color code for the login page background. Example: #f0f0f1', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Background Image', 'wpd'); ?></th>
                <td>
                    <input type="text" class="regular-text wpd-media-url" id="wpd_login_bg_image"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[login_bg_image]"
                           value="<?php echo esc_url($options['login_bg_image'] ?? ''); ?>">
                    <button type="button" class="button wpd-media-upload" data-target="#wpd_login_bg_image">
                        <?php esc_html_e('Select Image', 'wpd'); ?>
                    </button>
                    <p class="description"><?php esc_html_e('A full-screen background image for the login page. Will cover the entire viewport.', 'wpd'); ?></p>
                </td>
            </tr>
        </table>

        <hr>
        <h2><?php esc_html_e('Admin Branding', 'wpd'); ?></h2>
        <p class="description"><?php esc_html_e('Customize the WordPress admin area with your own footer text and a custom link in the admin bar.', 'wpd'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Enable Admin Branding', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[enable_admin_branding]"
                               value="1" <?php checked(!empty($options['enable_admin_branding'])); ?>>
                        <?php esc_html_e('Customize admin footer and admin bar.', 'wpd'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Footer Text', 'wpd'); ?></th>
                <td>
                    <textarea class="large-text" rows="3"
                              name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[admin_footer_text]"><?php echo esc_textarea($options['admin_footer_text'] ?? ''); ?></textarea>
                    <p class="description"><?php esc_html_e('Replaces the default "Thank you for creating with WordPress" text in the admin footer. HTML is allowed (links, bold, etc.).', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Admin Bar Link Label', 'wpd'); ?></th>
                <td>
                    <input type="text" class="regular-text"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[admin_bar_link_label]"
                           value="<?php echo esc_attr($options['admin_bar_link_label'] ?? ''); ?>">
                    <p class="description"><?php esc_html_e('Adds a custom link to the WordPress admin bar. Enter the visible text here.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Admin Bar Link URL', 'wpd'); ?></th>
                <td>
                    <input type="url" class="regular-text"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[admin_bar_link_url]"
                           value="<?php echo esc_url($options['admin_bar_link_url'] ?? ''); ?>">
                    <p class="description"><?php esc_html_e('The URL for the admin bar link. Both internal and external URLs are supported.', 'wpd'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    protected function render_hardening_tab(array $options): void {
        ?>
        <h2><?php esc_html_e('Security Hardening', 'wpd'); ?></h2>
        <p class="description"><?php esc_html_e('Apply lightweight security measures to reduce your site\'s attack surface. These settings remove unnecessary information and disable features commonly exploited by attackers.', 'wpd'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Enable Hardening', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[enable_hardening]"
                               value="1" <?php checked(!empty($options['enable_hardening'])); ?>>
                        <?php esc_html_e('Enable security hardening features.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Master switch for all hardening options below. Individual features can still be toggled.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Disable XML-RPC', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[hardening_disable_xmlrpc]"
                               value="1" <?php checked(!empty($options['hardening_disable_xmlrpc'])); ?>>
                        <?php esc_html_e('Completely disable the XML-RPC interface.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('XML-RPC is a legacy API that is frequently targeted in brute-force attacks. Disable it unless you use the WordPress mobile app, Jetpack, or other services that require it.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Author Enumeration', 'wpd'); ?></th>
                <td>
                    <select name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[hardening_author_enum]">
                        <option value="off" <?php selected($options['hardening_author_enum'] ?? 'off', 'off'); ?>>
                            <?php esc_html_e('Off (no protection)', 'wpd'); ?>
                        </option>
                        <option value="404" <?php selected($options['hardening_author_enum'] ?? 'off', '404'); ?>>
                            <?php esc_html_e('Return 404', 'wpd'); ?>
                        </option>
                        <option value="redirect" <?php selected($options['hardening_author_enum'] ?? 'off', 'redirect'); ?>>
                            <?php esc_html_e('Redirect to homepage', 'wpd'); ?>
                        </option>
                    </select>
                    <p class="description"><?php esc_html_e('Prevents attackers from discovering usernames via ?author=1 URLs. "Return 404" shows a not-found page, "Redirect" sends visitors to the homepage.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Remove RSD Link', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[hardening_remove_rsd]"
                               value="1" <?php checked(!empty($options['hardening_remove_rsd'])); ?>>
                        <?php esc_html_e('Remove the RSD (Really Simple Discovery) link from the header.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('RSD is only needed for remote publishing clients. Removing it reduces information leakage.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Remove WLW Manifest', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[hardening_remove_wlw]"
                               value="1" <?php checked(!empty($options['hardening_remove_wlw'])); ?>>
                        <?php esc_html_e('Remove the Windows Live Writer manifest link.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Windows Live Writer is discontinued software. This link is not needed and can be safely removed.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Remove Generator Meta', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[hardening_remove_generator]"
                               value="1" <?php checked(!empty($options['hardening_remove_generator'])); ?>>
                        <?php esc_html_e('Remove the WordPress version meta tag.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Hides the WordPress version number from your site\'s HTML source. Prevents attackers from targeting version-specific vulnerabilities.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Hide Theme/Plugin Editor', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[hardening_hide_editor]"
                               value="1" <?php checked(!empty($options['hardening_hide_editor'])); ?>>
                        <?php esc_html_e('Hide the built-in theme and plugin editor menus.', 'wpd'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Removes the editor menu entries from the admin. For full protection, also add DISALLOW_FILE_EDIT to wp-config.php.', 'wpd'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    protected function render_updates_tab(array $options): void {
        ?>
        <h2><?php esc_html_e('Plugin Updates', 'wpd'); ?></h2>
        <p class="description"><?php esc_html_e('Configure automatic update checks from GitHub releases or a custom update server. This allows WP Default to be updated like any other plugin through the WordPress dashboard.', 'wpd'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Enable Updater', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[enable_updater]"
                               value="1" <?php checked(!empty($options['enable_updater'])); ?>>
                        <?php esc_html_e('Enable automatic update checks.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('When enabled, WordPress will periodically check the configured source for new versions of this plugin.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Update Source', 'wpd'); ?></th>
                <td>
                    <select name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[updater_mode]">
                        <option value="off" <?php selected($options['updater_mode'] ?? 'off', 'off'); ?>>
                            <?php esc_html_e('Off', 'wpd'); ?>
                        </option>
                        <option value="github" <?php selected($options['updater_mode'] ?? 'off', 'github'); ?>>
                            <?php esc_html_e('GitHub', 'wpd'); ?>
                        </option>
                        <option value="custom" <?php selected($options['updater_mode'] ?? 'off', 'custom'); ?>>
                            <?php esc_html_e('Custom URL', 'wpd'); ?>
                        </option>
                    </select>
                    <p class="description"><?php esc_html_e('Choose where to check for updates. GitHub uses the releases API, Custom URL expects a JSON endpoint.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('GitHub Repository', 'wpd'); ?></th>
                <td>
                    <input type="text" class="regular-text"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[updater_github_repo]"
                           value="<?php echo esc_attr($options['updater_github_repo'] ?? ''); ?>"
                           placeholder="username/repository">
                    <p class="description"><?php esc_html_e('Format: username/repository (e.g., acme/wp-default-dashboard). The repository must have GitHub Releases with zip assets.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('GitHub Access Token', 'wpd'); ?></th>
                <td>
                    <input type="password" class="regular-text"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[updater_github_token]"
                           value="<?php echo esc_attr($options['updater_github_token'] ?? ''); ?>"
                           autocomplete="off">
                    <p class="description"><?php esc_html_e('Required only for private repositories. Generate a personal access token with "repo" scope at github.com/settings/tokens.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Custom Update URL', 'wpd'); ?></th>
                <td>
                    <input type="url" class="regular-text"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[updater_custom_url]"
                           value="<?php echo esc_url($options['updater_custom_url'] ?? ''); ?>"
                           placeholder="https://example.com/updates/info.json">
                    <p class="description"><?php esc_html_e('A URL to a JSON file with version info. Must contain "version", "download_url", and optionally "tested", "requires_php" fields.', 'wpd'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function sanitize_options(mixed $input): array {
        if (!is_array($input)) {
            $input = [];
        }

        $current  = get_option(WPD_OPTION_KEY, []);
        if (!is_array($current)) {
            $current = [];
        }
        $defaults = wpd_get_defaults();
        $current  = wp_parse_args($current, $defaults);

        $active_tab = isset($_POST['wpd_active_tab']) ? sanitize_key($_POST['wpd_active_tab']) : '';
        $tab_fields = self::get_tab_fields();

        $sanitized = $this->sanitize_all_fields($input);

        if ($active_tab && isset($tab_fields[$active_tab])) {
            $active_fields = $tab_fields[$active_tab];
            $merged = $current;

            foreach ($active_fields as $field) {
                $merged[$field] = $sanitized[$field] ?? $defaults[$field];
            }

            return $merged;
        }

        return $sanitized;
    }

    protected function sanitize_all_fields(array $input): array {
        $defaults  = wpd_get_defaults();
        $sanitized = [];

        $sanitized['enable_dashboard_manager']   = !empty($input['enable_dashboard_manager']);

        $active_widgets = [];
        if (isset($input['active_dashboard_widgets']) && is_array($input['active_dashboard_widgets'])) {
            foreach ($input['active_dashboard_widgets'] as $widget_id => $context) {
                $active_widgets[sanitize_text_field($widget_id)] = sanitize_text_field($context);
            }
        }

        $available = WPD_Dashboard::get_available_dashboard_widgets();
        $disabled_widgets = [];
        foreach ($available as $widget_id => $widget_data) {
            if (!isset($active_widgets[$widget_id])) {
                $disabled_widgets[$widget_id] = $widget_data['context'];
            }
        }
        $sanitized['disabled_dashboard_widgets'] = $disabled_widgets;

        $sanitized['enable_top_banner']       = !empty($input['enable_top_banner']);
        $sanitized['top_banner_headline']     = sanitize_text_field($input['top_banner_headline'] ?? '');
        $sanitized['top_banner_intro']        = sanitize_textarea_field($input['top_banner_intro'] ?? '');

        $columns = [];
        for ($i = 0; $i < 4; $i++) {
            $columns[$i] = [
                'content'      => wp_kses_post($input['top_banner_columns'][$i]['content'] ?? ''),
                'button_label' => sanitize_text_field($input['top_banner_columns'][$i]['button_label'] ?? ''),
                'button_url'   => esc_url_raw($input['top_banner_columns'][$i]['button_url'] ?? ''),
            ];
        }
        $sanitized['top_banner_columns'] = $columns;

        $sanitized['enable_posttypes_banner']  = !empty($input['enable_posttypes_banner']);
        $selected_pts = [];
        if (isset($input['posttypes_selected']) && is_array($input['posttypes_selected'])) {
            $selected_pts = array_map('sanitize_key', $input['posttypes_selected']);
        }
        $sanitized['posttypes_selected']       = $selected_pts;

        $sanitized['delete_data_on_uninstall'] = !empty($input['delete_data_on_uninstall']);

        $sanitized['enable_login_branding']    = !empty($input['enable_login_branding']);
        $sanitized['login_logo_url']           = esc_url_raw($input['login_logo_url'] ?? '');
        $sanitized['login_logo_link']          = esc_url_raw($input['login_logo_link'] ?? '');
        $sanitized['login_logo_title']         = sanitize_text_field($input['login_logo_title'] ?? '');
        $sanitized['login_bg_color']           = sanitize_hex_color($input['login_bg_color'] ?? '') ?: '';
        $sanitized['login_bg_image']           = esc_url_raw($input['login_bg_image'] ?? '');

        $sanitized['enable_admin_branding']    = !empty($input['enable_admin_branding']);
        $sanitized['admin_footer_text']        = wp_kses_post($input['admin_footer_text'] ?? '');
        $sanitized['admin_bar_link_label']     = sanitize_text_field($input['admin_bar_link_label'] ?? '');
        $sanitized['admin_bar_link_url']       = esc_url_raw($input['admin_bar_link_url'] ?? '');

        $sanitized['enable_hardening']            = !empty($input['enable_hardening']);
        $sanitized['hardening_disable_xmlrpc']    = !empty($input['hardening_disable_xmlrpc']);
        $author_enum = $input['hardening_author_enum'] ?? 'off';
        $sanitized['hardening_author_enum']       = in_array($author_enum, ['off', '404', 'redirect'], true) ? $author_enum : 'off';
        $sanitized['hardening_remove_rsd']        = !empty($input['hardening_remove_rsd']);
        $sanitized['hardening_remove_wlw']        = !empty($input['hardening_remove_wlw']);
        $sanitized['hardening_remove_generator']  = !empty($input['hardening_remove_generator']);
        $sanitized['hardening_hide_editor']       = !empty($input['hardening_hide_editor']);

        $sanitized['enable_updater']        = !empty($input['enable_updater']);
        $updater_mode = $input['updater_mode'] ?? 'off';
        $sanitized['updater_mode']          = in_array($updater_mode, ['off', 'github', 'custom'], true) ? $updater_mode : 'off';
        $sanitized['updater_github_repo']   = sanitize_text_field($input['updater_github_repo'] ?? '');
        $sanitized['updater_github_token']  = sanitize_text_field($input['updater_github_token'] ?? '');
        $sanitized['updater_custom_url']    = esc_url_raw($input['updater_custom_url'] ?? '');

        return $sanitized;
    }

}
