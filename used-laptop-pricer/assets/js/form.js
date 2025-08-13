/**
 * Used Laptop Pricer - Frontend JavaScript
 * Form handling and AJAX requests
 */

jQuery(document).ready(function($) {
    
    // Main form handler
    var ULPForm = {
        
        init: function() {
            this.bindEvents();
            this.initializeForm();
        },
        
        bindEvents: function() {
            // Brand change event
            $('#ulp-brand').on('change', this.handleBrandChange.bind(this));
            
            // Form submission
            $('#ulp-calculator-form').on('submit', this.handleFormSubmit.bind(this));
            
            // Calculate again button
            $('#ulp-calculate-again').on('click', this.handleCalculateAgain.bind(this));
            
            // Input validation
            $('.ulp-form-group input, .ulp-form-group select').on('blur', this.validateField.bind(this));
        },
        
        initializeForm: function() {
            // Load parts data on page load
            this.loadPartsData();
            
            // Set initial state
            this.updateFormState();
        },
        
        handleBrandChange: function() {
            var brand = $('#ulp-brand').val();
            var modelSelect = $('#ulp-model');
            
            if (brand) {
                this.loadModelsForBrand(brand);
                modelSelect.prop('disabled', false);
            } else {
                modelSelect.html('<option value="">' + ulp_ajax.strings.select_brand + '</option>');
                modelSelect.prop('disabled', true);
            }
        },
        
        loadModelsForBrand: function(brand) {
            var modelSelect = $('#ulp-model');
            
            // Show loading state
            modelSelect.html('<option value="">' + ulp_ajax.strings.calculating + '</option>');
            
            $.ajax({
                url: ulp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ulp_get_models',
                    brand: brand,
                    nonce: ulp_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var options = '<option value="">' + ulp_ajax.strings.select_model + '</option>';
                        response.data.forEach(function(model) {
                            options += '<option value="' + model.model + '">' + model.model + ' (' + model.release_year + ')</option>';
                        });
                        modelSelect.html(options);
                    } else {
                        modelSelect.html('<option value="">' + ulp_ajax.strings.no_models_found + '</option>');
                    }
                },
                error: function() {
                    modelSelect.html('<option value="">' + ulp_ajax.strings.error + '</option>');
                }
            });
        },
        
        loadPartsData: function() {
            var partTypes = ['cpu', 'ram', 'gpu', 'storage'];
            
            partTypes.forEach(function(type) {
                this.loadPartsForType(type);
            }.bind(this));
        },
        
        loadPartsForType: function(partType) {
            var select = $('#ulp-' + partType);
            
            $.ajax({
                url: ulp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ulp_get_parts',
                    part_type: partType,
                    nonce: ulp_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var options = '<option value="">' + ulp_ajax.strings.select_part + '</option>';
                        response.data.forEach(function(part) {
                            var displayName = part.part_name;
                            if (part.part_specs) {
                                displayName += ' - ' + part.part_specs;
                            }
                            options += '<option value="' + part.part_name + '">' + displayName + '</option>';
                        });
                        select.html(options);
                    } else {
                        select.html('<option value="">' + ulp_ajax.strings.no_parts_found + '</option>');
                    }
                },
                error: function() {
                    select.html('<option value="">' + ulp_ajax.strings.error + '</option>');
                }
            });
        },
        
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            if (!this.validateForm()) {
                return false;
            }
            
            this.showLoadingState();
            this.hideResults();
            
            var formData = this.serializeForm();
            
            $.ajax({
                url: ulp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ulp_calculate_price',
                    formData: formData,
                    nonce: ulp_ajax.nonce
                },
                success: function(response) {
                    this.hideLoadingState();
                    
                    if (response.success) {
                        this.showResults(response.data);
                    } else {
                        this.showError(response.data);
                    }
                }.bind(this),
                error: function() {
                    this.hideLoadingState();
                    this.showError(ulp_ajax.strings.server_error);
                }.bind(this)
            });
        },
        
        validateForm: function() {
            var isValid = true;
            var requiredFields = ['brand', 'model', 'year', 'condition'];
            
            requiredFields.forEach(function(field) {
                var value = $('[name="' + field + '"]').val();
                if (!value) {
                    this.showFieldError(field, ulp_ajax.strings.required_field);
                    isValid = false;
                } else {
                    this.clearFieldError(field);
                }
            }.bind(this));
            
            return isValid;
        },
        
        validateField: function(e) {
            var field = $(e.target);
            var fieldName = field.attr('name');
            var value = field.val();
            
            if (field.prop('required') && !value) {
                this.showFieldError(fieldName, ulp_ajax.strings.required_field);
            } else {
                this.clearFieldError(fieldName);
            }
        },
        
        showFieldError: function(fieldName, message) {
            var field = $('[name="' + fieldName + '"]');
            var formGroup = field.closest('.ulp-form-group');
            
            formGroup.addClass('ulp-error');
            
            var errorElement = formGroup.find('.ulp-field-error');
            if (errorElement.length === 0) {
                errorElement = $('<div class="ulp-field-error">' + message + '</div>');
                formGroup.append(errorElement);
            } else {
                errorElement.text(message);
            }
        },
        
        clearFieldError: function(fieldName) {
            var field = $('[name="' + fieldName + '"]');
            var formGroup = field.closest('.ulp-form-group');
            
            formGroup.removeClass('ulp-error');
            formGroup.find('.ulp-field-error').remove();
        },
        
        serializeForm: function() {
            var formData = {};
            $('#ulp-calculator-form').serializeArray().forEach(function(item) {
                formData[item.name] = item.value;
            });
            return formData;
        },
        
        showLoadingState: function() {
            var submitBtn = $('#ulp-calculate-btn');
            var btnText = submitBtn.find('.ulp-btn-text');
            var btnLoading = submitBtn.find('.ulp-btn-loading');
            
            btnText.hide();
            btnLoading.show();
            submitBtn.prop('disabled', true);
            
            $('#ulp-calculator-form').addClass('ulp-loading');
        },
        
        hideLoadingState: function() {
            var submitBtn = $('#ulp-calculate-btn');
            var btnText = submitBtn.find('.ulp-btn-text');
            var btnLoading = submitBtn.find('.ulp-btn-loading');
            
            btnText.show();
            btnLoading.hide();
            submitBtn.prop('disabled', false);
            
            $('#ulp-calculator-form').removeClass('ulp-loading');
        },
        
        showResults: function(data) {
            // Update price display
            $('#ulp-final-price').text(data.formatted_prices.final_price);
            $('#ulp-price-range-text').text(data.formatted_prices.min_price + ' تا ' + data.formatted_prices.max_price);
            
            // Show calculation breakdown if available
            if (data.calculation_details) {
                this.showCalculationBreakdown(data.calculation_details);
            }
            
            // Show results section
            $('#ulp-results').show();
            
            // Scroll to results
            this.scrollToElement('#ulp-results');
        },
        
        showCalculationBreakdown: function(details) {
            var breakdown = '';
            
            // Base price
            breakdown += '<div class="ulp-breakdown-item">';
            breakdown += '<span class="ulp-breakdown-label">قیمت پایه:</span>';
            breakdown += '<span class="ulp-breakdown-value">' + this.formatPrice(details.base_price) + '</span>';
            breakdown += '</div>';
            
            // Depreciation
            if (details.depreciation_amount > 0) {
                breakdown += '<div class="ulp-breakdown-item">';
                breakdown += '<span class="ulp-breakdown-label">کاهش ارزش:</span>';
                breakdown += '<span class="ulp-breakdown-value ulp-negative">-' + this.formatPrice(details.depreciation_amount) + '</span>';
                breakdown += '</div>';
            }
            
            // Condition factor
            breakdown += '<div class="ulp-breakdown-item">';
            breakdown += '<span class="ulp-breakdown-label">ضریب وضعیت:</span>';
            breakdown += '<span class="ulp-breakdown-value">× ' + details.condition_factor.toFixed(1) + '</span>';
            breakdown += '</div>';
            
            // Parts adjustment
            if (details.parts_adjustment !== 0) {
                var sign = details.parts_adjustment > 0 ? '+' : '';
                breakdown += '<div class="ulp-breakdown-item">';
                breakdown += '<span class="ulp-breakdown-label">تعدیل قطعات:</span>';
                breakdown += '<span class="ulp-breakdown-value">' + sign + this.formatPrice(details.parts_adjustment) + '</span>';
                breakdown += '</div>';
            }
            
            $('#ulp-breakdown').html(breakdown);
        },
        
        showError: function(message) {
            $('#ulp-error-message').text(message);
            $('#ulp-error').show();
            
            this.scrollToElement('#ulp-error');
        },
        
        hideResults: function() {
            $('#ulp-results, #ulp-error').hide();
        },
        
        handleCalculateAgain: function() {
            this.hideResults();
            this.scrollToElement('#ulp-calculator-form');
        },
        
        updateFormState: function() {
            // Update model select state based on brand selection
            var brand = $('#ulp-brand').val();
            var modelSelect = $('#ulp-model');
            
            if (!brand) {
                modelSelect.prop('disabled', true);
            }
        },
        
        scrollToElement: function(selector) {
            $('html, body').animate({
                scrollTop: $(selector).offset().top - 50
            }, 500);
        },
        
        formatPrice: function(price) {
            return new Intl.NumberFormat('fa-IR').format(price) + ' تومان';
        }
    };
    
    // Initialize the form
    ULPForm.init();
    
    // Add some additional utility functions
    window.ULPUtils = {
        
        // Format number with Persian digits
        formatNumber: function(num) {
            var persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            return num.toString().replace(/\d/g, function(x) {
                return persianNumbers[x];
            });
        },
        
        // Validate email format
        isValidEmail: function(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },
        
        // Debounce function for performance
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    };
    
    // Add responsive behavior
    $(window).on('resize', ULPUtils.debounce(function() {
        // Handle responsive layout changes
        if ($(window).width() <= 768) {
            $('.ulp-form-row').addClass('ulp-mobile');
        } else {
            $('.ulp-form-row').removeClass('ulp-mobile');
        }
    }, 250));
    
    // Initialize responsive state
    $(window).trigger('resize');
});