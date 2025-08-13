<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ulp_settings_page_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap" dir="rtl">
		<h1><?php echo esc_html__( 'تنظیمات لپ‌تاپ پرایسر', 'used-laptop-pricer' ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'ulp_settings_group' );
			do_settings_sections( 'ulp-settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

function ulp_register_settings() {
	register_setting( 'ulp_settings_group', 'ulp_settings', 'ulp_settings_sanitize' );

	add_settings_section( 'ulp_main_section', __( 'تنظیمات عمومی', 'used-laptop-pricer' ), '__return_false', 'ulp-settings' );

	add_settings_field( 'currency', __( 'واحد پول', 'used-laptop-pricer' ), 'ulp_field_currency_render', 'ulp-settings', 'ulp_main_section' );
	add_settings_field( 'dep_year1', __( 'استهلاک سال اول (%)', 'used-laptop-pricer' ), 'ulp_field_dep1_render', 'ulp-settings', 'ulp_main_section' );
	add_settings_field( 'dep_year2', __( 'استهلاک سال دوم (%)', 'used-laptop-pricer' ), 'ulp_field_dep2_render', 'ulp-settings', 'ulp_main_section' );
	add_settings_field( 'dep_year3plus', __( 'استهلاک سال سوم به بعد (%)', 'used-laptop-pricer' ), 'ulp_field_dep3_render', 'ulp-settings', 'ulp_main_section' );
	add_settings_field( 'condition_multipliers', __( 'ضرایب وضعیت ظاهری', 'used-laptop-pricer' ), 'ulp_field_conditions_render', 'ulp-settings', 'ulp_main_section' );
}
add_action( 'admin_init', 'ulp_register_settings' );

function ulp_settings_sanitize( $input ) {
	$output = ulp_get_settings();
	if ( isset( $input['currency'] ) ) {
		$output['currency'] = sanitize_text_field( $input['currency'] );
	}
	foreach ( array( 'dep_year1', 'dep_year2', 'dep_year3plus' ) as $k ) {
		if ( isset( $input[ $k ] ) ) {
			$output[ $k ] = max( 0, min( 100, intval( $input[ $k ] ) ) );
		}
	}
	if ( isset( $input['condition_multipliers'] ) && is_array( $input['condition_multipliers'] ) ) {
		$clean = array();
		foreach ( $input['condition_multipliers'] as $key => $val ) {
			$clean[ sanitize_key( $key ) ] = floatval( $val );
		}
		$output['condition_multipliers'] = $clean;
	}
	return $output;
}

function ulp_field_currency_render() {
	$settings = ulp_get_settings();
	?>
	<input type="text" name="ulp_settings[currency]" value="<?php echo esc_attr( $settings['currency'] ); ?>" />
	<p class="description"><?php echo esc_html__( 'مثال: IRR یا IRT یا Toman', 'used-laptop-pricer' ); ?></p>
	<?php
}

function ulp_field_dep1_render() {
	$settings = ulp_get_settings();
	?>
	<input type="number" min="0" max="100" name="ulp_settings[dep_year1]" value="<?php echo esc_attr( $settings['dep_year1'] ); ?>" />
	<?php
}

function ulp_field_dep2_render() {
	$settings = ulp_get_settings();
	?>
	<input type="number" min="0" max="100" name="ulp_settings[dep_year2]" value="<?php echo esc_attr( $settings['dep_year2'] ); ?>" />
	<?php
}

function ulp_field_dep3_render() {
	$settings = ulp_get_settings();
	?>
	<input type="number" min="0" max="100" name="ulp_settings[dep_year3plus]" value="<?php echo esc_attr( $settings['dep_year3plus'] ); ?>" />
	<?php
}

function ulp_field_conditions_render() {
	$settings = ulp_get_settings();
	$conds = $settings['condition_multipliers'];
	?>
	<table class="form-table"><tbody>
		<?php foreach ( $conds as $key => $val ) : ?>
		<tr>
			<th scope="row"><label for="cond_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $key ); ?></label></th>
			<td>
				<input id="cond_<?php echo esc_attr( $key ); ?>" type="number" step="0.01" min="0" name="ulp_settings[condition_multipliers][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $val ); ?>" />
			</td>
		</tr>
		<?php endforeach; ?>
		<tr>
			<th scope="row"><?php echo esc_html__( 'افزودن وضعیت جدید', 'used-laptop-pricer' ); ?></th>
			<td>
				<input type="text" placeholder="کلید وضعیت (english_key)" name="ulp_settings[condition_multipliers][new_key]" value="" />
				<input type="number" step="0.01" min="0" placeholder="1.00" name="ulp_settings[condition_multipliers][new_value]" value="" />
				<p class="description"><?php echo esc_html__( 'برای افزودن وضعیت جدید، پس از ذخیره نام کلید را ویرایش کنید.', 'used-laptop-pricer' ); ?></p>
			</td>
		</tr>
	</tbody></table>
	<?php
}