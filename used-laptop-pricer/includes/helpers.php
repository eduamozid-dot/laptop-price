<?php
/**
 * Helper functions for Used Laptop Pricer plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get plugin settings
 */
function ulp_get_settings() {
    $defaults = array(
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
    
    $settings = get_option('ulp_settings', array());
    return wp_parse_args($settings, $defaults);
}

/**
 * Format price with currency
 */
function ulp_format_price($price, $currency = null) {
    if ($currency === null) {
        $settings = ulp_get_settings();
        $currency = $settings['currency_symbol'];
    }
    
    return number_format($price, 0, '.', ',') . ' ' . $currency;
}

/**
 * Get base models from database
 */
function ulp_get_base_models($brand = null, $model = null) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'ulp_base_models';
    $where = array();
    $values = array();
    
    if ($brand) {
        $where[] = 'brand = %s';
        $values[] = $brand;
    }
    
    if ($model) {
        $where[] = 'model = %s';
        $values[] = $model;
    }
    
    $sql = "SELECT * FROM $table";
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY brand, model';
    
    if (!empty($values)) {
        $sql = $wpdb->prepare($sql, $values);
    }
    
    return $wpdb->get_results($sql);
}

/**
 * Get brands list
 */
function ulp_get_brands() {
    global $wpdb;
    
    $table = $wpdb->prefix . 'ulp_base_models';
    $sql = "SELECT DISTINCT brand FROM $table ORDER BY brand";
    
    return $wpdb->get_col($sql);
}

/**
 * Get models by brand
 */
function ulp_get_models_by_brand($brand) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'ulp_base_models';
    $sql = $wpdb->prepare("SELECT model FROM $table WHERE brand = %s ORDER BY model", $brand);
    
    return $wpdb->get_col($sql);
}

/**
 * Get parts prices
 */
function ulp_get_parts_prices($part_type = null) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'ulp_parts_prices';
    $where = array();
    $values = array();
    
    if ($part_type) {
        $where[] = 'part_type = %s';
        $values[] = $part_type;
    }
    
    $sql = "SELECT * FROM $table";
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY part_type, part_name';
    
    if (!empty($values)) {
        $sql = $wpdb->prepare($sql, $values);
    }
    
    return $wpdb->get_results($sql);
}

/**
 * Get part price by name and type
 */
function ulp_get_part_price($part_type, $part_name) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'ulp_parts_prices';
    $sql = $wpdb->prepare(
        "SELECT price FROM $table WHERE part_type = %s AND part_name = %s",
        $part_type,
        $part_name
    );
    
    return $wpdb->get_var($sql);
}

/**
 * Calculate depreciation based on years
 */
function ulp_calculate_depreciation($base_price, $release_year, $current_year = null) {
    if ($current_year === null) {
        $current_year = date('Y');
    }
    
    $years_old = $current_year - $release_year;
    if ($years_old <= 0) {
        return $base_price;
    }
    
    $settings = ulp_get_settings();
    $price = $base_price;
    
    // First year depreciation
    if ($years_old >= 1) {
        $price = $price * (1 - $settings['depreciation_first_year'] / 100);
    }
    
    // Second year depreciation
    if ($years_old >= 2) {
        $price = $price * (1 - $settings['depreciation_second_year'] / 100);
    }
    
    // Annual depreciation for remaining years
    if ($years_old > 2) {
        $remaining_years = $years_old - 2;
        $annual_rate = $settings['depreciation_annual'] / 100;
        $price = $price * pow((1 - $annual_rate), $remaining_years);
    }
    
    return max($price, 0);
}

/**
 * Get condition multiplier
 */
function ulp_get_condition_multiplier($condition) {
    $settings = ulp_get_settings();
    $multipliers = $settings['condition_multipliers'];
    
    return isset($multipliers[$condition]) ? $multipliers[$condition] : 1.0;
}

/**
 * Sanitize and validate input
 */
function ulp_sanitize_input($input, $type = 'text') {
    switch ($type) {
        case 'int':
            return intval($input);
        case 'float':
            return floatval($input);
        case 'email':
            return sanitize_email($input);
        case 'url':
            return esc_url_raw($input);
        case 'textarea':
            return sanitize_textarea_field($input);
        default:
            return sanitize_text_field($input);
    }
}

/**
 * Validate required fields
 */
function ulp_validate_required_fields($data, $required_fields) {
    $errors = array();
    
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[] = sprintf(__('فیلد %s الزامی است', 'used-laptop-pricer'), $field);
        }
    }
    
    return $errors;
}

/**
 * Get condition options for form
 */
function ulp_get_condition_options() {
    $settings = ulp_get_settings();
    $multipliers = $settings['condition_multipliers'];
    
    $options = array();
    foreach ($multipliers as $key => $value) {
        $options[$key] = ulp_get_condition_label($key);
    }
    
    return $options;
}

/**
 * Get condition label
 */
function ulp_get_condition_label($condition) {
    $labels = array(
        'new' => __('نو', 'used-laptop-pricer'),
        'excellent' => __('عالی', 'used-laptop-pricer'),
        'good' => __('خوب', 'used-laptop-pricer'),
        'fair' => __('متوسط', 'used-laptop-pricer'),
        'poor' => __('ضعیف', 'used-laptop-pricer')
    );
    
    return isset($labels[$condition]) ? $labels[$condition] : $condition;
}

/**
 * Get part type options
 */
function ulp_get_part_type_options() {
    return array(
        'cpu' => __('پردازنده', 'used-laptop-pricer'),
        'ram' => __('رم', 'used-laptop-pricer'),
        'gpu' => __('کارت گرافیک', 'used-laptop-pricer'),
        'ssd' => __('SSD', 'used-laptop-pricer'),
        'hdd' => __('HDD', 'used-laptop-pricer')
    );
}

/**
 * Get years list for form
 */
function ulp_get_years_list($start_year = 2010) {
    $current_year = date('Y');
    $years = array();
    
    for ($year = $current_year; $year >= $start_year; $year--) {
        $years[$year] = $year;
    }
    
    return $years;
}

/**
 * Log error for debugging
 */
function ulp_log_error($message, $data = array()) {
    if (WP_DEBUG) {
        error_log('ULP Error: ' . $message . ' - ' . print_r($data, true));
    }
}

/**
 * Check if user can manage plugin
 */
function ulp_can_manage() {
    return current_user_can('manage_options');
}

/**
 * Get admin page URL
 */
function ulp_get_admin_url($page = 'settings') {
    return admin_url('admin.php?page=ulp-' . $page);
}

/**
 * Display admin notice
 */
function ulp_admin_notice($message, $type = 'success') {
    $class = 'notice notice-' . $type;
    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}

/**
 * Get file extension
 */
function ulp_get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Validate Excel file
 */
function ulp_validate_excel_file($file) {
    $allowed_extensions = array('xlsx', 'xls');
    $extension = ulp_get_file_extension($file['name']);
    
    if (!in_array($extension, $allowed_extensions)) {
        return new WP_Error('invalid_file', __('فایل باید از نوع Excel باشد', 'used-laptop-pricer'));
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        return new WP_Error('file_too_large', __('حجم فایل نباید بیشتر از 5 مگابایت باشد', 'used-laptop-pricer'));
    }
    
    return true;
}