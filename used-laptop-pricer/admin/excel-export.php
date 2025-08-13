<?php
/**
 * Excel Export Functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ULP_Excel_Export {
    
    public function __construct() {
        add_action('wp_ajax_ulp_export_parts', array($this, 'export_parts'));
        add_action('wp_ajax_ulp_export_all_data', array($this, 'export_all_data'));
    }
    
    /**
     * Export parts to Excel
     */
    public function export_parts() {
        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'used-laptop-pricer'));
        }
        
        try {
            // Check if PhpSpreadsheet is available
            if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                wp_die(__('کتابخانه PhpSpreadsheet نصب نشده است.', 'used-laptop-pricer'));
            }
            
            $parts = ulp_get_parts_prices();
            $part_types = ulp_get_part_type_options();
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $headers = array('نوع قطعه', 'نام قطعه', 'مشخصات', 'قیمت');
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
            $worksheet->getStyle('A1:D1')->applyFromArray($headerStyle);
            
            // Add data
            $row = 2;
            foreach ($parts as $part) {
                $worksheet->setCellValue('A' . $row, $part_types[$part->part_type] ?? $part->part_type);
                $worksheet->setCellValue('B' . $row, $part->part_name);
                $worksheet->setCellValue('C' . $row, $part->part_specs);
                $worksheet->setCellValue('D' . $row, $part->price);
                $row++;
            }
            
            // Auto-size columns
            foreach (range('A', 'D') as $column) {
                $worksheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Create writer
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            
            // Set headers for download
            $filename = 'laptop-parts-' . date('Y-m-d') . '.xlsx';
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
     * Export all data to Excel (multiple sheets)
     */
    public function export_all_data() {
        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'used-laptop-pricer'));
        }
        
        try {
            // Check if PhpSpreadsheet is available
            if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                wp_die(__('کتابخانه PhpSpreadsheet نصب نشده است.', 'used-laptop-pricer'));
            }
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            
            // Export models
            $this->export_models_sheet($spreadsheet);
            
            // Export parts
            $this->export_parts_sheet($spreadsheet);
            
            // Export settings
            $this->export_settings_sheet($spreadsheet);
            
            // Create writer
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            
            // Set headers for download
            $filename = 'laptop-pricer-data-' . date('Y-m-d') . '.xlsx';
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
     * Export models to a sheet
     */
    private function export_models_sheet($spreadsheet) {
        $models = ulp_get_base_models();
        
        // Create new sheet
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('مدل‌های پایه');
        
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
    }
    
    /**
     * Export parts to a sheet
     */
    private function export_parts_sheet($spreadsheet) {
        $parts = ulp_get_parts_prices();
        $part_types = ulp_get_part_type_options();
        
        // Create new sheet
        $worksheet = $spreadsheet->createSheet();
        $worksheet->setTitle('قطعات');
        
        // Set headers
        $headers = array('نوع قطعه', 'نام قطعه', 'مشخصات', 'قیمت');
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
        $worksheet->getStyle('A1:D1')->applyFromArray($headerStyle);
        
        // Add data
        $row = 2;
        foreach ($parts as $part) {
            $worksheet->setCellValue('A' . $row, $part_types[$part->part_type] ?? $part->part_type);
            $worksheet->setCellValue('B' . $row, $part->part_name);
            $worksheet->setCellValue('C' . $row, $part->part_specs);
            $worksheet->setCellValue('D' . $row, $part->price);
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'D') as $column) {
            $worksheet->getColumnDimension($column)->setAutoSize(true);
        }
    }
    
    /**
     * Export settings to a sheet
     */
    private function export_settings_sheet($spreadsheet) {
        $settings = ulp_get_settings();
        
        // Create new sheet
        $worksheet = $spreadsheet->createSheet();
        $worksheet->setTitle('تنظیمات');
        
        // Set headers
        $headers = array('تنظیم', 'مقدار', 'توضیحات');
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
        $worksheet->getStyle('A1:C1')->applyFromArray($headerStyle);
        
        // Add settings data
        $row = 2;
        
        // General settings
        $worksheet->setCellValue('A' . $row, 'واحد پول');
        $worksheet->setCellValue('B' . $row, $settings['currency']);
        $worksheet->setCellValue('C' . $row, 'واحد پول اصلی');
        $row++;
        
        $worksheet->setCellValue('A' . $row, 'نماد پول');
        $worksheet->setCellValue('B' . $row, $settings['currency_symbol']);
        $worksheet->setCellValue('C' . $row, 'نماد نمایش پول');
        $row++;
        
        // Depreciation settings
        $worksheet->setCellValue('A' . $row, 'استهلاک سال اول (%)');
        $worksheet->setCellValue('B' . $row, $settings['depreciation_first_year']);
        $worksheet->setCellValue('C' . $row, 'درصد کاهش قیمت در سال اول');
        $row++;
        
        $worksheet->setCellValue('A' . $row, 'استهلاک سال دوم (%)');
        $worksheet->setCellValue('B' . $row, $settings['depreciation_second_year']);
        $worksheet->setCellValue('C' . $row, 'درصد کاهش قیمت در سال دوم');
        $row++;
        
        $worksheet->setCellValue('A' . $row, 'استهلاک سالانه (%)');
        $worksheet->setCellValue('B' . $row, $settings['depreciation_annual']);
        $worksheet->setCellValue('C' . $row, 'درصد کاهش قیمت سالانه از سال سوم');
        $row++;
        
        // Condition multipliers
        $worksheet->setCellValue('A' . $row, 'ضرایب وضعیت ظاهری');
        $worksheet->setCellValue('B' . $row, '');
        $worksheet->setCellValue('C' . $row, 'ضرایب قیمت برای هر وضعیت');
        $row++;
        
        foreach ($settings['condition_multipliers'] as $condition => $multiplier) {
            $condition_label = ulp_get_condition_label($condition);
            $worksheet->setCellValue('A' . $row, '  ' . $condition_label);
            $worksheet->setCellValue('B' . $row, $multiplier);
            $worksheet->setCellValue('C' . $row, 'ضریب برای وضعیت ' . $condition_label);
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'C') as $column) {
            $worksheet->getColumnDimension($column)->setAutoSize(true);
        }
    }
    
    /**
     * Create backup of all plugin data
     */
    public function create_backup() {
        if (!current_user_can('manage_options')) {
            return new WP_Error('unauthorized', __('دسترسی غیرمجاز', 'used-laptop-pricer'));
        }
        
        try {
            $backup_data = array(
                'models' => ulp_get_base_models(),
                'parts' => ulp_get_parts_prices(),
                'settings' => ulp_get_settings(),
                'export_date' => current_time('mysql'),
                'plugin_version' => ULP_PLUGIN_VERSION
            );
            
            $backup_file = ULP_PLUGIN_PATH . 'backups/backup-' . date('Y-m-d-H-i-s') . '.json';
            
            // Create backups directory if it doesn't exist
            $backup_dir = dirname($backup_file);
            if (!file_exists($backup_dir)) {
                wp_mkdir_p($backup_dir);
            }
            
            // Write backup file
            $result = file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            if ($result === false) {
                return new WP_Error('backup_failed', __('خطا در ایجاد فایل پشتیبان', 'used-laptop-pricer'));
            }
            
            return $backup_file;
            
        } catch (Exception $e) {
            return new WP_Error('backup_failed', __('خطا در ایجاد پشتیبان: ', 'used-laptop-pricer') . $e->getMessage());
        }
    }
    
    /**
     * Restore from backup file
     */
    public function restore_backup($backup_file) {
        if (!current_user_can('manage_options')) {
            return new WP_Error('unauthorized', __('دسترسی غیرمجاز', 'used-laptop-pricer'));
        }
        
        try {
            if (!file_exists($backup_file)) {
                return new WP_Error('file_not_found', __('فایل پشتیبان یافت نشد', 'used-laptop-pricer'));
            }
            
            $backup_data = json_decode(file_get_contents($backup_file), true);
            
            if (!$backup_data) {
                return new WP_Error('invalid_backup', __('فایل پشتیبان نامعتبر است', 'used-laptop-pricer'));
            }
            
            global $wpdb;
            
            // Restore models
            if (isset($backup_data['models'])) {
                $table = $wpdb->prefix . 'ulp_base_models';
                $wpdb->query("TRUNCATE TABLE $table");
                
                foreach ($backup_data['models'] as $model) {
                    $wpdb->insert($table, (array) $model);
                }
            }
            
            // Restore parts
            if (isset($backup_data['parts'])) {
                $table = $wpdb->prefix . 'ulp_parts_prices';
                $wpdb->query("TRUNCATE TABLE $table");
                
                foreach ($backup_data['parts'] as $part) {
                    $wpdb->insert($table, (array) $part);
                }
            }
            
            // Restore settings
            if (isset($backup_data['settings'])) {
                update_option('ulp_settings', $backup_data['settings']);
            }
            
            return true;
            
        } catch (Exception $e) {
            return new WP_Error('restore_failed', __('خطا در بازگردانی پشتیبان: ', 'used-laptop-pricer') . $e->getMessage());
        }
    }
}