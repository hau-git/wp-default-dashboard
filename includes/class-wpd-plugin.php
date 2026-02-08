<?php

defined('ABSPATH') || exit;

class WPD_Plugin {

    protected static ?WPD_Plugin $instance = null;

    protected ?WPD_Settings  $settings  = null;
    protected ?WPD_Admin     $admin     = null;
    protected ?WPD_Dashboard $dashboard = null;
    protected ?WPD_Branding  $branding  = null;
    protected ?WPD_Hardening $hardening = null;
    protected ?WPD_Updater   $updater   = null;

    public static function get_instance(): static {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    protected function __construct() {}

    public function init(): void {
        $this->settings  = new WPD_Settings($this);
        $this->admin     = new WPD_Admin($this);
        $this->dashboard = new WPD_Dashboard($this);
        $this->branding  = new WPD_Branding($this);
        $this->hardening = new WPD_Hardening($this);
        $this->updater   = new WPD_Updater($this);

        $this->settings->register();
        $this->admin->register();
        $this->dashboard->register();
        $this->branding->register();
        $this->hardening->register();
        $this->updater->register();

        do_action('wpd_init');
    }

    public function get_options(): array {
        return wpd_get_options();
    }

    public function get_option(string $key, mixed $fallback = null): mixed {
        return wpd_get_option($key, $fallback);
    }

    public function settings(): ?WPD_Settings {
        return $this->settings;
    }

    public function admin(): ?WPD_Admin {
        return $this->admin;
    }

    public function dashboard(): ?WPD_Dashboard {
        return $this->dashboard;
    }

    public function branding(): ?WPD_Branding {
        return $this->branding;
    }

    public function hardening(): ?WPD_Hardening {
        return $this->hardening;
    }

    public function updater(): ?WPD_Updater {
        return $this->updater;
    }
}
