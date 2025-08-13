<?php
/**
 * Admin Settings Page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ULP_Admin_Settings {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('لپ‌تاپ پرایسر', 'used-laptop-pricer'),
            __('لپ‌تاپ پرایسر', 'used-laptop-pricer'),
            'manage_options',
            'ulp-settings',
            array($this, 'settings_page'),
            'dashicons-calculator',
            30
        );
        
        add_submenu_page(
            'ulp-settings',
            __('تنظیمات', 'used-laptop-pricer'),
            __('تنظیمات', 'used-laptop-pricer'),
            'manage_options',
            'ulp-settings',
            array($this, 'settings_page')
        );
    }
    
    public function init_settings() {
        register_setting('ulp_settings_group', 'ulp_settings', array($this, 'sanitize_settings'));
        
        // General Settings Section
        add_settings_section(
            'ulp_general_section',
            __('تنظیمات عمومی', 'used-laptop-pricer'),
            array($this, 'general_section_callback'),
            'ulp-settings'
        );
        
        // Depreciation Settings Section
        add_settings_section(
            'ulp_depreciation_section',
            __('تنظیمات استهلاک', 'used-laptop-pricer'),
            array($this, 'depreciation_section_callback'),
            'ulp-settings'
        );
        
        // Condition Settings Section
        add_settings_section(
            'ulp_condition_section',
            __('تنظیمات وضعیت ظاهری', 'used-laptop-pricer'),
            array($this, 'condition_section_callback'),
            'ulp-settings'
        );
        
        // Currency Settings
        add_settings_field(
            'currency',
            __('واحد پول', 'used-laptop-pricer'),
            array($this, 'currency_field_callback'),
            'ulp-settings',
            'ulp_general_section'
        );
        
        add_settings_field(
            'currency_symbol',
            __('نماد پول', 'used-laptop-pricer'),
            array($this, 'currency_symbol_field_callback'),
            'ulp-settings',
            'ulp_general_section'
        );
        
        // Depreciation Fields
        add_settings_field(
            'depreciation_first_year',
            __('استهلاک سال اول (%)', 'used-laptop-pricer'),
            array($this, 'depreciation_first_year_field_callback'),
            'ulp-settings',
            'ulp_depreciation_section'
        );
        
        add_settings_field(
            'depreciation_second_year',
            __('استهلاک سال دوم (%)', 'used-laptop-pricer'),
            array($this, 'depreciation_second_year_field_callback'),
            'ulp-settings',
            'ulp_depreciation_section'
        );
        
        add_settings_field(
            'depreciation_annual',
            __('استهلاک سالانه (%)', 'used-laptop-pricer'),
            array($this, 'depreciation_annual_field_callback'),
            'ulp-settings',
            'ulp_depreciation_section'
        );
        
        // Condition Multipliers
        add_settings_field(
            'condition_multipliers',
            __('ضرایب وضعیت ظاهری', 'used-laptop-pricer'),
            array($this, 'condition_multipliers_field_callback'),
            'ulp-settings',
            'ulp_condition_section'
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ulp') === false) {
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
    }
    
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'ulp_messages',
                'ulp_message',
                __('تنظیمات با موفقیت ذخیره شد.', 'used-laptop-pricer'),
                'updated'
            );
        }
        
        ?>
        <div class="wrap ulp-admin-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors('ulp_messages'); ?>
            
            <div class="ulp-admin-content">
                <div class="ulp-admin-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('ulp_settings_group');
                        do_settings_sections('ulp-settings');
                        submit_button(__('ذخیره تنظیمات', 'used-laptop-pricer'));
                        ?>
                    </form>
                </div>
                
                <div class="ulp-admin-sidebar">
                    <div class="ulp-info-box">
                        <h3><?php _e('راهنمای استفاده', 'used-laptop-pricer'); ?></h3>
                        <p><?php _e('این افزونه برای محاسبه قیمت لپ‌تاپ‌های دست دوم طراحی شده است.', 'used-laptop-pricer'); ?></p>
                        <ul>
                            <li><?php _e('مدل‌های پایه را از بخش "مدیریت مدل‌ها" وارد کنید', 'used-laptop-pricer'); ?></li>
                            <li><?php _e('قیمت قطعات را از بخش "مدیریت قطعات" تنظیم کنید', 'used-laptop-pricer'); ?></li>
                            <li><?php _e('از شورت‌کد [used_laptop_pricer] در صفحات استفاده کنید', 'used-laptop-pricer'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="ulp-info-box">
                        <h3><?php _e('لینک‌های مفید', 'used-laptop-pricer'); ?></h3>
                        <ul>
                            <li><a href="<?php echo admin_url('admin.php?page=ulp-models'); ?>"><?php _e('مدیریت مدل‌ها', 'used-laptop-pricer'); ?></a></li>
                            <li><a href="<?php echo admin_url('admin.php?page=ulp-parts'); ?>"><?php _e('مدیریت قطعات', 'used-laptop-pricer'); ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function general_section_callback() {
        echo '<p>' . __('تنظیمات عمومی افزونه', 'used-laptop-pricer') . '</p>';
    }
    
    public function depreciation_section_callback() {
        echo '<p>' . __('تنظیمات نرخ استهلاک سالانه', 'used-laptop-pricer') . '</p>';
    }
    
    public function condition_section_callback() {
        echo '<p>' . __('تنظیمات ضرایب وضعیت ظاهری لپ‌تاپ', 'used-laptop-pricer') . '</p>';
    }
    
    public function currency_field_callback() {
        $options = get_option('ulp_settings');
        $value = isset($options['currency']) ? $options['currency'] : 'تومان';
        ?>
        <input type="text" name="ulp_settings[currency]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php _e('نام واحد پول (مثل: تومان، ریال)', 'used-laptop-pricer'); ?></p>
        <?php
    }
    
    public function currency_symbol_field_callback() {
        $options = get_option('ulp_settings');
        $value = isset($options['currency_symbol']) ? $options['currency_symbol'] : 'تومان';
        ?>
        <input type="text" name="ulp_settings[currency_symbol]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php _e('نماد واحد پول برای نمایش', 'used-laptop-pricer'); ?></p>
        <?php
    }
    
    public function depreciation_first_year_field_callback() {
        $options = get_option('ulp_settings');
        $value = isset($options['depreciation_first_year']) ? $options['depreciation_first_year'] : 30;
        ?>
        <input type="number" name="ulp_settings[depreciation_first_year]" value="<?php echo esc_attr($value); ?>" min="0" max="100" step="0.1" class="small-text" />
        <p class="description"><?php _e('درصد کاهش قیمت در سال اول', 'used-laptop-pricer'); ?></p>
        <?php
    }
    
    public function depreciation_second_year_field_callback() {
        $options = get_option('ulp_settings');
        $value = isset($options['depreciation_second_year']) ? $options['depreciation_second_year'] : 15;
        ?>
        <input type="number" name="ulp_settings[depreciation_second_year]" value="<?php echo esc_attr($value); ?>" min="0" max="100" step="0.1" class="small-text" />
        <p class="description"><?php _e('درصد کاهش قیمت در سال دوم', 'used-laptop-pricer'); ?></p>
        <?php
    }
    
    public function depreciation_annual_field_callback() {
        $options = get_option('ulp_settings');
        $value = isset($options['depreciation_annual']) ? $options['depreciation_annual'] : 10;
        ?>
        <input type="number" name="ulp_settings[depreciation_annual]" value="<?php echo esc_attr($value); ?>" min="0" max="100" step="0.1" class="small-text" />
        <p class="description"><?php _e('درصد کاهش قیمت سالانه از سال سوم به بعد', 'used-laptop-pricer'); ?></p>
        <?php
    }
    
    public function condition_multipliers_field_callback() {
        $options = get_option('ulp_settings');
        $multipliers = isset($options['condition_multipliers']) ? $options['condition_multipliers'] : array();
        
        $conditions = array(
            'new' => __('نو', 'used-laptop-pricer'),
            'excellent' => __('عالی', 'used-laptop-pricer'),
            'good' => __('خوب', 'used-laptop-pricer'),
            'fair' => __('متوسط', 'used-laptop-pricer'),
            'poor' => __('ضعیف', 'used-laptop-pricer')
        );
        
        foreach ($conditions as $key => $label) {
            $value = isset($multipliers[$key]) ? $multipliers[$key] : 1.0;
            ?>
            <div class="ulp-condition-row">
                <label for="condition_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?>:</label>
                <input type="number" 
                       name="ulp_settings[condition_multipliers][<?php echo esc_attr($key); ?>]" 
                       id="condition_<?php echo esc_attr($key); ?>"
                       value="<?php echo esc_attr($value); ?>" 
                       min="0" 
                       max="2" 
                       step="0.1" 
                       class="small-text" />
            </div>
            <?php
        }
        ?>
        <p class="description"><?php _e('ضرایب قیمت برای هر وضعیت ظاهری (1.0 = قیمت کامل)', 'used-laptop-pricer'); ?></p>
        <?php
    }
    
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Currency
        $sanitized['currency'] = sanitize_text_field($input['currency']);
        $sanitized['currency_symbol'] = sanitize_text_field($input['currency_symbol']);
        
        // Depreciation rates
        $sanitized['depreciation_first_year'] = floatval($input['depreciation_first_year']);
        $sanitized['depreciation_second_year'] = floatval($input['depreciation_second_year']);
        $sanitized['depreciation_annual'] = floatval($input['depreciation_annual']);
        
        // Condition multipliers
        if (isset($input['condition_multipliers']) && is_array($input['condition_multipliers'])) {
            foreach ($input['condition_multipliers'] as $key => $value) {
                $sanitized['condition_multipliers'][sanitize_key($key)] = floatval($value);
            }
        }
        
        return $sanitized;
    }
}