<?php
/**
 * Frontend Form Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$brands = ULP_Database::get_brands();
$condition_names = ulp_get_condition_names();
$years_range = ulp_get_years_range();
?>

<div class="ulp-calculator-container" dir="rtl">
    <div class="ulp-calculator-header">
        <h2><?php echo esc_html($atts['title']); ?></h2>
        <p><?php _e('اطلاعات لپ‌تاپ خود را وارد کنید تا قیمت تقریبی آن محاسبه شود', 'used-laptop-pricer'); ?></p>
    </div>
    
    <form id="ulp-calculator-form" class="ulp-calculator-form">
        <div class="ulp-form-row">
            <div class="ulp-form-group">
                <label for="ulp-brand"><?php _e('برند لپ‌تاپ', 'used-laptop-pricer'); ?> *</label>
                <select id="ulp-brand" name="brand" required>
                    <option value=""><?php _e('انتخاب کنید', 'used-laptop-pricer'); ?></option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?php echo esc_attr($brand); ?>"><?php echo esc_html($brand); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="ulp-form-group">
                <label for="ulp-model"><?php _e('مدل لپ‌تاپ', 'used-laptop-pricer'); ?> *</label>
                <select id="ulp-model" name="model" required disabled>
                    <option value=""><?php _e('ابتدا برند را انتخاب کنید', 'used-laptop-pricer'); ?></option>
                </select>
            </div>
        </div>
        
        <div class="ulp-form-row">
            <div class="ulp-form-group">
                <label for="ulp-year"><?php _e('سال ساخت', 'used-laptop-pricer'); ?> *</label>
                <select id="ulp-year" name="year" required>
                    <option value=""><?php _e('انتخاب کنید', 'used-laptop-pricer'); ?></option>
                    <?php foreach ($years_range as $year): ?>
                        <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="ulp-form-group">
                <label for="ulp-condition"><?php _e('وضعیت ظاهری', 'used-laptop-pricer'); ?> *</label>
                <select id="ulp-condition" name="condition" required>
                    <option value=""><?php _e('انتخاب کنید', 'used-laptop-pricer'); ?></option>
                    <?php foreach ($condition_names as $condition_key => $condition_name): ?>
                        <option value="<?php echo esc_attr($condition_key); ?>"><?php echo esc_html($condition_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="ulp-form-row">
            <div class="ulp-form-group">
                <label for="ulp-cpu"><?php _e('پردازنده (CPU)', 'used-laptop-pricer'); ?></label>
                <select id="ulp-cpu" name="cpu">
                    <option value=""><?php _e('انتخاب کنید', 'used-laptop-pricer'); ?></option>
                </select>
            </div>
            
            <div class="ulp-form-group">
                <label for="ulp-ram"><?php _e('رم (RAM)', 'used-laptop-pricer'); ?></label>
                <select id="ulp-ram" name="ram">
                    <option value=""><?php _e('انتخاب کنید', 'used-laptop-pricer'); ?></option>
                </select>
            </div>
        </div>
        
        <div class="ulp-form-row">
            <div class="ulp-form-group">
                <label for="ulp-gpu"><?php _e('کارت گرافیک (GPU)', 'used-laptop-pricer'); ?></label>
                <select id="ulp-gpu" name="gpu">
                    <option value=""><?php _e('انتخاب کنید', 'used-laptop-pricer'); ?></option>
                </select>
            </div>
            
            <div class="ulp-form-group">
                <label for="ulp-storage"><?php _e('حافظه (Storage)', 'used-laptop-pricer'); ?></label>
                <select id="ulp-storage" name="storage">
                    <option value=""><?php _e('انتخاب کنید', 'used-laptop-pricer'); ?></option>
                </select>
            </div>
        </div>
        
        <div class="ulp-form-actions">
            <button type="submit" id="ulp-calculate-btn" class="ulp-calculate-btn">
                <span class="ulp-btn-text"><?php _e('محاسبه قیمت', 'used-laptop-pricer'); ?></span>
                <span class="ulp-btn-loading" style="display: none;">
                    <span class="ulp-spinner"></span>
                    <?php _e('در حال محاسبه...', 'used-laptop-pricer'); ?>
                </span>
            </button>
        </div>
    </form>
    
    <!-- Results Section -->
    <div id="ulp-results" class="ulp-results" style="display: none;">
        <div class="ulp-results-header">
            <h3><?php _e('نتیجه محاسبه', 'used-laptop-pricer'); ?></h3>
        </div>
        
        <div class="ulp-price-range">
            <div class="ulp-price-main">
                <span class="ulp-price-label"><?php _e('قیمت پیشنهادی:', 'used-laptop-pricer'); ?></span>
                <span id="ulp-final-price" class="ulp-price-value"></span>
            </div>
            <div class="ulp-price-range-text">
                <span id="ulp-price-range-text"></span>
            </div>
        </div>
        
        <?php if ($atts['show_details'] === 'true'): ?>
            <div class="ulp-calculation-details">
                <h4><?php _e('جزئیات محاسبه', 'used-laptop-pricer'); ?></h4>
                <div id="ulp-breakdown" class="ulp-breakdown">
                    <!-- Calculation breakdown will be populated by JavaScript -->
                </div>
            </div>
        <?php endif; ?>
        
        <div class="ulp-results-actions">
            <button type="button" id="ulp-calculate-again" class="ulp-calculate-again-btn">
                <?php _e('محاسبه مجدد', 'used-laptop-pricer'); ?>
            </button>
        </div>
    </div>
    
    <!-- Error Messages -->
    <div id="ulp-error" class="ulp-error" style="display: none;">
        <div class="ulp-error-content">
            <span class="ulp-error-icon">⚠</span>
            <span id="ulp-error-message"></span>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize form
    var ulpForm = {
        init: function() {
            this.bindEvents();
            this.loadParts();
        },
        
        bindEvents: function() {
            $('#ulp-brand').on('change', this.onBrandChange.bind(this));
            $('#ulp-calculator-form').on('submit', this.onFormSubmit.bind(this));
            $('#ulp-calculate-again').on('click', this.onCalculateAgain.bind(this));
        },
        
        onBrandChange: function() {
            var brand = $('#ulp-brand').val();
            var modelSelect = $('#ulp-model');
            
            if (brand) {
                this.loadModels(brand);
                modelSelect.prop('disabled', false);
            } else {
                modelSelect.html('<option value=""><?php _e('ابتدا برند را انتخاب کنید', 'used-laptop-pricer'); ?></option>');
                modelSelect.prop('disabled', true);
            }
        },
        
        loadModels: function(brand) {
            var modelSelect = $('#ulp-model');
            modelSelect.html('<option value=""><?php _e('در حال بارگذاری...', 'used-laptop-pricer'); ?></option>');
            
            $.post(ulp_ajax.ajax_url, {
                action: 'ulp_get_models',
                brand: brand,
                nonce: ulp_ajax.nonce
            }, function(response) {
                if (response.success) {
                    var options = '<option value=""><?php _e('انتخاب کنید', 'used-laptop-pricer'); ?></option>';
                    response.data.forEach(function(model) {
                        options += '<option value="' + model.model + '">' + model.model + ' (' + model.release_year + ')</option>';
                    });
                    modelSelect.html(options);
                } else {
                    modelSelect.html('<option value=""><?php _e('خطا در بارگذاری مدل‌ها', 'used-laptop-pricer'); ?></option>');
                }
            });
        },
        
        loadParts: function() {
            var partTypes = ['cpu', 'ram', 'gpu', 'storage'];
            partTypes.forEach(function(type) {
                this.loadPartOptions(type);
            }.bind(this));
        },
        
        loadPartOptions: function(partType) {
            var select = $('#ulp-' + partType);
            
            $.post(ulp_ajax.ajax_url, {
                action: 'ulp_get_parts',
                part_type: partType,
                nonce: ulp_ajax.nonce
            }, function(response) {
                if (response.success) {
                    var options = '<option value=""><?php _e('انتخاب کنید', 'used-laptop-pricer'); ?></option>';
                    response.data.forEach(function(part) {
                        var displayName = part.part_name;
                        if (part.part_specs) {
                            displayName += ' - ' + part.part_specs;
                        }
                        options += '<option value="' + part.part_name + '">' + displayName + '</option>';
                    });
                    select.html(options);
                }
            });
        },
        
        onFormSubmit: function(e) {
            e.preventDefault();
            
            var formData = $('#ulp-calculator-form').serialize();
            var submitBtn = $('#ulp-calculate-btn');
            var btnText = submitBtn.find('.ulp-btn-text');
            var btnLoading = submitBtn.find('.ulp-btn-loading');
            
            // Show loading state
            btnText.hide();
            btnLoading.show();
            submitBtn.prop('disabled', true);
            
            // Hide previous results/errors
            $('#ulp-results, #ulp-error').hide();
            
            $.post(ulp_ajax.ajax_url, {
                action: 'ulp_calculate_price',
                formData: formData,
                nonce: ulp_ajax.nonce
            }, function(response) {
                btnText.show();
                btnLoading.hide();
                submitBtn.prop('disabled', false);
                
                if (response.success) {
                    ulpForm.showResults(response.data);
                } else {
                    ulpForm.showError(response.data);
                }
            }).fail(function() {
                btnText.show();
                btnLoading.hide();
                submitBtn.prop('disabled', false);
                ulpForm.showError('<?php _e('خطا در ارتباط با سرور', 'used-laptop-pricer'); ?>');
            });
        },
        
        showResults: function(data) {
            $('#ulp-final-price').text(data.formatted_prices.final_price);
            $('#ulp-price-range-text').text(data.formatted_prices.min_price + ' تا ' + data.formatted_prices.max_price);
            
            // Show calculation breakdown if enabled
            if (data.calculation_details) {
                var breakdown = '';
                var details = data.calculation_details;
                
                breakdown += '<div class="ulp-breakdown-item">';
                breakdown += '<span class="ulp-breakdown-label"><?php _e('قیمت پایه:', 'used-laptop-pricer'); ?></span>';
                breakdown += '<span class="ulp-breakdown-value">' + data.formatted_prices.base_price + '</span>';
                breakdown += '</div>';
                
                if (details.depreciation_amount > 0) {
                    breakdown += '<div class="ulp-breakdown-item">';
                    breakdown += '<span class="ulp-breakdown-label"><?php _e('کاهش ارزش:', 'used-laptop-pricer'); ?></span>';
                    breakdown += '<span class="ulp-breakdown-value ulp-negative">-' + data.formatted_prices.depreciation_amount + '</span>';
                    breakdown += '</div>';
                }
                
                if (details.parts_adjustment != 0) {
                    var adjustmentSign = details.parts_adjustment > 0 ? '+' : '';
                    breakdown += '<div class="ulp-breakdown-item">';
                    breakdown += '<span class="ulp-breakdown-label"><?php _e('تعدیل قطعات:', 'used-laptop-pricer'); ?></span>';
                    breakdown += '<span class="ulp-breakdown-value">' + adjustmentSign + data.formatted_prices.parts_adjustment + '</span>';
                    breakdown += '</div>';
                }
                
                $('#ulp-breakdown').html(breakdown);
            }
            
            $('#ulp-results').show();
            $('html, body').animate({
                scrollTop: $('#ulp-results').offset().top - 50
            }, 500);
        },
        
        showError: function(message) {
            $('#ulp-error-message').text(message);
            $('#ulp-error').show();
        },
        
        onCalculateAgain: function() {
            $('#ulp-results, #ulp-error').hide();
            $('html, body').animate({
                scrollTop: $('#ulp-calculator-form').offset().top - 50
            }, 500);
        }
    };
    
    // Initialize the form
    ulpForm.init();
});
</script>