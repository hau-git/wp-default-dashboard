# WP Default

A clean, standardized WordPress admin baseline plugin — dashboard cleanup, branding, light hardening, and backend maintenance tools.

## Features

### Dashboard
- **Widget Manager** — Globally disable dashboard widgets for all users
- **Top Banner** — Custom announcement banner with up to 4 content columns
- **Post Types Banner** — Card-based quick-access overview per post type with live counts

### Erscheinungsbild (Appearance)
- **Login Branding** — Custom logo, background color/image, button color, link color on the login screen
- **Admin Branding** — Custom footer text, admin bar link, and custom greeting (replaces "Howdy, %s")
- **Environment Indicator** — Dropdown badge (Live=green / Staging=red) in the admin bar with a one-click link to switch environments
- **Admin Colors** — Force a global color scheme (8 built-in WP schemes) or set custom primary and accent colors via CSS variables

### Sicherheit (Security)
- **Disable XML-RPC** — Block the legacy XML-RPC interface
- **Author Enumeration** — Block `?author=N` scans (404 or redirect)
- **Remove Meta Tags** — Strip RSD, WLW Manifest, and WordPress generator meta
- **Hide File Editors** — Remove theme/plugin editor menu entries
- **Disable REST API User Endpoint** — Block `/wp/v2/users` for unauthenticated requests
- **Security Headers** — Add X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy
- **Remove Version from Assets** — Strip `?ver=x.x` from script/style URLs
- **Disable Application Passwords** — Turn off REST API app password authentication
- **Disable Pingbacks** — Block pingback/trackback methods site-wide
- **Disable Emoji Scripts** — Remove unnecessary emoji detection JS and DNS prefetch
- **Disable jQuery Migrate** — Remove jQuery Migrate from the frontend

### Admin-Werkzeuge (Admin Tools)
- **Hide Update Notices** — Suppress core/plugin/theme update banners for non-admin users
- **Maintenance Mode** — Block frontend with HTTP 503 for non-logged-in visitors
- **Admin Menu Cleanup** — Hide specific menu items from editors and non-admins

### Updates
- Optional GitHub or custom server update integration

## Requirements

- WordPress 6.0+
- PHP 8.1+

## Installation

1. Upload the `wp-default-dashboard` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Navigate to **Settings → WP Default** to configure

## Configuration

All settings are managed through the admin interface under **Settings → WP Default** with five tabs:

| Tab | Features |
|-----|----------|
| **Dashboard** | Widget manager, top banner, post types banner, data settings |
| **Erscheinungsbild** | Login screen customization, admin bar greeting, environment indicator, admin colors, footer |
| **Sicherheit** | XML-RPC, author enumeration, meta tags, editor menus, advanced hardening |
| **Admin-Werkzeuge** | Update notices, maintenance mode, menu cleanup |
| **Updates** | GitHub or custom server update checking |

## Extensibility

The plugin provides public hooks and filters for customization:

### Filters

- `wpd_options_defaults` — Modify default option values
- `wpd_detected_dashboard_widgets` — Filter detected widgets
- `wpd_disabled_dashboard_widgets` — Filter disabled widget IDs
- `wpd_post_types_list` — Filter post types shown in the banner
- `wpd_top_banner_columns` — Filter top banner column data

### Actions

- `wpd_init` — Fires after plugin initialization
- `wpd_admin_init` — Fires after admin initialization
- `wpd_before_render_top_banner` / `wpd_after_render_top_banner` — Top banner rendering
- `wpd_before_render_posttypes_banner` / `wpd_after_render_posttypes_banner` — Post types banner rendering

## Data Storage

All plugin data is stored in a single WordPress option: `wpd_options`. Enable "Delete Data on Uninstall" in settings to clean up when the plugin is deleted.

## Changelog

### 1.2.3 — 2026-03-06

**Stability / Bugfixes**
- Fixed: Environment indicator badge never appeared when using URL-based auto-detection with "Environment" selector set to "Disabled" — hooks are now registered whenever Live URL or Staging URL is configured
- Fixed: Removed dead `try_gutenberg_panel` removal (panel was removed in WP 5.4)
- Fixed: `is_array()` guard added to `hide_admin_menu_items()` to prevent TypeError on corrupt stored data (PHP 8)

