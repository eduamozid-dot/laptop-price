<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PhpOffice\PhpSpreadsheet\IOFactory;

function ulp_excel_import_page_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$can_excel = class_exists( '\\PhpOffice\\PhpSpreadsheet\\IOFactory' );

	if ( isset( $_POST['ulp_excel_import_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ulp_excel_import_nonce'] ) ), 'ulp_excel_import_action' ) ) {
		if ( ! $can_excel ) {
			add_settings_error( 'ulp_excel', 'lib_missing', __( 'کتابخانه PhpSpreadsheet نصب نیست.', 'used-laptop-pricer' ), 'error' );
		} else {
			if ( ! empty( $_FILES['excel_file']['tmp_name'] ) && UPLOAD_ERR_OK === $_FILES['excel_file']['error'] ) {
				$uploaded = $_FILES['excel_file'];
				$ext = strtolower( pathinfo( $uploaded['name'], PATHINFO_EXTENSION ) );
				$allowed = array( 'xlsx', 'xls' );
				if ( in_array( $ext, $allowed, true ) ) {
					$reader = IOFactory::createReaderForFile( $uploaded['tmp_name'] );
					$spreadsheet = $reader->load( $uploaded['tmp_name'] );
					$sheet = $spreadsheet->getActiveSheet();
					$rows = $sheet->toArray();
					// Expect header row
					array_shift( $rows );
					$imported = 0;
					foreach ( $rows as $r ) {
						list( $brand, $model, $year, $price, $cpu, $ram, $gpu, $storage ) = array_pad( $r, 8, '' );
						$data = array(
							'brand' => sanitize_text_field( $brand ),
							'model' => sanitize_text_field( $model ),
							'release_year' => intval( $year ),
							'base_price' => intval( $price ),
							'base_cpu' => sanitize_text_field( $cpu ),
							'base_ram' => sanitize_text_field( $ram ),
							'base_gpu' => sanitize_text_field( $gpu ),
							'base_storage' => sanitize_text_field( $storage ),
						);
						if ( ! empty( $data['brand'] ) && ! empty( $data['model'] ) ) {
							if ( ulp_db_upsert_model( $data ) ) {
								$imported++;
							}
						}
					}
					add_settings_error( 'ulp_excel', 'import_success', sprintf( __( 'ورود اطلاعات انجام شد: %d مورد.', 'used-laptop-pricer' ), $imported ), 'updated' );
				} else {
					add_settings_error( 'ulp_excel', 'invalid_file', __( 'فرمت فایل پشتیبانی نمی‌شود.', 'used-laptop-pricer' ), 'error' );
				}
			} else {
				add_settings_error( 'ulp_excel', 'upload_error', __( 'آپلود فایل با خطا مواجه شد.', 'used-laptop-pricer' ), 'error' );
			}
		}
	}

	settings_errors( 'ulp_excel' );
	?>
	<div class="wrap" dir="rtl">
		<h1><?php echo esc_html__( 'ورود مدل‌ها از Excel', 'used-laptop-pricer' ); ?></h1>
		<?php if ( ! $can_excel ) : ?>
			<div class="notice notice-warning"><p><?php echo esc_html__( 'کتابخانه PhpSpreadsheet نصب نیست. برای استفاده از این بخش، Composer را اجرا کنید.', 'used-laptop-pricer' ); ?></p></div>
		<?php endif; ?>
		<form method="post" enctype="multipart/form-data" style="background:#fff;padding:16px;border:1px solid #ccd0d4;border-radius:8px;">
			<?php wp_nonce_field( 'ulp_excel_import_action', 'ulp_excel_import_nonce' ); ?>
			<input type="file" name="excel_file" accept=".xlsx,.xls" required />
			<?php submit_button( __( 'آپلود و ورود', 'used-laptop-pricer' ) ); ?>
		</form>
		<p class="description"><?php echo esc_html__( 'ساختار ستون‌ها: برند | مدل | سال عرضه | قیمت اولیه | CPU پایه | RAM پایه | GPU پایه | Storage پایه', 'used-laptop-pricer' ); ?></p>
	</div>
	<?php
}