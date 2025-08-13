<?php
/**
 * Frontend initialization for Used Laptop Pricer
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULP_Frontend_Init {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_ulp_get_models', array($this, 'ajax_get_models'));
        add_action('wp_ajax_nopriv_ulp_get_models', array($this, 'ajax_get_models'));
        add_action('wp_ajax_ulp_get_parts', array($this, 'ajax_get_parts'));
        add_action('wp_ajax_nopriv_ulp_get_parts', array($this, 'ajax_get_parts'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        ulp_enqueue_frontend_assets();
    }
    
    /**
     * AJAX handler for getting models by brand
     */
    public function ajax_get_models() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ulp_calculate_price')) {
            wp_die(__('خطای امنیتی', 'used-laptop-pricer'));
        }
        
        $brand = sanitize_text_field($_POST['brand']);
        
        if (empty($brand)) {
            wp_send_json_error(__('برند انتخاب نشده', 'used-laptop-pricer'));
        }
        
        $models = ULP_Database::get_models_by_brand($brand);
        
        if (empty($models)) {
            wp_send_json_error(__('مدلی برای این برند یافت نشد', 'used-laptop-pricer'));
        }
        
        wp_send_json_success($models);
    }
    
    /**
     * AJAX handler for getting parts by type
     */
    public function ajax_get_parts() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ulp_calculate_price')) {
            wp_die(__('خطای امنیتی', 'used-laptop-pricer'));
        }
        
        $part_type = sanitize_text_field($_POST['part_type']);
        
        if (empty($part_type)) {
            wp_send_json_error(__('نوع قطعه انتخاب نشده', 'used-laptop-pricer'));
        }
        
        $parts = ULP_Database::get_parts_prices($part_type);
        
        if (empty($parts)) {
            wp_send_json_error(__('قطعه‌ای برای این نوع یافت نشد', 'used-laptop-pricer'));
        }
        
        wp_send_json_success($parts);
    }
}