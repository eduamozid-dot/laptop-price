<?php
/**
 * Price Calculator Class
 * Implements Market-Based Pricing algorithm for used laptops
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ULP_Price_Calculator {
    
    public function __construct() {
        // Constructor
    }
    
    /**
     * Main calculation method
     */
    public function calculate_price($brand, $model, $year, $condition, $cpu, $ram, $gpu, $storage) {
        try {
            // Get base model data
            $base_model = $this->get_base_model($brand, $model);
            if (!$base_model) {
                return new WP_Error('model_not_found', __('مدل لپ‌تاپ یافت نشد', 'used-laptop-pricer'));
            }
            
            // Calculate base price with depreciation
            $depreciated_price = ulp_calculate_depreciation($base_model->base_price, $base_model->release_year, $year);
            
            // Apply condition multiplier
            $condition_multiplier = ulp_get_condition_multiplier($condition);
            $condition_adjusted_price = $depreciated_price * $condition_multiplier;
            
            // Calculate parts adjustment
            $parts_adjustment = $this->calculate_parts_adjustment($base_model, $cpu, $ram, $gpu, $storage);
            
            // Final price calculation
            $final_price = $condition_adjusted_price + $parts_adjustment;
            
            // Calculate price range (min price = 90% of final price)
            $min_price = $final_price * 0.9;
            
            // Prepare result
            $result = array(
                'final_price' => $final_price,
                'min_price' => $min_price,
                'max_price' => $final_price,
                'price_range' => array(
                    'min' => $min_price,
                    'max' => $final_price
                ),
                'calculation_details' => array(
                    'base_price' => $base_model->base_price,
                    'depreciated_price' => $depreciated_price,
                    'condition_multiplier' => $condition_multiplier,
                    'condition_adjusted_price' => $condition_adjusted_price,
                    'parts_adjustment' => $parts_adjustment,
                    'depreciation_percentage' => $this->calculate_depreciation_percentage($base_model->base_price, $depreciated_price)
                ),
                'formatted_prices' => array(
                    'final' => ulp_format_price($final_price),
                    'min' => ulp_format_price($min_price),
                    'max' => ulp_format_price($final_price),
                    'range' => ulp_format_price($min_price) . ' - ' . ulp_format_price($final_price)
                )
            );
            
            return $result;
            
        } catch (Exception $e) {
            ulp_log_error('Price calculation error: ' . $e->getMessage());
            return new WP_Error('calculation_error', __('خطا در محاسبه قیمت', 'used-laptop-pricer'));
        }
    }
    
    /**
     * Get base model from database
     */
    private function get_base_model($brand, $model) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_base_models';
        $sql = $wpdb->prepare(
            "SELECT * FROM $table WHERE brand = %s AND model = %s",
            $brand,
            $model
        );
        
        return $wpdb->get_row($sql);
    }
    
    /**
     * Calculate parts adjustment based on differences from base configuration
     */
    private function calculate_parts_adjustment($base_model, $cpu, $ram, $gpu, $storage) {
        $adjustment = 0;
        
        // CPU adjustment
        if ($cpu && $cpu !== $base_model->base_cpu) {
            $cpu_adjustment = $this->calculate_part_adjustment('cpu', $base_model->base_cpu, $cpu);
            $adjustment += $cpu_adjustment;
        }
        
        // RAM adjustment
        if ($ram && $ram !== $base_model->base_ram) {
            $ram_adjustment = $this->calculate_part_adjustment('ram', $base_model->base_ram, $ram);
            $adjustment += $ram_adjustment;
        }
        
        // GPU adjustment
        if ($gpu && $gpu !== $base_model->base_gpu) {
            $gpu_adjustment = $this->calculate_part_adjustment('gpu', $base_model->base_gpu, $gpu);
            $adjustment += $gpu_adjustment;
        }
        
        // Storage adjustment
        if ($storage && $storage !== $base_model->base_storage) {
            $storage_adjustment = $this->calculate_part_adjustment('storage', $base_model->base_storage, $storage);
            $adjustment += $storage_adjustment;
        }
        
        return $adjustment;
    }
    
    /**
     * Calculate adjustment for a specific part
     */
    private function calculate_part_adjustment($part_type, $base_part, $current_part) {
        $base_price = ulp_get_part_price($part_type, $base_part);
        $current_price = ulp_get_part_price($part_type, $current_part);
        
        if ($base_price === null || $current_price === null) {
            return 0; // If we can't find prices, no adjustment
        }
        
        return $current_price - $base_price;
    }
    
    /**
     * Calculate depreciation percentage
     */
    private function calculate_depreciation_percentage($original_price, $depreciated_price) {
        if ($original_price <= 0) {
            return 0;
        }
        
        $depreciation_amount = $original_price - $depreciated_price;
        return round(($depreciation_amount / $original_price) * 100, 2);
    }
    
    /**
     * Validate input parameters
     */
    public function validate_input($brand, $model, $year, $condition, $cpu, $ram, $gpu, $storage) {
        $errors = array();
        
        // Required fields
        if (empty($brand)) {
            $errors[] = __('برند الزامی است', 'used-laptop-pricer');
        }
        
        if (empty($model)) {
            $errors[] = __('مدل الزامی است', 'used-laptop-pricer');
        }
        
        if (empty($year)) {
            $errors[] = __('سال ساخت الزامی است', 'used-laptop-pricer');
        }
        
        if (empty($condition)) {
            $errors[] = __('وضعیت ظاهری الزامی است', 'used-laptop-pricer');
        }
        
        // Validate year
        if ($year && ($year < 2010 || $year > date('Y'))) {
            $errors[] = __('سال ساخت باید بین ۲۰۱۰ و سال جاری باشد', 'used-laptop-pricer');
        }
        
        // Validate condition
        $valid_conditions = array_keys(ulp_get_condition_options());
        if ($condition && !in_array($condition, $valid_conditions)) {
            $errors[] = __('وضعیت ظاهری نامعتبر است', 'used-laptop-pricer');
        }
        
        return $errors;
    }
    
    /**
     * Get calculation summary for display
     */
    public function get_calculation_summary($result) {
        if (is_wp_error($result)) {
            return array();
        }
        
        $details = $result['calculation_details'];
        
        return array(
            'base_price' => array(
                'label' => __('قیمت پایه', 'used-laptop-pricer'),
                'value' => ulp_format_price($details['base_price']),
                'description' => __('قیمت اولیه لپ‌تاپ در زمان عرضه', 'used-laptop-pricer')
            ),
            'depreciation' => array(
                'label' => __('استهلاک', 'used-laptop-pricer'),
                'value' => ulp_format_price($details['base_price'] - $details['depreciated_price']),
                'description' => sprintf(__('کاهش قیمت بر اساس %s%% استهلاک', 'used-laptop-pricer'), $details['depreciation_percentage'])
            ),
            'condition_adjustment' => array(
                'label' => __('تعدیل وضعیت', 'used-laptop-pricer'),
                'value' => ulp_format_price($details['condition_adjusted_price'] - $details['depreciated_price']),
                'description' => sprintf(__('تعدیل بر اساس ضریب وضعیت (%s)', 'used-laptop-pricer'), $details['condition_multiplier'])
            ),
            'parts_adjustment' => array(
                'label' => __('تعدیل قطعات', 'used-laptop-pricer'),
                'value' => ulp_format_price($details['parts_adjustment']),
                'description' => __('تفاوت قیمت قطعات با کانفیگ پایه', 'used-laptop-pricer')
            ),
            'final_price' => array(
                'label' => __('قیمت نهایی', 'used-laptop-pricer'),
                'value' => ulp_format_price($result['final_price']),
                'description' => __('قیمت محاسبه شده نهایی', 'used-laptop-pricer')
            )
        );
    }
    
    /**
     * Get price range display
     */
    public function get_price_range_display($result) {
        if (is_wp_error($result)) {
            return '';
        }
        
        $min_price = $result['price_range']['min'];
        $max_price = $result['price_range']['max'];
        
        if ($min_price == $max_price) {
            return ulp_format_price($max_price);
        }
        
        return ulp_format_price($min_price) . ' - ' . ulp_format_price($max_price);
    }
}