<?php
/**
 * Plugin Name: لپ‌تاپ پرایسر | Used Laptop Pricer
 * Plugin URI: https://example.com/used-laptop-pricer
 * Description: افزونه محاسبه قیمت لپ‌تاپ دست دوم بر اساس Market-Based Pricing با پشتیبانی از ورود/خروج Excel، مدیریت مدل‌ها و قطعات.
 * Version: 1.0.0
 * Author: hoseinmos
 * Text Domain: used-laptop-pricer
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Constants
// -----------------------------------------------------------------------------
if ( ! defined( 'ULP_VERSION' ) ) {
	define( 'ULP_VERSION', '1.0.0' );
}
if ( ! defined( 'ULP_PLUGIN_FILE' ) ) {
	define( 'ULP_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'ULP_PLUGIN_DIR' ) ) {
	define( 'ULP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'ULP_PLUGIN_URL' ) ) {
	define( 'ULP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// -----------------------------------------------------------------------------
// Autoload vendor (PhpSpreadsheet if installed via Composer)
// -----------------------------------------------------------------------------
$ulp_composer_autoload = ULP_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $ulp_composer_autoload ) ) {
	require_once $ulp_composer_autoload;
}

// -----------------------------------------------------------------------------
// Includes
// -----------------------------------------------------------------------------
require_once ULP_PLUGIN_DIR . 'includes/helpers.php';
require_once ULP_PLUGIN_DIR . 'includes/calculate-price.php';

// Admin files are conditionally included
if ( is_admin() ) {
	require_once ULP_PLUGIN_DIR . 'admin/settings-page.php';
	require_once ULP_PLUGIN_DIR . 'admin/model-manager.php';
	require_once ULP_PLUGIN_DIR . 'admin/parts-manager.php';
	require_once ULP_PLUGIN_DIR . 'admin/excel-import.php';
	require_once ULP_PLUGIN_DIR . 'admin/excel-export.php';
}

// -----------------------------------------------------------------------------
// i18n
// -----------------------------------------------------------------------------
function ulp_load_textdomain() {
	load_plugin_textdomain( 'used-laptop-pricer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'ulp_load_textdomain' );

// -----------------------------------------------------------------------------
// Activation / Deactivation
// -----------------------------------------------------------------------------
function ulp_activate() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$models_table    = $wpdb->prefix . 'ulp_models';
	$parts_table     = $wpdb->prefix . 'ulp_parts';

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$models_sql = "CREATE TABLE $models_table (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		brand VARCHAR(191) NOT NULL,
		model VARCHAR(191) NOT NULL,
		release_year INT NOT NULL,
		base_price BIGINT NOT NULL,
		base_cpu VARCHAR(191) NOT NULL,
		base_ram VARCHAR(191) NOT NULL,
		base_gpu VARCHAR(191) NOT NULL,
		base_storage VARCHAR(191) NOT NULL,
		PRIMARY KEY  (id),
		KEY brand (brand),
		KEY model (model),
		KEY release_year (release_year)
	) $charset_collate;";

	$parts_sql = "CREATE TABLE $parts_table (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		type VARCHAR(50) NOT NULL,
		name VARCHAR(191) NOT NULL,
		price BIGINT NOT NULL,
		PRIMARY KEY  (id),
		KEY type (type),
		KEY name (name)
	) $charset_collate;";

	dbDelta( $models_sql );
	dbDelta( $parts_sql );

	// Default settings
	$default_settings = array(
		'currency'           => 'IRR',
		'dep_year1'          => 30,
		'dep_year2'          => 15,
		'dep_year3plus'      => 10,
		'condition_multipliers' => array(
			'new'      => 1.00,
			'clean'    => 0.95,
			'used'     => 0.90,
			'heavily_used' => 0.80,
		),
	);
	if ( ! get_option( 'ulp_settings' ) ) {
		add_option( 'ulp_settings', $default_settings );
	}
}
register_activation_hook( __FILE__, 'ulp_activate' );

function ulp_deactivate() {
	// Nothing for now. We keep data on deactivate.
}
register_deactivation_hook( __FILE__, 'ulp_deactivate' );

// -----------------------------------------------------------------------------
// Enqueue Scripts / Styles
// -----------------------------------------------------------------------------
function ulp_enqueue_frontend_assets() {
	wp_register_style( 'ulp-style', ULP_PLUGIN_URL . 'assets/css/style.css', array(), ULP_VERSION );
	wp_enqueue_style( 'ulp-style' );

	wp_register_script( 'ulp-form', ULP_PLUGIN_URL . 'assets/js/form.js', array( 'jquery' ), ULP_VERSION, true );
	wp_localize_script( 'ulp-form', 'ULPAjax', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'ulp_frontend_nonce' ),
	) );
	wp_enqueue_script( 'ulp-form' );
}
add_action( 'wp_enqueue_scripts' , 'ulp_enqueue_frontend_assets' );

function ulp_enqueue_admin_assets( $hook ) {
	// Load on our pages only
	if ( strpos( $hook, 'used-laptop-pricer' ) === false ) {
		return;
	}
	wp_register_style( 'ulp-admin-style', ULP_PLUGIN_URL . 'assets/css/style.css', array(), ULP_VERSION );
	wp_enqueue_style( 'ulp-admin-style' );

	wp_register_script( 'ulp-admin', ULP_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), ULP_VERSION, true );
	wp_localize_script( 'ulp-admin', 'ULPAdmin', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'ulp_admin_nonce' ),
	) );
	wp_enqueue_script( 'ulp-admin' );
}
add_action( 'admin_enqueue_scripts' , 'ulp_enqueue_admin_assets' );

// -----------------------------------------------------------------------------
// Menus
// -----------------------------------------------------------------------------
function ulp_register_admin_menus() {
	add_menu_page(
		__( 'لپ‌تاپ پرایسر', 'used-laptop-pricer' ),
		__( 'لپ‌تاپ پرایسر', 'used-laptop-pricer' ),
		'manage_options',
		'used-laptop-pricer',
		'ulp_models_page_render',
		'dashicons-laptop',
		26
	);

	add_submenu_page(
		'used-laptop-pricer',
		__( 'مدیریت مدل‌های پایه', 'used-laptop-pricer' ),
		__( 'مدل‌های پایه', 'used-laptop-pricer' ),
		'manage_options',
		'used-laptop-pricer',
		'ulp_models_page_render'
	);

	add_submenu_page(
		'used-laptop-pricer',
		__( 'مدیریت قطعات', 'used-laptop-pricer' ),
		__( 'قطعات', 'used-laptop-pricer' ),
		'manage_options',
		'ulp-parts',
		'ulp_parts_page_render'
	);

	add_submenu_page(
		'used-laptop-pricer',
		__( 'تنظیمات', 'used-laptop-pricer' ),
		__( 'تنظیمات', 'used-laptop-pricer' ),
		'manage_options',
		'ulp-settings',
		'ulp_settings_page_render'
	);

	add_submenu_page(
		'used-laptop-pricer',
		__( 'ورود از Excel', 'used-laptop-pricer' ),
		__( 'ورود Excel', 'used-laptop-pricer' ),
		'manage_options',
		'ulp-excel-import',
		'ulp_excel_import_page_render'
	);

	add_submenu_page(
		'used-laptop-pricer',
		__( 'خروجی Excel', 'used-laptop-pricer' ),
		__( 'خروجی Excel', 'used-laptop-pricer' ),
		'manage_options',
		'ulp-excel-export',
		'ulp_excel_export_page_render'
	);
}
add_action( 'admin_menu', 'ulp_register_admin_menus' );

// -----------------------------------------------------------------------------
// Shortcode
// -----------------------------------------------------------------------------
function ulp_shortcode_handler() {
	ob_start();
	include ULP_PLUGIN_DIR . 'templates/form.php';
	return ob_get_clean();
}
add_shortcode( 'used_laptop_pricer', 'ulp_shortcode_handler' );

// -----------------------------------------------------------------------------
// AJAX: Calculate price
// -----------------------------------------------------------------------------
function ulp_ajax_calculate_price() {
	check_ajax_referer( 'ulp_frontend_nonce', 'nonce' );

	$brand         = isset( $_POST['brand'] ) ? sanitize_text_field( wp_unslash( $_POST['brand'] ) ) : '';
	$model         = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : '';
	$release_year  = isset( $_POST['release_year'] ) ? intval( $_POST['release_year'] ) : 0;
	$condition_key = isset( $_POST['condition'] ) ? sanitize_text_field( wp_unslash( $_POST['condition'] ) ) : 'used';
	$user_cpu      = isset( $_POST['cpu'] ) ? sanitize_text_field( wp_unslash( $_POST['cpu'] ) ) : '';
	$user_ram      = isset( $_POST['ram'] ) ? sanitize_text_field( wp_unslash( $_POST['ram'] ) ) : '';
	$user_gpu      = isset( $_POST['gpu'] ) ? sanitize_text_field( wp_unslash( $_POST['gpu'] ) ) : '';
	$user_storage  = isset( $_POST['storage'] ) ? sanitize_text_field( wp_unslash( $_POST['storage'] ) ) : '';

	$result = ulp_calculate_price( compact( 'brand', 'model', 'release_year', 'condition_key', 'user_cpu', 'user_ram', 'user_gpu', 'user_storage' ) );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
	}

	wp_send_json_success( $result );
}
add_action( 'wp_ajax_ulp_calculate_price', 'ulp_ajax_calculate_price' );
add_action( 'wp_ajax_nopriv_ulp_calculate_price', 'ulp_ajax_calculate_price' );

// -----------------------------------------------------------------------------
// AJAX: Dynamic dropdowns for brand/model and parts
// -----------------------------------------------------------------------------
function ulp_ajax_get_brands() {
	check_ajax_referer( 'ulp_frontend_nonce', 'nonce' );
	$brands = ulp_db_get_distinct_brands();
	wp_send_json_success( array( 'brands' => $brands ) );
}
add_action( 'wp_ajax_ulp_get_brands', 'ulp_ajax_get_brands' );
add_action( 'wp_ajax_nopriv_ulp_get_brands', 'ulp_ajax_get_brands' );

function ulp_ajax_get_models_by_brand() {
	check_ajax_referer( 'ulp_frontend_nonce', 'nonce' );
	$brand = isset( $_GET['brand'] ) ? sanitize_text_field( wp_unslash( $_GET['brand'] ) ) : '';
	$models = $brand ? ulp_db_get_models_by_brand( $brand ) : array();
	wp_send_json_success( array( 'models' => $models ) );
}
add_action( 'wp_ajax_ulp_get_models', 'ulp_ajax_get_models_by_brand' );
add_action( 'wp_ajax_nopriv_ulp_get_models', 'ulp_ajax_get_models_by_brand' );

function ulp_ajax_get_parts() {
	check_ajax_referer( 'ulp_frontend_nonce', 'nonce' );
	$parts = array(
		'cpu' => ulp_db_get_parts_by_type( 'cpu' ),
		'ram' => ulp_db_get_parts_by_type( 'ram' ),
		'gpu' => ulp_db_get_parts_by_type( 'gpu' ),
		'ssd' => ulp_db_get_parts_by_type( 'ssd' ),
		'hdd' => ulp_db_get_parts_by_type( 'hdd' ),
	);
	wp_send_json_success( $parts );
}
add_action( 'wp_ajax_ulp_get_parts', 'ulp_ajax_get_parts' );
add_action( 'wp_ajax_nopriv_ulp_get_parts', 'ulp_ajax_get_parts' );

// -----------------------------------------------------------------------------
// Admin Notices: Missing PhpSpreadsheet
// -----------------------------------------------------------------------------
function ulp_admin_notice_if_phpspreadsheet_missing() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! class_exists( '\\PhpOffice\\PhpSpreadsheet\\Spreadsheet' ) ) {
		$install_url = esc_url( 'https://github.com/PHPOffice/PhpSpreadsheet' );
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'کتابخانه PhpSpreadsheet یافت نشد. برای فعال‌سازی ورود/خروج Excel، پوشه vendor را با اجرای Composer در دایرکتوری افزونه نصب کنید.', 'used-laptop-pricer' ) . '</p>' .
			'<p>' . sprintf( wp_kses_post( __( 'روی سرور وردپرس، در مسیر افزونه دستور زیر را اجرا کنید: %s', 'used-laptop-pricer' ) ), '<code>composer install</code>' ) . '</p>' .
			'<p><a href="' . $install_url . '" target="_blank" rel="noreferrer">' . esc_html__( 'اطلاعات بیشتر درباره PhpSpreadsheet', 'used-laptop-pricer' ) . '</a></p></div>';
	}
}
add_action( 'admin_notices', 'ulp_admin_notice_if_phpspreadsheet_missing' );