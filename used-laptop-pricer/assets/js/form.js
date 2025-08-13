/**
 * Used Laptop Pricer - Frontend JavaScript
 * Form handling, AJAX requests, and dynamic behavior
 */

(function($) {
    'use strict';
    
    // Store models data for dynamic loading
    let modelsData = {};
    
    $(document).ready(function() {
        initializeForm();
        loadModelsData();
    });
    
    /**
     * Initialize form functionality
     */
    function initializeForm() {
        // Brand change handler
        $('#ulp-brand').on('change', function() {
            const brand = $(this).val();
            updateModelsDropdown(brand);
        });
        
        // Form submission
        $('#ulp-calculator-form').on('submit', function(e) {
            e.preventDefault();
            handleFormSubmission();
        });
        
        // Reset form
        $(document).on('click', '.ulp-reset-btn', function() {
            resetForm();
        });
    }
    
    /**
     * Load models data for dynamic dropdowns
     */
    function loadModelsData() {
        if (typeof ulp_ajax !== 'undefined' && ulp_ajax.ajax_url) {
            $.ajax({
                url: ulp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ulp_get_models_data',
                    nonce: ulp_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        modelsData = response.data;
                    }
                },
                error: function() {
                    console.error('Failed to load models data');
                }
            });
        }
    }
    
    /**
     * Update models dropdown based on selected brand
     */
    function updateModelsDropdown(brand) {
        const modelSelect = $('#ulp-model');
        modelSelect.empty();
        modelSelect.append('<option value="">' + ulp_ajax.strings.select_model + '</option>');
        
        if (!brand) {
            modelSelect.prop('disabled', true);
            return;
        }
        
        // Get models for selected brand
        $.ajax({
            url: ulp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ulp_get_models_by_brand',
                brand: brand,
                nonce: ulp_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    response.data.forEach(function(model) {
                        modelSelect.append('<option value="' + model + '">' + model + '</option>');
                    });
                    modelSelect.prop('disabled', false);
                }
            },
            error: function() {
                showError(ulp_ajax.strings.error);
            }
        });
    }
    
    /**
     * Handle form submission
     */
    function handleFormSubmission() {
        const form = $('#ulp-calculator-form');
        const submitBtn = form.find('.ulp-submit-btn');
        const btnText = submitBtn.find('.ulp-btn-text');
        const btnLoading = submitBtn.find('.ulp-btn-loading');
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Show loading state
        submitBtn.prop('disabled', true);
        btnText.hide();
        btnLoading.show();
        
        // Hide previous results and errors
        hideResults();
        hideError();
        
        // Collect form data
        const formData = {
            action: 'calculate_laptop_price',
            nonce: ulp_ajax.nonce,
            brand: $('#ulp-brand').val(),
            model: $('#ulp-model').val(),
            year: $('#ulp-year').val(),
            condition: $('#ulp-condition').val(),
            cpu: $('#ulp-cpu').val(),
            ram: $('#ulp-ram').val(),
            gpu: $('#ulp-gpu').val(),
            storage: $('#ulp-storage').val()
        };
        
        // Send AJAX request
        $.ajax({
            url: ulp_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    displayResults(response.data);
                } else {
                    showError(response.data || ulp_ajax.strings.error);
                }
            },
            error: function() {
                showError(ulp_ajax.strings.error);
            },
            complete: function() {
                // Reset button state
                submitBtn.prop('disabled', false);
                btnText.show();
                btnLoading.hide();
            }
        });
    }
    
    /**
     * Validate form inputs
     */
    function validateForm() {
        const requiredFields = ['brand', 'model', 'year', 'condition'];
        let isValid = true;
        
        requiredFields.forEach(function(field) {
            const value = $('#' + 'ulp-' + field).val();
            if (!value) {
                showFieldError(field, ulp_ajax.strings['select_' + field] || 'This field is required');
                isValid = false;
            } else {
                clearFieldError(field);
            }
        });
        
        return isValid;
    }
    
    /**
     * Show field-specific error
     */
    function showFieldError(field, message) {
        const fieldElement = $('#' + 'ulp-' + field);
        const errorDiv = fieldElement.siblings('.ulp-field-error');
        
        if (errorDiv.length === 0) {
            fieldElement.after('<div class="ulp-field-error" style="color: #dc3545; font-size: 0.9em; margin-top: 5px;">' + message + '</div>');
        } else {
            errorDiv.text(message);
        }
        
        fieldElement.addClass('ulp-error');
    }
    
    /**
     * Clear field-specific error
     */
    function clearFieldError(field) {
        const fieldElement = $('#' + 'ulp-' + field);
        fieldElement.siblings('.ulp-field-error').remove();
        fieldElement.removeClass('ulp-error');
    }
    
    /**
     * Display calculation results
     */
    function displayResults(data) {
        const resultsContainer = $('#ulp-results');
        const priceRange = $('#ulp-price-range');
        const detailsGrid = $('#ulp-details-grid');
        
        // Display price range
        priceRange.text(data.formatted_prices.range);
        
        // Display calculation details
        detailsGrid.empty();
        
        if (data.calculation_details) {
            const details = [
                {
                    label: 'قیمت پایه',
                    value: data.formatted_prices.base || formatPrice(data.calculation_details.base_price),
                    description: 'قیمت اولیه لپ‌تاپ در زمان عرضه'
                },
                {
                    label: 'استهلاک',
                    value: formatPrice(data.calculation_details.base_price - data.calculation_details.depreciated_price),
                    description: 'کاهش قیمت بر اساس ' + data.calculation_details.depreciation_percentage + '% استهلاک'
                },
                {
                    label: 'تعدیل وضعیت',
                    value: formatPrice(data.calculation_details.condition_adjusted_price - data.calculation_details.depreciated_price),
                    description: 'تعدیل بر اساس ضریب وضعیت (' + data.calculation_details.condition_multiplier + ')'
                },
                {
                    label: 'تعدیل قطعات',
                    value: formatPrice(data.calculation_details.parts_adjustment),
                    description: 'تفاوت قیمت قطعات با کانفیگ پایه'
                },
                {
                    label: 'قیمت نهایی',
                    value: data.formatted_prices.final,
                    description: 'قیمت محاسبه شده نهایی'
                }
            ];
            
            details.forEach(function(detail) {
                const detailHtml = `
                    <div class="ulp-result-item">
                        <div class="ulp-result-label">${detail.label}</div>
                        <div class="ulp-result-value">${detail.value}</div>
                        <div class="ulp-result-description">${detail.description}</div>
                    </div>
                `;
                detailsGrid.append(detailHtml);
            });
        }
        
        // Show results
        resultsContainer.show();
        
        // Scroll to results
        $('html, body').animate({
            scrollTop: resultsContainer.offset().top - 50
        }, 500);
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        const errorContainer = $('#ulp-error');
        const errorMessage = $('#ulp-error-message');
        
        errorMessage.text(message);
        errorContainer.show();
        
        // Scroll to error
        $('html, body').animate({
            scrollTop: errorContainer.offset().top - 50
        }, 500);
    }
    
    /**
     * Hide error message
     */
    function hideError() {
        $('#ulp-error').hide();
    }
    
    /**
     * Hide results
     */
    function hideResults() {
        $('#ulp-results').hide();
    }
    
    /**
     * Reset form
     */
    function resetForm() {
        const form = $('#ulp-calculator-form')[0];
        form.reset();
        
        // Reset model dropdown
        $('#ulp-model').empty().append('<option value="">' + ulp_ajax.strings.select_model + '</option>').prop('disabled', true);
        
        // Clear field errors
        $('.ulp-field-error').remove();
        $('.ulp-error').removeClass('ulp-error');
        
        // Hide results and errors
        hideResults();
        hideError();
        
        // Scroll to top
        $('html, body').animate({
            scrollTop: $('.ulp-calculator-container').offset().top
        }, 500);
    }
    
    /**
     * Format price with currency
     */
    function formatPrice(price) {
        if (typeof price !== 'number') {
            return price;
        }
        
        return new Intl.NumberFormat('fa-IR').format(price) + ' تومان';
    }
    
    /**
     * Global reset function for onclick handlers
     */
    window.ulpResetForm = resetForm;
    
})(jQuery);