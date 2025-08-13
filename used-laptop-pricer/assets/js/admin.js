/**
 * Used Laptop Pricer - Admin JavaScript
 * Admin panel interactions and AJAX requests
 */

jQuery(document).ready(function($) {
    
    // Main admin handler
    var ULPAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initializeAdmin();
        },
        
        bindEvents: function() {
            // Confirm delete actions
            $('.button-link-delete').on('click', this.confirmDelete.bind(this));
            
            // Form validation
            $('.ulp-form-section input, .ulp-form-section select, .ulp-form-section textarea').on('blur', this.validateField.bind(this));
            
            // Auto-save settings
            $('.ulp-settings-section input, .ulp-settings-section select').on('change', this.autoSaveSettings.bind(this));
            
            // Sample data buttons
            $('[onclick*="ulp_add_sample"]').on('click', this.handleSampleData.bind(this));
            
            // Excel file validation
            $('input[type="file"]').on('change', this.validateExcelFile.bind(this));
            
            // Filter functionality
            $('#filter_part_type').on('change', this.filterParts.bind(this));
        },
        
        initializeAdmin: function() {
            // Initialize tooltips
            this.initTooltips();
            
            // Initialize sortable tables
            this.initSortableTables();
            
            // Set up auto-refresh for stats
            this.setupAutoRefresh();
        },
        
        confirmDelete: function(e) {
            var message = ulp_admin.strings.confirm_delete;
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        },
        
        validateField: function(e) {
            var field = $(e.target);
            var fieldName = field.attr('name');
            var value = field.val();
            
            if (field.prop('required') && !value) {
                this.showFieldError(fieldName, ulp_admin.strings.required_field);
            } else {
                this.clearFieldError(fieldName);
            }
        },
        
        showFieldError: function(fieldName, message) {
            var field = $('[name="' + fieldName + '"]');
            var formGroup = field.closest('tr');
            
            formGroup.addClass('ulp-error');
            
            var errorElement = formGroup.find('.ulp-field-error');
            if (errorElement.length === 0) {
                errorElement = $('<div class="ulp-field-error">' + message + '</div>');
                formGroup.find('td').last().append(errorElement);
            } else {
                errorElement.text(message);
            }
        },
        
        clearFieldError: function(fieldName) {
            var field = $('[name="' + fieldName + '"]');
            var formGroup = field.closest('tr');
            
            formGroup.removeClass('ulp-error');
            formGroup.find('.ulp-field-error').remove();
        },
        
        autoSaveSettings: function(e) {
            var field = $(e.target);
            var form = field.closest('form');
            
            // Debounce the auto-save
            clearTimeout(this.autoSaveTimeout);
            this.autoSaveTimeout = setTimeout(function() {
                ULPAdmin.saveSettings(form);
            }, 1000);
        },
        
        saveSettings: function(form) {
            var formData = new FormData(form[0]);
            formData.append('action', 'ulp_save_settings');
            formData.append('nonce', ulp_admin.nonce);
            
            $.ajax({
                url: ulp_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        ULPAdmin.showNotice(ulp_admin.strings.saved, 'success');
                    } else {
                        ULPAdmin.showNotice(ulp_admin.strings.error, 'error');
                    }
                },
                error: function() {
                    ULPAdmin.showNotice(ulp_admin.strings.error, 'error');
                }
            });
        },
        
        handleSampleData: function(e) {
            e.preventDefault();
            
            var button = $(e.target);
            var dataType = button.data('type') || this.getDataTypeFromOnclick(button.attr('onclick'));
            
            if (!dataType) {
                return;
            }
            
            if (confirm(ulp_admin.strings.confirm_sample_data)) {
                this.addSampleData(dataType, button);
            }
        },
        
        getDataTypeFromOnclick: function(onclick) {
            if (onclick && onclick.includes('ulp_add_sample_')) {
                return onclick.match(/ulp_add_sample_(\w+)/)[1];
            }
            return null;
        },
        
        addSampleData: function(dataType, button) {
            var originalText = button.text();
            button.prop('disabled', true).text(ulp_admin.strings.saving);
            
            $.ajax({
                url: ulp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ulp_add_sample_data',
                    type: dataType,
                    nonce: ulp_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ULPAdmin.showNotice(ulp_admin.strings.sample_data_added, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        ULPAdmin.showNotice(ulp_admin.strings.error, 'error');
                        button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    ULPAdmin.showNotice(ulp_admin.strings.error, 'error');
                    button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        validateExcelFile: function(e) {
            var file = e.target.files[0];
            var allowedTypes = ['.xlsx', '.xls'];
            var maxSize = 5 * 1024 * 1024; // 5MB
            
            if (file) {
                // Check file size
                if (file.size > maxSize) {
                    ULPAdmin.showNotice('فایل باید کمتر از 5 مگابایت باشد.', 'error');
                    e.target.value = '';
                    return;
                }
                
                // Check file extension
                var fileName = file.name.toLowerCase();
                var isValidType = allowedTypes.some(function(type) {
                    return fileName.endsWith(type);
                });
                
                if (!isValidType) {
                    ULPAdmin.showNotice('فقط فایل‌های Excel (.xlsx یا .xls) مجاز هستند.', 'error');
                    e.target.value = '';
                    return;
                }
            }
        },
        
        filterParts: function() {
            var selectedType = $(this).val();
            var table = $('.wp-list-table tbody');
            
            if (selectedType === '') {
                table.find('tr').show();
            } else {
                table.find('tr').hide();
                table.find('tr[data-part-type="' + selectedType + '"]').show();
            }
        },
        
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                var element = $(this);
                var tooltipText = element.data('tooltip');
                
                element.on('mouseenter', function() {
                    ULPAdmin.showTooltip(element, tooltipText);
                }).on('mouseleave', function() {
                    ULPAdmin.hideTooltip();
                });
            });
        },
        
        showTooltip: function(element, text) {
            var tooltip = $('<div class="ulp-tooltip">' + text + '</div>');
            $('body').append(tooltip);
            
            var offset = element.offset();
            tooltip.css({
                position: 'absolute',
                top: offset.top - tooltip.outerHeight() - 10,
                left: offset.left + (element.outerWidth() / 2) - (tooltip.outerWidth() / 2),
                zIndex: 9999
            });
        },
        
        hideTooltip: function() {
            $('.ulp-tooltip').remove();
        },
        
        initSortableTables: function() {
            $('.wp-list-table').each(function() {
                var table = $(this);
                var headers = table.find('th[data-sortable]');
                
                headers.on('click', function() {
                    var header = $(this);
                    var column = header.index();
                    var direction = header.hasClass('sorted-asc') ? 'desc' : 'asc';
                    
                    // Update header classes
                    headers.removeClass('sorted-asc sorted-desc');
                    header.addClass('sorted-' + direction);
                    
                    // Sort table
                    ULPAdmin.sortTable(table, column, direction);
                });
            });
        },
        
        sortTable: function(table, column, direction) {
            var tbody = table.find('tbody');
            var rows = tbody.find('tr').get();
            
            rows.sort(function(a, b) {
                var aVal = $(a).find('td').eq(column).text();
                var bVal = $(b).find('td').eq(column).text();
                
                // Try to convert to numbers for numeric sorting
                var aNum = parseFloat(aVal.replace(/[^\d.-]/g, ''));
                var bNum = parseFloat(bVal.replace(/[^\d.-]/g, ''));
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return direction === 'asc' ? aNum - bNum : bNum - aNum;
                } else {
                    return direction === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
                }
            });
            
            tbody.empty().append(rows);
        },
        
        setupAutoRefresh: function() {
            // Auto-refresh dashboard stats every 30 seconds
            if ($('.ulp-dashboard-stats').length > 0) {
                setInterval(function() {
                    ULPAdmin.refreshStats();
                }, 30000);
            }
        },
        
        refreshStats: function() {
            $.ajax({
                url: ulp_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ulp_get_stats',
                    nonce: ulp_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ULPAdmin.updateStats(response.data);
                    }
                }
            });
        },
        
        updateStats: function(stats) {
            $('.ulp-stat-content h3').each(function() {
                var statCard = $(this).closest('.ulp-stat-card');
                var statType = statCard.data('stat-type');
                
                if (stats[statType]) {
                    $(this).text(stats[statType]);
                }
            });
        },
        
        showNotice: function(message, type) {
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap h1').after(notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        // Utility functions
        formatNumber: function(num) {
            return new Intl.NumberFormat('fa-IR').format(num);
        },
        
        formatPrice: function(price) {
            return this.formatNumber(price) + ' تومان';
        },
        
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
    
    // Initialize admin functionality
    ULPAdmin.init();
    
    // Add global admin utilities
    window.ULPAdminUtils = {
        
        // Export table to CSV
        exportTableToCSV: function(tableSelector, filename) {
            var table = $(tableSelector);
            var csv = [];
            var rows = table.find('tr');
            
            rows.each(function() {
                var row = [];
                $(this).find('td, th').each(function() {
                    var text = $(this).text().replace(/"/g, '""');
                    row.push('"' + text + '"');
                });
                csv.push(row.join(','));
            });
            
            var csvContent = csv.join('\n');
            var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            
            if (link.download !== undefined) {
                var url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        },
        
        // Print table
        printTable: function(tableSelector) {
            var table = $(tableSelector).clone();
            var printWindow = window.open('', '_blank');
            
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Print</title>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            table { border-collapse: collapse; width: 100%; }
                            th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
                            th { background-color: #f2f2f2; }
                        </style>
                    </head>
                    <body>
                        ${table.prop('outerHTML')}
                    </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.print();
        },
        
        // Search functionality
        searchTable: function(tableSelector, searchTerm) {
            var table = $(tableSelector);
            var rows = table.find('tbody tr');
            
            rows.each(function() {
                var row = $(this);
                var text = row.text().toLowerCase();
                var matches = text.includes(searchTerm.toLowerCase());
                row.toggle(matches);
            });
        }
    };
    
    // Add keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S to save settings
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            $('.ulp-settings-section form').submit();
        }
        
        // Ctrl/Cmd + F to search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            $('.ulp-search-input').focus();
        }
    });
    
    // Add responsive behavior for admin
    $(window).on('resize', ULPAdmin.debounce(function() {
        if ($(window).width() <= 768) {
            $('.ulp-dashboard-stats').addClass('ulp-mobile');
            $('.ulp-dashboard-actions').addClass('ulp-mobile');
        } else {
            $('.ulp-dashboard-stats').removeClass('ulp-mobile');
            $('.ulp-dashboard-actions').removeClass('ulp-mobile');
        }
    }, 250));
    
    // Initialize responsive state
    $(window).trigger('resize');
});