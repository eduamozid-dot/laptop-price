<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ulp_models_page_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle form submissions
	if ( isset( $_POST['ulp_model_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ulp_model_nonce'] ) ), 'ulp_model_action' ) ) {
		$action = sanitize_text_field( wp_unslash( $_POST['action_type'] ?? '' ) );
		if ( $action === 'save' ) {
			$data = array(
				'brand' => sanitize_text_field( wp_unslash( $_POST['brand'] ?? '' ) ),
				'model' => sanitize_text_field( wp_unslash( $_POST['model'] ?? '' ) ),
				'release_year' => intval( $_POST['release_year'] ?? 0 ),
				'base_price' => intval( $_POST['base_price'] ?? 0 ),
				'base_cpu' => sanitize_text_field( wp_unslash( $_POST['base_cpu'] ?? '' ) ),
				'base_ram' => sanitize_text_field( wp_unslash( $_POST['base_ram'] ?? '' ) ),
				'base_gpu' => sanitize_text_field( wp_unslash( $_POST['base_gpu'] ?? '' ) ),
				'base_storage' => sanitize_text_field( wp_unslash( $_POST['base_storage'] ?? '' ) ),
			);
			ulp_db_upsert_model( $data );
			add_settings_error( 'ulp_models', 'saved', __( 'مدل ذخیره شد.', 'used-laptop-pricer' ), 'updated' );
		} elseif ( $action === 'delete' ) {
			$id = intval( $_POST['id'] ?? 0 );
			if ( $id ) {
				ulp_db_delete_model( $id );
				add_settings_error( 'ulp_models', 'deleted', __( 'مدل حذف شد.', 'used-laptop-pricer' ), 'updated' );
			}
		}
	}

	$filter_brand = isset( $_GET['brand'] ) ? sanitize_text_field( wp_unslash( $_GET['brand'] ) ) : '';
	$filter_year  = isset( $_GET['release_year'] ) ? intval( $_GET['release_year'] ) : '';
	$filter_minp  = isset( $_GET['min_price'] ) ? intval( $_GET['min_price'] ) : '';
	$filter_maxp  = isset( $_GET['max_price'] ) ? intval( $_GET['max_price'] ) : '';
	$models = ulp_db_query_models( array(
		'brand' => $filter_brand,
		'release_year' => $filter_year,
		'min_price' => $filter_minp,
		'max_price' => $filter_maxp,
	) );

	$brands = ulp_db_get_distinct_brands();
	settings_errors( 'ulp_models' );
	?>
	<div class="wrap" dir="rtl">
		<h1><?php echo esc_html__( 'مدیریت مدل‌های پایه', 'used-laptop-pricer' ); ?></h1>

		<h2 class="title"><?php echo esc_html__( 'افزودن/ویرایش مدل', 'used-laptop-pricer' ); ?></h2>
		<form method="post" style="background:#fff;padding:16px;border:1px solid #ccd0d4;border-radius:8px;margin-bottom:24px;">
			<?php wp_nonce_field( 'ulp_model_action', 'ulp_model_nonce' ); ?>
			<input type="hidden" name="action_type" value="save" />
			<table class="form-table"><tbody>
				<tr><th><label><?php echo esc_html__( 'برند', 'used-laptop-pricer' ); ?></label></th><td><input required name="brand" type="text" /></td></tr>
				<tr><th><label><?php echo esc_html__( 'مدل', 'used-laptop-pricer' ); ?></label></th><td><input required name="model" type="text" /></td></tr>
				<tr><th><label><?php echo esc_html__( 'سال عرضه', 'used-laptop-pricer' ); ?></label></th><td><input required name="release_year" type="number" min="2000" max="2100" /></td></tr>
				<tr><th><label><?php echo esc_html__( 'قیمت اولیه (MSRP)', 'used-laptop-pricer' ); ?></label></th><td><input required name="base_price" type="number" min="0" /></td></tr>
				<tr><th><label><?php echo esc_html__( 'CPU پایه', 'used-laptop-pricer' ); ?></label></th><td><input required name="base_cpu" type="text" /></td></tr>
				<tr><th><label><?php echo esc_html__( 'RAM پایه', 'used-laptop-pricer' ); ?></label></th><td><input required name="base_ram" type="text" /></td></tr>
				<tr><th><label><?php echo esc_html__( 'GPU پایه', 'used-laptop-pricer' ); ?></label></th><td><input required name="base_gpu" type="text" /></td></tr>
				<tr><th><label><?php echo esc_html__( 'Storage پایه', 'used-laptop-pricer' ); ?></label></th><td><input required name="base_storage" type="text" /></td></tr>
			</tbody></table>
			<?php submit_button( __( 'ذخیره مدل', 'used-laptop-pricer' ) ); ?>
		</form>

		<h2><?php echo esc_html__( 'فیلتر', 'used-laptop-pricer' ); ?></h2>
		<form method="get" style="margin-bottom:16px;">
			<input type="hidden" name="page" value="used-laptop-pricer" />
			<select name="brand">
				<option value=""><?php echo esc_html__( 'همه برندها', 'used-laptop-pricer' ); ?></option>
				<?php foreach ( $brands as $b ) : ?>
					<option value="<?php echo esc_attr( $b ); ?>" <?php selected( $filter_brand, $b ); ?>><?php echo esc_html( $b ); ?></option>
				<?php endforeach; ?>
			</select>
			<input type="number" name="release_year" placeholder="<?php echo esc_attr__( 'سال عرضه', 'used-laptop-pricer' ); ?>" value="<?php echo esc_attr( $filter_year ); ?>" />
			<input type="number" name="min_price" placeholder="<?php echo esc_attr__( 'حداقل قیمت', 'used-laptop-pricer' ); ?>" value="<?php echo esc_attr( $filter_minp ); ?>" />
			<input type="number" name="max_price" placeholder="<?php echo esc_attr__( 'حداکثر قیمت', 'used-laptop-pricer' ); ?>" value="<?php echo esc_attr( $filter_maxp ); ?>" />
			<?php submit_button( __( 'فیلتر', 'used-laptop-pricer' ), 'secondary', '', false ); ?>
		</form>

		<h2><?php echo esc_html__( 'لیست مدل‌ها', 'used-laptop-pricer' ); ?></h2>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php echo esc_html__( 'برند', 'used-laptop-pricer' ); ?></th>
					<th><?php echo esc_html__( 'مدل', 'used-laptop-pricer' ); ?></th>
					<th><?php echo esc_html__( 'سال', 'used-laptop-pricer' ); ?></th>
					<th><?php echo esc_html__( 'MSRP', 'used-laptop-pricer' ); ?></th>
					<th><?php echo esc_html__( 'CPU', 'used-laptop-pricer' ); ?></th>
					<th><?php echo esc_html__( 'RAM', 'used-laptop-pricer' ); ?></th>
					<th><?php echo esc_html__( 'GPU', 'used-laptop-pricer' ); ?></th>
					<th><?php echo esc_html__( 'Storage', 'used-laptop-pricer' ); ?></th>
					<th><?php echo esc_html__( 'اقدامات', 'used-laptop-pricer' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $models ) ) : ?>
					<tr><td colspan="9"><?php echo esc_html__( 'مدلی یافت نشد.', 'used-laptop-pricer' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $models as $m ) : ?>
						<tr>
							<td><?php echo esc_html( $m['brand'] ); ?></td>
							<td><?php echo esc_html( $m['model'] ); ?></td>
							<td><?php echo esc_html( $m['release_year'] ); ?></td>
							<td><?php echo ulp_format_price( $m['base_price'] ); ?></td>
							<td><?php echo esc_html( $m['base_cpu'] ); ?></td>
							<td><?php echo esc_html( $m['base_ram'] ); ?></td>
							<td><?php echo esc_html( $m['base_gpu'] ); ?></td>
							<td><?php echo esc_html( $m['base_storage'] ); ?></td>
							<td>
								<form method="post" onsubmit="return confirm('Are you sure?');" style="display:inline-block;">
									<?php wp_nonce_field( 'ulp_model_action', 'ulp_model_nonce' ); ?>
									<input type="hidden" name="action_type" value="delete" />
									<input type="hidden" name="id" value="<?php echo esc_attr( $m['id'] ); ?>" />
									<?php submit_button( __( 'حذف', 'used-laptop-pricer' ), 'link-delete', '', false ); ?>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}