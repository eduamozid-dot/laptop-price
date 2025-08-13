<?php
/**
 * Admin initialization for Used Laptop Pricer
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULP_Admin_Init {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'init_admin_pages'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('لپ‌تاپ پرایسر', 'used-laptop-pricer'),
            __('لپ‌تاپ پرایسر', 'used-laptop-pricer'),
            'manage_options',
            'used-laptop-pricer',
            array($this, 'render_dashboard_page'),
            'dashicons-calculator',
            30
        );
        
        // Submenus
        add_submenu_page(
            'used-laptop-pricer',
            __('داشبورد', 'used-laptop-pricer'),
            __('داشبورد', 'used-laptop-pricer'),
            'manage_options',
            'used-laptop-pricer',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'used-laptop-pricer',
            __('مدیریت مدل‌های پایه', 'used-laptop-pricer'),
            __('مدل‌های پایه', 'used-laptop-pricer'),
            'manage_options',
            'used-laptop-pricer-models',
            array($this, 'render_models_page')
        );
        
        add_submenu_page(
            'used-laptop-pricer',
            __('مدیریت قطعات', 'used-laptop-pricer'),
            __('قطعات', 'used-laptop-pricer'),
            'manage_options',
            'used-laptop-pricer-parts',
            array($this, 'render_parts_page')
        );
        
        add_submenu_page(
            'used-laptop-pricer',
            __('تنظیمات', 'used-laptop-pricer'),
            __('تنظیمات', 'used-laptop-pricer'),
            'manage_options',
            'used-laptop-pricer-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        ulp_enqueue_admin_assets($hook);
    }
    
    /**
     * Initialize admin pages
     */
    public function init_admin_pages() {
        // Load admin page classes
        require_once ULP_PLUGIN_PATH . 'admin/settings-page.php';
        require_once ULP_PLUGIN_PATH . 'admin/model-manager.php';
        require_once ULP_PLUGIN_PATH . 'admin/parts-manager.php';
        require_once ULP_PLUGIN_PATH . 'admin/excel-import.php';
        require_once ULP_PLUGIN_PATH . 'admin/excel-export.php';
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        $models_count = count(ULP_Database::get_laptop_models());
        $parts_count = count(ULP_Database::get_parts_prices());
        $brands_count = count(ULP_Database::get_brands());
        
        include ULP_PLUGIN_PATH . 'admin/views/dashboard.php';
    }
    
    /**
     * Render models page
     */
    public function render_models_page() {
        $model_manager = new ULP_Model_Manager();
        $model_manager->handle_actions();
        $model_manager->render_page();
    }
    
    /**
     * Render parts page
     */
    public function render_parts_page() {
        $parts_manager = new ULP_Parts_Manager();
        $parts_manager->handle_actions();
        $parts_manager->render_page();
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        $settings_page = new ULP_Settings_Page();
        $settings_page->handle_actions();
        $settings_page->render_page();
    }
}