<?php
/**
 * Plugin Name: Used Laptop Pricer
 * Plugin URI: https://github.com/hoseinmos/used-laptop-pricer
 * Description: افزونه پیشرفته محاسبه قیمت لپ‌تاپ‌های دست دوم بر اساس روش Market-Based Pricing
 * Version: 1.0.0
 * Author: hoseinmos
 * Author URI: https://github.com/hoseinmos
 * Text Domain: used-laptop-pricer
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ULP_PLUGIN_VERSION', '1.0.0');
define('ULP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ULP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ULP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    $prefix = 'UsedLaptopPricer\\';
    $base_dir = ULP_PLUGIN_PATH . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Main plugin class
class UsedLaptopPricer {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    private function load_dependencies() {
        // Load Composer autoloader if exists
        if (file_exists(ULP_PLUGIN_PATH . 'vendor/autoload.php')) {
            require_once ULP_PLUGIN_PATH . 'vendor/autoload.php';
        }
        
        // Load helper functions
        require_once ULP_PLUGIN_PATH . 'includes/helpers.php';
        
        // Load admin files
        if (is_admin()) {
            require_once ULP_PLUGIN_PATH . 'admin/settings-page.php';
            require_once ULP_PLUGIN_PATH . 'admin/model-manager.php';
            require_once ULP_PLUGIN_PATH . 'admin/parts-manager.php';
            require_once ULP_PLUGIN_PATH . 'admin/excel-import.php';
            require_once ULP_PLUGIN_PATH . 'admin/excel-export.php';
        }
        
        // Load frontend files
        require_once ULP_PLUGIN_PATH . 'includes/calculate-price.php';
        require_once ULP_PLUGIN_PATH . 'templates/form.php';
        require_once ULP_PLUGIN_PATH . 'templates/result.php';
    }
    
    public function init() {
        // Initialize admin
        if (is_admin()) {
            new ULP_Admin_Settings();
            new ULP_Model_Manager();
            new ULP_Parts_Manager();
            new ULP_Excel_Import();
            new ULP_Excel_Export();
        }
        
        // Initialize frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_calculate_laptop_price', array($this, 'ajax_calculate_price'));
        add_action('wp_ajax_nopriv_calculate_laptop_price', array($this, 'ajax_calculate_price'));
        add_shortcode('used_laptop_pricer', array($this, 'shortcode_handler'));
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'used-laptop-pricer',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }
    
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Base models table
        $table_models = $wpdb->prefix . 'ulp_base_models';
        $sql_models = "CREATE TABLE $table_models (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            brand varchar(100) NOT NULL,
            model varchar(200) NOT NULL,
            release_year int(4) NOT NULL,
            base_price decimal(10,2) NOT NULL,
            base_cpu varchar(200) NOT NULL,
            base_ram varchar(50) NOT NULL,
            base_gpu varchar(200) NOT NULL,
            base_storage varchar(100) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY brand_model (brand, model)
        ) $charset_collate;";
        
        // Parts prices table
        $table_parts = $wpdb->prefix . 'ulp_parts_prices';
        $sql_parts = "CREATE TABLE $table_parts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            part_type varchar(50) NOT NULL,
            part_name varchar(200) NOT NULL,
            part_specs text,
            price decimal(10,2) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY part_type_name (part_type, part_name)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_models);
        dbDelta($sql_parts);
    }
    
    private function set_default_options() {
        $default_options = array(
            'depreciation_first_year' => 30,
            'depreciation_second_year' => 15,
            'depreciation_annual' => 10,
            'condition_multipliers' => array(
                'new' => 1.0,
                'excellent' => 0.9,
                'good' => 0.8,
                'fair' => 0.7,
                'poor' => 0.5
            ),
            'currency' => 'تومان',
            'currency_symbol' => 'تومان'
        );
        
        add_option('ulp_settings', $default_options);
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style(
            'ulp-style',
            ULP_PLUGIN_URL . 'assets/css/style.css',
            array(),
            ULP_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'ulp-form',
            ULP_PLUGIN_URL . 'assets/js/form.js',
            array('jquery'),
            ULP_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script('ulp-form', 'ulp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ulp_calculate_price'),
            'strings' => array(
                'calculating' => __('در حال محاسبه...', 'used-laptop-pricer'),
                'error' => __('خطا در محاسبه قیمت', 'used-laptop-pricer'),
                'select_brand' => __('لطفاً برند را انتخاب کنید', 'used-laptop-pricer'),
                'select_model' => __('لطفاً مدل را انتخاب کنید', 'used-laptop-pricer')
            )
        ));
    }
    
    public function ajax_calculate_price() {
        check_ajax_referer('ulp_calculate_price', 'nonce');
        
        $brand = sanitize_text_field($_POST['brand']);
        $model = sanitize_text_field($_POST['model']);
        $year = intval($_POST['year']);
        $condition = sanitize_text_field($_POST['condition']);
        $cpu = sanitize_text_field($_POST['cpu']);
        $ram = sanitize_text_field($_POST['ram']);
        $gpu = sanitize_text_field($_POST['gpu']);
        $storage = sanitize_text_field($_POST['storage']);
        
        if (empty($brand) || empty($model) || empty($year)) {
            wp_send_json_error(__('لطفاً تمام فیلدهای ضروری را پر کنید', 'used-laptop-pricer'));
        }
        
        $calculator = new ULP_Price_Calculator();
        $result = $calculator->calculate_price($brand, $model, $year, $condition, $cpu, $ram, $gpu, $storage);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'title' => __('محاسبه قیمت لپ‌تاپ دست دوم', 'used-laptop-pricer')
        ), $atts);
        
        ob_start();
        include ULP_PLUGIN_PATH . 'templates/form.php';
        return ob_get_clean();
    }
}

// Initialize the plugin
function ulp_init() {
    return UsedLaptopPricer::get_instance();
}

// Start the plugin
ulp_init();