<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ulp_parts_page_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['ulp_parts_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ulp_parts_nonce'] ) ), 'ulp_parts_action' ) ) {
		$action = sanitize_text_field( wp_unslash( $_POST['action_type'] ?? '' ) );
		if ( $action === 'save' ) {
			$data = array(
				'type'  => sanitize_text_field( wp_unslash( $_POST['type'] ?? '' ) ),
				'name'  => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
				'price' => intval( $_POST['price'] ?? 0 ),
			);
			if ( in_array( $data['type'], array( 'cpu', 'ram', 'gpu', 'ssd', 'hdd' ), true ) ) {
				ulp_db_upsert_part( $data );
				add_settings_error( 'ulp_parts', 'saved', __( 'قطعه ذخیره شد.', 'used-laptop-pricer' ), 'updated' );
			}
		} elseif ( $action === 'delete' ) {
			$id = intval( $_POST['id'] ?? 0 );
			if ( $id ) {
				ulp_db_delete_part( $id );
				add_settings_error( 'ulp_parts', 'deleted', __( 'قطعه حذف شد.', 'used-laptop-pricer' ), 'updated' );
			}
		}
	}

	$parts = array(
		'cpu' => ulp_db_get_parts_by_type( 'cpu' ),
		'ram' => ulp_db_get_parts_by_type( 'ram' ),
		'gpu' => ulp_db_get_parts_by_type( 'gpu' ),
		'ssd' => ulp_db_get_parts_by_type( 'ssd' ),
		'hdd' => ulp_db_get_parts_by_type( 'hdd' ),
	);
	settings_errors( 'ulp_parts' );
	?>
	<div class="wrap" dir="rtl">
		<h1><?php echo esc_html__( 'مدیریت قطعات', 'used-laptop-pricer' ); ?></h1>

		<h2><?php echo esc_html__( 'افزودن/ویرایش قطعه', 'used-laptop-pricer' ); ?></h2>
		<form method="post" style="background:#fff;padding:16px;border:1px solid #ccd0d4;border-radius:8px;margin-bottom:24px;">
			<?php wp_nonce_field( 'ulp_parts_action', 'ulp_parts_nonce' ); ?>
			<input type="hidden" name="action_type" value="save" />
			<table class="form-table"><tbody>
				<tr><th><label><?php echo esc_html__( 'نوع', 'used-laptop-pricer' ); ?></label></th>
					<td>
						<select name="type" required>
							<option value="cpu">CPU</option>
							<option value="ram">RAM</option>
							<option value="gpu">GPU</option>
							<option value="ssd">SSD</option>
							<option value="hdd">HDD</option>
						</select>
					</td>
				</tr>
				<tr><th><label><?php echo esc_html__( 'نام', 'used-laptop-pricer' ); ?></label></th><td><input required name="name" type="text" /></td></tr>
				<tr><th><label><?php echo esc_html__( 'قیمت', 'used-laptop-pricer' ); ?></label></th><td><input required name="price" type="number" min="0" /></td></tr>
			</tbody></table>
			<?php submit_button( __( 'ذخیره قطعه', 'used-laptop-pricer' ) ); ?>
		</form>

		<h2><?php echo esc_html__( 'لیست قطعات', 'used-laptop-pricer' ); ?></h2>
		<?php foreach ( $parts as $type => $items ) : ?>
			<h3><?php echo esc_html( strtoupper( $type ) ); ?></h3>
			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th><?php echo esc_html__( 'نام', 'used-laptop-pricer' ); ?></th><th><?php echo esc_html__( 'قیمت', 'used-laptop-pricer' ); ?></th><th><?php echo esc_html__( 'اقدامات', 'used-laptop-pricer' ); ?></th></tr></thead>
				<tbody>
					<?php if ( empty( $items ) ) : ?>
						<tr><td colspan="3"><?php echo esc_html__( 'موردی وجود ندارد.', 'used-laptop-pricer' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $items as $p ) : ?>
							<tr>
								<td><?php echo esc_html( $p['name'] ); ?></td>
								<td><?php echo ulp_format_price( $p['price'] ); ?></td>
								<td>
									<form method="post" onsubmit="return confirm('Are you sure?');" style="display:inline-block;">
										<?php wp_nonce_field( 'ulp_parts_action', 'ulp_parts_nonce' ); ?>
										<input type="hidden" name="action_type" value="delete" />
										<input type="hidden" name="id" value="<?php echo esc_attr( $p['id'] ); ?>" />
										<?php submit_button( __( 'حذف', 'used-laptop-pricer' ), 'link-delete', '', false ); ?>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		<?php endforeach; ?>
	</div>
	<?php
}