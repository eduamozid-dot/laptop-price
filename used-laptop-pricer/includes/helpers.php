<?php
/**
 * Helper functions for Used Laptop Pricer
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Format price with currency
 */
function ulp_format_price($price, $currency = null) {
    if ($currency === null) {
        $settings = get_option('ulp_settings', array());
        $currency = isset($settings['currency_symbol']) ? $settings['currency_symbol'] : 'تومان';
    }
    
    return number_format($price, 0, '.', ',') . ' ' . $currency;
}

/**
 * Get plugin settings
 */
function ulp_get_settings() {
    $default_settings = array(
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
    
    $settings = get_option('ulp_settings', array());
    return wp_parse_args($settings, $default_settings);
}

/**
 * Get condition factors
 */
function ulp_get_condition_factors() {
    $settings = ulp_get_settings();
    return $settings['condition_factors'];
}

/**
 * Get condition factor by key
 */
function ulp_get_condition_factor($condition) {
    $factors = ulp_get_condition_factors();
    return isset($factors[$condition]) ? $factors[$condition] : 0.8;
}

/**
 * Get condition display names
 */
function ulp_get_condition_names() {
    return array(
        'new' => __('نو', 'used-laptop-pricer'),
        'excellent' => __('عالی', 'used-laptop-pricer'),
        'good' => __('خوب', 'used-laptop-pricer'),
        'fair' => __('متوسط', 'used-laptop-pricer'),
        'poor' => __('ضعیف', 'used-laptop-pricer')
    );
}

/**
 * Get part type display names
 */
function ulp_get_part_type_names() {
    return array(
        'cpu' => __('پردازنده', 'used-laptop-pricer'),
        'ram' => __('رم', 'used-laptop-pricer'),
        'gpu' => __('کارت گرافیک', 'used-laptop-pricer'),
        'ssd' => __('SSD', 'used-laptop-pricer'),
        'hdd' => __('هارد دیسک', 'used-laptop-pricer')
    );
}

/**
 * Validate year
 */
function ulp_validate_year($year) {
    $current_year = date('Y');
    $min_year = 1990;
    
    return intval($year) >= $min_year && intval($year) <= $current_year;
}

/**
 * Calculate years since release
 */
function ulp_calculate_years_since_release($release_year) {
    $current_year = date('Y');
    return max(0, $current_year - $release_year);
}

/**
 * Calculate depreciation
 */
function ulp_calculate_depreciation($base_price, $years_since_release) {
    $settings = ulp_get_settings();
    
    if ($years_since_release == 0) {
        return 0;
    }
    
    $remaining_price = $base_price;
    
    for ($year = 1; $year <= $years_since_release; $year++) {
        if ($year == 1) {
            $depreciation_rate = $settings['depreciation_first_year'] / 100;
        } elseif ($year == 2) {
            $depreciation_rate = $settings['depreciation_second_year'] / 100;
        } else {
            $depreciation_rate = $settings['depreciation_other_years'] / 100;
        }
        
        $depreciation_amount = $remaining_price * $depreciation_rate;
        $remaining_price -= $depreciation_amount;
    }
    
    return $base_price - $remaining_price;
}

/**
 * Get current year
 */
function ulp_get_current_year() {
    return date('Y');
}

/**
 * Get years range for select
 */
function ulp_get_years_range($start_year = null, $end_year = null) {
    if ($start_year === null) {
        $start_year = 1990;
    }
    if ($end_year === null) {
        $end_year = ulp_get_current_year();
    }
    
    $years = array();
    for ($year = $end_year; $year >= $start_year; $year--) {
        $years[$year] = $year;
    }
    
    return $years;
}

/**
 * Sanitize and validate form data
 */
function ulp_sanitize_form_data($data) {
    $sanitized = array();
    
    $sanitized['brand'] = sanitize_text_field($data['brand']);
    $sanitized['model'] = sanitize_text_field($data['model']);
    $sanitized['year'] = intval($data['year']);
    $sanitized['condition'] = sanitize_text_field($data['condition']);
    $sanitized['cpu'] = sanitize_text_field($data['cpu']);
    $sanitized['ram'] = sanitize_text_field($data['ram']);
    $sanitized['gpu'] = sanitize_text_field($data['gpu']);
    $sanitized['storage'] = sanitize_text_field($data['storage']);
    
    return $sanitized;
}

/**
 * Validate form data
 */
function ulp_validate_form_data($data) {
    $errors = array();
    
    if (empty($data['brand'])) {
        $errors[] = __('برند لپ‌تاپ را انتخاب کنید', 'used-laptop-pricer');
    }
    
    if (empty($data['model'])) {
        $errors[] = __('مدل لپ‌تاپ را انتخاب کنید', 'used-laptop-pricer');
    }
    
    if (!ulp_validate_year($data['year'])) {
        $errors[] = __('سال ساخت معتبر نیست', 'used-laptop-pricer');
    }
    
    if (empty($data['condition'])) {
        $errors[] = __('وضعیت ظاهری را انتخاب کنید', 'used-laptop-pricer');
    }
    
    return $errors;
}

/**
 * Get AJAX URL
 */
function ulp_get_ajax_url() {
    return admin_url('admin-ajax.php');
}

/**
 * Get nonce for AJAX requests
 */
function ulp_get_nonce() {
    return wp_create_nonce('ulp_calculate_price');
}

/**
 * Enqueue frontend scripts and styles
 */
function ulp_enqueue_frontend_assets() {
    wp_enqueue_style(
        'ulp-frontend-style',
        ULP_PLUGIN_URL . 'assets/css/style.css',
        array(),
        ULP_PLUGIN_VERSION
    );
    
    wp_enqueue_script(
        'ulp-frontend-script',
        ULP_PLUGIN_URL . 'assets/js/form.js',
        array('jquery'),
        ULP_PLUGIN_VERSION,
        true
    );
    
    wp_localize_script('ulp-frontend-script', 'ulp_ajax', array(
        'ajax_url' => ulp_get_ajax_url(),
        'nonce' => ulp_get_nonce(),
        'strings' => array(
            'calculating' => __('در حال محاسبه...', 'used-laptop-pricer'),
            'error' => __('خطا در محاسبه', 'used-laptop-pricer'),
            'select_brand' => __('ابتدا برند را انتخاب کنید', 'used-laptop-pricer'),
            'select_model' => __('ابتدا مدل را انتخاب کنید', 'used-laptop-pricer')
        )
    ));
}

/**
 * Enqueue admin scripts and styles
 */
function ulp_enqueue_admin_assets($hook) {
    if (strpos($hook, 'used-laptop-pricer') === false) {
        return;
    }
    
    wp_enqueue_style(
        'ulp-admin-style',
        ULP_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        ULP_PLUGIN_VERSION
    );
    
    wp_enqueue_script(
        'ulp-admin-script',
        ULP_PLUGIN_URL . 'assets/js/admin.js',
        array('jquery'),
        ULP_PLUGIN_VERSION,
        true
    );
    
    wp_localize_script('ulp-admin-script', 'ulp_admin', array(
        'ajax_url' => ulp_get_ajax_url(),
        'nonce' => wp_create_nonce('ulp_admin_nonce'),
        'strings' => array(
            'confirm_delete' => __('آیا مطمئن هستید که می‌خواهید این مورد را حذف کنید؟', 'used-laptop-pricer'),
            'saving' => __('در حال ذخیره...', 'used-laptop-pricer'),
            'saved' => __('ذخیره شد', 'used-laptop-pricer'),
            'error' => __('خطا در ذخیره', 'used-laptop-pricer')
        )
    ));
}

/**
 * Get file extension
 */
function ulp_get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Check if file is Excel
 */
function ulp_is_excel_file($filename) {
    $allowed_extensions = array('xlsx', 'xls');
    $extension = ulp_get_file_extension($filename);
    return in_array($extension, $allowed_extensions);
}

/**
 * Create download link for file
 */
function ulp_create_download_link($file_path, $filename) {
    $upload_dir = wp_upload_dir();
    $file_url = $upload_dir['baseurl'] . '/ulp-exports/' . $filename;
    
    return sprintf(
        '<a href="%s" download="%s" class="button button-secondary">%s</a>',
        esc_url($file_url),
        esc_attr($filename),
        __('دانلود فایل', 'used-laptop-pricer')
    );
}

/**
 * Ensure upload directory exists
 */
function ulp_ensure_upload_directory() {
    $upload_dir = wp_upload_dir();
    $ulp_dir = $upload_dir['basedir'] . '/ulp-exports';
    
    if (!file_exists($ulp_dir)) {
        wp_mkdir_p($ulp_dir);
    }
    
    return $ulp_dir;
}

/**
 * Log error for debugging
 */
function ulp_log_error($message, $data = null) {
    if (WP_DEBUG) {
        error_log('ULP Error: ' . $message);
        if ($data) {
            error_log('ULP Data: ' . print_r($data, true));
        }
    }
}