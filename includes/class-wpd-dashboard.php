<?php

defined('ABSPATH') || exit;

class WPD_Dashboard {

    protected WPD_Plugin $plugin;

    public function __construct(WPD_Plugin $plugin) {
        $this->plugin = $plugin;
    }

    public function register(): void {
        add_action('wp_dashboard_setup', [$this, 'disable_dashboard_widgets'], 999);
        add_action('admin_notices', [$this, 'render_dashboard_banners']);
    }

    public function disable_dashboard_widgets(): void {
        $options = wpd_get_options();

        if (empty($options['enable_dashboard_manager'])) {
            return;
        }

        $disabled = $options['disabled_dashboard_widgets'] ?? [];

        $disabled = self::normalize_disabled_widgets($disabled);

        $disabled = apply_filters('wpd_disabled_dashboard_widgets', $disabled);

        if (empty($disabled)) {
            return;
        }

        $screen = get_current_screen();
        foreach ($disabled as $widget_id => $widget_context) {
            if ($widget_id === 'dashboard_welcome_panel') {
                remove_action('welcome_panel', 'wp_welcome_panel');
                continue;
            }

            if ($widget_context && $widget_context !== 'all') {
                remove_meta_box($widget_id, $screen, $widget_context);
            } else {
                foreach (['normal', 'side', 'column3', 'column4'] as $ctx) {
                    remove_meta_box($widget_id, $screen, $ctx);
                }
            }
        }
    }

