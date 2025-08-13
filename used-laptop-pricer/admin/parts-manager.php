<?php
/**
 * Parts Manager Admin Page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ULP_Parts_Manager {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'ulp-settings',
            __('مدیریت قطعات', 'used-laptop-pricer'),
            __('مدیریت قطعات', 'used-laptop-pricer'),
            'manage_options',
            'ulp-parts',
            array($this, 'admin_page')
        );
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'laptop-pricer_page_ulp-parts') {
            return;
        }
        
        wp_enqueue_style(
            'ulp-admin-style',
            ULP_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ULP_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'ulp-admin-script',
            ULP_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            ULP_PLUGIN_VERSION,
            true
        );
    }
    
    public function handle_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'ulp-parts') {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle form submissions
        if (isset($_POST['ulp_action'])) {
            $action = sanitize_text_field($_POST['ulp_action']);
            
            switch ($action) {
                case 'add_part':
                    $this->handle_add_part();
                    break;
                case 'edit_part':
                    $this->handle_edit_part();
                    break;
                case 'delete_part':
                    $this->handle_delete_part();
                    break;
            }
        }
    }
    
    public function admin_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        ?>
        <div class="wrap ulp-admin-wrap">
            <h1><?php _e('مدیریت قطعات', 'used-laptop-pricer'); ?></h1>
            
            <?php $this->display_notices(); ?>
            
            <div class="ulp-admin-content">
                <?php
                switch ($action) {
                    case 'add':
                        $this->display_add_form();
                        break;
                    case 'edit':
                        $this->display_edit_form();
                        break;
                    default:
                        $this->display_parts_list();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    private function display_parts_list() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_parts_prices';
        
        // Handle search and filters
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        
        $where = array();
        $values = array();
        
        if ($search) {
            $where[] = '(part_name LIKE %s OR part_specs LIKE %s)';
            $values[] = '%' . $wpdb->esc_like($search) . '%';
            $values[] = '%' . $wpdb->esc_like($search) . '%';
        }
        
        if ($type_filter) {
            $where[] = 'part_type = %s';
            $values[] = $type_filter;
        }
        
        $sql = "SELECT * FROM $table";
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY part_type, part_name';
        
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        
        $parts = $wpdb->get_results($sql);
        $part_types = ulp_get_part_type_options();
        
        ?>
        <div class="ulp-parts-header">
            <div class="ulp-actions">
                <a href="<?php echo admin_url('admin.php?page=ulp-parts&action=add'); ?>" class="button button-primary">
                    <?php _e('افزودن قطعه جدید', 'used-laptop-pricer'); ?>
                </a>
            </div>
            
            <div class="ulp-filters">
                <form method="get">
                    <input type="hidden" name="page" value="ulp-parts" />
                    <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('جستجو...', 'used-laptop-pricer'); ?>" />
                    <select name="type">
                        <option value=""><?php _e('همه انواع', 'used-laptop-pricer'); ?></option>
                        <?php foreach ($part_types as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($type_filter, $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" class="button" value="<?php _e('فیلتر', 'used-laptop-pricer'); ?>" />
                </form>
            </div>
        </div>
        
        <div class="ulp-parts-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('نوع قطعه', 'used-laptop-pricer'); ?></th>
                        <th><?php _e('نام قطعه', 'used-laptop-pricer'); ?></th>
                        <th><?php _e('مشخصات', 'used-laptop-pricer'); ?></th>
                        <th><?php _e('قیمت', 'used-laptop-pricer'); ?></th>
                        <th><?php _e('عملیات', 'used-laptop-pricer'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($parts)): ?>
                        <tr>
                            <td colspan="5"><?php _e('هیچ قطعه‌ای یافت نشد.', 'used-laptop-pricer'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($parts as $part): ?>
                            <tr>
                                <td><?php echo esc_html($part_types[$part->part_type] ?? $part->part_type); ?></td>
                                <td><?php echo esc_html($part->part_name); ?></td>
                                <td><?php echo esc_html($part->part_specs); ?></td>
                                <td><?php echo ulp_format_price($part->price); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=ulp-parts&action=edit&id=' . $part->id); ?>" class="button button-small">
                                        <?php _e('ویرایش', 'used-laptop-pricer'); ?>
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=ulp-parts&action=delete&id=' . $part->id . '&_wpnonce=' . wp_create_nonce('delete_part_' . $part->id)); ?>" 
                                       class="button button-small button-link-delete"
                                       onclick="return confirm('<?php _e('آیا مطمئن هستید؟', 'used-laptop-pricer'); ?>')">
                                        <?php _e('حذف', 'used-laptop-pricer'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    private function display_add_form() {
        $part_types = ulp_get_part_type_options();
        ?>
        <div class="ulp-form-container">
            <h2><?php _e('افزودن قطعه جدید', 'used-laptop-pricer'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('ulp_add_part', 'ulp_nonce'); ?>
                <input type="hidden" name="ulp_action" value="add_part" />
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="part_type"><?php _e('نوع قطعه', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <select name="part_type" id="part_type" required>
                                <option value=""><?php _e('انتخاب کنید', 'used-laptop-pricer'); ?></option>
                                <?php foreach ($part_types as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="part_name"><?php _e('نام قطعه', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" name="part_name" id="part_name" class="regular-text" required />
                            <p class="description"><?php _e('مثال: Intel Core i7-10700K', 'used-laptop-pricer'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="part_specs"><?php _e('مشخصات', 'used-laptop-pricer'); ?></label>
                        </th>
                        <td>
                            <textarea name="part_specs" id="part_specs" rows="3" class="large-text"></textarea>
                            <p class="description"><?php _e('مشخصات اضافی قطعه (اختیاری)', 'used-laptop-pricer'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="price"><?php _e('قیمت', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="number" name="price" id="price" min="0" step="0.01" required />
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('افزودن قطعه', 'used-laptop-pricer'); ?>" />
                    <a href="<?php echo admin_url('admin.php?page=ulp-parts'); ?>" class="button"><?php _e('انصراف', 'used-laptop-pricer'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }
    
    private function display_edit_form() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (!$id) {
            wp_die(__('شناسه قطعه نامعتبر است.', 'used-laptop-pricer'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ulp_parts_prices';
        $part = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        
        if (!$part) {
            wp_die(__('قطعه یافت نشد.', 'used-laptop-pricer'));
        }
        
        $part_types = ulp_get_part_type_options();
        ?>
        <div class="ulp-form-container">
            <h2><?php _e('ویرایش قطعه', 'used-laptop-pricer'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('ulp_edit_part', 'ulp_nonce'); ?>
                <input type="hidden" name="ulp_action" value="edit_part" />
                <input type="hidden" name="part_id" value="<?php echo esc_attr($part->id); ?>" />
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="part_type"><?php _e('نوع قطعه', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <select name="part_type" id="part_type" required>
                                <option value=""><?php _e('انتخاب کنید', 'used-laptop-pricer'); ?></option>
                                <?php foreach ($part_types as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($part->part_type, $key); ?>>
                                        <?php echo esc_html($label); ?>
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
                            <input type="text" name="part_name" id="part_name" class="regular-text" value="<?php echo esc_attr($part->part_name); ?>" required />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="part_specs"><?php _e('مشخصات', 'used-laptop-pricer'); ?></label>
                        </th>
                        <td>
                            <textarea name="part_specs" id="part_specs" rows="3" class="large-text"><?php echo esc_textarea($part->part_specs); ?></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="price"><?php _e('قیمت', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="number" name="price" id="price" min="0" step="0.01" value="<?php echo esc_attr($part->price); ?>" required />
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('بروزرسانی قطعه', 'used-laptop-pricer'); ?>" />
                    <a href="<?php echo admin_url('admin.php?page=ulp-parts'); ?>" class="button"><?php _e('انصراف', 'used-laptop-pricer'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }
    
    private function handle_add_part() {
        if (!wp_verify_nonce($_POST['ulp_nonce'], 'ulp_add_part')) {
            wp_die(__('خطای امنیتی', 'used-laptop-pricer'));
        }
        
        $data = array(
            'part_type' => sanitize_text_field($_POST['part_type']),
            'part_name' => sanitize_text_field($_POST['part_name']),
            'part_specs' => sanitize_textarea_field($_POST['part_specs']),
            'price' => floatval($_POST['price'])
        );
        
        $result = $this->insert_part($data);
        
        if (is_wp_error($result)) {
            $this->add_notice($result->get_error_message(), 'error');
        } else {
            $this->add_notice(__('قطعه با موفقیت اضافه شد.', 'used-laptop-pricer'), 'success');
        }
        
        wp_redirect(admin_url('admin.php?page=ulp-parts'));
        exit;
    }
    
    private function handle_edit_part() {
        if (!wp_verify_nonce($_POST['ulp_nonce'], 'ulp_edit_part')) {
            wp_die(__('خطای امنیتی', 'used-laptop-pricer'));
        }
        
        $id = intval($_POST['part_id']);
        $data = array(
            'part_type' => sanitize_text_field($_POST['part_type']),
            'part_name' => sanitize_text_field($_POST['part_name']),
            'part_specs' => sanitize_textarea_field($_POST['part_specs']),
            'price' => floatval($_POST['price'])
        );
        
        $result = $this->update_part($id, $data);
        
        if (is_wp_error($result)) {
            $this->add_notice($result->get_error_message(), 'error');
        } else {
            $this->add_notice(__('قطعه با موفقیت بروزرسانی شد.', 'used-laptop-pricer'), 'success');
        }
        
        wp_redirect(admin_url('admin.php?page=ulp-parts'));
        exit;
    }
    
    private function handle_delete_part() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_part_' . $id)) {
            wp_die(__('خطای امنیتی', 'used-laptop-pricer'));
        }
        
        $result = $this->delete_part($id);
        
        if (is_wp_error($result)) {
            $this->add_notice($result->get_error_message(), 'error');
        } else {
            $this->add_notice(__('قطعه با موفقیت حذف شد.', 'used-laptop-pricer'), 'success');
        }
        
        wp_redirect(admin_url('admin.php?page=ulp-parts'));
        exit;
    }
    
    private function insert_part($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_parts_prices';
        
        $result = $wpdb->insert(
            $table,
            $data,
            array('%s', '%s', '%s', '%f')
        );
        
        if ($result === false) {
            return new WP_Error('insert_failed', __('خطا در ذخیره قطعه', 'used-laptop-pricer'));
        }
        
        return $wpdb->insert_id;
    }
    
    private function update_part($id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_parts_prices';
        
        $result = $wpdb->update(
            $table,
            $data,
            array('id' => $id),
            array('%s', '%s', '%s', '%f'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('update_failed', __('خطا در بروزرسانی قطعه', 'used-laptop-pricer'));
        }
        
        return true;
    }
    
    private function delete_part($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_parts_prices';
        
        $result = $wpdb->delete(
            $table,
            array('id' => $id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('delete_failed', __('خطا در حذف قطعه', 'used-laptop-pricer'));
        }
        
        return true;
    }
    
    private function add_notice($message, $type = 'success') {
        $notices = get_option('ulp_admin_notices', array());
        $notices[] = array(
            'message' => $message,
            'type' => $type
        );
        update_option('ulp_admin_notices', $notices);
    }
    
    private function display_notices() {
        $notices = get_option('ulp_admin_notices', array());
        
        foreach ($notices as $notice) {
            $class = 'notice notice-' . $notice['type'];
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($notice['message']));
        }
        
        delete_option('ulp_admin_notices');
    }
}