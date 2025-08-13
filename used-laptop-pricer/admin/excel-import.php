<?php
/**
 * Excel Import functionality for Used Laptop Pricer
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULP_Excel_Import {
    
    /**
     * Import laptop models from Excel file
     */
    public static function import_laptop_models($file_path) {
        try {
            // Load PhpSpreadsheet
            require_once ULP_PLUGIN_PATH . 'vendor/autoload.php';
            
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Skip header row
            array_shift($rows);
            
            $imported = 0;
            $errors = 0;
            $error_messages = array();
            
            foreach ($rows as $row_index => $row) {
                $row_number = $row_index + 2; // +2 because we skipped header and arrays are 0-indexed
                
                if (count($row) < 8) {
                    $errors++;
                    $error_messages[] = sprintf(
                        __('خطا در ردیف %d: تعداد ستون‌ها ناکافی است', 'used-laptop-pricer'),
                        $row_number
                    );
                    continue;
                }
                
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
                
                // Validate required fields
                if (empty($model_data['brand']) || empty($model_data['model'])) {
                    $errors++;
                    $error_messages[] = sprintf(
                        __('خطا در ردیف %d: برند و مدل الزامی هستند', 'used-laptop-pricer'),
                        $row_number
                    );
                    continue;
                }
                
                // Validate year
                if (!ulp_validate_year($model_data['release_year'])) {
                    $errors++;
                    $error_messages[] = sprintf(
                        __('خطا در ردیف %d: سال عرضه نامعتبر است', 'used-laptop-pricer'),
                        $row_number
                    );
                    continue;
                }
                
                // Validate price
                if ($model_data['base_price'] <= 0) {
                    $errors++;
                    $error_messages[] = sprintf(
                        __('خطا در ردیف %d: قیمت پایه باید مثبت باشد', 'used-laptop-pricer'),
                        $row_number
                    );
                    continue;
                }
                
                // Save to database
                $result = ULP_Database::save_laptop_model($model_data);
                if ($result !== false) {
                    $imported++;
                } else {
                    $errors++;
                    $error_messages[] = sprintf(
                        __('خطا در ردیف %d: خطا در ذخیره در دیتابیس', 'used-laptop-pricer'),
                        $row_number
                    );
                }
            }
            
            return array(
                'success' => true,
                'imported' => $imported,
                'errors' => $errors,
                'error_messages' => $error_messages
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => __('خطا در خواندن فایل Excel: ', 'used-laptop-pricer') . $e->getMessage()
            );
        }
    }
    
    /**
     * Import parts from Excel file
     */
    public static function import_parts($file_path) {
        try {
            // Load PhpSpreadsheet
            require_once ULP_PLUGIN_PATH . 'vendor/autoload.php';
            
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Skip header row
            array_shift($rows);
            
            $imported = 0;
            $errors = 0;
            $error_messages = array();
            
            foreach ($rows as $row_index => $row) {
                $row_number = $row_index + 2;
                
                if (count($row) < 4) {
                    $errors++;
                    $error_messages[] = sprintf(
                        __('خطا در ردیف %d: تعداد ستون‌ها ناکافی است', 'used-laptop-pricer'),
                        $row_number
                    );
                    continue;
                }
                
                $part_data = array(
                    'part_type' => trim($row[0]),
                    'part_name' => trim($row[1]),
                    'part_specs' => trim($row[2]),
                    'price' => floatval($row[3])
                );
                
                // Validate required fields
                if (empty($part_data['part_type']) || empty($part_data['part_name'])) {
                    $errors++;
                    $error_messages[] = sprintf(
                        __('خطا در ردیف %d: نوع و نام قطعه الزامی هستند', 'used-laptop-pricer'),
                        $row_number
                    );
                    continue;
                }
                
                // Validate part type
                $valid_types = array('cpu', 'ram', 'gpu', 'ssd', 'hdd');
                if (!in_array($part_data['part_type'], $valid_types)) {
                    $errors++;
                    $error_messages[] = sprintf(
                        __('خطا در ردیف %d: نوع قطعه نامعتبر است', 'used-laptop-pricer'),
                        $row_number
                    );
                    continue;
                }
                
                // Validate price
                if ($part_data['price'] < 0) {
                    $errors++;
                    $error_messages[] = sprintf(
                        __('خطا در ردیف %d: قیمت نمی‌تواند منفی باشد', 'used-laptop-pricer'),
                        $row_number
                    );
                    continue;
                }
                
                // Save to database
                $result = ULP_Database::save_part_price($part_data);
                if ($result !== false) {
                    $imported++;
                } else {
                    $errors++;
                    $error_messages[] = sprintf(
                        __('خطا در ردیف %d: خطا در ذخیره در دیتابیس', 'used-laptop-pricer'),
                        $row_number
                    );
                }
            }
            
            return array(
                'success' => true,
                'imported' => $imported,
                'errors' => $errors,
                'error_messages' => $error_messages
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => __('خطا در خواندن فایل Excel: ', 'used-laptop-pricer') . $e->getMessage()
            );
        }
    }
    
    /**
     * Create sample Excel template for laptop models
     */
    public static function create_laptop_models_template() {
        try {
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
            
            // Add sample data
            $sample_data = array(
                array('Dell', 'XPS 13', 2020, 45000000, 'Intel Core i7-1065G7', '16GB', 'Intel UHD Graphics', '512GB SSD'),
                array('Apple', 'MacBook Pro 13', 2021, 55000000, 'Apple M1', '8GB', 'Apple M1 GPU', '256GB SSD'),
                array('Lenovo', 'ThinkPad X1 Carbon', 2019, 35000000, 'Intel Core i5-8265U', '8GB', 'Intel UHD Graphics 620', '256GB SSD')
            );
            
            $row = 2;
            foreach ($sample_data as $data) {
                $worksheet->fromArray($data, null, "A$row");
                $row++;
            }
            
            // Style the header row
            $headerStyle = array(
                'font' => array(
                    'bold' => true,
                    'color' => array('rgb' => 'FFFFFF')
                ),
                'fill' => array(
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => array('rgb' => '667EEA')
                )
            );
            
            $worksheet->getStyle('A1:H1')->applyFromArray($headerStyle);
            
            // Auto-size columns
            foreach (range('A', 'H') as $column) {
                $worksheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Create writer
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // Save to temporary file
            $temp_file = wp_tempnam('laptop-models-template.xlsx');
            $writer->save($temp_file);
            
            return $temp_file;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Create sample Excel template for parts
     */
    public static function create_parts_template() {
        try {
            require_once ULP_PLUGIN_PATH . 'vendor/autoload.php';
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $headers = array(
                'نوع قطعه',
                'نام قطعه',
                'مشخصات',
                'قیمت'
            );
            
            $worksheet->fromArray($headers, null, 'A1');
            
            // Add sample data
            $sample_data = array(
                array('cpu', 'Intel Core i7-10700K', '8 cores, 3.8GHz', 2500000),
                array('ram', 'Corsair Vengeance 16GB', 'DDR4, 3200MHz', 800000),
                array('gpu', 'NVIDIA RTX 3070', '8GB GDDR6', 3500000),
                array('ssd', 'Samsung 970 EVO 1TB', 'NVMe, 3500MB/s', 1200000)
            );
            
            $row = 2;
            foreach ($sample_data as $data) {
                $worksheet->fromArray($data, null, "A$row");
                $row++;
            }
            
            // Style the header row
            $headerStyle = array(
                'font' => array(
                    'bold' => true,
                    'color' => array('rgb' => 'FFFFFF')
                ),
                'fill' => array(
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => array('rgb' => '667EEA')
                )
            );
            
            $worksheet->getStyle('A1:D1')->applyFromArray($headerStyle);
            
            // Auto-size columns
            foreach (range('A', 'D') as $column) {
                $worksheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Create writer
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // Save to temporary file
            $temp_file = wp_tempnam('parts-template.xlsx');
            $writer->save($temp_file);
            
            return $temp_file;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Validate Excel file
     */
    public static function validate_excel_file($file_path) {
        try {
            require_once ULP_PLUGIN_PATH . 'vendor/autoload.php';
            
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            if (count($rows) < 2) {
                return array(
                    'valid' => false,
                    'message' => __('فایل باید حداقل یک ردیف داده داشته باشد', 'used-laptop-pricer')
                );
            }
            
            $headers = $rows[0];
            $expected_headers = array('برند', 'مدل', 'سال عرضه', 'قیمت پایه', 'CPU پایه', 'RAM پایه', 'GPU پایه', 'Storage پایه');
            
            if (count($headers) < count($expected_headers)) {
                return array(
                    'valid' => false,
                    'message' => __('تعداد ستون‌ها ناکافی است', 'used-laptop-pricer')
                );
            }
            
            return array(
                'valid' => true,
                'message' => __('فایل معتبر است', 'used-laptop-pricer')
            );
            
        } catch (Exception $e) {
            return array(
                'valid' => false,
                'message' => __('خطا در خواندن فایل: ', 'used-laptop-pricer') . $e->getMessage()
            );
        }
    }
}