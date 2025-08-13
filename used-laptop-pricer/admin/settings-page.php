<?php
/**
 * Settings page for Used Laptop Pricer
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULP_Settings_Page {
    
    public function __construct() {
        // Constructor
    }
    
    /**
     * Handle form submissions
     */
    public function handle_actions() {
        if (isset($_POST['ulp_save_settings']) && wp_verify_nonce($_POST['ulp_settings_nonce'], 'ulp_save_settings')) {
            $this->save_settings();
        }
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        $settings = array();
        
        // Depreciation rates
        $settings['depreciation_first_year'] = intval($_POST['depreciation_first_year']);
        $settings['depreciation_second_year'] = intval($_POST['depreciation_second_year']);
        $settings['depreciation_other_years'] = intval($_POST['depreciation_other_years']);
        
        // Condition factors
        $settings['condition_factors'] = array(
            'new' => floatval($_POST['condition_new']),
            'excellent' => floatval($_POST['condition_excellent']),
            'good' => floatval($_POST['condition_good']),
            'fair' => floatval($_POST['condition_fair']),
            'poor' => floatval($_POST['condition_poor'])
        );
        
        // Currency settings
        $settings['currency'] = sanitize_text_field($_POST['currency']);
        $settings['currency_symbol'] = sanitize_text_field($_POST['currency_symbol']);
        
        // Save settings
        update_option('ulp_settings', $settings);
        
        // Show success message
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 __('تنظیمات با موفقیت ذخیره شد.', 'used-laptop-pricer') . 
                 '</p></div>';
        });
    }
    
    /**
     * Render settings page
     */
    public function render_page() {
        $settings = ulp_get_settings();
        ?>
        <div class="wrap">
            <h1><?php _e('تنظیمات لپ‌تاپ پرایسر', 'used-laptop-pricer'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('ulp_save_settings', 'ulp_settings_nonce'); ?>
                
                <div class="ulp-settings-container">
                    <!-- Depreciation Settings -->
                    <div class="ulp-settings-section">
                        <h2><?php _e('تنظیمات استهلاک', 'used-laptop-pricer'); ?></h2>
                        <p><?php _e('نرخ کاهش ارزش سالانه لپ‌تاپ‌ها را تنظیم کنید:', 'used-laptop-pricer'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="depreciation_first_year"><?php _e('سال اول (%)', 'used-laptop-pricer'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="depreciation_first_year" 
                                           name="depreciation_first_year" 
                                           value="<?php echo esc_attr($settings['depreciation_first_year']); ?>" 
                                           min="0" 
                                           max="100" 
                                           step="1" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('نرخ کاهش ارزش در سال اول (پیش‌فرض: 30%)', 'used-laptop-pricer'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="depreciation_second_year"><?php _e('سال دوم (%)', 'used-laptop-pricer'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="depreciation_second_year" 
                                           name="depreciation_second_year" 
                                           value="<?php echo esc_attr($settings['depreciation_second_year']); ?>" 
                                           min="0" 
                                           max="100" 
                                           step="1" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('نرخ کاهش ارزش در سال دوم (پیش‌فرض: 15%)', 'used-laptop-pricer'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="depreciation_other_years"><?php _e('سال‌های بعدی (%)', 'used-laptop-pricer'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="depreciation_other_years" 
                                           name="depreciation_other_years" 
                                           value="<?php echo esc_attr($settings['depreciation_other_years']); ?>" 
                                           min="0" 
                                           max="100" 
                                           step="1" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('نرخ کاهش ارزش سالانه از سال سوم به بعد (پیش‌فرض: 10%)', 'used-laptop-pricer'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Condition Factors -->
                    <div class="ulp-settings-section">
                        <h2><?php _e('ضرایب وضعیت ظاهری', 'used-laptop-pricer'); ?></h2>
                        <p><?php _e('ضریب قیمت برای هر وضعیت ظاهری را تنظیم کنید:', 'used-laptop-pricer'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="condition_new"><?php _e('نو', 'used-laptop-pricer'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="condition_new" 
                                           name="condition_new" 
                                           value="<?php echo esc_attr($settings['condition_factors']['new']); ?>" 
                                           min="0" 
                                           max="2" 
                                           step="0.1" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('ضریب برای لپ‌تاپ‌های نو (پیش‌فرض: 1.0)', 'used-laptop-pricer'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="condition_excellent"><?php _e('عالی', 'used-laptop-pricer'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="condition_excellent" 
                                           name="condition_excellent" 
                                           value="<?php echo esc_attr($settings['condition_factors']['excellent']); ?>" 
                                           min="0" 
                                           max="2" 
                                           step="0.1" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('ضریب برای لپ‌تاپ‌های در وضعیت عالی (پیش‌فرض: 0.9)', 'used-laptop-pricer'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="condition_good"><?php _e('خوب', 'used-laptop-pricer'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="condition_good" 
                                           name="condition_good" 
                                           value="<?php echo esc_attr($settings['condition_factors']['good']); ?>" 
                                           min="0" 
                                           max="2" 
                                           step="0.1" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('ضریب برای لپ‌تاپ‌های در وضعیت خوب (پیش‌فرض: 0.8)', 'used-laptop-pricer'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="condition_fair"><?php _e('متوسط', 'used-laptop-pricer'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="condition_fair" 
                                           name="condition_fair" 
                                           value="<?php echo esc_attr($settings['condition_factors']['fair']); ?>" 
                                           min="0" 
                                           max="2" 
                                           step="0.1" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('ضریب برای لپ‌تاپ‌های در وضعیت متوسط (پیش‌فرض: 0.7)', 'used-laptop-pricer'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="condition_poor"><?php _e('ضعیف', 'used-laptop-pricer'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="condition_poor" 
                                           name="condition_poor" 
                                           value="<?php echo esc_attr($settings['condition_factors']['poor']); ?>" 
                                           min="0" 
                                           max="2" 
                                           step="0.1" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('ضریب برای لپ‌تاپ‌های در وضعیت ضعیف (پیش‌فرض: 0.5)', 'used-laptop-pricer'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Currency Settings -->
                    <div class="ulp-settings-section">
                        <h2><?php _e('تنظیمات واحد پول', 'used-laptop-pricer'); ?></h2>
                        <p><?php _e('واحد پول و نماد آن را تنظیم کنید:', 'used-laptop-pricer'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="currency"><?php _e('واحد پول', 'used-laptop-pricer'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="currency" 
                                           name="currency" 
                                           value="<?php echo esc_attr($settings['currency']); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('نام واحد پول (مثال: تومان)', 'used-laptop-pricer'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="currency_symbol"><?php _e('نماد واحد پول', 'used-laptop-pricer'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="currency_symbol" 
                                           name="currency_symbol" 
                                           value="<?php echo esc_attr($settings['currency_symbol']); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('نماد واحد پول (مثال: تومان)', 'used-laptop-pricer'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" 
                           name="ulp_save_settings" 
                           class="button button-primary" 
                           value="<?php _e('ذخیره تنظیمات', 'used-laptop-pricer'); ?>" />
                </p>
            </form>
        </div>
        <?php
    }
}