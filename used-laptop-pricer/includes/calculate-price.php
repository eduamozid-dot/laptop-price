<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calculate used laptop price based on Market-Based Pricing rules.
 *
 * @param array $args {
 *   @type string $brand
 *   @type string $model
 *   @type int    $release_year
 *   @type string $condition_key
 *   @type string $user_cpu
 *   @type string $user_ram
 *   @type string $user_gpu
 *   @type string $user_storage
 * }
 *
 * @return array|WP_Error
 */
function ulp_calculate_price( array $args ) {
	$brand         = sanitize_text_field( $args['brand'] ?? '' );
	$model         = sanitize_text_field( $args['model'] ?? '' );
	$release_year  = intval( $args['release_year'] ?? 0 );
	$condition_key = sanitize_text_field( $args['condition_key'] ?? 'used' );
	$user_cpu      = sanitize_text_field( $args['user_cpu'] ?? '' );
	$user_ram      = sanitize_text_field( $args['user_ram'] ?? '' );
	$user_gpu      = sanitize_text_field( $args['user_gpu'] ?? '' );
	$user_storage  = sanitize_text_field( $args['user_storage'] ?? '' );

	if ( empty( $brand ) || empty( $model ) ) {
		return new WP_Error( 'invalid_input', __( 'برند و مدل الزامی است.', 'used-laptop-pricer' ) );
	}

	$model_row = ulp_db_get_model( $brand, $model );
	if ( ! $model_row ) {
		return new WP_Error( 'not_found', __( 'مدل یافت نشد.', 'used-laptop-pricer' ) );
	}

	$settings = ulp_get_settings();
	$base_price   = intval( $model_row['base_price'] );
	$base_year    = intval( $model_row['release_year'] );
	$years_used   = max( 0, intval( $release_year ) - $base_year );

	// Depreciation rules
	$price_after_dep = $base_price;
	$dep_year1   = max( 0, min( 100, intval( $settings['dep_year1'] ) ) );
	$dep_year2   = max( 0, min( 100, intval( $settings['dep_year2'] ) ) );
	$dep_year3p  = max( 0, min( 100, intval( $settings['dep_year3plus'] ) ) );

	if ( $years_used >= 1 ) {
		$price_after_dep -= ( $price_after_dep * ( $dep_year1 / 100 ) );
	}
	if ( $years_used >= 2 ) {
		$price_after_dep -= ( $price_after_dep * ( $dep_year2 / 100 ) );
	}
	if ( $years_used >= 3 ) {
		for ( $i = 3; $i <= $years_used; $i++ ) {
			$price_after_dep -= ( $price_after_dep * ( $dep_year3p / 100 ) );
		}
	}

	$condition_multipliers = is_array( $settings['condition_multipliers'] ?? null ) ? $settings['condition_multipliers'] : array();
	$condition_multiplier  = floatval( $condition_multipliers[ $condition_key ] ?? 0.90 );
	$price_after_condition = (int) round( $price_after_dep * $condition_multiplier );

	// Parts adjustments: compare user parts with base parts and sum part price deltas
	$adjustments = array();
	$total_adjustment = 0;

	$base_cpu = $model_row['base_cpu'];
	$base_ram = $model_row['base_ram'];
	$base_gpu = $model_row['base_gpu'];
	$base_storage = $model_row['base_storage'];

	if ( $user_cpu && $user_cpu !== $base_cpu ) {
		$base_part = ulp_db_get_part_by_name( 'cpu', $base_cpu );
		$user_part = ulp_db_get_part_by_name( 'cpu', $user_cpu );
		$delta = ( $user_part['price'] ?? 0 ) - ( $base_part['price'] ?? 0 );
		$adjustments['cpu'] = intval( $delta );
		$total_adjustment += intval( $delta );
	}
	if ( $user_ram && $user_ram !== $base_ram ) {
		$base_part = ulp_db_get_part_by_name( 'ram', $base_ram );
		$user_part = ulp_db_get_part_by_name( 'ram', $user_ram );
		$delta = ( $user_part['price'] ?? 0 ) - ( $base_part['price'] ?? 0 );
		$adjustments['ram'] = intval( $delta );
		$total_adjustment += intval( $delta );
	}
	if ( $user_gpu && $user_gpu !== $base_gpu ) {
		$base_part = ulp_db_get_part_by_name( 'gpu', $base_gpu );
		$user_part = ulp_db_get_part_by_name( 'gpu', $user_gpu );
		$delta = ( $user_part['price'] ?? 0 ) - ( $base_part['price'] ?? 0 );
		$adjustments['gpu'] = intval( $delta );
		$total_adjustment += intval( $delta );
	}
	if ( $user_storage && $user_storage !== $base_storage ) {
		// Storage type could be ssd or hdd; try both
		$base_part = ulp_db_get_part_by_name( 'ssd', $base_storage );
		if ( ! $base_part ) {
			$base_part = ulp_db_get_part_by_name( 'hdd', $base_storage );
		}
		$user_part = ulp_db_get_part_by_name( 'ssd', $user_storage );
		if ( ! $user_part ) {
			$user_part = ulp_db_get_part_by_name( 'hdd', $user_storage );
		}
		$delta = ( $user_part['price'] ?? 0 ) - ( $base_part['price'] ?? 0 );
		$adjustments['storage'] = intval( $delta );
		$total_adjustment += intval( $delta );
	}

	$final_price = max( 0, (int) round( $price_after_condition + $total_adjustment ) );
	$min_price   = (int) round( $final_price * 0.9 );
	$depreciation_amount = max( 0, (int) ( $base_price - $price_after_dep ) );

	return array(
		'brand' => $brand,
		'model' => $model,
		'release_year' => $release_year,
		'base_price' => $base_price,
		'depreciated_price' => (int) $price_after_dep,
		'depreciation_amount' => $depreciation_amount,
		'condition_multiplier' => $condition_multiplier,
		'price_after_condition' => $price_after_condition,
		'adjustments' => $adjustments,
		'total_adjustment' => (int) $total_adjustment,
		'final_price' => $final_price,
		'min_price' => $min_price,
		'currency' => $settings['currency'] ?? 'IRR',
	);
}