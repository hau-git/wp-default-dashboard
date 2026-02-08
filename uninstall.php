<?php

defined('WP_UNINSTALL_PLUGIN') || exit;

$options = get_option('wpd_options', []);

if (is_array($options) && !empty($options['delete_data_on_uninstall'])) {
    delete_option('wpd_options');
}
