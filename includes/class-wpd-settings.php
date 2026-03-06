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
        $valid_tabs = ['dashboard', 'appearance', 'security', 'admin-tools', 'updates'];
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
            'appearance' => [
                'enable_login_branding',
                'login_logo_url',
                'login_logo_link',
                'login_logo_title',
                'login_bg_color',
                'login_bg_image',
                'login_button_color',
                'login_link_color',
                'enable_admin_branding',
                'admin_footer_text',
                'admin_bar_link_label',
                'admin_bar_link_url',
                'admin_bar_greeting',
                'admin_environment',
                'admin_environment_live_url',
                'admin_environment_stage_url',
                'admin_color_scheme',
                'admin_primary_color',
                'admin_accent_color',
            ],
            'security' => [
                'enable_hardening',
                'hardening_disable_xmlrpc',
                'hardening_author_enum',
                'hardening_remove_rsd',
                'hardening_remove_wlw',
                'hardening_remove_generator',
                'hardening_hide_editor',
                'hardening_disable_rest_users',
                'hardening_security_headers',
                'hardening_remove_version_assets',
                'hardening_disable_app_passwords',
                'hardening_disable_pingbacks',
                'hardening_disable_emoji',
                'hardening_disable_jquery_migrate',
            ],
            'admin-tools' => [
                'admin_hide_update_notices',
                'enable_maintenance_mode',
                'admin_hide_menu_items',
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
            'dashboard'   => __('Dashboard', 'wpd'),
            'appearance'  => __('Erscheinungsbild', 'wpd'),
            'security'    => __('Sicherheit', 'wpd'),
            'admin-tools' => __('Admin-Werkzeuge', 'wpd'),
            'updates'     => __('Updates', 'wpd'),
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
            case 'appearance':
                $this->render_appearance_tab($options);
                break;
            case 'security':
                $this->render_security_tab($options);
                break;
            case 'admin-tools':
                $this->render_admin_tools_tab($options);
                break;
            case 'updates':
                $this->render_updates_tab($options);
                break;
        }
    }

    protected function render_dashboard_tab(array $options): void {
        $k = WPD_OPTION_KEY;
        ?>

        <?php /* ── Widget-Verwaltung ───────────────────────────────────── */ ?>
        <div class="wpd-section <?php echo !empty($options['enable_dashboard_manager']) ? 'is-active' : ''; ?>">
            <div class="wpd-section__head">
                <label class="wpd-section__master">
                    <span class="wpd-toggle">
                        <input type="checkbox"
                               id="wpd_toggle_widgets"
                               name="<?php echo esc_attr($k); ?>[enable_dashboard_manager]"
                               value="1"
                               <?php checked(!empty($options['enable_dashboard_manager'])); ?>>
                        <span class="wpd-toggle__track"></span>
                    </span>
                    <span class="wpd-section__title"><?php esc_html_e('Widget-Verwaltung', 'wpd'); ?></span>
                </label>
                <span class="wpd-section__badge"><?php echo !empty($options['enable_dashboard_manager']) ? esc_html__('aktiv', 'wpd') : esc_html__('inaktiv', 'wpd'); ?></span>
            </div>
            <p class="description"><?php esc_html_e('Legt global für alle Benutzer fest, welche Dashboard-Widgets sichtbar sind. Deaktivierte Widgets werden vollständig entfernt.', 'wpd'); ?></p>

            <div class="wpd-section__body" data-controlled-by="wpd_toggle_widgets">
                <?php
                $available = WPD_Dashboard::get_available_dashboard_widgets();
                $disabled  = WPD_Dashboard::normalize_disabled_widgets($options['disabled_dashboard_widgets'] ?? []);

                if (empty($available)) {
                    echo '<p class="description">' . esc_html__('Noch keine Widgets erkannt — sie werden beim ersten Öffnen des Dashboards automatisch gefunden.', 'wpd') . '</p>';
                } else {
                    echo '<div class="wpd-checkbox-grid">';
                    foreach ($available as $widget_id => $widget_data) {
                        $is_active = !isset($disabled[$widget_id]);
                        printf(
                            '<label class="wpd-checkbox-item"><input type="checkbox" name="%1$s[active_dashboard_widgets][%2$s]" value="%3$s" %4$s><span>%5$s</span><code>%2$s</code></label>',
                            esc_attr($k),
                            esc_attr($widget_id),
                            esc_attr($widget_data['context']),
                            checked($is_active, true, false),
                            esc_html($widget_data['title'])
                        );
                    }
                    echo '</div>';
                    echo '<p class="description" style="margin-top:12px;">' . esc_html__('Aktivierte Widgets bleiben sichtbar. Deaktivierte Widgets werden für alle Nutzer ausgeblendet.', 'wpd') . '</p>';
                }
                ?>
            </div>
        </div>

        <hr>

        <?php /* ── Oberes Banner ──────────────────────────────────────── */ ?>
        <div class="wpd-section <?php echo !empty($options['enable_top_banner']) ? 'is-active' : ''; ?>">
            <div class="wpd-section__head">
                <label class="wpd-section__master">
                    <span class="wpd-toggle">
                        <input type="checkbox"
                               id="wpd_toggle_top_banner"
                               name="<?php echo esc_attr($k); ?>[enable_top_banner]"
                               value="1"
                               <?php checked(!empty($options['enable_top_banner'])); ?>>
                        <span class="wpd-toggle__track"></span>
                    </span>
                    <span class="wpd-section__title"><?php esc_html_e('Oberes Banner', 'wpd'); ?></span>
                </label>
                <span class="wpd-section__badge"><?php echo !empty($options['enable_top_banner']) ? esc_html__('aktiv', 'wpd') : esc_html__('inaktiv', 'wpd'); ?></span>
            </div>
            <p class="description"><?php esc_html_e('Anpassbares Informationsbanner oben auf dem Dashboard — für Ankündigungen, Links oder Onboarding-Inhalte mit bis zu 4 Spalten.', 'wpd'); ?></p>

            <div class="wpd-section__body" data-controlled-by="wpd_toggle_top_banner">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Überschrift', 'wpd'); ?></th>
                        <td>
                            <input type="text" class="large-text"
                                   name="<?php echo esc_attr($k); ?>[top_banner_headline]"
                                   value="<?php echo esc_attr($options['top_banner_headline'] ?? ''); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Einleitungstext', 'wpd'); ?></th>
                        <td>
                            <textarea class="large-text" rows="2"
                                      name="<?php echo esc_attr($k); ?>[top_banner_intro]"><?php echo esc_textarea($options['top_banner_intro'] ?? ''); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Spalten', 'wpd'); ?>
                            <p class="description" style="font-weight:400;margin-top:4px;"><?php esc_html_e('HTML erlaubt. Leere Spalten werden ausgeblendet.', 'wpd'); ?></p>
                        </th>
                        <td>
                            <div class="wpd-columns-grid">
                                <?php
                                $columns = $options['top_banner_columns'] ?? [];
                                for ($i = 0; $i < 4; $i++) :
                                    $col = $columns[$i] ?? ['content' => '', 'button_label' => '', 'button_url' => ''];
                                ?>
                                <div class="wpd-column-card">
                                    <div class="wpd-column-card__head">
                                        <?php printf(esc_html__('Spalte %d', 'wpd'), $i + 1); ?>
                                    </div>
                                    <div class="wpd-column-card__body">
                                        <label class="wpd-field-label"><?php esc_html_e('Inhalt', 'wpd'); ?></label>
                                        <textarea class="widefat" rows="6"
                                                  name="<?php echo esc_attr($k); ?>[top_banner_columns][<?php echo $i; ?>][content]"
                                                  placeholder="<?php esc_attr_e('HTML-Inhalt der Spalte …', 'wpd'); ?>"><?php echo esc_textarea($col['content'] ?? ''); ?></textarea>

                                        <label class="wpd-field-label"><?php esc_html_e('Button-Beschriftung', 'wpd'); ?></label>
                                        <input type="text" class="widefat"
                                               name="<?php echo esc_attr($k); ?>[top_banner_columns][<?php echo $i; ?>][button_label]"
                                               value="<?php echo esc_attr($col['button_label'] ?? ''); ?>"
                                               placeholder="<?php esc_attr_e('z. B. Mehr erfahren', 'wpd'); ?>">

                                        <label class="wpd-field-label"><?php esc_html_e('Button-URL', 'wpd'); ?></label>
                                        <input type="url" class="widefat"
                                               name="<?php echo esc_attr($k); ?>[top_banner_columns][<?php echo $i; ?>][button_url]"
                                               value="<?php echo esc_url($col['button_url'] ?? ''); ?>"
                                               placeholder="https://">
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <hr>

        <?php /* ── Inhaltstypen-Banner ────────────────────────────────── */ ?>
        <div class="wpd-section <?php echo !empty($options['enable_posttypes_banner']) ? 'is-active' : ''; ?>">
            <div class="wpd-section__head">
                <label class="wpd-section__master">
                    <span class="wpd-toggle">
                        <input type="checkbox"
                               id="wpd_toggle_posttypes"
                               name="<?php echo esc_attr($k); ?>[enable_posttypes_banner]"
                               value="1"
                               <?php checked(!empty($options['enable_posttypes_banner'])); ?>>
                        <span class="wpd-toggle__track"></span>
                    </span>
                    <span class="wpd-section__title"><?php esc_html_e('Inhaltstypen-Banner', 'wpd'); ?></span>
                </label>
                <span class="wpd-section__badge"><?php echo !empty($options['enable_posttypes_banner']) ? esc_html__('aktiv', 'wpd') : esc_html__('inaktiv', 'wpd'); ?></span>
            </div>
            <p class="description"><?php esc_html_e('Schnellzugriff-Kacheln auf dem Dashboard für das Anlegen neuer Inhalte — mit Zähler für Veröffentlichungen und Entwürfe.', 'wpd'); ?></p>

            <div class="wpd-section__body" data-controlled-by="wpd_toggle_posttypes">
                <?php
                $all_post_types = get_post_types(['public' => true, 'show_ui' => true], 'objects');
                unset($all_post_types['attachment']);
                $selected_pts = $options['posttypes_selected'] ?? [];

                if (empty($all_post_types)) {
                    echo '<p class="description">' . esc_html__('Keine öffentlichen Inhaltstypen gefunden.', 'wpd') . '</p>';
                } else {
                    echo '<div class="wpd-checkbox-grid">';
                    foreach ($all_post_types as $pt) {
                        $is_selected = empty($selected_pts) || in_array($pt->name, $selected_pts, true);
                        printf(
                            '<label class="wpd-checkbox-item"><input type="checkbox" name="%1$s[posttypes_selected][]" value="%2$s" %3$s><span>%4$s</span><code>%2$s</code></label>',
                            esc_attr($k),
                            esc_attr($pt->name),
                            checked($is_selected, true, false),
                            esc_html($pt->labels->name ?? $pt->label)
                        );
                    }
                    echo '</div>';
                    echo '<p class="description" style="margin-top:12px;">' . esc_html__('Ausgewählte Inhaltstypen erscheinen im Banner. Wenn nichts ausgewählt ist, werden alle angezeigt.', 'wpd') . '</p>';
                }
                ?>
            </div>
        </div>

        <hr>

        <?php /* ── Daten ───────────────────────────────────────────────── */ ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Daten bei Deinstallation löschen', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="<?php echo esc_attr($k); ?>[delete_data_on_uninstall]"
                               value="1"
                               <?php checked(!empty($options['delete_data_on_uninstall'])); ?>>
                        <?php esc_html_e('Alle Plugin-Einstellungen beim Löschen des Plugins permanent aus der Datenbank entfernen.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Das Deaktivieren des Plugins löscht keine Daten.', 'wpd'); ?></p>
                </td>
            </tr>
        </table>

        <?php
    }

    protected function render_appearance_tab(array $options): void {
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
            <tr>
                <th scope="row"><?php esc_html_e('Button Color', 'wpd'); ?></th>
                <td>
                    <input type="text" class="regular-text"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[login_button_color]"
                           value="<?php echo esc_attr($options['login_button_color'] ?? ''); ?>"
                           placeholder="#2271b1">
                    <p class="description"><?php esc_html_e('Background color for the login button. Example: #2271b1', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Link Color', 'wpd'); ?></th>
                <td>
                    <input type="text" class="regular-text"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[login_link_color]"
                           value="<?php echo esc_attr($options['login_link_color'] ?? ''); ?>"
                           placeholder="#2271b1">
                    <p class="description"><?php esc_html_e('Color for links on the login page ("Back to site", "Lost your password?"). Example: #2271b1', 'wpd'); ?></p>
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
            <tr>
                <th scope="row"><?php esc_html_e('Greeting', 'wpd'); ?></th>
                <td>
                    <input type="text" class="regular-text"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[admin_bar_greeting]"
                           value="<?php echo esc_attr($options['admin_bar_greeting'] ?? ''); ?>"
                           placeholder="<?php esc_attr_e('Moin, %s', 'wpd'); ?>">
                    <p class="description"><?php esc_html_e('Replaces the default "Howdy, %s" greeting in the admin bar. Use %s as placeholder for the display name. Leave empty for the WordPress default.', 'wpd'); ?></p>
                </td>
            </tr>
        </table>

        <hr>
        <h2><?php esc_html_e('Environment Indicator', 'wpd'); ?></h2>
        <p class="description"><?php esc_html_e('Display a colored badge in the admin bar to identify the current environment. Helps prevent accidental changes on live sites.', 'wpd'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Environment', 'wpd'); ?></th>
                <td>
                    <select name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[admin_environment]">
                        <option value="off" <?php selected($options['admin_environment'] ?? 'off', 'off'); ?>><?php esc_html_e('Disabled', 'wpd'); ?></option>
                        <option value="stage" <?php selected($options['admin_environment'] ?? 'off', 'stage'); ?>><?php esc_html_e('Staging', 'wpd'); ?></option>
                        <option value="live" <?php selected($options['admin_environment'] ?? 'off', 'live'); ?>><?php esc_html_e('Live', 'wpd'); ?></option>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Fallback — used only when the current URL does not match either configured URL below.', 'wpd'); ?>
                        <?php esc_html_e('The badge is visible to all logged-in users in the admin bar — both in the backend and on the frontend.', 'wpd'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Live URL', 'wpd'); ?></th>
                <td>
                    <input type="url" class="regular-text"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[admin_environment_live_url]"
                           value="<?php echo esc_url($options['admin_environment_live_url'] ?? ''); ?>"
                           placeholder="https://example.com">
                    <p class="description">
                        <?php esc_html_e('URL of the live site. When the plugin is installed on both sites, the badge is auto-detected by comparing this URL with the current site URL — no manual "Environment" selection needed.', 'wpd'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Staging URL', 'wpd'); ?></th>
                <td>
                    <input type="url" class="regular-text"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[admin_environment_stage_url]"
                           value="<?php echo esc_url($options['admin_environment_stage_url'] ?? ''); ?>"
                           placeholder="https://staging.example.com">
                    <p class="description">
                        <?php esc_html_e('URL of the staging site. The dropdown always shows a one-click link to switch to the other environment.', 'wpd'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <hr>
        <h2><?php esc_html_e('Admin Colors', 'wpd'); ?></h2>
        <p class="description"><?php esc_html_e('Set a global admin color scheme or define custom brand colors for the WordPress backend.', 'wpd'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Color Scheme', 'wpd'); ?></th>
                <td>
                    <select name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[admin_color_scheme]">
                        <option value="" <?php selected($options['admin_color_scheme'] ?? '', ''); ?>><?php esc_html_e('— User preference (no override) —', 'wpd'); ?></option>
                        <option value="fresh" <?php selected($options['admin_color_scheme'] ?? '', 'fresh'); ?>><?php esc_html_e('Default (Fresh)', 'wpd'); ?></option>
                        <option value="light" <?php selected($options['admin_color_scheme'] ?? '', 'light'); ?>><?php esc_html_e('Light', 'wpd'); ?></option>
                        <option value="blue" <?php selected($options['admin_color_scheme'] ?? '', 'blue'); ?>><?php esc_html_e('Blue', 'wpd'); ?></option>
                        <option value="coffee" <?php selected($options['admin_color_scheme'] ?? '', 'coffee'); ?>><?php esc_html_e('Coffee', 'wpd'); ?></option>
                        <option value="ectoplasm" <?php selected($options['admin_color_scheme'] ?? '', 'ectoplasm'); ?>><?php esc_html_e('Ectoplasm', 'wpd'); ?></option>
                        <option value="midnight" <?php selected($options['admin_color_scheme'] ?? '', 'midnight'); ?>><?php esc_html_e('Midnight', 'wpd'); ?></option>
                        <option value="ocean" <?php selected($options['admin_color_scheme'] ?? '', 'ocean'); ?>><?php esc_html_e('Ocean', 'wpd'); ?></option>
                        <option value="sunrise" <?php selected($options['admin_color_scheme'] ?? '', 'sunrise'); ?>><?php esc_html_e('Sunrise', 'wpd'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Forces a specific color scheme for all users, overriding their individual profile setting.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Custom Primary Color', 'wpd'); ?></th>
                <td>
                    <input type="text" class="regular-text"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[admin_primary_color]"
                           value="<?php echo esc_attr($options['admin_primary_color'] ?? ''); ?>"
                           placeholder="#1d2327">
                    <p class="description"><?php esc_html_e('Sets the admin menu background and CSS variable --wp-admin-theme-color. Example: #1a3a5c', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Custom Accent Color', 'wpd'); ?></th>
                <td>
                    <input type="text" class="regular-text"
                           name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[admin_accent_color]"
                           value="<?php echo esc_attr($options['admin_accent_color'] ?? ''); ?>"
                           placeholder="#2271b1">
                    <p class="description"><?php esc_html_e('Color for primary action buttons. Example: #e05c00', 'wpd'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    protected function render_security_tab(array $options): void {
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

        <hr>
        <h2><?php esc_html_e('Advanced Hardening', 'wpd'); ?></h2>
        <p class="description"><?php esc_html_e('Additional measures to reduce information leakage and attack surface.', 'wpd'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Disable REST API User Endpoint', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[hardening_disable_rest_users]"
                               value="1" <?php checked(!empty($options['hardening_disable_rest_users'])); ?>>
                        <?php esc_html_e('Block the /wp/v2/users endpoint for unauthenticated requests.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Prevents username enumeration via the REST API. Authenticated requests are not affected.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Send Security Headers', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[hardening_security_headers]"
                               value="1" <?php checked(!empty($options['hardening_security_headers'])); ?>>
                        <?php esc_html_e('Add X-Frame-Options, X-Content-Type-Options, Referrer-Policy, and Permissions-Policy headers.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Adds standard HTTP security headers to all responses. Protects against clickjacking, MIME-sniffing, and information leakage.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Remove Version from Assets', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[hardening_remove_version_assets]"
                               value="1" <?php checked(!empty($options['hardening_remove_version_assets'])); ?>>
                        <?php esc_html_e('Strip ?ver=x.x from enqueued script and style URLs.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Hides WordPress and plugin version numbers from asset URLs, supplementing the generator meta removal.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Disable Application Passwords', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[hardening_disable_app_passwords]"
                               value="1" <?php checked(!empty($options['hardening_disable_app_passwords'])); ?>>
                        <?php esc_html_e('Disable the Application Passwords feature entirely.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Removes REST API authentication via app passwords. Disable if you do not use external app integrations.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Disable Pingbacks', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[hardening_disable_pingbacks]"
                               value="1" <?php checked(!empty($options['hardening_disable_pingbacks'])); ?>>
                        <?php esc_html_e('Disable pingbacks and trackbacks site-wide.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Removes pingback XML-RPC methods and closes ping endpoints. Prevents your site from being used in DDoS amplification attacks.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Disable Emoji Scripts', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[hardening_disable_emoji]"
                               value="1" <?php checked(!empty($options['hardening_disable_emoji'])); ?>>
                        <?php esc_html_e('Remove WordPress emoji detection scripts and DNS prefetch.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Modern browsers render emoji natively — this WordPress script is not needed and adds unnecessary page weight.', 'wpd'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Disable jQuery Migrate', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[hardening_disable_jquery_migrate]"
                               value="1" <?php checked(!empty($options['hardening_disable_jquery_migrate'])); ?>>
                        <?php esc_html_e('Remove jQuery Migrate from frontend pages.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('jQuery Migrate is a compatibility shim for legacy code. Remove it on the frontend if your theme and plugins use modern jQuery.', 'wpd'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    protected function render_admin_tools_tab(array $options): void {
        $available_menu_items = [
            'edit-comments.php'       => __('Comments', 'wpd'),
            'upload.php'              => __('Media', 'wpd'),
            'link-manager.php'        => __('Links', 'wpd'),
            'tools.php'               => __('Tools', 'wpd'),
            'edit.php?post_type=page' => __('Pages', 'wpd'),
        ];
        $hidden_items = $options['admin_hide_menu_items'] ?? [];
        ?>
        <h2><?php esc_html_e('Update Notices', 'wpd'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Hide for Non-Admins', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[admin_hide_update_notices]"
                               value="1" <?php checked(!empty($options['admin_hide_update_notices'])); ?>>
                        <?php esc_html_e('Hide WordPress core, plugin, and theme update notices for editors and other non-admin users.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Keeps the backend clean for content editors who do not manage updates. Admins always see update notices.', 'wpd'); ?></p>
                </td>
            </tr>
        </table>

        <hr>
        <h2><?php esc_html_e('Maintenance Mode', 'wpd'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Enable Maintenance Mode', 'wpd'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[enable_maintenance_mode]"
                               value="1" <?php checked(!empty($options['enable_maintenance_mode'])); ?>>
                        <?php esc_html_e('Block the frontend for non-logged-in visitors with a maintenance message.', 'wpd'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Returns HTTP 503 for all frontend visitors who are not logged in. Logged-in users and the admin area are unaffected.', 'wpd'); ?></p>
                </td>
            </tr>
        </table>

        <hr>
        <h2><?php esc_html_e('Admin Menu', 'wpd'); ?></h2>
        <p class="description"><?php esc_html_e('Hide specific menu items from non-admin users (editors, authors, etc.). Admins always see all menu items.', 'wpd'); ?></p>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Hide Menu Items', 'wpd'); ?></th>
                <td>
                    <?php foreach ($available_menu_items as $slug => $label) : ?>
                        <label style="display:block;margin-bottom:6px;">
                            <input type="checkbox"
                                   name="<?php echo esc_attr(WPD_OPTION_KEY); ?>[admin_hide_menu_items][]"
                                   value="<?php echo esc_attr($slug); ?>"
                                   <?php checked(in_array($slug, $hidden_items, true)); ?>>
                            <?php echo esc_html($label); ?>
                            <code style="font-size:11px;color:#787c82;"><?php echo esc_html($slug); ?></code>
                        </label>
                    <?php endforeach; ?>
                    <p class="description"><?php esc_html_e('Checked items are hidden for non-admin users. Content remains accessible via direct URL.', 'wpd'); ?></p>
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
        $sanitized['login_button_color']       = sanitize_hex_color($input['login_button_color'] ?? '') ?: '';
        $sanitized['login_link_color']         = sanitize_hex_color($input['login_link_color'] ?? '') ?: '';

        $sanitized['enable_admin_branding']    = !empty($input['enable_admin_branding']);
        $sanitized['admin_footer_text']        = wp_kses_post($input['admin_footer_text'] ?? '');
        $sanitized['admin_bar_link_label']     = sanitize_text_field($input['admin_bar_link_label'] ?? '');
        $sanitized['admin_bar_link_url']       = esc_url_raw($input['admin_bar_link_url'] ?? '');
        $sanitized['admin_bar_greeting']       = sanitize_text_field($input['admin_bar_greeting'] ?? '');

        $valid_schemes = ['', 'fresh', 'light', 'blue', 'coffee', 'ectoplasm', 'midnight', 'ocean', 'sunrise'];
        $scheme = $input['admin_color_scheme'] ?? '';
        $sanitized['admin_color_scheme']   = in_array($scheme, $valid_schemes, true) ? $scheme : '';
        $sanitized['admin_primary_color']  = sanitize_hex_color($input['admin_primary_color'] ?? '') ?: '';
        $sanitized['admin_accent_color']   = sanitize_hex_color($input['admin_accent_color'] ?? '') ?: '';

        $sanitized['enable_hardening']                 = !empty($input['enable_hardening']);
        $sanitized['hardening_disable_xmlrpc']         = !empty($input['hardening_disable_xmlrpc']);
        $author_enum = $input['hardening_author_enum'] ?? 'off';
        $sanitized['hardening_author_enum']            = in_array($author_enum, ['off', '404', 'redirect'], true) ? $author_enum : 'off';
        $sanitized['hardening_remove_rsd']             = !empty($input['hardening_remove_rsd']);
        $sanitized['hardening_remove_wlw']             = !empty($input['hardening_remove_wlw']);
        $sanitized['hardening_remove_generator']       = !empty($input['hardening_remove_generator']);
        $sanitized['hardening_hide_editor']            = !empty($input['hardening_hide_editor']);
        $sanitized['hardening_disable_rest_users']     = !empty($input['hardening_disable_rest_users']);
        $sanitized['hardening_security_headers']       = !empty($input['hardening_security_headers']);
        $sanitized['hardening_remove_version_assets']  = !empty($input['hardening_remove_version_assets']);
        $sanitized['hardening_disable_app_passwords']  = !empty($input['hardening_disable_app_passwords']);
        $sanitized['hardening_disable_pingbacks']      = !empty($input['hardening_disable_pingbacks']);
        $sanitized['hardening_disable_emoji']          = !empty($input['hardening_disable_emoji']);
        $sanitized['hardening_disable_jquery_migrate'] = !empty($input['hardening_disable_jquery_migrate']);

        $env_val = $input['admin_environment'] ?? 'off';
        $sanitized['admin_environment']              = in_array($env_val, ['off', 'live', 'stage'], true) ? $env_val : 'off';
        $sanitized['admin_environment_live_url']     = esc_url_raw($input['admin_environment_live_url'] ?? '');
        $sanitized['admin_environment_stage_url']    = esc_url_raw($input['admin_environment_stage_url'] ?? '');
        $sanitized['admin_hide_update_notices'] = !empty($input['admin_hide_update_notices']);
        $sanitized['enable_maintenance_mode']   = !empty($input['enable_maintenance_mode']);
        $hide_menu = [];
        if (isset($input['admin_hide_menu_items']) && is_array($input['admin_hide_menu_items'])) {
            $hide_menu = array_map('sanitize_text_field', $input['admin_hide_menu_items']);
        }
        $sanitized['admin_hide_menu_items'] = $hide_menu;

        $sanitized['enable_updater']        = !empty($input['enable_updater']);
        $updater_mode = $input['updater_mode'] ?? 'off';
        $sanitized['updater_mode']          = in_array($updater_mode, ['off', 'github', 'custom'], true) ? $updater_mode : 'off';
        $sanitized['updater_github_repo']   = sanitize_text_field($input['updater_github_repo'] ?? '');
        $sanitized['updater_github_token']  = sanitize_text_field($input['updater_github_token'] ?? '');
        $sanitized['updater_custom_url']    = esc_url_raw($input['updater_custom_url'] ?? '');

        return $sanitized;
    }

}
