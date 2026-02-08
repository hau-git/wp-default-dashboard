<?php

defined('ABSPATH') || exit;

class WPD_Updater {

    protected WPD_Plugin $plugin;

    public function __construct(WPD_Plugin $plugin) {
        $this->plugin = $plugin;
    }

    public function register(): void {
        $options = wpd_get_options();

        if (empty($options['enable_updater'])) {
            return;
        }

        $mode = $options['updater_mode'] ?? 'off';
        if ($mode === 'off') {
            return;
        }

        add_action('admin_init', [$this, 'init_updater']);
    }

    public function init_updater(): void {
        $checker_file = WPD_PLUGIN_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';

        if (!file_exists($checker_file)) {
            return;
        }

        require_once $checker_file;

        $options = wpd_get_options();
        $mode    = $options['updater_mode'] ?? 'off';

        if ($mode === 'github') {
            $this->init_github_updater($options);
        } elseif ($mode === 'custom') {
            $this->init_custom_updater($options);
        }
    }

    protected function init_github_updater(array $options): void {
        $repo = $options['updater_github_repo'] ?? '';
        if (empty($repo)) {
            return;
        }

        if (!class_exists('YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory')) {
            return;
        }

        $builder = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/' . $repo,
            WPD_PLUGIN_FILE,
            'wp-default-dashboard'
        );

        $token = $options['updater_github_token'] ?? '';
        if (!empty($token) && method_exists($builder, 'setAuthentication')) {
            $builder->setAuthentication($token);
        }

        if (method_exists($builder, 'setBranch')) {
            $builder->setBranch('main');
        }
    }

    protected function init_custom_updater(array $options): void {
        $url = $options['updater_custom_url'] ?? '';
        if (empty($url)) {
            return;
        }

        if (!class_exists('YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory')) {
            return;
        }

        \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            $url,
            WPD_PLUGIN_FILE,
            'wp-default-dashboard'
        );
    }
}
