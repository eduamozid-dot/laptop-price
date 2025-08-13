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

/**
 * Main plugin class
 */
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
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load required files
        $this->load_dependencies();
        
        // Initialize admin
        if (is_admin()) {
            require_once ULP_PLUGIN_PATH . 'admin/admin-init.php';
            new ULP_Admin_Init();
        }
        
        // Initialize frontend
        require_once ULP_PLUGIN_PATH . 'includes/frontend-init.php';
        new ULP_Frontend_Init();
        
        // Initialize AJAX handlers
        add_action('wp_ajax_ulp_calculate_price', array($this, 'ajax_calculate_price'));
        add_action('wp_ajax_nopriv_ulp_calculate_price', array($this, 'ajax_calculate_price'));
        
        // Add shortcode
        add_shortcode('used_laptop_pricer', array($this, 'shortcode_handler'));
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'used-laptop-pricer',
            false,
            dirname(ULP_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    private function load_dependencies() {
        // Load helper functions
        require_once ULP_PLUGIN_PATH . 'includes/helpers.php';
        
        // Load calculation logic
        require_once ULP_PLUGIN_PATH . 'includes/calculate-price.php';
        
        // Load database functions
        require_once ULP_PLUGIN_PATH . 'includes/database.php';
    }
    
    public function activate() {
        // Create database tables
        ULP_Database::create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function set_default_options() {
        $default_options = array(
            'depreciation_first_year' => 30,
            'depreciation_second_year' => 15,
            'depreciation_other_years' => 10,
            'condition_factors' => array(
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
    
    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'title' => __('محاسبه قیمت لپ‌تاپ دست دوم', 'used-laptop-pricer'),
            'show_details' => 'true'
        ), $atts);
        
        ob_start();
        include ULP_PLUGIN_PATH . 'templates/form.php';
        return ob_get_clean();
    }
    
    public function ajax_calculate_price() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ulp_calculate_price')) {
            wp_die(__('خطای امنیتی', 'used-laptop-pricer'));
        }
        
        // Sanitize input
        $brand = sanitize_text_field($_POST['brand']);
        $model = sanitize_text_field($_POST['model']);
        $year = intval($_POST['year']);
        $condition = sanitize_text_field($_POST['condition']);
        $cpu = sanitize_text_field($_POST['cpu']);
        $ram = sanitize_text_field($_POST['ram']);
        $gpu = sanitize_text_field($_POST['gpu']);
        $storage = sanitize_text_field($_POST['storage']);
        
        // Calculate price
        $calculator = new ULP_Price_Calculator();
        $result = $calculator->calculate_price($brand, $model, $year, $condition, $cpu, $ram, $gpu, $storage);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
}

// Initialize the plugin
function ulp_init() {
    return UsedLaptopPricer::get_instance();
}

// Start the plugin
ulp_init();