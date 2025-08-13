<?php
/**
 * Frontend Form Template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$brands = ulp_get_brands();
$condition_options = ulp_get_condition_options();
$years_list = ulp_get_years_list();
$part_types = ulp_get_part_type_options();

// Get parts for dropdowns
$cpu_parts = ulp_get_parts_prices('cpu');
$ram_parts = ulp_get_parts_prices('ram');
$gpu_parts = ulp_get_parts_prices('gpu');
$storage_parts = ulp_get_parts_prices('ssd');
$hdd_parts = ulp_get_parts_prices('hdd');
?>

<div class="ulp-calculator-container" dir="rtl">
    <div class="ulp-calculator-header">
        <h2><?php echo esc_html($atts['title']); ?></h2>
        <p class="ulp-description"><?php _e('برای محاسبه قیمت لپ‌تاپ دست دوم، اطلاعات زیر را وارد کنید:', 'used-laptop-pricer'); ?></p>
    </div>
    
    <form id="ulp-calculator-form" class="ulp-calculator-form">
        <div class="ulp-form-grid">
            <!-- Basic Information -->
            <div class="ulp-form-section">
                <h3><?php _e('اطلاعات پایه', 'used-laptop-pricer'); ?></h3>
                
                <div class="ulp-form-row">
                    <div class="ulp-form-group">
                        <label for="ulp-brand"><?php _e('برند', 'used-laptop-pricer'); ?> *</label>
                        <select name="brand" id="ulp-brand" required>
                            <option value=""><?php _e('انتخاب برند', 'used-laptop-pricer'); ?></option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo esc_attr($brand); ?>"><?php echo esc_html($brand); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="ulp-form-group">
                        <label for="ulp-model"><?php _e('مدل', 'used-laptop-pricer'); ?> *</label>
                        <select name="model" id="ulp-model" required disabled>
                            <option value=""><?php _e('ابتدا برند را انتخاب کنید', 'used-laptop-pricer'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="ulp-form-row">
                    <div class="ulp-form-group">
                        <label for="ulp-year"><?php _e('سال ساخت', 'used-laptop-pricer'); ?> *</label>
                        <select name="year" id="ulp-year" required>
                            <option value=""><?php _e('انتخاب سال', 'used-laptop-pricer'); ?></option>
                            <?php foreach ($years_list as $year => $year_label): ?>
                                <option value="<?php echo esc_attr($year); ?>"><?php echo esc_html($year_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="ulp-form-group">
                        <label for="ulp-condition"><?php _e('وضعیت ظاهری', 'used-laptop-pricer'); ?> *</label>
                        <select name="condition" id="ulp-condition" required>
                            <option value=""><?php _e('انتخاب وضعیت', 'used-laptop-pricer'); ?></option>
                            <?php foreach ($condition_options as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Specifications -->
            <div class="ulp-form-section">
                <h3><?php _e('مشخصات قطعات', 'used-laptop-pricer'); ?></h3>
                <p class="ulp-section-description"><?php _e('مشخصات دقیق قطعات لپ‌تاپ خود را انتخاب کنید:', 'used-laptop-pricer'); ?></p>
                
                <div class="ulp-form-row">
                    <div class="ulp-form-group">
                        <label for="ulp-cpu"><?php _e('پردازنده (CPU)', 'used-laptop-pricer'); ?></label>
                        <select name="cpu" id="ulp-cpu">
                            <option value=""><?php _e('انتخاب پردازنده', 'used-laptop-pricer'); ?></option>
                            <?php foreach ($cpu_parts as $part): ?>
                                <option value="<?php echo esc_attr($part->part_name); ?>">
                                    <?php echo esc_html($part->part_name); ?>
                                    <?php if ($part->part_specs): ?>
                                        (<?php echo esc_html($part->part_specs); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="ulp-form-group">
                        <label for="ulp-ram"><?php _e('رم (RAM)', 'used-laptop-pricer'); ?></label>
                        <select name="ram" id="ulp-ram">
                            <option value=""><?php _e('انتخاب رم', 'used-laptop-pricer'); ?></option>
                            <?php foreach ($ram_parts as $part): ?>
                                <option value="<?php echo esc_attr($part->part_name); ?>">
                                    <?php echo esc_html($part->part_name); ?>
                                    <?php if ($part->part_specs): ?>
                                        (<?php echo esc_html($part->part_specs); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="ulp-form-row">
                    <div class="ulp-form-group">
                        <label for="ulp-gpu"><?php _e('کارت گرافیک (GPU)', 'used-laptop-pricer'); ?></label>
                        <select name="gpu" id="ulp-gpu">
                            <option value=""><?php _e('انتخاب کارت گرافیک', 'used-laptop-pricer'); ?></option>
                            <?php foreach ($gpu_parts as $part): ?>
                                <option value="<?php echo esc_attr($part->part_name); ?>">
                                    <?php echo esc_html($part->part_name); ?>
                                    <?php if ($part->part_specs): ?>
                                        (<?php echo esc_html($part->part_specs); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="ulp-form-group">
                        <label for="ulp-storage"><?php _e('حافظه (Storage)', 'used-laptop-pricer'); ?></label>
                        <select name="storage" id="ulp-storage">
                            <option value=""><?php _e('انتخاب حافظه', 'used-laptop-pricer'); ?></option>
                            <?php foreach ($storage_parts as $part): ?>
                                <option value="<?php echo esc_attr($part->part_name); ?>">
                                    <?php echo esc_html($part->part_name); ?>
                                    <?php if ($part->part_specs): ?>
                                        (<?php echo esc_html($part->part_specs); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                            <?php foreach ($hdd_parts as $part): ?>
                                <option value="<?php echo esc_attr($part->part_name); ?>">
                                    <?php echo esc_html($part->part_name); ?>
                                    <?php if ($part->part_specs): ?>
                                        (<?php echo esc_html($part->part_specs); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="ulp-form-actions">
            <button type="submit" class="ulp-submit-btn">
                <span class="ulp-btn-text"><?php _e('محاسبه قیمت', 'used-laptop-pricer'); ?></span>
                <span class="ulp-btn-loading" style="display: none;">
                    <span class="ulp-spinner"></span>
                    <?php _e('در حال محاسبه...', 'used-laptop-pricer'); ?>
                </span>
            </button>
        </div>
    </form>
    
    <!-- Results Container -->
    <div id="ulp-results" class="ulp-results-container" style="display: none;">
        <div class="ulp-results-header">
            <h3><?php _e('نتیجه محاسبه', 'used-laptop-pricer'); ?></h3>
        </div>
        
        <div class="ulp-results-content">
            <div class="ulp-price-range">
                <div class="ulp-price-main">
                    <span class="ulp-price-label"><?php _e('بازه قیمتی:', 'used-laptop-pricer'); ?></span>
                    <span class="ulp-price-value" id="ulp-price-range"></span>
                </div>
                <div class="ulp-price-note">
                    <?php _e('قیمت نهایی تا ۱۰٪ کمتر از قیمت محاسبه شده', 'used-laptop-pricer'); ?>
                </div>
            </div>
            
            <div class="ulp-calculation-details">
                <h4><?php _e('جزئیات محاسبه', 'used-laptop-pricer'); ?></h4>
                <div class="ulp-details-grid" id="ulp-details-grid">
                    <!-- Details will be populated by JavaScript -->
                </div>
            </div>
        </div>
        
        <div class="ulp-results-actions">
            <button type="button" class="ulp-reset-btn" onclick="ulpResetForm()">
                <?php _e('محاسبه جدید', 'used-laptop-pricer'); ?>
            </button>
        </div>
    </div>
    
    <!-- Error Container -->
    <div id="ulp-error" class="ulp-error-container" style="display: none;">
        <div class="ulp-error-content">
            <span class="ulp-error-icon">⚠️</span>
            <span class="ulp-error-message" id="ulp-error-message"></span>
        </div>
    </div>
</div>

<script type="text/javascript">
// Store parts data for dynamic loading
window.ulpPartsData = {
    cpu: <?php echo json_encode($cpu_parts); ?>,
    ram: <?php echo json_encode($ram_parts); ?>,
    gpu: <?php echo json_encode($gpu_parts); ?>,
    storage: <?php echo json_encode(array_merge($storage_parts, $hdd_parts)); ?>
};

// Store brands and models data
window.ulpBrandsData = <?php echo json_encode($brands); ?>;
</script>