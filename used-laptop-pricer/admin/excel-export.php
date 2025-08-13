<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function ulp_excel_export_page_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$can_excel = class_exists( '\\PhpOffice\\PhpSpreadsheet\\Spreadsheet' );

	if ( isset( $_POST['ulp_excel_export_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ulp_excel_export_nonce'] ) ), 'ulp_excel_export_action' ) ) {
		if ( ! $can_excel ) {
			add_settings_error( 'ulp_excel_export', 'lib_missing', __( 'کتابخانه PhpSpreadsheet نصب نیست.', 'used-laptop-pricer' ), 'error' );
		} else {
			$rows = ulp_db_query_models();
			$spreadsheet = new Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();
			$sheet->fromArray( array( 'برند', 'مدل', 'سال عرضه', 'قیمت اولیه', 'CPU پایه', 'RAM پایه', 'GPU پایه', 'Storage پایه' ), null, 'A1' );
			$line = 2;
			foreach ( $rows as $r ) {
				$sheet->setCellValue( 'A' . $line, $r['brand'] );
				$sheet->setCellValue( 'B' . $line, $r['model'] );
				$sheet->setCellValue( 'C' . $line, intval( $r['release_year'] ) );
				$sheet->setCellValue( 'D' . $line, intval( $r['base_price'] ) );
				$sheet->setCellValue( 'E' . $line, $r['base_cpu'] );
				$sheet->setCellValue( 'F' . $line, $r['base_ram'] );
				$sheet->setCellValue( 'G' . $line, $r['base_gpu'] );
				$sheet->setCellValue( 'H' . $line, $r['base_storage'] );
				$line++;
			}

			$filename = 'ulp-models-' . date( 'Ymd-His' ) . '.xlsx';
			header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Cache-Control: max-age=0' );

			$writer = new Xlsx( $spreadsheet );
			$writer->save( 'php://output' );
			exit;
		}
	}

	settings_errors( 'ulp_excel_export' );
	?>
	<div class="wrap" dir="rtl">
		<h1><?php echo esc_html__( 'خروجی Excel از مدل‌ها', 'used-laptop-pricer' ); ?></h1>
		<?php if ( ! $can_excel ) : ?>
			<div class="notice notice-warning"><p><?php echo esc_html__( 'کتابخانه PhpSpreadsheet نصب نیست. برای استفاده از این بخش، Composer را اجرا کنید.', 'used-laptop-pricer' ); ?></p></div>
		<?php endif; ?>
		<form method="post" style="background:#fff;padding:16px;border:1px solid #ccd0d4;border-radius:8px;">
			<?php wp_nonce_field( 'ulp_excel_export_action', 'ulp_excel_export_nonce' ); ?>
			<?php submit_button( __( 'دانلود فایل Excel', 'used-laptop-pricer' ) ); ?>
		</form>
	</div>
	<?php
}