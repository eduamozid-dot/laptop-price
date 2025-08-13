<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$settings = ulp_get_settings();
$conditions = $settings['condition_multipliers'];
?>
<div class="ulp-container" dir="rtl">
	<form id="ulp-form" class="ulp-form" method="post">
		<input type="hidden" name="action" value="ulp_calculate_price" />
		<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'ulp_frontend_nonce' ) ); ?>" />
		<div class="ulp-row">
			<label><?php echo esc_html__( 'برند', 'used-laptop-pricer' ); ?></label>
			<select name="brand" id="ulp-brand" required></select>
		</div>
		<div class="ulp-row">
			<label><?php echo esc_html__( 'مدل', 'used-laptop-pricer' ); ?></label>
			<select name="model" id="ulp-model" required></select>
		</div>
		<div class="ulp-row">
			<label><?php echo esc_html__( 'سال ساخت', 'used-laptop-pricer' ); ?></label>
			<input type="number" min="2000" max="2100" name="release_year" required />
		</div>
		<div class="ulp-row">
			<label><?php echo esc_html__( 'وضعیت ظاهری', 'used-laptop-pricer' ); ?></label>
			<select name="condition" required>
				<?php foreach ( $conditions as $key => $val ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $key ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="ulp-row">
			<label>CPU</label>
			<select name="cpu" id="ulp-cpu"></select>
		</div>
		<div class="ulp-row">
			<label>RAM</label>
			<select name="ram" id="ulp-ram"></select>
		</div>
		<div class="ulp-row">
			<label>GPU</label>
			<select name="gpu" id="ulp-gpu"></select>
		</div>
		<div class="ulp-row">
			<label>Storage</label>
			<select name="storage" id="ulp-storage"></select>
		</div>
		<div class="ulp-actions">
			<button type="submit" class="ulp-btn"><?php echo esc_html__( 'محاسبه قیمت', 'used-laptop-pricer' ); ?></button>
		</div>
	</form>
	<div id="ulp-result" class="ulp-result" style="display:none;"></div>
</div>