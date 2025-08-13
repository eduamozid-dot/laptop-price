<?php
/**
 * Excel Export functionality for Used Laptop Pricer
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULP_Excel_Export {
    
    /**
     * Export laptop models to Excel
     */
    public static function export_laptop_models($filters = array()) {
        try {
            require_once ULP_PLUGIN_PATH . 'vendor/autoload.php';
            
            $models = ULP_Database::get_laptop_models($filters);
            
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
                'Storage پایه',
                'تاریخ ایجاد',
                'تاریخ بروزرسانی'
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
                    $model->base_storage,
                    $model->created_at,
                    $model->updated_at
                ), null, "A$row");
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
                ),
                'alignment' => array(
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                )
            );
            
            $worksheet->getStyle('A1:J1')->applyFromArray($headerStyle);
            
            // Auto-size columns
            foreach (range('A', 'J') as $column) {
                $worksheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Add borders to all cells
            $borderStyle = array(
                'borders' => array(
                    'allBorders' => array(
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => array('rgb' => 'CCCCCC')
                    )
                )
            );
            
            $worksheet->getStyle('A1:J' . ($row - 1))->applyFromArray($borderStyle);
            
            // Format price column
            $worksheet->getStyle('D2:D' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
            
            // Create writer
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="laptop-models-' . date('Y-m-d-H-i-s') . '.xlsx"');
            header('Cache-Control: max-age=0');
            
            $writer->save('php://output');
            exit;
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => __('خطا در ایجاد فایل Excel: ', 'used-laptop-pricer') . $e->getMessage()
            );
        }
    }
    
    /**
     * Export parts to Excel
     */
    public static function export_parts($filters = array()) {
        try {
            require_once ULP_PLUGIN_PATH . 'vendor/autoload.php';
            
            $parts = ULP_Database::get_parts_prices($filters['part_type'] ?? null);
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $headers = array(
                'نوع قطعه',
                'نام قطعه',
                'مشخصات',
                'قیمت',
                'تاریخ ایجاد',
                'تاریخ بروزرسانی'
            );
            
            $worksheet->fromArray($headers, null, 'A1');
            
            // Add data
            $row = 2;
            foreach ($parts as $part) {
                $worksheet->fromArray(array(
                    $part->part_type,
                    $part->part_name,
                    $part->part_specs,
                    $part->price,
                    $part->created_at,
                    $part->updated_at
                ), null, "A$row");
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
                ),
                'alignment' => array(
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                )
            );
            
            $worksheet->getStyle('A1:F1')->applyFromArray($headerStyle);
            
            // Auto-size columns
            foreach (range('A', 'F') as $column) {
                $worksheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Add borders to all cells
            $borderStyle = array(
                'borders' => array(
                    'allBorders' => array(
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => array('rgb' => 'CCCCCC')
                    )
                )
            );
            
            $worksheet->getStyle('A1:F' . ($row - 1))->applyFromArray($borderStyle);
            
            // Format price column
            $worksheet->getStyle('D2:D' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
            
            // Create writer
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="parts-' . date('Y-m-d-H-i-s') . '.xlsx"');
            header('Cache-Control: max-age=0');
            
            $writer->save('php://output');
            exit;
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => __('خطا در ایجاد فایل Excel: ', 'used-laptop-pricer') . $e->getMessage()
            );
        }
    }
    
    /**
     * Export calculation report to Excel
     */
    public static function export_calculation_report($calculations) {
        try {
            require_once ULP_PLUGIN_PATH . 'vendor/autoload.php';
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $headers = array(
                'تاریخ محاسبه',
                'برند',
                'مدل',
                'سال ساخت',
                'وضعیت ظاهری',
                'CPU',
                'RAM',
                'GPU',
                'Storage',
                'قیمت پایه',
                'مبلغ استهلاک',
                'ضریب وضعیت',
                'تعدیل قطعات',
                'قیمت نهایی',
                'حداقل قیمت',
                'حداکثر قیمت'
            );
            
            $worksheet->fromArray($headers, null, 'A1');
            
            // Add data
            $row = 2;
            foreach ($calculations as $calc) {
                $worksheet->fromArray(array(
                    $calc['calculation_date'],
                    $calc['brand'],
                    $calc['model'],
                    $calc['year'],
                    $calc['condition'],
                    $calc['cpu'],
                    $calc['ram'],
                    $calc['gpu'],
                    $calc['storage'],
                    $calc['base_price'],
                    $calc['depreciation_amount'],
                    $calc['condition_factor'],
                    $calc['parts_adjustment'],
                    $calc['final_price'],
                    $calc['min_price'],
                    $calc['max_price']
                ), null, "A$row");
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
                ),
                'alignment' => array(
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                )
            );
            
            $worksheet->getStyle('A1:P1')->applyFromArray($headerStyle);
            
            // Auto-size columns
            foreach (range('A', 'P') as $column) {
                $worksheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Add borders to all cells
            $borderStyle = array(
                'borders' => array(
                    'allBorders' => array(
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => array('rgb' => 'CCCCCC')
                    )
                )
            );
            
            $worksheet->getStyle('A1:P' . ($row - 1))->applyFromArray($borderStyle);
            
            // Format price columns
            $priceColumns = array('J', 'K', 'M', 'N', 'O', 'P');
            foreach ($priceColumns as $column) {
                $worksheet->getStyle($column . '2:' . $column . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
            }
            
            // Format factor column
            $worksheet->getStyle('L2:L' . ($row - 1))->getNumberFormat()->setFormatCode('0.0');
            
            // Create writer
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="calculation-report-' . date('Y-m-d-H-i-s') . '.xlsx"');
            header('Cache-Control: max-age=0');
            
            $writer->save('php://output');
            exit;
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => __('خطا در ایجاد فایل Excel: ', 'used-laptop-pricer') . $e->getMessage()
            );
        }
    }
    
    /**
     * Export statistics report to Excel
     */
    public static function export_statistics_report() {
        try {
            require_once ULP_PLUGIN_PATH . 'vendor/autoload.php';
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            
            // Create multiple worksheets
            $models_worksheet = $spreadsheet->getActiveSheet();
            $models_worksheet->setTitle('آمار مدل‌ها');
            
            $parts_worksheet = $spreadsheet->createSheet();
            $parts_worksheet->setTitle('آمار قطعات');
            
            $brands_worksheet = $spreadsheet->createSheet();
            $brands_worksheet->setTitle('آمار برندها');
            
            // Models statistics
            $models = ULP_Database::get_laptop_models();
            $models_by_brand = array();
            $models_by_year = array();
            
            foreach ($models as $model) {
                $models_by_brand[$model->brand] = ($models_by_brand[$model->brand] ?? 0) + 1;
                $models_by_year[$model->release_year] = ($models_by_year[$model->release_year] ?? 0) + 1;
            }
            
            // Models by brand
            $models_worksheet->fromArray(array('برند', 'تعداد مدل'), null, 'A1');
            $row = 2;
            foreach ($models_by_brand as $brand => $count) {
                $models_worksheet->fromArray(array($brand, $count), null, "A$row");
                $row++;
            }
            
            // Models by year
            $models_worksheet->fromArray(array('سال عرضه', 'تعداد مدل'), null, 'D1');
            $row = 2;
            foreach ($models_by_year as $year => $count) {
                $models_worksheet->fromArray(array($year, $count), null, "D$row");
                $row++;
            }
            
            // Parts statistics
            $parts = ULP_Database::get_parts_prices();
            $parts_by_type = array();
            $total_parts_value = 0;
            
            foreach ($parts as $part) {
                $parts_by_type[$part->part_type] = ($parts_by_type[$part->part_type] ?? 0) + 1;
                $total_parts_value += $part->price;
            }
            
            $parts_worksheet->fromArray(array('نوع قطعه', 'تعداد', 'میانگین قیمت'), null, 'A1');
            $row = 2;
            foreach ($parts_by_type as $type => $count) {
                $type_parts = array_filter($parts, function($part) use ($type) {
                    return $part->part_type === $type;
                });
                $avg_price = array_sum(array_column($type_parts, 'price')) / count($type_parts);
                $parts_worksheet->fromArray(array($type, $count, $avg_price), null, "A$row");
                $row++;
            }
            
            // Brands statistics
            $brands = ULP_Database::get_brands();
            $brands_worksheet->fromArray(array('برند', 'تعداد مدل', 'میانگین قیمت پایه'), null, 'A1');
            $row = 2;
            foreach ($brands as $brand) {
                $brand_models = array_filter($models, function($model) use ($brand) {
                    return $model->brand === $brand;
                });
                $count = count($brand_models);
                $avg_price = array_sum(array_column($brand_models, 'base_price')) / $count;
                $brands_worksheet->fromArray(array($brand, $count, $avg_price), null, "A$row");
                $row++;
            }
            
            // Style all worksheets
            foreach ($spreadsheet->getAllSheets() as $worksheet) {
                $headerStyle = array(
                    'font' => array(
                        'bold' => true,
                        'color' => array('rgb' => 'FFFFFF')
                    ),
                    'fill' => array(
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => array('rgb' => '667EEA')
                    ),
                    'alignment' => array(
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                    )
                );
                
                $worksheet->getStyle('A1:Z1')->applyFromArray($headerStyle);
                
                // Auto-size columns
                foreach (range('A', 'Z') as $column) {
                    $worksheet->getColumnDimension($column)->setAutoSize(true);
                }
            }
            
            // Create writer
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="statistics-report-' . date('Y-m-d-H-i-s') . '.xlsx"');
            header('Cache-Control: max-age=0');
            
            $writer->save('php://output');
            exit;
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => __('خطا در ایجاد فایل Excel: ', 'used-laptop-pricer') . $e->getMessage()
            );
        }
    }
    
    /**
     * Save Excel file to server
     */
    public static function save_excel_file($spreadsheet, $filename) {
        try {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // Ensure upload directory exists
            $upload_dir = ulp_ensure_upload_directory();
            $file_path = $upload_dir . '/' . $filename;
            
            $writer->save($file_path);
            
            return array(
                'success' => true,
                'file_path' => $file_path,
                'file_url' => str_replace(ABSPATH, site_url('/'), $file_path)
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => __('خطا در ذخیره فایل: ', 'used-laptop-pricer') . $e->getMessage()
            );
        }
    }
}