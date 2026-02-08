# WP Default

A clean, standardized WordPress admin baseline plugin — dashboard cleanup, branding, and light hardening.

## Features

- **Dashboard Widget Manager** — Detect and globally disable dashboard widgets for all users
- **Dashboard Banners** — Custom top banner with columns and post type quick-access buttons
- **Login Branding** — Custom logo, background color/image on the login screen
- **Admin Branding** — Custom footer text and admin bar link
- **Hardening** — Disable XML-RPC, author enumeration protection, remove meta tags, hide editors
- **Updates** — Optional GitHub or custom server update integration

## Requirements

- WordPress 6.0+
- PHP 8.1+

## Installation

1. Upload the `wp-default-dashboard` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Navigate to **Settings → WP Default** to configure

## Configuration

All settings are managed through the admin interface under **Settings → WP Default** with four tabs:

| Tab | Features |
|-----|----------|
| **Dashboard** | Widget manager, top banner, post types banner, data settings |
| **Branding** | Login screen customization, admin footer and bar |
| **Hardening** | XML-RPC, author enumeration, meta tags, editor menus |
| **Updates** | GitHub or custom server update checking |

## Extensibility

The plugin provides public hooks and filters for customization:

### Filters

- `wpd_options_defaults` — Modify default option values
- `wpd_detected_dashboard_widgets` — Filter detected widgets
- `wpd_disabled_dashboard_widgets` — Filter disabled widget IDs
- `wpd_post_types_list` — Filter post types shown in the banner
- `wpd_top_banner_columns` — Filter top banner column data
- `wpd_posttypes_button_labels` — Filter post type button label templates

### Actions

- `wpd_init` — Fires after plugin initialization
- `wpd_admin_init` — Fires after admin initialization
- `wpd_before_dashboard_scan` / `wpd_after_dashboard_scan` — Dashboard widget scan
- `wpd_before_render_top_banner` / `wpd_after_render_top_banner` — Top banner rendering
- `wpd_before_render_posttypes_banner` / `wpd_after_render_posttypes_banner` — Post types banner rendering

## Data Storage

All plugin data is stored in a single WordPress option: `wpd_options`. Enable "Delete Data on Uninstall" in settings to clean up when the plugin is deleted.

## License

GPL-2.0-or-later
