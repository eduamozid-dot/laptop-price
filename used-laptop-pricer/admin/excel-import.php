<?php
/**
 * Excel Import Functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ULP_Excel_Import {
    
    public function __construct() {
        add_action('wp_ajax_ulp_export_models', array($this, 'export_models'));
    }
    
    /**
     * Import models from Excel file
     */
    public function import_models($file) {
        try {
            // Check if PhpSpreadsheet is available
            if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                return new WP_Error('phpspreadsheet_missing', __('کتابخانه PhpSpreadsheet نصب نشده است.', 'used-laptop-pricer'));
            }
            
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Remove header row
            $header = array_shift($rows);
            
            // Validate header
            $expected_headers = array('برند', 'مدل', 'سال عرضه', 'قیمت پایه', 'CPU پایه', 'RAM پایه', 'GPU پایه', 'Storage پایه');
            if (!$this->validate_header($header, $expected_headers)) {
                return new WP_Error('invalid_header', __('ساختار فایل Excel نامعتبر است.', 'used-laptop-pricer'));
            }
            
            $imported_count = 0;
            $errors = array();
            
            foreach ($rows as $row_index => $row) {
                $row_number = $row_index + 2; // +2 because we removed header and arrays are 0-indexed
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Validate required fields
                if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3])) {
                    $errors[] = sprintf(__('ردیف %d: فیلدهای الزامی خالی هستند', 'used-laptop-pricer'), $row_number);
                    continue;
                }
                
                $data = array(
                    'brand' => trim($row[0]),
                    'model' => trim($row[1]),
                    'release_year' => intval($row[2]),
                    'base_price' => floatval($row[3]),
                    'base_cpu' => trim($row[4]),
                    'base_ram' => trim($row[5]),
                    'base_gpu' => trim($row[6]),
                    'base_storage' => trim($row[7])
                );
                
                // Validate data
                $validation_result = $this->validate_model_data($data, $row_number);
                if (is_wp_error($validation_result)) {
                    $errors[] = $validation_result->get_error_message();
                    continue;
                }
                
                // Insert or update model
                $result = $this->insert_or_update_model($data);
                if (is_wp_error($result)) {
                    $errors[] = sprintf(__('ردیف %d: %s', 'used-laptop-pricer'), $row_number, $result->get_error_message());
                } else {
                    $imported_count++;
                }
            }
            
            // If there were errors, return them
            if (!empty($errors)) {
                $error_message = __('خطاهای زیر در ورود داده‌ها رخ داد:', 'used-laptop-pricer') . "\n" . implode("\n", $errors);
                return new WP_Error('import_errors', $error_message);
            }
            
            return $imported_count;
            
        } catch (Exception $e) {
            return new WP_Error('import_failed', __('خطا در خواندن فایل Excel: ', 'used-laptop-pricer') . $e->getMessage());
        }
    }
    
    /**
     * Validate Excel header
     */
    private function validate_header($header, $expected_headers) {
        if (count($header) < count($expected_headers)) {
            return false;
        }
        
        // Check if all expected headers are present (case-insensitive)
        $header_lower = array_map('strtolower', array_map('trim', $header));
        $expected_lower = array_map('strtolower', $expected_headers);
        
        foreach ($expected_lower as $expected) {
            if (!in_array($expected, $header_lower)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate model data
     */
    private function validate_model_data($data, $row_number) {
        // Validate brand
        if (strlen($data['brand']) > 100) {
            return new WP_Error('invalid_brand', sprintf(__('ردیف %d: نام برند بیش از حد طولانی است', 'used-laptop-pricer'), $row_number));
        }
        
        // Validate model
        if (strlen($data['model']) > 200) {
            return new WP_Error('invalid_model', sprintf(__('ردیف %d: نام مدل بیش از حد طولانی است', 'used-laptop-pricer'), $row_number));
        }
        
        // Validate release year
        if ($data['release_year'] < 2010 || $data['release_year'] > date('Y')) {
            return new WP_Error('invalid_year', sprintf(__('ردیف %d: سال عرضه باید بین ۲۰۱۰ و سال جاری باشد', 'used-laptop-pricer'), $row_number));
        }
        
        // Validate base price
        if ($data['base_price'] <= 0) {
            return new WP_Error('invalid_price', sprintf(__('ردیف %d: قیمت پایه باید بیشتر از صفر باشد', 'used-laptop-pricer'), $row_number));
        }
        
        // Validate CPU
        if (strlen($data['base_cpu']) > 200) {
            return new WP_Error('invalid_cpu', sprintf(__('ردیف %d: نام CPU بیش از حد طولانی است', 'used-laptop-pricer'), $row_number));
        }
        
        // Validate RAM
        if (strlen($data['base_ram']) > 50) {
            return new WP_Error('invalid_ram', sprintf(__('ردیف %d: نام RAM بیش از حد طولانی است', 'used-laptop-pricer'), $row_number));
        }
        
        // Validate GPU
        if (strlen($data['base_gpu']) > 200) {
            return new WP_Error('invalid_gpu', sprintf(__('ردیف %d: نام GPU بیش از حد طولانی است', 'used-laptop-pricer'), $row_number));
        }
        
        // Validate Storage
        if (strlen($data['base_storage']) > 100) {
            return new WP_Error('invalid_storage', sprintf(__('ردیف %d: نام Storage بیش از حد طولانی است', 'used-laptop-pricer'), $row_number));
        }
        
        return true;
    }
    
    /**
     * Insert or update model in database
     */
    private function insert_or_update_model($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_base_models';
        
        // Check if model already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table WHERE brand = %s AND model = %s",
            $data['brand'],
            $data['model']
        ));
        
        if ($existing) {
            // Update existing model
            $result = $wpdb->update(
                $table,
                $data,
                array('id' => $existing->id),
                array('%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            if ($result === false) {
                return new WP_Error('update_failed', __('خطا در بروزرسانی مدل', 'used-laptop-pricer'));
            }
        } else {
            // Insert new model
            $result = $wpdb->insert(
                $table,
                $data,
                array('%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                return new WP_Error('insert_failed', __('خطا در ذخیره مدل', 'used-laptop-pricer'));
            }
        }
        
        return true;
    }
    
    /**
     * Export models to Excel
     */
    public function export_models() {
        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'used-laptop-pricer'));
        }
        
        try {
            // Check if PhpSpreadsheet is available
            if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                wp_die(__('کتابخانه PhpSpreadsheet نصب نشده است.', 'used-laptop-pricer'));
            }
            
            $models = ulp_get_base_models();
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $headers = array('برند', 'مدل', 'سال عرضه', 'قیمت پایه', 'CPU پایه', 'RAM پایه', 'GPU پایه', 'Storage پایه');
            $worksheet->fromArray($headers, null, 'A1');
            
            // Style headers
            $headerStyle = array(
                'font' => array(
                    'bold' => true,
                    'color' => array('rgb' => 'FFFFFF'),
                ),
                'fill' => array(
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => array('rgb' => '4472C4'),
                ),
            );
            $worksheet->getStyle('A1:H1')->applyFromArray($headerStyle);
            
            // Add data
            $row = 2;
            foreach ($models as $model) {
                $worksheet->setCellValue('A' . $row, $model->brand);
                $worksheet->setCellValue('B' . $row, $model->model);
                $worksheet->setCellValue('C' . $row, $model->release_year);
                $worksheet->setCellValue('D' . $row, $model->base_price);
                $worksheet->setCellValue('E' . $row, $model->base_cpu);
                $worksheet->setCellValue('F' . $row, $model->base_ram);
                $worksheet->setCellValue('G' . $row, $model->base_gpu);
                $worksheet->setCellValue('H' . $row, $model->base_storage);
                $row++;
            }
            
            // Auto-size columns
            foreach (range('A', 'H') as $column) {
                $worksheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Create writer
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            
            // Set headers for download
            $filename = 'laptop-models-' . date('Y-m-d') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            // Output file
            $writer->save('php://output');
            exit;
            
        } catch (Exception $e) {
            wp_die(__('خطا در ایجاد فایل Excel: ', 'used-laptop-pricer') . $e->getMessage());
        }
    }
    
    /**
     * Create sample Excel template
     */
    public function create_sample_template() {
        try {
            if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                return new WP_Error('phpspreadsheet_missing', __('کتابخانه PhpSpreadsheet نصب نشده است.', 'used-laptop-pricer'));
            }
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $headers = array('برند', 'مدل', 'سال عرضه', 'قیمت پایه', 'CPU پایه', 'RAM پایه', 'GPU پایه', 'Storage پایه');
            $worksheet->fromArray($headers, null, 'A1');
            
            // Style headers
            $headerStyle = array(
                'font' => array(
                    'bold' => true,
                    'color' => array('rgb' => 'FFFFFF'),
                ),
                'fill' => array(
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => array('rgb' => '4472C4'),
                ),
            );
            $worksheet->getStyle('A1:H1')->applyFromArray($headerStyle);
            
            // Add sample data
            $sample_data = array(
                array('Dell', 'Inspiron 15 3000', '2021', '25000000', 'Intel Core i5-1135G7', '8GB DDR4', 'Intel UHD Graphics', '512GB SSD'),
                array('HP', 'Pavilion 15', '2022', '30000000', 'AMD Ryzen 5 5500U', '16GB DDR4', 'AMD Radeon Graphics', '1TB SSD'),
                array('Lenovo', 'ThinkPad E14', '2020', '28000000', 'Intel Core i7-10510U', '8GB DDR4', 'Intel UHD Graphics', '256GB SSD'),
            );
            
            $row = 2;
            foreach ($sample_data as $data) {
                $worksheet->fromArray($data, null, 'A' . $row);
                $row++;
            }
            
            // Auto-size columns
            foreach (range('A', 'H') as $column) {
                $worksheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Create writer
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            
            // Save to file
            $filename = ULP_PLUGIN_PATH . 'templates/sample-models-template.xlsx';
            $writer->save($filename);
            
            return $filename;
            
        } catch (Exception $e) {
            return new WP_Error('template_creation_failed', __('خطا در ایجاد قالب نمونه: ', 'used-laptop-pricer') . $e->getMessage());
        }
    }
}