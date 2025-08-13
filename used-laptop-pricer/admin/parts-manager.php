<?php
/**
 * Parts Manager for Used Laptop Pricer
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULP_Parts_Manager {
    
    public function __construct() {
        // Constructor
    }
    
    /**
     * Handle form actions
     */
    public function handle_actions() {
        // Handle delete action
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $this->delete_part(intval($_GET['id']));
        }
        
        // Handle form submission
        if (isset($_POST['ulp_save_part']) && wp_verify_nonce($_POST['ulp_part_nonce'], 'ulp_save_part')) {
            $this->save_part();
        }
    }
    
    /**
     * Save part
     */
    private function save_part() {
        $part_data = array(
            'part_type' => sanitize_text_field($_POST['part_type']),
            'part_name' => sanitize_text_field($_POST['part_name']),
            'part_specs' => sanitize_textarea_field($_POST['part_specs']),
            'price' => floatval($_POST['price'])
        );
        
        if (isset($_POST['part_id']) && $_POST['part_id']) {
            $part_data['id'] = intval($_POST['part_id']);
        }
        
        $result = ULP_Database::save_part_price($part_data);
        
        if ($result !== false) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . 
                     __('قطعه با موفقیت ذخیره شد.', 'used-laptop-pricer') . 
                     '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . 
                     __('خطا در ذخیره قطعه.', 'used-laptop-pricer') . 
                     '</p></div>';
            });
        }
    }
    
    /**
     * Delete part
     */
    private function delete_part($id) {
        $result = ULP_Database::delete_part_price($id);
        
        if ($result !== false) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . 
                     __('قطعه با موفقیت حذف شد.', 'used-laptop-pricer') . 
                     '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . 
                     __('خطا در حذف قطعه.', 'used-laptop-pricer') . 
                     '</p></div>';
            });
        }
    }
    
    /**
     * Render the page
     */
    public function render_page() {
        $parts = ULP_Database::get_parts_prices();
        $part_types = ulp_get_part_type_names();
        
        // Get part for editing
        $edit_part = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $edit_part = ULP_Database::get_part_price_by_id(intval($_GET['id']));
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('مدیریت قطعات', 'used-laptop-pricer'); ?></h1>
            
            <!-- Add/Edit Form -->
            <div class="ulp-form-section">
                <h2><?php echo $edit_part ? __('ویرایش قطعه', 'used-laptop-pricer') : __('افزودن قطعه جدید', 'used-laptop-pricer'); ?></h2>
                
                <form method="post" action="">
                    <?php wp_nonce_field('ulp_save_part', 'ulp_part_nonce'); ?>
                    
                    <?php if ($edit_part): ?>
                        <input type="hidden" name="part_id" value="<?php echo $edit_part->id; ?>" />
                    <?php endif; ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="part_type"><?php _e('نوع قطعه', 'used-laptop-pricer'); ?> *</label>
                            </th>
                            <td>
                                <select id="part_type" name="part_type" required>
                                    <option value=""><?php _e('انتخاب کنید', 'used-laptop-pricer'); ?></option>
                                    <?php foreach ($part_types as $type_key => $type_name): ?>
                                        <option value="<?php echo $type_key; ?>" 
                                                <?php echo ($edit_part && $edit_part->part_type === $type_key) ? 'selected' : ''; ?>>
                                            <?php echo $type_name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="part_name"><?php _e('نام قطعه', 'used-laptop-pricer'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="part_name" 
                                       name="part_name" 
                                       value="<?php echo $edit_part ? esc_attr($edit_part->part_name) : ''; ?>" 
                                       class="regular-text" 
                                       required />
                                <p class="description"><?php _e('مثال: Intel Core i7-10700K', 'used-laptop-pricer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="part_specs"><?php _e('مشخصات', 'used-laptop-pricer'); ?></label>
                            </th>
                            <td>
                                <textarea id="part_specs" 
                                          name="part_specs" 
                                          rows="3" 
                                          class="large-text"><?php echo $edit_part ? esc_textarea($edit_part->part_specs) : ''; ?></textarea>
                                <p class="description"><?php _e('مشخصات اضافی قطعه (اختیاری)', 'used-laptop-pricer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="price"><?php _e('قیمت', 'used-laptop-pricer'); ?> *</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="price" 
                                       name="price" 
                                       value="<?php echo $edit_part ? esc_attr($edit_part->price) : ''; ?>" 
                                       min="0" 
                                       step="1000" 
                                       class="regular-text" 
                                       required />
                                <p class="description"><?php _e('قیمت قطعه به تومان', 'used-laptop-pricer'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" 
                               name="ulp_save_part" 
                               class="button button-primary" 
                               value="<?php echo $edit_part ? __('بروزرسانی', 'used-laptop-pricer') : __('افزودن', 'used-laptop-pricer'); ?>" />
                        
                        <?php if ($edit_part): ?>
                            <a href="<?php echo admin_url('admin.php?page=used-laptop-pricer-parts'); ?>" class="button">
                                <?php _e('انصراف', 'used-laptop-pricer'); ?>
                            </a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
            
            <!-- Parts List -->
            <div class="ulp-parts-list">
                <h2><?php _e('لیست قطعات', 'used-laptop-pricer'); ?></h2>
                
                <?php if (empty($parts)): ?>
                    <p><?php _e('هیچ قطعه‌ای یافت نشد.', 'used-laptop-pricer'); ?></p>
                <?php else: ?>
                    <!-- Filter by part type -->
                    <div class="ulp-filter-section">
                        <label for="filter_part_type"><?php _e('فیلتر بر اساس نوع:', 'used-laptop-pricer'); ?></label>
                        <select id="filter_part_type">
                            <option value=""><?php _e('همه', 'used-laptop-pricer'); ?></option>
                            <?php foreach ($part_types as $type_key => $type_name): ?>
                                <option value="<?php echo $type_key; ?>"><?php echo $type_name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('نوع', 'used-laptop-pricer'); ?></th>
                                <th><?php _e('نام', 'used-laptop-pricer'); ?></th>
                                <th><?php _e('مشخصات', 'used-laptop-pricer'); ?></th>
                                <th><?php _e('قیمت', 'used-laptop-pricer'); ?></th>
                                <th><?php _e('عملیات', 'used-laptop-pricer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parts as $part): ?>
                                <tr data-part-type="<?php echo esc_attr($part->part_type); ?>">
                                    <td><?php echo esc_html($part_types[$part->part_type] ?? $part->part_type); ?></td>
                                    <td><?php echo esc_html($part->part_name); ?></td>
                                    <td><?php echo esc_html($part->part_specs); ?></td>
                                    <td><?php echo ulp_format_price($part->price); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=used-laptop-pricer-parts&action=edit&id=' . $part->id); ?>" 
                                           class="button button-small">
                                            <?php _e('ویرایش', 'used-laptop-pricer'); ?>
                                        </a>
                                        <a href="<?php echo admin_url('admin.php?page=used-laptop-pricer-parts&action=delete&id=' . $part->id); ?>" 
                                           class="button button-small button-link-delete" 
                                           onclick="return confirm('<?php _e('آیا مطمئن هستید؟', 'used-laptop-pricer'); ?>')">
                                            <?php _e('حذف', 'used-laptop-pricer'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Sample Data -->
            <div class="ulp-sample-data">
                <h2><?php _e('داده‌های نمونه', 'used-laptop-pricer'); ?></h2>
                <p><?php _e('برای شروع سریع، می‌توانید از داده‌های نمونه استفاده کنید:', 'used-laptop-pricer'); ?></p>
                
                <div class="ulp-sample-buttons">
                    <button type="button" class="button button-secondary" onclick="ulp_add_sample_cpus()">
                        <?php _e('افزود CPU های نمونه', 'used-laptop-pricer'); ?>
                    </button>
                    <button type="button" class="button button-secondary" onclick="ulp_add_sample_rams()">
                        <?php _e('افزود RAM های نمونه', 'used-laptop-pricer'); ?>
                    </button>
                    <button type="button" class="button button-secondary" onclick="ulp_add_sample_gpus()">
                        <?php _e('افزود GPU های نمونه', 'used-laptop-pricer'); ?>
                    </button>
                    <button type="button" class="button button-secondary" onclick="ulp_add_sample_storage()">
                        <?php _e('افزود Storage های نمونه', 'used-laptop-pricer'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Filter functionality
            $('#filter_part_type').on('change', function() {
                var selectedType = $(this).val();
                
                if (selectedType === '') {
                    $('tbody tr').show();
                } else {
                    $('tbody tr').hide();
                    $('tbody tr[data-part-type="' + selectedType + '"]').show();
                }
            });
        });
        
        // Sample data functions
        function ulp_add_sample_cpus() {
            if (confirm('<?php _e('آیا می‌خواهید CPU های نمونه اضافه شوند؟', 'used-laptop-pricer'); ?>')) {
                // Add sample CPUs via AJAX
                jQuery.post(ajaxurl, {
                    action: 'ulp_add_sample_data',
                    type: 'cpu',
                    nonce: '<?php echo wp_create_nonce('ulp_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('<?php _e('خطا در افزودن داده‌های نمونه', 'used-laptop-pricer'); ?>');
                    }
                });
            }
        }
        
        function ulp_add_sample_rams() {
            if (confirm('<?php _e('آیا می‌خواهید RAM های نمونه اضافه شوند؟', 'used-laptop-pricer'); ?>')) {
                jQuery.post(ajaxurl, {
                    action: 'ulp_add_sample_data',
                    type: 'ram',
                    nonce: '<?php echo wp_create_nonce('ulp_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('<?php _e('خطا در افزودن داده‌های نمونه', 'used-laptop-pricer'); ?>');
                    }
                });
            }
        }
        
        function ulp_add_sample_gpus() {
            if (confirm('<?php _e('آیا می‌خواهید GPU های نمونه اضافه شوند؟', 'used-laptop-pricer'); ?>')) {
                jQuery.post(ajaxurl, {
                    action: 'ulp_add_sample_data',
                    type: 'gpu',
                    nonce: '<?php echo wp_create_nonce('ulp_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('<?php _e('خطا در افزودن داده‌های نمونه', 'used-laptop-pricer'); ?>');
                    }
                });
            }
        }
        
        function ulp_add_sample_storage() {
            if (confirm('<?php _e('آیا می‌خواهید Storage های نمونه اضافه شوند؟', 'used-laptop-pricer'); ?>')) {
                jQuery.post(ajaxurl, {
                    action: 'ulp_add_sample_data',
                    type: 'storage',
                    nonce: '<?php echo wp_create_nonce('ulp_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('<?php _e('خطا در افزودن داده‌های نمونه', 'used-laptop-pricer'); ?>');
                    }
                });
            }
        }
        </script>
        <?php
    }
}