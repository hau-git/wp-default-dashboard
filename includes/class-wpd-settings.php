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
                'admin_bar_bg_color',
                'admin_bar_text_color',
                'admin_menu_text_color',
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
            <p class="description"><?php esc_html_e('WordPress-Adminbereich anpassen, Dashboard aufräumen und Sicherheitshärtung anwenden.', 'wpd'); ?></p>
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
        $k = WPD_OPTION_KEY;
        ?>

        <?php /* ── Login-Branding ──────────────────────────────────── */ ?>
        <div class="wpd-section <?php echo !empty($options['enable_login_branding']) ? 'is-active' : ''; ?>">
            <div class="wpd-section__head">
                <label class="wpd-section__master">
                    <span class="wpd-toggle">
                        <input type="checkbox"
                               id="wpd_toggle_login_branding"
                               name="<?php echo esc_attr($k); ?>[enable_login_branding]"
                               value="1"
                               <?php checked(!empty($options['enable_login_branding'])); ?>>
                        <span class="wpd-toggle__track"></span>
                    </span>
                    <span class="wpd-section__title"><?php esc_html_e('Login-Branding', 'wpd'); ?></span>
                </label>
                <span class="wpd-section__badge"><?php echo !empty($options['enable_login_branding']) ? esc_html__('aktiv', 'wpd') : esc_html__('inaktiv', 'wpd'); ?></span>
            </div>
            <p class="description"><?php esc_html_e('Passe das Erscheinungsbild der WordPress-Anmeldeseite mit eigenem Logo, Farben und Hintergrundbild an.', 'wpd'); ?></p>

            <div class="wpd-section__body" data-controlled-by="wpd_toggle_login_branding">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Logo', 'wpd'); ?></th>
                        <td>
                            <input type="text" class="regular-text wpd-media-url" id="wpd_login_logo_url"
                                   name="<?php echo esc_attr($k); ?>[login_logo_url]"
                                   value="<?php echo esc_url($options['login_logo_url'] ?? ''); ?>">
                            <button type="button" class="button wpd-media-upload" data-target="#wpd_login_logo_url">
                                <?php esc_html_e('Bild auswählen', 'wpd'); ?>
                            </button>
                            <?php if (!empty($options['login_logo_url'])) : ?>
                                <p><img src="<?php echo esc_url($options['login_logo_url']); ?>" style="max-width:200px;height:auto;margin-top:8px;"></p>
                            <?php endif; ?>
                            <p class="description"><?php esc_html_e('Ersetzt das WordPress-Logo auf der Anmeldeseite. Empfohlene Größe: 320×80 Pixel.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Logo-Link-URL', 'wpd'); ?></th>
                        <td>
                            <input type="url" class="regular-text"
                                   name="<?php echo esc_attr($k); ?>[login_logo_link]"
                                   value="<?php echo esc_url($options['login_logo_link'] ?? ''); ?>">
                            <p class="description"><?php esc_html_e('URL, auf die das Logo verlinkt. Standard ist die Startseite der Website.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Logo-Titel', 'wpd'); ?></th>
                        <td>
                            <input type="text" class="regular-text"
                                   name="<?php echo esc_attr($k); ?>[login_logo_title]"
                                   value="<?php echo esc_attr($options['login_logo_title'] ?? ''); ?>">
                            <p class="description"><?php esc_html_e('Tooltip-Text beim Hovern über das Logo. Standard: "Powered by WordPress".', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Hintergrundfarbe', 'wpd'); ?></th>
                        <td>
                            <input type="text" class="wpd-color-picker"
                                   name="<?php echo esc_attr($k); ?>[login_bg_color]"
                                   value="<?php echo esc_attr($options['login_bg_color'] ?? ''); ?>"
                                   data-default-color="">
                            <p class="description"><?php esc_html_e('Hintergrundfarbe der Anmeldeseite.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Hintergrundbild', 'wpd'); ?></th>
                        <td>
                            <input type="text" class="regular-text wpd-media-url" id="wpd_login_bg_image"
                                   name="<?php echo esc_attr($k); ?>[login_bg_image]"
                                   value="<?php echo esc_url($options['login_bg_image'] ?? ''); ?>">
                            <button type="button" class="button wpd-media-upload" data-target="#wpd_login_bg_image">
                                <?php esc_html_e('Bild auswählen', 'wpd'); ?>
                            </button>
                            <p class="description"><?php esc_html_e('Vollbild-Hintergrundbild für die Anmeldeseite.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Button-Farbe', 'wpd'); ?></th>
                        <td>
                            <input type="text" class="wpd-color-picker"
                                   name="<?php echo esc_attr($k); ?>[login_button_color]"
                                   value="<?php echo esc_attr($options['login_button_color'] ?? ''); ?>"
                                   data-default-color="">
                            <p class="description"><?php esc_html_e('Hintergrundfarbe des Anmelde-Buttons.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Link-Farbe', 'wpd'); ?></th>
                        <td>
                            <input type="text" class="wpd-color-picker"
                                   name="<?php echo esc_attr($k); ?>[login_link_color]"
                                   value="<?php echo esc_attr($options['login_link_color'] ?? ''); ?>"
                                   data-default-color="">
                            <p class="description"><?php esc_html_e('Farbe für Links auf der Anmeldeseite ("Zurück zur Website", "Passwort vergessen?").', 'wpd'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <hr>

        <?php /* ── Admin-Branding ──────────────────────────────────── */ ?>
        <div class="wpd-section <?php echo !empty($options['enable_admin_branding']) ? 'is-active' : ''; ?>">
            <div class="wpd-section__head">
                <label class="wpd-section__master">
                    <span class="wpd-toggle">
                        <input type="checkbox"
                               id="wpd_toggle_admin_branding"
                               name="<?php echo esc_attr($k); ?>[enable_admin_branding]"
                               value="1"
                               <?php checked(!empty($options['enable_admin_branding'])); ?>>
                        <span class="wpd-toggle__track"></span>
                    </span>
                    <span class="wpd-section__title"><?php esc_html_e('Admin-Branding', 'wpd'); ?></span>
                </label>
                <span class="wpd-section__badge"><?php echo !empty($options['enable_admin_branding']) ? esc_html__('aktiv', 'wpd') : esc_html__('inaktiv', 'wpd'); ?></span>
            </div>
            <p class="description"><?php esc_html_e('Passe den WordPress-Adminbereich mit eigenem Footer-Text und einem benutzerdefinierten Admin-Bar-Link an.', 'wpd'); ?></p>

            <div class="wpd-section__body" data-controlled-by="wpd_toggle_admin_branding">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Footer-Text', 'wpd'); ?></th>
                        <td>
                            <textarea class="large-text" rows="3"
                                      name="<?php echo esc_attr($k); ?>[admin_footer_text]"><?php echo esc_textarea($options['admin_footer_text'] ?? ''); ?></textarea>
                            <p class="description"><?php esc_html_e('Ersetzt den Standard-Footer-Text im WordPress-Admin. HTML erlaubt (Links, Fettschrift etc.).', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Admin-Bar-Link', 'wpd'); ?></th>
                        <td>
                            <input type="text" class="regular-text"
                                   name="<?php echo esc_attr($k); ?>[admin_bar_link_label]"
                                   value="<?php echo esc_attr($options['admin_bar_link_label'] ?? ''); ?>"
                                   placeholder="<?php esc_attr_e('Beschriftung', 'wpd'); ?>">
                            <input type="url" class="regular-text"
                                   name="<?php echo esc_attr($k); ?>[admin_bar_link_url]"
                                   value="<?php echo esc_url($options['admin_bar_link_url'] ?? ''); ?>"
                                   placeholder="https://"
                                   style="margin-top:6px;">
                            <p class="description"><?php esc_html_e('Fügt einen benutzerdefinierten Link in die Admin-Bar ein — Beschriftung und URL.', 'wpd'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <hr>

        <?php /* ── Begrüßung ────────────────────────────────────────── */ ?>
        <div class="wpd-section is-active">
            <div class="wpd-section__head">
                <span class="wpd-section__title"><?php esc_html_e('Begrüßung', 'wpd'); ?></span>
            </div>
            <p class="description"><?php esc_html_e('Zeigt einen persönlichen Begrüßungstext in der Admin-Bar vor dem Benutzernamen an. Immer aktiv — unabhängig vom Admin-Branding.', 'wpd'); ?></p>

            <div class="wpd-section__body">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Begrüßungstext', 'wpd'); ?></th>
                        <td>
                            <input type="text" class="regular-text"
                                   name="<?php echo esc_attr($k); ?>[admin_bar_greeting]"
                                   value="<?php echo esc_attr($options['admin_bar_greeting'] ?? ''); ?>"
                                   placeholder="<?php esc_attr_e('Moin,', 'wpd'); ?>">
                            <p class="description"><?php esc_html_e('Wird in der Admin-Bar vor dem Nutzernamen angezeigt, z. B. „Moin, Max Mustermann". Feld leeren, um die Begrüßung zu deaktivieren.', 'wpd'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <hr>

        <?php /* ── Umgebungsindikator ──────────────────────────────── */ ?>
        <div class="wpd-section is-active">
            <div class="wpd-section__head">
                <span class="wpd-section__title"><?php esc_html_e('Umgebungsindikator', 'wpd'); ?></span>
            </div>
            <p class="description"><?php esc_html_e('Zeigt ein farbiges Badge in der Admin-Bar zur Identifizierung der aktuellen Umgebung (Live/Staging) — verhindert versehentliche Änderungen auf der Live-Seite.', 'wpd'); ?></p>

            <div class="wpd-section__body">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Umgebung', 'wpd'); ?></th>
                        <td>
                            <select name="<?php echo esc_attr($k); ?>[admin_environment]">
                                <option value="off" <?php selected($options['admin_environment'] ?? 'off', 'off'); ?>><?php esc_html_e('Deaktiviert', 'wpd'); ?></option>
                                <option value="stage" <?php selected($options['admin_environment'] ?? 'off', 'stage'); ?>><?php esc_html_e('Staging', 'wpd'); ?></option>
                                <option value="live" <?php selected($options['admin_environment'] ?? 'off', 'live'); ?>><?php esc_html_e('Live', 'wpd'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Fallback-Einstellung — wird nur verwendet, wenn die aktuelle URL keiner der konfigurierten URLs entspricht. Das Badge ist für alle eingeloggten Nutzer sichtbar, auch im Frontend.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Live-URL', 'wpd'); ?></th>
                        <td>
                            <input type="url" class="regular-text"
                                   name="<?php echo esc_attr($k); ?>[admin_environment_live_url]"
                                   value="<?php echo esc_url($options['admin_environment_live_url'] ?? ''); ?>"
                                   placeholder="https://example.com">
                            <p class="description"><?php esc_html_e('URL der Live-Website. Das Badge wird automatisch erkannt, wenn das Plugin auf beiden Umgebungen installiert ist — ohne manuelle Auswahl.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Staging-URL', 'wpd'); ?></th>
                        <td>
                            <input type="url" class="regular-text"
                                   name="<?php echo esc_attr($k); ?>[admin_environment_stage_url]"
                                   value="<?php echo esc_url($options['admin_environment_stage_url'] ?? ''); ?>"
                                   placeholder="https://staging.example.com">
                            <p class="description"><?php esc_html_e('URL der Staging-Website. Das Dropdown zeigt immer einen Klick-Link zum Wechseln der Umgebung.', 'wpd'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <hr>

        <?php /* ── Admin-Farben ──────────────────────────────────── */ ?>
        <div class="wpd-section is-active">
            <div class="wpd-section__head">
                <span class="wpd-section__title"><?php esc_html_e('Admin-Farben', 'wpd'); ?></span>
            </div>
            <p class="description"><?php esc_html_e('Globales Admin-Farbschema oder eigene Markenfarben für das WordPress-Backend. Alle Farb-Felder zeigen eine direkte Live-Vorschau.', 'wpd'); ?></p>

            <div class="wpd-section__body">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Farbschema', 'wpd'); ?></th>
                        <td>
                            <select name="<?php echo esc_attr($k); ?>[admin_color_scheme]">
                                <option value="" <?php selected($options['admin_color_scheme'] ?? '', ''); ?>><?php esc_html_e('— Benutzerpräferenz (keine Überschreibung) —', 'wpd'); ?></option>
                                <option value="fresh" <?php selected($options['admin_color_scheme'] ?? '', 'fresh'); ?>><?php esc_html_e('Standard (Fresh)', 'wpd'); ?></option>
                                <option value="light" <?php selected($options['admin_color_scheme'] ?? '', 'light'); ?>><?php esc_html_e('Hell (Light)', 'wpd'); ?></option>
                                <option value="blue" <?php selected($options['admin_color_scheme'] ?? '', 'blue'); ?>><?php esc_html_e('Blau (Blue)', 'wpd'); ?></option>
                                <option value="coffee" <?php selected($options['admin_color_scheme'] ?? '', 'coffee'); ?>><?php esc_html_e('Kaffee (Coffee)', 'wpd'); ?></option>
                                <option value="ectoplasm" <?php selected($options['admin_color_scheme'] ?? '', 'ectoplasm'); ?>><?php esc_html_e('Ektoplasma (Ectoplasm)', 'wpd'); ?></option>
                                <option value="midnight" <?php selected($options['admin_color_scheme'] ?? '', 'midnight'); ?>><?php esc_html_e('Mitternacht (Midnight)', 'wpd'); ?></option>
                                <option value="ocean" <?php selected($options['admin_color_scheme'] ?? '', 'ocean'); ?>><?php esc_html_e('Ozean (Ocean)', 'wpd'); ?></option>
                                <option value="sunrise" <?php selected($options['admin_color_scheme'] ?? '', 'sunrise'); ?>><?php esc_html_e('Sonnenaufgang (Sunrise)', 'wpd'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Erzwingt ein bestimmtes Farbschema für alle Benutzer, unabhängig von deren individuellen Profileinstellungen.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Sidebar-Hintergrund', 'wpd'); ?></th>
                        <td>
                            <input type="text" class="wpd-color-picker"
                                   name="<?php echo esc_attr($k); ?>[admin_primary_color]"
                                   value="<?php echo esc_attr($options['admin_primary_color'] ?? ''); ?>"
                                   data-preview="primary"
                                   data-default-color="">
                            <p class="description"><?php esc_html_e('Hintergrundfarbe des Admin-Menüs (Sidebar). Überschreibt das gewählte Farbschema.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Akzentfarbe (Buttons)', 'wpd'); ?></th>
                        <td>
                            <input type="text" class="wpd-color-picker"
                                   name="<?php echo esc_attr($k); ?>[admin_accent_color]"
                                   value="<?php echo esc_attr($options['admin_accent_color'] ?? ''); ?>"
                                   data-preview="accent"
                                   data-default-color="">
                            <p class="description"><?php esc_html_e('Farbe für primäre Aktions-Buttons (z. B. "Speichern"). Überschreibt das gewählte Farbschema.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Admin-Bar Hintergrund', 'wpd'); ?></th>
                        <td>
                            <input type="text" class="wpd-color-picker"
                                   name="<?php echo esc_attr($k); ?>[admin_bar_bg_color]"
                                   value="<?php echo esc_attr($options['admin_bar_bg_color'] ?? ''); ?>"
                                   data-preview="bar_bg"
                                   data-default-color="">
                            <p class="description"><?php esc_html_e('Hintergrundfarbe der Admin-Bar (schwarze Leiste oben). Gilt auch im Frontend für eingeloggte Nutzer.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Admin-Bar Text', 'wpd'); ?></th>
                        <td>
                            <input type="text" class="wpd-color-picker"
                                   name="<?php echo esc_attr($k); ?>[admin_bar_text_color]"
                                   value="<?php echo esc_attr($options['admin_bar_text_color'] ?? ''); ?>"
                                   data-preview="bar_text"
                                   data-default-color="">
                            <p class="description"><?php esc_html_e('Textfarbe der Admin-Bar-Links und -Elemente.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Menü-Textfarbe', 'wpd'); ?></th>
                        <td>
                            <input type="text" class="wpd-color-picker"
                                   name="<?php echo esc_attr($k); ?>[admin_menu_text_color]"
                                   value="<?php echo esc_attr($options['admin_menu_text_color'] ?? ''); ?>"
                                   data-preview="menu_text"
                                   data-default-color="">
                            <p class="description"><?php esc_html_e('Textfarbe der Menüeinträge in der Sidebar.', 'wpd'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <?php
    }

    protected function render_security_tab(array $options): void {
        $k = WPD_OPTION_KEY;
        ?>

        <?php /* ── Sicherheitshärtung ──────────────────────────── */ ?>
        <div class="wpd-section <?php echo !empty($options['enable_hardening']) ? 'is-active' : ''; ?>">
            <div class="wpd-section__head">
                <label class="wpd-section__master">
                    <span class="wpd-toggle">
                        <input type="checkbox"
                               id="wpd_toggle_hardening"
                               name="<?php echo esc_attr($k); ?>[enable_hardening]"
                               value="1"
                               <?php checked(!empty($options['enable_hardening'])); ?>>
                        <span class="wpd-toggle__track"></span>
                    </span>
                    <span class="wpd-section__title"><?php esc_html_e('Sicherheitshärtung', 'wpd'); ?></span>
                </label>
                <span class="wpd-section__badge"><?php echo !empty($options['enable_hardening']) ? esc_html__('aktiv', 'wpd') : esc_html__('inaktiv', 'wpd'); ?></span>
            </div>
            <p class="description"><?php esc_html_e('Leichte Sicherheitsmaßnahmen zur Reduzierung der Angriffsfläche — entfernt unnötige Informationen und deaktiviert häufig missbrauchte Funktionen.', 'wpd'); ?></p>

            <div class="wpd-section__body" data-controlled-by="wpd_toggle_hardening">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('XML-RPC deaktivieren', 'wpd'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr($k); ?>[hardening_disable_xmlrpc]"
                                       value="1" <?php checked(!empty($options['hardening_disable_xmlrpc'])); ?>>
                                <?php esc_html_e('XML-RPC-Schnittstelle vollständig deaktivieren.', 'wpd'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('XML-RPC ist eine veraltete API, die häufig für Brute-Force-Angriffe missbraucht wird. Deaktivieren, sofern keine WordPress-App, Jetpack o. Ä. verwendet wird.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Autoren-Enumeration', 'wpd'); ?></th>
                        <td>
                            <select name="<?php echo esc_attr($k); ?>[hardening_author_enum]">
                                <option value="off" <?php selected($options['hardening_author_enum'] ?? 'off', 'off'); ?>><?php esc_html_e('Aus (kein Schutz)', 'wpd'); ?></option>
                                <option value="404" <?php selected($options['hardening_author_enum'] ?? 'off', '404'); ?>><?php esc_html_e('404 zurückgeben', 'wpd'); ?></option>
                                <option value="redirect" <?php selected($options['hardening_author_enum'] ?? 'off', 'redirect'); ?>><?php esc_html_e('Zur Startseite weiterleiten', 'wpd'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Verhindert die Entdeckung von Benutzernamen über ?author=1 URLs. "404 zurückgeben" zeigt eine Fehlerseite, "Weiterleiten" leitet zur Startseite weiter.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('RSD-Link entfernen', 'wpd'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr($k); ?>[hardening_remove_rsd]"
                                       value="1" <?php checked(!empty($options['hardening_remove_rsd'])); ?>>
                                <?php esc_html_e('RSD-Link (Really Simple Discovery) aus dem Header entfernen.', 'wpd'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('RSD wird nur für Remote-Publishing-Clients benötigt. Entfernen reduziert Informationspreisgabe.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('WLW-Manifest entfernen', 'wpd'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr($k); ?>[hardening_remove_wlw]"
                                       value="1" <?php checked(!empty($options['hardening_remove_wlw'])); ?>>
                                <?php esc_html_e('Windows Live Writer Manifest-Link entfernen.', 'wpd'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Windows Live Writer ist eingestellt. Dieser Link wird nicht benötigt und kann bedenkenlos entfernt werden.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Generator-Meta entfernen', 'wpd'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr($k); ?>[hardening_remove_generator]"
                                       value="1" <?php checked(!empty($options['hardening_remove_generator'])); ?>>
                                <?php esc_html_e('WordPress-Versions-Meta-Tag entfernen.', 'wpd'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Versteckt die WordPress-Versionsnummer im HTML-Quelltext. Verhindert, dass Angreifer versionsspezifische Schwachstellen ausnutzen.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Theme-/Plugin-Editor ausblenden', 'wpd'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr($k); ?>[hardening_hide_editor]"
                                       value="1" <?php checked(!empty($options['hardening_hide_editor'])); ?>>
                                <?php esc_html_e('Den eingebauten Theme- und Plugin-Editor aus dem Menü ausblenden.', 'wpd'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Entfernt die Editor-Menüeinträge. Für vollständigen Schutz zusätzlich DISALLOW_FILE_EDIT in der wp-config.php setzen.', 'wpd'); ?></p>
                        </td>
                    </tr>
                </table>

                <h3 style="margin:20px 0 8px; font-size:13px; color:#1d2327;"><?php esc_html_e('Erweiterte Härtung', 'wpd'); ?></h3>
                <p class="description" style="margin-bottom:12px;"><?php esc_html_e('Weitere Maßnahmen zur Reduzierung von Informationspreisgabe und Angriffsfläche.', 'wpd'); ?></p>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('REST-API Benutzer-Endpoint sperren', 'wpd'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr($k); ?>[hardening_disable_rest_users]"
                                       value="1" <?php checked(!empty($options['hardening_disable_rest_users'])); ?>>
                                <?php esc_html_e('/wp/v2/users für nicht authentifizierte Anfragen blockieren.', 'wpd'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Verhindert Benutzernamen-Enumeration über die REST-API. Authentifizierte Anfragen sind nicht betroffen.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Sicherheits-Header senden', 'wpd'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr($k); ?>[hardening_security_headers]"
                                       value="1" <?php checked(!empty($options['hardening_security_headers'])); ?>>
                                <?php esc_html_e('X-Frame-Options, X-Content-Type-Options, Referrer-Policy und Permissions-Policy hinzufügen.', 'wpd'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Fügt Standard-HTTP-Sicherheits-Header zu allen Antworten hinzu. Schützt vor Clickjacking, MIME-Sniffing und Informationspreisgabe.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Version aus Asset-URLs entfernen', 'wpd'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr($k); ?>[hardening_remove_version_assets]"
                                       value="1" <?php checked(!empty($options['hardening_remove_version_assets'])); ?>>
                                <?php esc_html_e('?ver=x.x aus Script- und Style-URLs entfernen.', 'wpd'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Versteckt WordPress- und Plugin-Versionsnummern in Asset-URLs — ergänzt das Entfernen des Generator-Meta-Tags.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Anwendungspasswörter deaktivieren', 'wpd'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr($k); ?>[hardening_disable_app_passwords]"
                                       value="1" <?php checked(!empty($options['hardening_disable_app_passwords'])); ?>>
                                <?php esc_html_e('Application Passwords Funktion vollständig deaktivieren.', 'wpd'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Entfernt REST-API-Authentifizierung über App-Passwörter. Deaktivieren, wenn keine externen App-Integrationen verwendet werden.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Pingbacks deaktivieren', 'wpd'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr($k); ?>[hardening_disable_pingbacks]"
                                       value="1" <?php checked(!empty($options['hardening_disable_pingbacks'])); ?>>
                                <?php esc_html_e('Pingbacks und Trackbacks seitenübergreifend deaktivieren.', 'wpd'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Entfernt Pingback-XML-RPC-Methoden und schließt Ping-Endpunkte. Verhindert, dass die Website für DDoS-Amplifikationsangriffe missbraucht wird.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Emoji-Skripte deaktivieren', 'wpd'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr($k); ?>[hardening_disable_emoji]"
                                       value="1" <?php checked(!empty($options['hardening_disable_emoji'])); ?>>
                                <?php esc_html_e('WordPress Emoji-Erkennungsskripte und DNS-Prefetch entfernen.', 'wpd'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Moderne Browser rendern Emojis nativ — das WordPress-Skript ist nicht erforderlich und erhöht das Seitengewicht unnötig.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('jQuery Migrate deaktivieren', 'wpd'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr($k); ?>[hardening_disable_jquery_migrate]"
                                       value="1" <?php checked(!empty($options['hardening_disable_jquery_migrate'])); ?>>
                                <?php esc_html_e('jQuery Migrate von Frontend-Seiten entfernen.', 'wpd'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('jQuery Migrate ist ein Kompatibilitäts-Shim für veralteten Code. Im Frontend entfernen, wenn Theme und Plugins modernes jQuery verwenden.', 'wpd'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <?php
    }

    protected function render_admin_tools_tab(array $options): void {
        $k = WPD_OPTION_KEY;
        $available_menu_items = [
            'edit-comments.php'       => __('Kommentare', 'wpd'),
            'upload.php'              => __('Medien', 'wpd'),
            'link-manager.php'        => __('Links', 'wpd'),
            'tools.php'               => __('Werkzeuge', 'wpd'),
            'edit.php?post_type=page' => __('Seiten', 'wpd'),
        ];
        $hidden_items = $options['admin_hide_menu_items'] ?? [];
        ?>

        <?php /* ── Update-Hinweise ────────────────────────────── */ ?>
        <div class="wpd-section <?php echo !empty($options['admin_hide_update_notices']) ? 'is-active' : ''; ?>">
            <div class="wpd-section__head">
                <label class="wpd-section__master">
                    <span class="wpd-toggle">
                        <input type="checkbox"
                               id="wpd_toggle_update_notices"
                               name="<?php echo esc_attr($k); ?>[admin_hide_update_notices]"
                               value="1"
                               <?php checked(!empty($options['admin_hide_update_notices'])); ?>>
                        <span class="wpd-toggle__track"></span>
                    </span>
                    <span class="wpd-section__title"><?php esc_html_e('Update-Hinweise ausblenden', 'wpd'); ?></span>
                </label>
                <span class="wpd-section__badge"><?php echo !empty($options['admin_hide_update_notices']) ? esc_html__('aktiv', 'wpd') : esc_html__('inaktiv', 'wpd'); ?></span>
            </div>
            <p class="description"><?php esc_html_e('WordPress-Core-, Plugin- und Theme-Update-Hinweise für Redakteure und andere Nicht-Admin-Benutzer ausblenden. Admins sehen Update-Hinweise immer.', 'wpd'); ?></p>
        </div>

        <hr>

        <?php /* ── Wartungsmodus ────────────────────────────────── */ ?>
        <div class="wpd-section <?php echo !empty($options['enable_maintenance_mode']) ? 'is-active' : ''; ?>">
            <div class="wpd-section__head">
                <label class="wpd-section__master">
                    <span class="wpd-toggle">
                        <input type="checkbox"
                               id="wpd_toggle_maintenance"
                               name="<?php echo esc_attr($k); ?>[enable_maintenance_mode]"
                               value="1"
                               <?php checked(!empty($options['enable_maintenance_mode'])); ?>>
                        <span class="wpd-toggle__track"></span>
                    </span>
                    <span class="wpd-section__title"><?php esc_html_e('Wartungsmodus', 'wpd'); ?></span>
                </label>
                <span class="wpd-section__badge"><?php echo !empty($options['enable_maintenance_mode']) ? esc_html__('aktiv', 'wpd') : esc_html__('inaktiv', 'wpd'); ?></span>
            </div>
            <p class="description"><?php esc_html_e('Sperrt das Frontend für nicht eingeloggte Besucher mit einer Wartungsmeldung (HTTP 503). Eingeloggte Nutzer und der Adminbereich sind nicht betroffen.', 'wpd'); ?></p>
        </div>

        <hr>

        <?php /* ── Admin-Menü ───────────────────────────────────── */ ?>
        <div class="wpd-section is-active">
            <div class="wpd-section__head">
                <span class="wpd-section__title"><?php esc_html_e('Admin-Menü', 'wpd'); ?></span>
            </div>
            <p class="description"><?php esc_html_e('Bestimmte Menüeinträge für Nicht-Admin-Benutzer (Redakteure, Autoren usw.) ausblenden. Admins sehen immer alle Menüeinträge.', 'wpd'); ?></p>

            <div class="wpd-section__body">
                <div class="wpd-checkbox-grid" style="margin-top:8px;">
                    <?php foreach ($available_menu_items as $slug => $label) : ?>
                        <label class="wpd-checkbox-item">
                            <input type="checkbox"
                                   name="<?php echo esc_attr($k); ?>[admin_hide_menu_items][]"
                                   value="<?php echo esc_attr($slug); ?>"
                                   <?php checked(in_array($slug, $hidden_items, true)); ?>>
                            <span><?php echo esc_html($label); ?></span>
                            <code><?php echo esc_html($slug); ?></code>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="description" style="margin-top:12px;"><?php esc_html_e('Aktivierte Einträge werden für Nicht-Admin-Nutzer ausgeblendet. Die Inhalte bleiben über direkte URLs zugänglich.', 'wpd'); ?></p>
            </div>
        </div>

        <?php
    }

    protected function render_updates_tab(array $options): void {
        $k = WPD_OPTION_KEY;
        ?>

        <?php /* ── Plugin-Updates ───────────────────────────────── */ ?>
        <div class="wpd-section <?php echo !empty($options['enable_updater']) ? 'is-active' : ''; ?>">
            <div class="wpd-section__head">
                <label class="wpd-section__master">
                    <span class="wpd-toggle">
                        <input type="checkbox"
                               id="wpd_toggle_updater"
                               name="<?php echo esc_attr($k); ?>[enable_updater]"
                               value="1"
                               <?php checked(!empty($options['enable_updater'])); ?>>
                        <span class="wpd-toggle__track"></span>
                    </span>
                    <span class="wpd-section__title"><?php esc_html_e('Plugin-Updates', 'wpd'); ?></span>
                </label>
                <span class="wpd-section__badge"><?php echo !empty($options['enable_updater']) ? esc_html__('aktiv', 'wpd') : esc_html__('inaktiv', 'wpd'); ?></span>
            </div>
            <p class="description"><?php esc_html_e('Automatische Update-Prüfungen von GitHub-Releases oder einem benutzerdefinierten Update-Server. Ermöglicht das Aktualisieren von WP Default wie jedes andere Plugin.', 'wpd'); ?></p>

            <div class="wpd-section__body" data-controlled-by="wpd_toggle_updater">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Update-Quelle', 'wpd'); ?></th>
                        <td>
                            <select name="<?php echo esc_attr($k); ?>[updater_mode]">
                                <option value="off" <?php selected($options['updater_mode'] ?? 'off', 'off'); ?>><?php esc_html_e('Aus', 'wpd'); ?></option>
                                <option value="github" <?php selected($options['updater_mode'] ?? 'off', 'github'); ?>><?php esc_html_e('GitHub', 'wpd'); ?></option>
                                <option value="custom" <?php selected($options['updater_mode'] ?? 'off', 'custom'); ?>><?php esc_html_e('Benutzerdefinierte URL', 'wpd'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Update-Quelle auswählen. GitHub nutzt die Releases-API, Benutzerdefinierte URL erwartet einen JSON-Endpunkt.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('GitHub-Repository', 'wpd'); ?></th>
                        <td>
                            <input type="text" class="regular-text"
                                   name="<?php echo esc_attr($k); ?>[updater_github_repo]"
                                   value="<?php echo esc_attr($options['updater_github_repo'] ?? ''); ?>"
                                   placeholder="benutzername/repository">
                            <p class="description"><?php esc_html_e('Format: benutzername/repository (z. B. acme/wp-default-dashboard). Das Repository muss GitHub Releases mit ZIP-Assets haben.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('GitHub Access Token', 'wpd'); ?></th>
                        <td>
                            <input type="password" class="regular-text"
                                   name="<?php echo esc_attr($k); ?>[updater_github_token]"
                                   value="<?php echo esc_attr($options['updater_github_token'] ?? ''); ?>"
                                   autocomplete="off">
                            <p class="description"><?php esc_html_e('Nur für private Repositories erforderlich. Personal Access Token mit "repo"-Scope unter github.com/settings/tokens erstellen.', 'wpd'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Benutzerdefinierte Update-URL', 'wpd'); ?></th>
                        <td>
                            <input type="url" class="regular-text"
                                   name="<?php echo esc_attr($k); ?>[updater_custom_url]"
                                   value="<?php echo esc_url($options['updater_custom_url'] ?? ''); ?>"
                                   placeholder="https://example.com/updates/info.json">
                            <p class="description"><?php esc_html_e('URL zu einer JSON-Datei mit Versionsinformationen. Muss "version", "download_url" und optional "tested", "requires_php" enthalten.', 'wpd'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

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
        $sanitized['admin_color_scheme']     = in_array($scheme, $valid_schemes, true) ? $scheme : '';
        $sanitized['admin_primary_color']    = sanitize_hex_color($input['admin_primary_color'] ?? '') ?: '';
        $sanitized['admin_accent_color']     = sanitize_hex_color($input['admin_accent_color'] ?? '') ?: '';
        $sanitized['admin_bar_bg_color']     = sanitize_hex_color($input['admin_bar_bg_color'] ?? '') ?: '';
        $sanitized['admin_bar_text_color']   = sanitize_hex_color($input['admin_bar_text_color'] ?? '') ?: '';
        $sanitized['admin_menu_text_color']  = sanitize_hex_color($input['admin_menu_text_color'] ?? '') ?: '';

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
