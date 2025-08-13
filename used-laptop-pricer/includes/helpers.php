<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get plugin settings with defaults merged.
 */
function ulp_get_settings(): array {
	$defaults = array(
		'currency' => 'IRR',
		'dep_year1' => 30,
		'dep_year2' => 15,
		'dep_year3plus' => 10,
		'condition_multipliers' => array(
			'new' => 1.00,
			'clean' => 0.95,
			'used' => 0.90,
			'heavily_used' => 0.80,
		),
	);
	$settings = get_option( 'ulp_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
	return wp_parse_args( $settings, $defaults );
}

/**
 * Format amount with currency.
 */
function ulp_format_price( $amount ): string {
	$settings = ulp_get_settings();
	$currency = isset( $settings['currency'] ) ? $settings['currency'] : 'IRR';
	$amount_int = (int) $amount;
	return number_format_i18n( $amount_int ) . ' ' . esc_html( $currency );
}

/**
 * Get distinct brands from models table.
 */
function ulp_db_get_distinct_brands(): array {
	global $wpdb;
	$table = $wpdb->prefix . 'ulp_models';
	$rows = $wpdb->get_col( "SELECT DISTINCT brand FROM {$table} ORDER BY brand ASC" ); // phpcs:ignore WordPress.DB
	if ( ! is_array( $rows ) ) {
		return array();
	}
	return array_map( 'esc_html', $rows );
}

/**
 * Get models by brand.
 */
function ulp_db_get_models_by_brand( string $brand ): array {
	global $wpdb;
	$table = $wpdb->prefix . 'ulp_models';
	$prepared = $wpdb->prepare( "SELECT model FROM {$table} WHERE brand=%s ORDER BY model ASC", $brand );
	$rows = $wpdb->get_col( $prepared ); // phpcs:ignore WordPress.DB
	if ( ! is_array( $rows ) ) {
		return array();
	}
	return array_map( 'esc_html', $rows );
}

/**
 * Get a model record by brand+model.
 */
function ulp_db_get_model( string $brand, string $model ): ?array {
	global $wpdb;
	$table = $wpdb->prefix . 'ulp_models';
	$prepared = $wpdb->prepare( "SELECT * FROM {$table} WHERE brand=%s AND model=%s LIMIT 1", $brand, $model );
	$row = $wpdb->get_row( $prepared, ARRAY_A ); // phpcs:ignore WordPress.DB
	return is_array( $row ) ? $row : null;
}

/**
 * Insert or update model.
 */
function ulp_db_upsert_model( array $data ): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'ulp_models';
	$exists = ulp_db_get_model( $data['brand'], $data['model'] );
	$san = array(
		'brand' => sanitize_text_field( $data['brand'] ?? '' ),
		'model' => sanitize_text_field( $data['model'] ?? '' ),
		'release_year' => intval( $data['release_year'] ?? 0 ),
		'base_price' => intval( $data['base_price'] ?? 0 ),
		'base_cpu' => sanitize_text_field( $data['base_cpu'] ?? '' ),
		'base_ram' => sanitize_text_field( $data['base_ram'] ?? '' ),
		'base_gpu' => sanitize_text_field( $data['base_gpu'] ?? '' ),
		'base_storage' => sanitize_text_field( $data['base_storage'] ?? '' ),
	);
	if ( $exists ) {
		$res = $wpdb->update( $table, $san, array( 'id' => intval( $exists['id'] ) ) ); // phpcs:ignore WordPress.DB
		return $res !== false;
	}
	$res = $wpdb->insert( $table, $san ); // phpcs:ignore WordPress.DB
	return $res !== false;
}

/**
 * Delete model by id.
 */
function ulp_db_delete_model( int $id ): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'ulp_models';
	$res = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB
	return $res !== false;
}

/**
 * Query models with filters and pagination.
 */
function ulp_db_query_models( array $args = array() ): array {
	global $wpdb;
	$table = $wpdb->prefix . 'ulp_models';
	$where = array();
	$params = array();
	if ( ! empty( $args['brand'] ) ) {
		$where[] = 'brand = %s';
		$params[] = $args['brand'];
	}
	if ( ! empty( $args['release_year'] ) ) {
		$where[] = 'release_year = %d';
		$params[] = intval( $args['release_year'] );
	}
	if ( ! empty( $args['min_price'] ) ) {
		$where[] = 'base_price >= %d';
		$params[] = intval( $args['min_price'] );
	}
	if ( ! empty( $args['max_price'] ) ) {
		$where[] = 'base_price <= %d';
		$params[] = intval( $args['max_price'] );
	}
	$sql = "SELECT * FROM {$table}";
	if ( $where ) {
		$sql .= ' WHERE ' . implode( ' AND ', $where );
	}
	$sql .= ' ORDER BY brand ASC, model ASC';
	$prepared = $params ? $wpdb->prepare( $sql, $params ) : $sql;
	$rows = $wpdb->get_results( $prepared, ARRAY_A ); // phpcs:ignore WordPress.DB
	return is_array( $rows ) ? $rows : array();
}

/**
 * Parts helpers
 */
function ulp_db_get_parts_by_type( string $type ): array {
	global $wpdb;
	$table = $wpdb->prefix . 'ulp_parts';
	$prepared = $wpdb->prepare( "SELECT id, name, price FROM {$table} WHERE type=%s ORDER BY price ASC", $type );
	$rows = $wpdb->get_results( $prepared, ARRAY_A ); // phpcs:ignore WordPress.DB
	return is_array( $rows ) ? $rows : array();
}

function ulp_db_get_part_by_name( string $type, string $name ): ?array {
	global $wpdb;
	$table = $wpdb->prefix . 'ulp_parts';
	$prepared = $wpdb->prepare( "SELECT * FROM {$table} WHERE type=%s AND name=%s LIMIT 1", $type, $name );
	$row = $wpdb->get_row( $prepared, ARRAY_A ); // phpcs:ignore WordPress.DB
	return is_array( $row ) ? $row : null;
}

function ulp_db_upsert_part( array $data ): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'ulp_parts';
	$type = sanitize_text_field( $data['type'] ?? '' );
	$name = sanitize_text_field( $data['name'] ?? '' );
	$price = intval( $data['price'] ?? 0 );
	$existing = ulp_db_get_part_by_name( $type, $name );
	if ( $existing ) {
		$res = $wpdb->update( $table, array( 'price' => $price ), array( 'id' => intval( $existing['id'] ) ) ); // phpcs:ignore WordPress.DB
		return $res !== false;
	}
	$res = $wpdb->insert( $table, array( 'type' => $type, 'name' => $name, 'price' => $price ) ); // phpcs:ignore WordPress.DB
	return $res !== false;
}

function ulp_db_delete_part( int $id ): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'ulp_parts';
	$res = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB
	return $res !== false;
}