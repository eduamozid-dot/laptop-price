/**
 * Used Laptop Pricer - Admin JavaScript
 * Enhanced admin interface functionality
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        initializeAdmin();
    });
    
    /**
     * Initialize admin functionality
     */
    function initializeAdmin() {
        // Initialize tooltips
        initializeTooltips();
        
        // Initialize form enhancements
        initializeFormEnhancements();
        
        // Initialize table enhancements
        initializeTableEnhancements();
        
        // Initialize bulk actions
        initializeBulkActions();
        
        // Initialize search and filters
        initializeSearchFilters();
    }
    
    /**
     * Initialize tooltips
     */
    function initializeTooltips() {
        $('[data-tooltip]').each(function() {
            const $element = $(this);
            const tooltipText = $element.data('tooltip');
            
            $element.on('mouseenter', function() {
                showTooltip($element, tooltipText);
            }).on('mouseleave', function() {
                hideTooltip();
            });
        });
    }
    
    /**
     * Show tooltip
     */
    function showTooltip($element, text) {
        const tooltip = $('<div class="ulp-tooltip">' + text + '</div>');
        $('body').append(tooltip);
        
        const elementOffset = $element.offset();
        const elementWidth = $element.outerWidth();
        const elementHeight = $element.outerHeight();
        const tooltipWidth = tooltip.outerWidth();
        const tooltipHeight = tooltip.outerHeight();
        
        let left = elementOffset.left + (elementWidth / 2) - (tooltipWidth / 2);
        let top = elementOffset.top - tooltipHeight - 10;
        
        // Adjust if tooltip goes off screen
        if (left < 0) left = 10;
        if (left + tooltipWidth > $(window).width()) {
            left = $(window).width() - tooltipWidth - 10;
        }
        if (top < 0) {
            top = elementOffset.top + elementHeight + 10;
        }
        
        tooltip.css({
            position: 'absolute',
            left: left + 'px',
            top: top + 'px',
            zIndex: 10000
        });
        
        tooltip.fadeIn(200);
    }
    
    /**
     * Hide tooltip
     */
    function hideTooltip() {
        $('.ulp-tooltip').fadeOut(200, function() {
            $(this).remove();
        });
    }
    
    /**
     * Initialize form enhancements
     */
    function initializeFormEnhancements() {
        // Auto-save form data
        initializeAutoSave();
        
        // Form validation
        initializeFormValidation();
        
        // Dynamic form fields
        initializeDynamicFields();
    }
    
    /**
     * Initialize auto-save functionality
     */
    function initializeAutoSave() {
        const $form = $('.ulp-form-container form');
        if ($form.length === 0) return;
        
        let autoSaveTimer;
        const autoSaveDelay = 3000; // 3 seconds
        
        $form.find('input, select, textarea').on('input change', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                saveFormData($form);
            }, autoSaveDelay);
        });
        
        // Save on page unload
        $(window).on('beforeunload', function() {
            if ($form.find('input, select, textarea').filter(function() {
                return $(this).val() !== '';
            }).length > 0) {
                saveFormData($form);
            }
        });
    }
    
    /**
     * Save form data to localStorage
     */
    function saveFormData($form) {
        const formData = {};
        $form.find('input, select, textarea').each(function() {
            const $field = $(this);
            const name = $field.attr('name');
            if (name) {
                formData[name] = $field.val();
            }
        });
        
        localStorage.setItem('ulp_form_data', JSON.stringify(formData));
        showAutoSaveIndicator();
    }
    
    /**
     * Show auto-save indicator
     */
    function showAutoSaveIndicator() {
        const indicator = $('<div class="ulp-auto-save-indicator">ذخیره خودکار...</div>');
        $('body').append(indicator);
        
        indicator.fadeIn(200).delay(2000).fadeOut(200, function() {
            $(this).remove();
        });
    }
    
    /**
     * Initialize form validation
     */
    function initializeFormValidation() {
        $('.ulp-form-container form').on('submit', function(e) {
            const $form = $(this);
            const errors = validateForm($form);
            
            if (errors.length > 0) {
                e.preventDefault();
                showValidationErrors(errors);
            }
        });
    }
    
    /**
     * Validate form
     */
    function validateForm($form) {
        const errors = [];
        
        $form.find('[required]').each(function() {
            const $field = $(this);
            const value = $field.val().trim();
            
            if (!value) {
                errors.push({
                    field: $field,
                    message: 'این فیلد الزامی است'
                });
            }
        });
        
        // Validate numeric fields
        $form.find('input[type="number"]').each(function() {
            const $field = $(this);
            const value = parseFloat($field.val());
            const min = parseFloat($field.attr('min'));
            const max = parseFloat($field.attr('max'));
            
            if (!isNaN(min) && value < min) {
                errors.push({
                    field: $field,
                    message: 'مقدار باید بیشتر از ' + min + ' باشد'
                });
            }
            
            if (!isNaN(max) && value > max) {
                errors.push({
                    field: $field,
                    message: 'مقدار باید کمتر از ' + max + ' باشد'
                });
            }
        });
        
        return errors;
    }
    
    /**
     * Show validation errors
     */
    function showValidationErrors(errors) {
        // Clear previous errors
        $('.ulp-field-error').remove();
        $('.ulp-error').removeClass('ulp-error');
        
        errors.forEach(function(error) {
            const $field = error.field;
            const $errorDiv = $('<div class="ulp-field-error">' + error.message + '</div>');
            
            $field.addClass('ulp-error');
            $field.after($errorDiv);
            
            // Scroll to first error
            if (errors.indexOf(error) === 0) {
                $('html, body').animate({
                    scrollTop: $field.offset().top - 100
                }, 500);
            }
        });
    }
    
    /**
     * Initialize dynamic fields
     */
    function initializeDynamicFields() {
        // Add/remove condition multiplier rows
        $('.ulp-add-condition').on('click', function() {
            addConditionRow();
        });
        
        $(document).on('click', '.ulp-remove-condition', function() {
            $(this).closest('.ulp-condition-row').remove();
        });
    }
    
    /**
     * Add condition multiplier row
     */
    function addConditionRow() {
        const rowHtml = `
            <div class="ulp-condition-row">
                <input type="text" name="condition_labels[]" placeholder="وضعیت" class="regular-text">
                <input type="number" name="condition_multipliers[]" placeholder="ضریب" min="0" max="2" step="0.1" class="small-text">
                <button type="button" class="button ulp-remove-condition">حذف</button>
            </div>
        `;
        
        $('.ulp-condition-multipliers').append(rowHtml);
    }
    
    /**
     * Initialize table enhancements
     */
    function initializeTableEnhancements() {
        // Sortable tables
        initializeSortableTables();
        
        // Row selection
        initializeRowSelection();
        
        // Inline editing
        initializeInlineEditing();
    }
    
    /**
     * Initialize sortable tables
     */
    function initializeSortableTables() {
        $('.ulp-models-table th, .ulp-parts-table th').on('click', function() {
            const $header = $(this);
            const $table = $header.closest('table');
            const columnIndex = $header.index();
            const isAscending = $header.hasClass('sort-asc');
            
            // Clear previous sort classes
            $table.find('th').removeClass('sort-asc sort-desc');
            
            // Add sort class
            $header.addClass(isAscending ? 'sort-desc' : 'sort-asc');
            
            // Sort table
            sortTable($table, columnIndex, !isAscending);
        });
    }
    
    /**
     * Sort table
     */
    function sortTable($table, columnIndex, ascending) {
        const $tbody = $table.find('tbody');
        const $rows = $tbody.find('tr').toArray();
        
        $rows.sort(function(a, b) {
            const aValue = $(a).find('td').eq(columnIndex).text().trim();
            const bValue = $(b).find('td').eq(columnIndex).text().trim();
            
            // Try to parse as number
            const aNum = parseFloat(aValue.replace(/[^\d.-]/g, ''));
            const bNum = parseFloat(bValue.replace(/[^\d.-]/g, ''));
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return ascending ? aNum - bNum : bNum - aNum;
            }
            
            // String comparison
            return ascending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
        });
        
        $tbody.empty().append($rows);
    }
    
    /**
     * Initialize row selection
     */
    function initializeRowSelection() {
        // Select all checkbox
        $('.ulp-select-all').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.ulp-row-checkbox').prop('checked', isChecked);
            updateBulkActions();
        });
        
        // Individual row checkboxes
        $(document).on('change', '.ulp-row-checkbox', function() {
            updateBulkActions();
        });
    }
    
    /**
     * Update bulk actions visibility
     */
    function updateBulkActions() {
        const checkedCount = $('.ulp-row-checkbox:checked').length;
        const $bulkActions = $('.ulp-bulk-actions');
        
        if (checkedCount > 0) {
            $bulkActions.show();
            $bulkActions.find('.ulp-selected-count').text(checkedCount);
        } else {
            $bulkActions.hide();
        }
    }
    
    /**
     * Initialize inline editing
     */
    function initializeInlineEditing() {
        $('.ulp-editable').on('dblclick', function() {
            const $cell = $(this);
            const currentValue = $cell.text().trim();
            const fieldName = $cell.data('field');
            
            const $input = $('<input type="text" class="ulp-inline-edit" value="' + currentValue + '">');
            $cell.html($input);
            $input.focus();
            
            $input.on('blur', function() {
                const newValue = $(this).val().trim();
                if (newValue !== currentValue) {
                    saveInlineEdit($cell, fieldName, newValue);
                } else {
                    $cell.text(currentValue);
                }
            }).on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    $(this).blur();
                } else if (e.which === 27) { // Escape key
                    $cell.text(currentValue);
                }
            });
        });
    }
    
    /**
     * Save inline edit
     */
    function saveInlineEdit($cell, fieldName, newValue) {
        const $row = $cell.closest('tr');
        const rowId = $row.data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ulp_save_inline_edit',
                id: rowId,
                field: fieldName,
                value: newValue,
                nonce: ulp_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $cell.text(newValue);
                    showSuccessMessage('تغییرات با موفقیت ذخیره شد');
                } else {
                    $cell.text($cell.data('original-value'));
                    showErrorMessage(response.data || 'خطا در ذخیره تغییرات');
                }
            },
            error: function() {
                $cell.text($cell.data('original-value'));
                showErrorMessage('خطا در ذخیره تغییرات');
            }
        });
    }
    
    /**
     * Initialize bulk actions
     */
    function initializeBulkActions() {
        $('.ulp-bulk-delete').on('click', function() {
            const checkedRows = $('.ulp-row-checkbox:checked');
            if (checkedRows.length === 0) return;
            
            if (confirm('آیا مطمئن هستید که می‌خواهید موارد انتخاب شده را حذف کنید؟')) {
                const ids = checkedRows.map(function() {
                    return $(this).val();
                }).get();
                
                deleteBulkItems(ids);
            }
        });
    }
    
    /**
     * Delete bulk items
     */
    function deleteBulkItems(ids) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ulp_bulk_delete',
                ids: ids,
                nonce: ulp_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    ids.forEach(function(id) {
                        $('[data-id="' + id + '"]').fadeOut(300, function() {
                            $(this).remove();
                        });
                    });
                    showSuccessMessage('موارد انتخاب شده با موفقیت حذف شدند');
                    updateBulkActions();
                } else {
                    showErrorMessage(response.data || 'خطا در حذف موارد');
                }
            },
            error: function() {
                showErrorMessage('خطا در حذف موارد');
            }
        });
    }
    
    /**
     * Initialize search and filters
     */
    function initializeSearchFilters() {
        // Live search
        $('.ulp-search-input').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            const $table = $(this).closest('.ulp-admin-wrap').find('table tbody');
            
            $table.find('tr').each(function() {
                const $row = $(this);
                const text = $row.text().toLowerCase();
                $row.toggle(text.includes(searchTerm));
            });
        });
        
        // Filter dropdowns
        $('.ulp-filter-select').on('change', function() {
            const filterValue = $(this).val();
            const filterType = $(this).data('filter');
            const $table = $(this).closest('.ulp-admin-wrap').find('table tbody');
            
            if (!filterValue) {
                $table.find('tr').show();
                return;
            }
            
            $table.find('tr').each(function() {
                const $row = $(this);
                const cellValue = $row.find('td[data-' + filterType + ']').data(filterType);
                $row.toggle(cellValue === filterValue);
            });
        });
    }
    
    /**
     * Show success message
     */
    function showSuccessMessage(message) {
        showMessage(message, 'success');
    }
    
    /**
     * Show error message
     */
    function showErrorMessage(message) {
        showMessage(message, 'error');
    }
    
    /**
     * Show message
     */
    function showMessage(message, type) {
        const $message = $('<div class="ulp-message ulp-message-' + type + '">' + message + '</div>');
        $('body').append($message);
        
        $message.fadeIn(300).delay(3000).fadeOut(300, function() {
            $(this).remove();
        });
    }
    
})(jQuery);