    public static function normalize_disabled_widgets(array $disabled): array {
        $normalized = [];

        foreach ($disabled as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $normalized[$value] = 'all';
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    public static function get_available_dashboard_widgets(): array {
        global $wp_meta_boxes;

        $screen = 'dashboard';

        if (!isset($wp_meta_boxes[$screen]) || !is_array($wp_meta_boxes[$screen])) {
            require_once ABSPATH . '/wp-admin/includes/dashboard.php';

            $current_screen = get_current_screen();

            set_current_screen($screen);

            $removed_callbacks = [];
            global $wp_filter;
            if (isset($wp_filter['wp_dashboard_setup'])) {
                foreach ($wp_filter['wp_dashboard_setup']->callbacks as $priority => $callbacks) {
                    foreach ($callbacks as $key => $callback) {
                        if (is_array($callback['function']) && isset($callback['function'][0]) && $callback['function'][0] instanceof self && $callback['function'][1] === 'disable_dashboard_widgets') {
                            $removed_callbacks[] = ['function' => $callback['function'], 'priority' => $priority];
                            remove_action('wp_dashboard_setup', $callback['function'], $priority);
                        }
                    }
                }
            }

            wp_dashboard_setup();

            foreach ($removed_callbacks as $cb) {
                add_action('wp_dashboard_setup', $cb['function'], $cb['priority']);
            }

            if ($current_screen) {
                set_current_screen($current_screen);
            }
        }

        if (isset($wp_meta_boxes[$screen][0])) {
            unset($wp_meta_boxes[$screen][0]);
        }

        $widgets = [];

        if (!isset($wp_meta_boxes[$screen]) || !is_array($wp_meta_boxes[$screen])) {
            return $widgets;
        }

        foreach ($wp_meta_boxes[$screen] as $context => $priorities) {
            if (!is_array($priorities)) {
                continue;
            }
            foreach ($priorities as $priority => $boxes) {
                if (!is_array($boxes)) {
                    continue;
                }
                foreach ($boxes as $widget_id => $widget_data) {
                    if ($widget_data === false || !is_array($widget_data)) {
                        continue;
                    }
                    $title = '';
                    if (!empty($widget_data['title'])) {
                        $title = wp_strip_all_tags($widget_data['title']);
                    }
                    if (empty($title)) {
                        $title = $widget_id;
                    }

                    $widgets[$widget_id] = [
                        'title'   => $title,
                        'context' => $context,
                    ];
                }
            }
        }

        $widgets = apply_filters('wpd_detected_dashboard_widgets', $widgets);

        return $widgets;
    }

    public function render_dashboard_banners(): void {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'dashboard') {
            return;
        }

        $options = wpd_get_options();

        if (!empty($options['enable_top_banner'])) {
            $this->render_top_banner($options);
        }

        if (!empty($options['enable_posttypes_banner'])) {
            $this->render_posttypes_banner($options);
        }
    }

    protected function render_top_banner(array $options): void {
        do_action('wpd_before_render_top_banner');

        $headline = $options['top_banner_headline'] ?? '';
        $intro    = $options['top_banner_intro'] ?? '';
        $columns  = $options['top_banner_columns'] ?? [];
        $columns  = apply_filters('wpd_top_banner_columns', $columns);

        $has_columns = false;
        foreach ($columns as $col) {
            if (!empty($col['content']) || !empty($col['button_label'])) {
                $has_columns = true;
                break;
            }
        }

        if (empty($headline) && empty($intro) && !$has_columns) {
            do_action('wpd_after_render_top_banner');
            return;
        }

        ?>
        <div class="wpd-top-banner">
            <?php if (!empty($headline)) : ?>
                <h2 class="wpd-top-banner__headline"><?php echo esc_html($headline); ?></h2>
            <?php endif; ?>
            <?php if (!empty($intro)) : ?>
                <p class="wpd-top-banner__intro"><?php echo esc_html($intro); ?></p>
            <?php endif; ?>
            <?php if ($has_columns) : ?>
                <div class="wpd-top-banner__columns">
                    <?php foreach ($columns as $col) :
                        if (empty($col['content']) && empty($col['button_label'])) {
                            continue;
                        }
                        ?>
                        <div class="wpd-top-banner__column">
                            <?php if (!empty($col['content'])) : ?>
                                <div class="wpd-top-banner__content"><?php echo wp_kses_post($col['content']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($col['button_label']) && !empty($col['button_url'])) : ?>
                                <p>
                                    <a href="<?php echo esc_url($col['button_url']); ?>" class="button button-primary">
                                        <?php echo esc_html($col['button_label']); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php

        do_action('wpd_after_render_top_banner');
    }

    protected function render_posttypes_banner(array $options): void {
        do_action('wpd_before_render_posttypes_banner');

        $post_types = get_post_types(['public' => true, 'show_ui' => true], 'objects');
        unset($post_types['attachment']);

        $post_types = apply_filters('wpd_post_types_list', $post_types);

        $selected_pts = $options['posttypes_selected'] ?? [];
        if (!empty($selected_pts)) {
            $post_types = array_filter($post_types, function ($pt) use ($selected_pts) {
                return in_array($pt->name, $selected_pts, true);
            });
        }

        if (empty($post_types)) {
            do_action('wpd_after_render_posttypes_banner');
            return;
        }

        ?>
        <div class="wpd-posttypes-banner">
            <div class="wpd-posttypes-banner__grid">
                <?php foreach ($post_types as $pt) :
                    $counts      = wp_count_posts($pt->name);
                    $published   = (int) ($counts->publish ?? 0);
                    $drafts      = (int) ($counts->draft ?? 0);
                    $description = $pt->description ?? '';
                    $pt_label    = $pt->labels->name ?? $pt->label;
                    $icon_class  = self::get_post_type_dashicon($pt);
                    $list_url    = ($pt->name === 'post') ? admin_url('edit.php') : admin_url('edit.php?post_type=' . $pt->name);
                    $new_url     = ($pt->name === 'post') ? admin_url('post-new.php') : admin_url('post-new.php?post_type=' . $pt->name);
                    ?>
                    <div class="postbox wpd-pt-card">
                        <div class="inside">
                            <div class="wpd-pt-card__header">
                                <span class="dashicons <?php echo esc_attr($icon_class); ?>"></span>
                                <strong class="wpd-pt-card__title"><?php echo esc_html($pt_label); ?></strong>
                            </div>
                            <p class="wpd-pt-card__counts">
                                <?php echo esc_html($published . ' ' . __('veröffentlicht', 'wpd')); ?>
                                <span class="wpd-pt-card__sep"> | </span>
                                <?php echo esc_html($drafts . ' ' . __('Entwürfe', 'wpd')); ?>
                            </p>
                            <?php if (!empty($description)) : ?>
                                <p class="wpd-pt-card__desc"><?php echo esc_html($description); ?></p>
                            <?php endif; ?>
                            <hr class="wpd-pt-card__divider">
                            <div class="wpd-pt-card__footer">
                                <a href="<?php echo esc_url($list_url); ?>"><?php esc_html_e('Alle anzeigen', 'wpd'); ?></a>
                                <span class="wpd-pt-card__sep"> | </span>
                                <a href="<?php echo esc_url($new_url); ?>">+ <?php esc_html_e('Neu erstellen', 'wpd'); ?></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php

        do_action('wpd_after_render_posttypes_banner');
    }

    protected static function get_post_type_dashicon(\WP_Post_Type $pt): string {
        $icon = $pt->menu_icon ?? '';

        if (!empty($icon) && str_starts_with($icon, 'dashicons-')) {
            return $icon;
        }

        $defaults = [
            'post' => 'dashicons-admin-post',
            'page' => 'dashicons-admin-page',
        ];

        return $defaults[$pt->name] ?? 'dashicons-admin-post';
    }
}
