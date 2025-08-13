<?php
/**
 * Price calculation logic for Used Laptop Pricer
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULP_Price_Calculator {
    
    /**
     * Calculate price for a used laptop
     */
    public function calculate_price($brand, $model, $year, $condition, $cpu, $ram, $gpu, $storage) {
        try {
            // Validate inputs
            $validation_result = $this->validate_inputs($brand, $model, $year, $condition, $cpu, $ram, $gpu, $storage);
            if (!$validation_result['success']) {
                return $validation_result;
            }
            
            // Get base laptop model
            $base_model = ULP_Database::get_laptop_model_by_brand_model($brand, $model, $year);
            if (!$base_model) {
                return array(
                    'success' => false,
                    'message' => __('مدل لپ‌تاپ یافت نشد', 'used-laptop-pricer')
                );
            }
            
            // Calculate base price after depreciation
            $years_since_release = ulp_calculate_years_since_release($base_model->release_year);
            $depreciation_amount = ulp_calculate_depreciation($base_model->base_price, $years_since_release);
            $depreciated_price = $base_model->base_price - $depreciation_amount;
            
            // Apply condition factor
            $condition_factor = ulp_get_condition_factor($condition);
            $condition_adjusted_price = $depreciated_price * $condition_factor;
            
            // Calculate parts adjustment
            $parts_adjustment = $this->calculate_parts_adjustment(
                $base_model, $cpu, $ram, $gpu, $storage
            );
            
            // Calculate final price
            $final_price = $condition_adjusted_price + $parts_adjustment;
            
            // Calculate price range (final price to 10% less)
            $min_price = $final_price * 0.9;
            
            // Prepare detailed calculation breakdown
            $calculation_details = array(
                'base_price' => $base_model->base_price,
                'depreciation_amount' => $depreciation_amount,
                'depreciated_price' => $depreciated_price,
                'condition_factor' => $condition_factor,
                'condition_adjusted_price' => $condition_adjusted_price,
                'parts_adjustment' => $parts_adjustment,
                'final_price' => $final_price,
                'min_price' => $min_price,
                'years_since_release' => $years_since_release
            );
            
            return array(
                'success' => true,
                'data' => array(
                    'final_price' => $final_price,
                    'min_price' => $min_price,
                    'max_price' => $final_price,
                    'calculation_details' => $calculation_details,
                    'formatted_prices' => array(
                        'final_price' => ulp_format_price($final_price),
                        'min_price' => ulp_format_price($min_price),
                        'max_price' => ulp_format_price($final_price)
                    )
                )
            );
            
        } catch (Exception $e) {
            ulp_log_error('Price calculation error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => __('خطا در محاسبه قیمت', 'used-laptop-pricer')
            );
        }
    }
    
    /**
     * Validate input parameters
     */
    private function validate_inputs($brand, $model, $year, $condition, $cpu, $ram, $gpu, $storage) {
        if (empty($brand)) {
            return array(
                'success' => false,
                'message' => __('برند لپ‌تاپ را انتخاب کنید', 'used-laptop-pricer')
            );
        }
        
        if (empty($model)) {
            return array(
                'success' => false,
                'message' => __('مدل لپ‌تاپ را انتخاب کنید', 'used-laptop-pricer')
            );
        }
        
        if (!ulp_validate_year($year)) {
            return array(
                'success' => false,
                'message' => __('سال ساخت معتبر نیست', 'used-laptop-pricer')
            );
        }
        
        if (empty($condition)) {
            return array(
                'success' => false,
                'message' => __('وضعیت ظاهری را انتخاب کنید', 'used-laptop-pricer')
            );
        }
        
        return array('success' => true);
    }
    
    /**
     * Calculate parts adjustment based on differences from base configuration
     */
    private function calculate_parts_adjustment($base_model, $cpu, $ram, $gpu, $storage) {
        $total_adjustment = 0;
        
        // CPU adjustment
        if (!empty($cpu) && $cpu !== $base_model->base_cpu) {
            $cpu_adjustment = $this->get_part_price_difference('cpu', $base_model->base_cpu, $cpu);
            $total_adjustment += $cpu_adjustment;
        }
        
        // RAM adjustment
        if (!empty($ram) && $ram !== $base_model->base_ram) {
            $ram_adjustment = $this->get_part_price_difference('ram', $base_model->base_ram, $ram);
            $total_adjustment += $ram_adjustment;
        }
        
        // GPU adjustment
        if (!empty($gpu) && $gpu !== $base_model->base_gpu) {
            $gpu_adjustment = $this->get_part_price_difference('gpu', $base_model->base_gpu, $gpu);
            $total_adjustment += $gpu_adjustment;
        }
        
        // Storage adjustment
        if (!empty($storage) && $storage !== $base_model->base_storage) {
            $storage_adjustment = $this->get_part_price_difference('storage', $base_model->base_storage, $storage);
            $total_adjustment += $storage_adjustment;
        }
        
        return $total_adjustment;
    }
    
    /**
     * Get price difference between two parts
     */
    private function get_part_price_difference($part_type, $base_part, $new_part) {
        // Get base part price
        $base_part_data = ULP_Database::get_part_price($part_type, $base_part, '');
        $base_price = $base_part_data ? $base_part_data->price : 0;
        
        // Get new part price
        $new_part_data = ULP_Database::get_part_price($part_type, $new_part, '');
        $new_price = $new_part_data ? $new_part_data->price : 0;
        
        // Return the difference (positive if upgrade, negative if downgrade)
        return $new_price - $base_price;
    }
    
    /**
     * Get detailed calculation breakdown for display
     */
    public function get_calculation_breakdown($calculation_details) {
        $breakdown = array();
        
        // Base price
        $breakdown[] = array(
            'label' => __('قیمت پایه لپ‌تاپ', 'used-laptop-pricer'),
            'value' => ulp_format_price($calculation_details['base_price']),
            'description' => sprintf(
                __('قیمت اولیه لپ‌تاپ در سال %d', 'used-laptop-pricer'),
                $calculation_details['years_since_release'] > 0 ? 
                    (date('Y') - $calculation_details['years_since_release']) : date('Y')
            )
        );
        
        // Depreciation
        if ($calculation_details['depreciation_amount'] > 0) {
            $breakdown[] = array(
                'label' => __('کاهش ارزش (استهلاک)', 'used-laptop-pricer'),
                'value' => '-' . ulp_format_price($calculation_details['depreciation_amount']),
                'description' => sprintf(
                    __('کاهش %d ساله بر اساس نرخ استهلاک', 'used-laptop-pricer'),
                    $calculation_details['years_since_release']
                )
            );
        }
        
        // Condition factor
        $condition_names = ulp_get_condition_names();
        $condition_name = isset($condition_names[$condition]) ? $condition_names[$condition] : $condition;
        $breakdown[] = array(
            'label' => __('ضریب وضعیت ظاهری', 'used-laptop-pricer'),
            'value' => sprintf('× %.1f', $calculation_details['condition_factor']),
            'description' => sprintf(
                __('وضعیت: %s', 'used-laptop-pricer'),
                $condition_name
            )
        );
        
        // Parts adjustment
        if ($calculation_details['parts_adjustment'] != 0) {
            $adjustment_sign = $calculation_details['parts_adjustment'] > 0 ? '+' : '';
            $breakdown[] = array(
                'label' => __('تعدیل قطعات', 'used-laptop-pricer'),
                'value' => $adjustment_sign . ulp_format_price($calculation_details['parts_adjustment']),
                'description' => __('تفاوت قیمت قطعات با کانفیگ پایه', 'used-laptop-pricer')
            );
        }
        
        return $breakdown;
    }
    
    /**
     * Get price range display
     */
    public function get_price_range_display($min_price, $max_price) {
        return array(
            'min_price' => ulp_format_price($min_price),
            'max_price' => ulp_format_price($max_price),
            'range_text' => sprintf(
                __('از %s تا %s', 'used-laptop-pricer'),
                ulp_format_price($min_price),
                ulp_format_price($max_price)
            )
        );
    }
}