**Performance**
- Improved: `wpd_get_options()` now caches its result in a static variable — eliminates repeated `wp_parse_args()` + `get_defaults()` calls across multiple hooks in the same request
- Improved: Custom admin bar greeting now hooks into `admin_bar_menu` (priority 200) instead of the `gettext` filter — `gettext` fired on every translated string (hundreds per page); `admin_bar_menu` fires once

**Code Quality**
- Fixed: Maintenance mode text is now translatable via i18n (`__()`)
- Fixed: Removed redundant `require_once helpers.php` inside activation hook (already loaded before)
- Fixed: `WPD_VERSION` constant and plugin header version synced to `1.2.3`

---

### 1.2.2 — 2026-03-06

**Dashboard Tab**
- Redesigned: Dashboard tab layout completely overhauled — each feature (Widget-Verwaltung, Oberes Banner, Inhaltstypen-Banner) is now a collapsible card with a toggle switch as its master control
- Fixed: Widget list and Post Type list now only appear when the respective master toggle is enabled (the body collapses/expands with animation)
- Redesigned: Top Banner columns are now displayed in a compact 2×2 card grid instead of four consecutive table rows — replacing TinyMCE editors with plain textareas (HTML supported) for a cleaner editing experience
- Added: Active/inactive badge per section that updates live when toggling

---

### 1.2.1 — 2026-03-06

**Erscheinungsbild**
- Redesigned: Environment indicator dropdown now matches the new design — colored pill badge with dropdown arrow in the top bar; submenu shows "UMGEBUNG WECHSELN" header, current env with "aktuell" label (grey, non-clickable), other env with "→ wechseln" link (green)
- Added: Auto-detection of environment — when both Live URL and Staging URL are configured on their respective sites, the badge is automatically set to the correct environment by comparing `home_url()` with the configured URLs (no manual "Environment" selection needed)
- The manual Environment selector is retained as fallback for single-site setups without URL matching

---

### 1.2.0 — 2026-03-06

**Settings Overhaul**
- Tabs reorganized: Branding → Erscheinungsbild, Hardening → Sicherheit, Admin → Admin-Werkzeuge
- Environment Indicator moved from Admin-Werkzeuge into Erscheinungsbild (it's a visual feature)

**Erscheinungsbild**
- Added: Custom admin bar greeting — replace "Howdy, %s" with any text (e.g. "Moin, %s")
- Added: Live URL and Staging URL fields for the environment indicator
- Redesigned: Environment indicator now shows as a dropdown in `top-secondary` with a link to switch to the other environment; Live = green badge, Staging = red badge

**Translations**
- Updated German translation (de_DE) with all new strings

---

### 1.1.1 — 2026-03-05

**Hardening**
- Added: Disable REST API `/wp/v2/users` endpoint for unauthenticated requests
- Added: Security headers (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy)
- Added: Remove WordPress version from enqueued script/style asset URLs
- Added: Disable Application Passwords (WP 5.6+)
- Added: Disable pingbacks and trackbacks site-wide
- Added: Disable WordPress emoji detection scripts and DNS prefetch
- Added: Disable jQuery Migrate on the frontend

**Branding**
- Added: Login page button color and link color pickers
- Added: Global admin color scheme selector (all 8 built-in WP schemes)
- Added: Custom primary color (admin menu + CSS variable) and accent color (buttons)

**Admin Tools** *(new tab)*
- Added: Environment indicator badge — STAGE (orange) or LIVE (red) in the admin bar
- Added: Hide update notices (core/plugin/theme) for non-admin users
- Added: Simple maintenance mode — HTTP 503 for non-logged-in frontend visitors
- Added: Admin menu cleanup — hide specific entries for editors and non-admins

### 1.1.0 — 2026-03-05

- Post Types Banner redesigned as card-based layout with live published/draft counts

### 1.0.1

- Initial release

## License

GPL-2.0-or-later
