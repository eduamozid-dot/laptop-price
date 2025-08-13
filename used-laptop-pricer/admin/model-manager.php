<?php
/**
 * Model Manager Admin Page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ULP_Model_Manager {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'ulp-settings',
            __('مدیریت مدل‌های پایه', 'used-laptop-pricer'),
            __('مدیریت مدل‌ها', 'used-laptop-pricer'),
            'manage_options',
            'ulp-models',
            array($this, 'admin_page')
        );
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'laptop-pricer_page_ulp-models') {
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
        if (!isset($_GET['page']) || $_GET['page'] !== 'ulp-models') {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle form submissions
        if (isset($_POST['ulp_action'])) {
            $action = sanitize_text_field($_POST['ulp_action']);
            
            switch ($action) {
                case 'add_model':
                    $this->handle_add_model();
                    break;
                case 'edit_model':
                    $this->handle_edit_model();
                    break;
                case 'delete_model':
                    $this->handle_delete_model();
                    break;
                case 'import_excel':
                    $this->handle_import_excel();
                    break;
            }
        }
    }
    
    public function admin_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        ?>
        <div class="wrap ulp-admin-wrap">
            <h1><?php _e('مدیریت مدل‌های پایه', 'used-laptop-pricer'); ?></h1>
            
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
                    case 'import':
                        $this->display_import_form();
                        break;
                    default:
                        $this->display_models_list();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    private function display_models_list() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_base_models';
        
        // Handle search and filters
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $brand_filter = isset($_GET['brand']) ? sanitize_text_field($_GET['brand']) : '';
        
        $where = array();
        $values = array();
        
        if ($search) {
            $where[] = '(brand LIKE %s OR model LIKE %s)';
            $values[] = '%' . $wpdb->esc_like($search) . '%';
            $values[] = '%' . $wpdb->esc_like($search) . '%';
        }
        
        if ($brand_filter) {
            $where[] = 'brand = %s';
            $values[] = $brand_filter;
        }
        
        $sql = "SELECT * FROM $table";
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY brand, model';
        
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        
        $models = $wpdb->get_results($sql);
        $brands = ulp_get_brands();
        
        ?>
        <div class="ulp-models-header">
            <div class="ulp-actions">
                <a href="<?php echo admin_url('admin.php?page=ulp-models&action=add'); ?>" class="button button-primary">
                    <?php _e('افزودن مدل جدید', 'used-laptop-pricer'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ulp-models&action=import'); ?>" class="button">
                    <?php _e('ورود از Excel', 'used-laptop-pricer'); ?>
                </a>
                <a href="<?php echo admin_url('admin-ajax.php?action=ulp_export_models'); ?>" class="button">
                    <?php _e('خروجی Excel', 'used-laptop-pricer'); ?>
                </a>
            </div>
            
            <div class="ulp-filters">
                <form method="get">
                    <input type="hidden" name="page" value="ulp-models" />
                    <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('جستجو...', 'used-laptop-pricer'); ?>" />
                    <select name="brand">
                        <option value=""><?php _e('همه برندها', 'used-laptop-pricer'); ?></option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo esc_attr($brand); ?>" <?php selected($brand_filter, $brand); ?>>
                                <?php echo esc_html($brand); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" class="button" value="<?php _e('فیلتر', 'used-laptop-pricer'); ?>" />
                </form>
            </div>
        </div>
        
        <div class="ulp-models-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('برند', 'used-laptop-pricer'); ?></th>
                        <th><?php _e('مدل', 'used-laptop-pricer'); ?></th>
                        <th><?php _e('سال عرضه', 'used-laptop-pricer'); ?></th>
                        <th><?php _e('قیمت پایه', 'used-laptop-pricer'); ?></th>
                        <th><?php _e('CPU پایه', 'used-laptop-pricer'); ?></th>
                        <th><?php _e('RAM پایه', 'used-laptop-pricer'); ?></th>
                        <th><?php _e('GPU پایه', 'used-laptop-pricer'); ?></th>
                        <th><?php _e('Storage پایه', 'used-laptop-pricer'); ?></th>
                        <th><?php _e('عملیات', 'used-laptop-pricer'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($models)): ?>
                        <tr>
                            <td colspan="9"><?php _e('هیچ مدلی یافت نشد.', 'used-laptop-pricer'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($models as $model): ?>
                            <tr>
                                <td><?php echo esc_html($model->brand); ?></td>
                                <td><?php echo esc_html($model->model); ?></td>
                                <td><?php echo esc_html($model->release_year); ?></td>
                                <td><?php echo ulp_format_price($model->base_price); ?></td>
                                <td><?php echo esc_html($model->base_cpu); ?></td>
                                <td><?php echo esc_html($model->base_ram); ?></td>
                                <td><?php echo esc_html($model->base_gpu); ?></td>
                                <td><?php echo esc_html($model->base_storage); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=ulp-models&action=edit&id=' . $model->id); ?>" class="button button-small">
                                        <?php _e('ویرایش', 'used-laptop-pricer'); ?>
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=ulp-models&action=delete&id=' . $model->id . '&_wpnonce=' . wp_create_nonce('delete_model_' . $model->id)); ?>" 
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
        ?>
        <div class="ulp-form-container">
            <h2><?php _e('افزودن مدل جدید', 'used-laptop-pricer'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('ulp_add_model', 'ulp_nonce'); ?>
                <input type="hidden" name="ulp_action" value="add_model" />
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="brand"><?php _e('برند', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" name="brand" id="brand" class="regular-text" required />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="model"><?php _e('مدل', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" name="model" id="model" class="regular-text" required />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="release_year"><?php _e('سال عرضه', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="number" name="release_year" id="release_year" min="2010" max="<?php echo date('Y'); ?>" required />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="base_price"><?php _e('قیمت پایه', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="number" name="base_price" id="base_price" min="0" step="0.01" required />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="base_cpu"><?php _e('CPU پایه', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" name="base_cpu" id="base_cpu" class="regular-text" required />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="base_ram"><?php _e('RAM پایه', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" name="base_ram" id="base_ram" class="regular-text" required />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="base_gpu"><?php _e('GPU پایه', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" name="base_gpu" id="base_gpu" class="regular-text" required />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="base_storage"><?php _e('Storage پایه', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" name="base_storage" id="base_storage" class="regular-text" required />
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('افزودن مدل', 'used-laptop-pricer'); ?>" />
                    <a href="<?php echo admin_url('admin.php?page=ulp-models'); ?>" class="button"><?php _e('انصراف', 'used-laptop-pricer'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }
    
    private function display_edit_form() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (!$id) {
            wp_die(__('شناسه مدل نامعتبر است.', 'used-laptop-pricer'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ulp_base_models';
        $model = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        
        if (!$model) {
            wp_die(__('مدل یافت نشد.', 'used-laptop-pricer'));
        }
        
        ?>
        <div class="ulp-form-container">
            <h2><?php _e('ویرایش مدل', 'used-laptop-pricer'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('ulp_edit_model', 'ulp_nonce'); ?>
                <input type="hidden" name="ulp_action" value="edit_model" />
                <input type="hidden" name="model_id" value="<?php echo esc_attr($model->id); ?>" />
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="brand"><?php _e('برند', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" name="brand" id="brand" class="regular-text" value="<?php echo esc_attr($model->brand); ?>" required />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="model"><?php _e('مدل', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" name="model" id="model" class="regular-text" value="<?php echo esc_attr($model->model); ?>" required />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="release_year"><?php _e('سال عرضه', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="number" name="release_year" id="release_year" min="2010" max="<?php echo date('Y'); ?>" value="<?php echo esc_attr($model->release_year); ?>" required />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="base_price"><?php _e('قیمت پایه', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="number" name="base_price" id="base_price" min="0" step="0.01" value="<?php echo esc_attr($model->base_price); ?>" required />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="base_cpu"><?php _e('CPU پایه', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" name="base_cpu" id="base_cpu" class="regular-text" value="<?php echo esc_attr($model->base_cpu); ?>" required />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="base_ram"><?php _e('RAM پایه', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" name="base_ram" id="base_ram" class="regular-text" value="<?php echo esc_attr($model->base_ram); ?>" required />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="base_gpu"><?php _e('GPU پایه', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" name="base_gpu" id="base_gpu" class="regular-text" value="<?php echo esc_attr($model->base_gpu); ?>" required />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="base_storage"><?php _e('Storage پایه', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" name="base_storage" id="base_storage" class="regular-text" value="<?php echo esc_attr($model->base_storage); ?>" required />
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('بروزرسانی مدل', 'used-laptop-pricer'); ?>" />
                    <a href="<?php echo admin_url('admin.php?page=ulp-models'); ?>" class="button"><?php _e('انصراف', 'used-laptop-pricer'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }
    
    private function display_import_form() {
        ?>
        <div class="ulp-form-container">
            <h2><?php _e('ورود مدل‌ها از فایل Excel', 'used-laptop-pricer'); ?></h2>
            
            <div class="ulp-import-info">
                <h3><?php _e('نحوه استفاده:', 'used-laptop-pricer'); ?></h3>
                <ol>
                    <li><?php _e('فایل Excel باید شامل ستون‌های زیر باشد:', 'used-laptop-pricer'); ?></li>
                    <li><?php _e('برند | مدل | سال عرضه | قیمت پایه | CPU پایه | RAM پایه | GPU پایه | Storage پایه', 'used-laptop-pricer'); ?></li>
                    <li><?php _e('اگر مدل قبلاً وجود داشته باشد، بروزرسانی خواهد شد', 'used-laptop-pricer'); ?></li>
                    <li><?php _e('اگر مدل جدید باشد، اضافه خواهد شد', 'used-laptop-pricer'); ?></li>
                </ol>
            </div>
            
            <form method="post" enctype="multipart/form-data" action="">
                <?php wp_nonce_field('ulp_import_excel', 'ulp_nonce'); ?>
                <input type="hidden" name="ulp_action" value="import_excel" />
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="excel_file"><?php _e('فایل Excel', 'used-laptop-pricer'); ?> *</label>
                        </th>
                        <td>
                            <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls" required />
                            <p class="description"><?php _e('فقط فایل‌های Excel (.xlsx, .xls) قابل قبول است', 'used-laptop-pricer'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('آپلود و ورود', 'used-laptop-pricer'); ?>" />
                    <a href="<?php echo admin_url('admin.php?page=ulp-models'); ?>" class="button"><?php _e('انصراف', 'used-laptop-pricer'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }
    
    private function handle_add_model() {
        if (!wp_verify_nonce($_POST['ulp_nonce'], 'ulp_add_model')) {
            wp_die(__('خطای امنیتی', 'used-laptop-pricer'));
        }
        
        $data = array(
            'brand' => sanitize_text_field($_POST['brand']),
            'model' => sanitize_text_field($_POST['model']),
            'release_year' => intval($_POST['release_year']),
            'base_price' => floatval($_POST['base_price']),
            'base_cpu' => sanitize_text_field($_POST['base_cpu']),
            'base_ram' => sanitize_text_field($_POST['base_ram']),
            'base_gpu' => sanitize_text_field($_POST['base_gpu']),
            'base_storage' => sanitize_text_field($_POST['base_storage'])
        );
        
        $result = $this->insert_model($data);
        
        if (is_wp_error($result)) {
            $this->add_notice($result->get_error_message(), 'error');
        } else {
            $this->add_notice(__('مدل با موفقیت اضافه شد.', 'used-laptop-pricer'), 'success');
        }
        
        wp_redirect(admin_url('admin.php?page=ulp-models'));
        exit;
    }
    
    private function handle_edit_model() {
        if (!wp_verify_nonce($_POST['ulp_nonce'], 'ulp_edit_model')) {
            wp_die(__('خطای امنیتی', 'used-laptop-pricer'));
        }
        
        $id = intval($_POST['model_id']);
        $data = array(
            'brand' => sanitize_text_field($_POST['brand']),
            'model' => sanitize_text_field($_POST['model']),
            'release_year' => intval($_POST['release_year']),
            'base_price' => floatval($_POST['base_price']),
            'base_cpu' => sanitize_text_field($_POST['base_cpu']),
            'base_ram' => sanitize_text_field($_POST['base_ram']),
            'base_gpu' => sanitize_text_field($_POST['base_gpu']),
            'base_storage' => sanitize_text_field($_POST['base_storage'])
        );
        
        $result = $this->update_model($id, $data);
        
        if (is_wp_error($result)) {
            $this->add_notice($result->get_error_message(), 'error');
        } else {
            $this->add_notice(__('مدل با موفقیت بروزرسانی شد.', 'used-laptop-pricer'), 'success');
        }
        
        wp_redirect(admin_url('admin.php?page=ulp-models'));
        exit;
    }
    
    private function handle_delete_model() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_model_' . $id)) {
            wp_die(__('خطای امنیتی', 'used-laptop-pricer'));
        }
        
        $result = $this->delete_model($id);
        
        if (is_wp_error($result)) {
            $this->add_notice($result->get_error_message(), 'error');
        } else {
            $this->add_notice(__('مدل با موفقیت حذف شد.', 'used-laptop-pricer'), 'success');
        }
        
        wp_redirect(admin_url('admin.php?page=ulp-models'));
        exit;
    }
    
    private function handle_import_excel() {
        if (!wp_verify_nonce($_POST['ulp_nonce'], 'ulp_import_excel')) {
            wp_die(__('خطای امنیتی', 'used-laptop-pricer'));
        }
        
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $this->add_notice(__('خطا در آپلود فایل', 'used-laptop-pricer'), 'error');
            wp_redirect(admin_url('admin.php?page=ulp-models&action=import'));
            exit;
        }
        
        $file = $_FILES['excel_file'];
        $validation = ulp_validate_excel_file($file);
        
        if (is_wp_error($validation)) {
            $this->add_notice($validation->get_error_message(), 'error');
            wp_redirect(admin_url('admin.php?page=ulp-models&action=import'));
            exit;
        }
        
        // Import logic will be handled by Excel Import class
        $importer = new ULP_Excel_Import();
        $result = $importer->import_models($file);
        
        if (is_wp_error($result)) {
            $this->add_notice($result->get_error_message(), 'error');
        } else {
            $this->add_notice(sprintf(__('%d مدل با موفقیت وارد شد.', 'used-laptop-pricer'), $result), 'success');
        }
        
        wp_redirect(admin_url('admin.php?page=ulp-models'));
        exit;
    }
    
    private function insert_model($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_base_models';
        
        $result = $wpdb->insert(
            $table,
            $data,
            array('%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('insert_failed', __('خطا در ذخیره مدل', 'used-laptop-pricer'));
        }
        
        return $wpdb->insert_id;
    }
    
    private function update_model($id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_base_models';
        
        $result = $wpdb->update(
            $table,
            $data,
            array('id' => $id),
            array('%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('update_failed', __('خطا در بروزرسانی مدل', 'used-laptop-pricer'));
        }
        
        return true;
    }
    
    private function delete_model($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_base_models';
        
        $result = $wpdb->delete(
            $table,
            array('id' => $id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('delete_failed', __('خطا در حذف مدل', 'used-laptop-pricer'));
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