<?php
/**
 * Model Manager for Used Laptop Pricer
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULP_Model_Manager {
    
    public function __construct() {
        // Constructor
    }
    
    /**
     * Handle form actions
     */
    public function handle_actions() {
        // Handle delete action
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $this->delete_model(intval($_GET['id']));
        }
        
        // Handle form submission
        if (isset($_POST['ulp_save_model']) && wp_verify_nonce($_POST['ulp_model_nonce'], 'ulp_save_model')) {
            $this->save_model();
        }
        
        // Handle Excel import
        if (isset($_POST['ulp_import_excel']) && wp_verify_nonce($_POST['ulp_import_nonce'], 'ulp_import_excel')) {
            $this->import_excel();
        }
        
        // Handle Excel export
        if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
            $this->export_excel();
        }
    }
    
    /**
     * Save model
     */
    private function save_model() {
        $model_data = array(
            'brand' => sanitize_text_field($_POST['brand']),
            'model' => sanitize_text_field($_POST['model']),
            'release_year' => intval($_POST['release_year']),
            'base_price' => floatval($_POST['base_price']),
            'base_cpu' => sanitize_text_field($_POST['base_cpu']),
            'base_ram' => sanitize_text_field($_POST['base_ram']),
            'base_gpu' => sanitize_text_field($_POST['base_gpu']),
            'base_storage' => sanitize_text_field($_POST['base_storage'])
        );
        
        if (isset($_POST['model_id']) && $_POST['model_id']) {
            $model_data['id'] = intval($_POST['model_id']);
        }
        
        $result = ULP_Database::save_laptop_model($model_data);
        
        if ($result !== false) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . 
                     __('مدل لپ‌تاپ با موفقیت ذخیره شد.', 'used-laptop-pricer') . 
                     '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . 
                     __('خطا در ذخیره مدل لپ‌تاپ.', 'used-laptop-pricer') . 
                     '</p></div>';
            });
        }
    }
    
    /**
     * Delete model
     */
    private function delete_model($id) {
        $result = ULP_Database::delete_laptop_model($id);
        
        if ($result !== false) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . 
                     __('مدل لپ‌تاپ با موفقیت حذف شد.', 'used-laptop-pricer') . 
                     '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . 
                     __('خطا در حذف مدل لپ‌تاپ.', 'used-laptop-pricer') . 
                     '</p></div>';
            });
        }
    }
    
    /**
     * Import Excel file
     */
    private function import_excel() {
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . 
                     __('خطا در آپلود فایل.', 'used-laptop-pricer') . 
                     '</p></div>';
            });
            return;
        }
        
        $file = $_FILES['excel_file'];
        
        if (!ulp_is_excel_file($file['name'])) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . 
                     __('فایل باید از نوع Excel باشد (.xlsx یا .xls).', 'used-laptop-pricer') . 
                     '</p></div>';
            });
            return;
        }
        
        // Load PhpSpreadsheet
        require_once ULP_PLUGIN_PATH . 'vendor/autoload.php';
        
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Skip header row
            array_shift($rows);
            
            $imported = 0;
            $errors = 0;
            
            foreach ($rows as $row) {
                if (count($row) >= 8) {
                    $model_data = array(
                        'brand' => trim($row[0]),
                        'model' => trim($row[1]),
                        'release_year' => intval($row[2]),
                        'base_price' => floatval($row[3]),
                        'base_cpu' => trim($row[4]),
                        'base_ram' => trim($row[5]),
                        'base_gpu' => trim($row[6]),
                        'base_storage' => trim($row[7])
                    );
                    
                    if (!empty($model_data['brand']) && !empty($model_data['model'])) {
                        $result = ULP_Database::save_laptop_model($model_data);
                        if ($result !== false) {
                            $imported++;
                        } else {
                            $errors++;
                        }
                    }
                }
            }
            
            add_action('admin_notices', function() use ($imported, $errors) {
                echo '<div class="notice notice-success is-dismissible"><p>' . 
                     sprintf(__('%d مدل با موفقیت وارد شد. %d خطا.', 'used-laptop-pricer'), $imported, $errors) . 
                     '</p></div>';
            });
            
        } catch (Exception $e) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error is-dismissible"><p>' . 
                     __('خطا در خواندن فایل Excel: ', 'used-laptop-pricer') . $e->getMessage() . 
                     '</p></div>';
            });
        }
    }
    
    /**
     * Export to Excel
     */
    private function export_excel() {
        $models = ULP_Database::get_laptop_models();
        
        // Load PhpSpreadsheet
        require_once ULP_PLUGIN_PATH . 'vendor/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $headers = array(
            'برند',
            'مدل',
            'سال عرضه',
            'قیمت پایه',
            'CPU پایه',
            'RAM پایه',
            'GPU پایه',
            'Storage پایه'
        );
        
        $worksheet->fromArray($headers, null, 'A1');
        
        // Add data
        $row = 2;
        foreach ($models as $model) {
            $worksheet->fromArray(array(
                $model->brand,
                $model->model,
                $model->release_year,
                $model->base_price,
                $model->base_cpu,
                $model->base_ram,
                $model->base_gpu,
                $model->base_storage
            ), null, "A$row");
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'H') as $column) {
            $worksheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Create writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="laptop-models-' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
    
    /**
     * Render the page
     */
    public function render_page() {
        $models = ULP_Database::get_laptop_models();
        $brands = ULP_Database::get_brands();
        $years_range = ulp_get_years_range();
        
        // Get model for editing
        $edit_model = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $edit_model = ULP_Database::get_laptop_model(intval($_GET['id']));
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('مدیریت مدل‌های پایه', 'used-laptop-pricer'); ?></h1>
            
            <!-- Add/Edit Form -->
            <div class="ulp-form-section">
                <h2><?php echo $edit_model ? __('ویرایش مدل', 'used-laptop-pricer') : __('افزودن مدل جدید', 'used-laptop-pricer'); ?></h2>
                
                <form method="post" action="">
                    <?php wp_nonce_field('ulp_save_model', 'ulp_model_nonce'); ?>
                    
                    <?php if ($edit_model): ?>
                        <input type="hidden" name="model_id" value="<?php echo $edit_model->id; ?>" />
                    <?php endif; ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="brand"><?php _e('برند', 'used-laptop-pricer'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="brand" 
                                       name="brand" 
                                       value="<?php echo $edit_model ? esc_attr($edit_model->brand) : ''; ?>" 
                                       class="regular-text" 
                                       required />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="model"><?php _e('مدل', 'used-laptop-pricer'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="model" 
                                       name="model" 
                                       value="<?php echo $edit_model ? esc_attr($edit_model->model) : ''; ?>" 
                                       class="regular-text" 
                                       required />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="release_year"><?php _e('سال عرضه', 'used-laptop-pricer'); ?> *</label>
                            </th>
                            <td>
                                <select id="release_year" name="release_year" required>
                                    <option value=""><?php _e('انتخاب کنید', 'used-laptop-pricer'); ?></option>
                                    <?php foreach ($years_range as $year): ?>
                                        <option value="<?php echo $year; ?>" 
                                                <?php echo ($edit_model && $edit_model->release_year == $year) ? 'selected' : ''; ?>>
                                            <?php echo $year; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="base_price"><?php _e('قیمت پایه', 'used-laptop-pricer'); ?> *</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="base_price" 
                                       name="base_price" 
                                       value="<?php echo $edit_model ? esc_attr($edit_model->base_price) : ''; ?>" 
                                       min="0" 
                                       step="1000" 
                                       class="regular-text" 
                                       required />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="base_cpu"><?php _e('CPU پایه', 'used-laptop-pricer'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="base_cpu" 
                                       name="base_cpu" 
                                       value="<?php echo $edit_model ? esc_attr($edit_model->base_cpu) : ''; ?>" 
                                       class="regular-text" 
                                       required />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="base_ram"><?php _e('RAM پایه', 'used-laptop-pricer'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="base_ram" 
                                       name="base_ram" 
                                       value="<?php echo $edit_model ? esc_attr($edit_model->base_ram) : ''; ?>" 
                                       class="regular-text" 
                                       required />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="base_gpu"><?php _e('GPU پایه', 'used-laptop-pricer'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="base_gpu" 
                                       name="base_gpu" 
                                       value="<?php echo $edit_model ? esc_attr($edit_model->base_gpu) : ''; ?>" 
                                       class="regular-text" 
                                       required />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="base_storage"><?php _e('Storage پایه', 'used-laptop-pricer'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="base_storage" 
                                       name="base_storage" 
                                       value="<?php echo $edit_model ? esc_attr($edit_model->base_storage) : ''; ?>" 
                                       class="regular-text" 
                                       required />
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" 
                               name="ulp_save_model" 
                               class="button button-primary" 
                               value="<?php echo $edit_model ? __('بروزرسانی', 'used-laptop-pricer') : __('افزودن', 'used-laptop-pricer'); ?>" />
                        
                        <?php if ($edit_model): ?>
                            <a href="<?php echo admin_url('admin.php?page=used-laptop-pricer-models'); ?>" class="button">
                                <?php _e('انصراف', 'used-laptop-pricer'); ?>
                            </a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
            
            <!-- Excel Import/Export -->
            <div class="ulp-excel-section">
                <h2><?php _e('آپلود و خروجی Excel', 'used-laptop-pricer'); ?></h2>
                
                <div class="ulp-excel-import">
                    <h3><?php _e('آپلود فایل Excel', 'used-laptop-pricer'); ?></h3>
                    <p><?php _e('فایل Excel باید شامل ستون‌های: برند، مدل، سال عرضه، قیمت پایه، CPU پایه، RAM پایه، GPU پایه، Storage پایه باشد.', 'used-laptop-pricer'); ?></p>
                    
                    <form method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field('ulp_import_excel', 'ulp_import_nonce'); ?>
                        <input type="file" name="excel_file" accept=".xlsx,.xls" required />
                        <input type="submit" name="ulp_import_excel" class="button button-secondary" value="<?php _e('آپلود', 'used-laptop-pricer'); ?>" />
                    </form>
                </div>
                
                <div class="ulp-excel-export">
                    <h3><?php _e('خروجی Excel', 'used-laptop-pricer'); ?></h3>
                    <p><?php _e('دانلود لیست تمام مدل‌ها به صورت فایل Excel:', 'used-laptop-pricer'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=used-laptop-pricer-models&action=export_excel'); ?>" class="button button-secondary">
                        <?php _e('دانلود Excel', 'used-laptop-pricer'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Models List -->
            <div class="ulp-models-list">
                <h2><?php _e('لیست مدل‌ها', 'used-laptop-pricer'); ?></h2>
                
                <?php if (empty($models)): ?>
                    <p><?php _e('هیچ مدلی یافت نشد.', 'used-laptop-pricer'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('برند', 'used-laptop-pricer'); ?></th>
                                <th><?php _e('مدل', 'used-laptop-pricer'); ?></th>
                                <th><?php _e('سال عرضه', 'used-laptop-pricer'); ?></th>
                                <th><?php _e('قیمت پایه', 'used-laptop-pricer'); ?></th>
                                <th><?php _e('CPU پایه', 'used-laptop-pricer'); ?></th>
                                <th><?php _e('RAM پایه', 'used-laptop-pricer'); ?></th>
                                <th><?php _e('عملیات', 'used-laptop-pricer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($models as $model): ?>
                                <tr>
                                    <td><?php echo esc_html($model->brand); ?></td>
                                    <td><?php echo esc_html($model->model); ?></td>
                                    <td><?php echo esc_html($model->release_year); ?></td>
                                    <td><?php echo ulp_format_price($model->base_price); ?></td>
                                    <td><?php echo esc_html($model->base_cpu); ?></td>
                                    <td><?php echo esc_html($model->base_ram); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=used-laptop-pricer-models&action=edit&id=' . $model->id); ?>" 
                                           class="button button-small">
                                            <?php _e('ویرایش', 'used-laptop-pricer'); ?>
                                        </a>
                                        <a href="<?php echo admin_url('admin.php?page=used-laptop-pricer-models&action=delete&id=' . $model->id); ?>" 
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
        </div>
        <?php
    }
}