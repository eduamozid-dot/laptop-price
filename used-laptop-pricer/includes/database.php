<?php
/**
 * Database operations for Used Laptop Pricer
 */

if (!defined('ABSPATH')) {
    exit;
}

class ULP_Database {
    
    /**
     * Create database tables on plugin activation
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for laptop models
        $table_models = $wpdb->prefix . 'ulp_laptop_models';
        $sql_models = "CREATE TABLE $table_models (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            brand varchar(100) NOT NULL,
            model varchar(200) NOT NULL,
            release_year int(4) NOT NULL,
            base_price decimal(15,2) NOT NULL,
            base_cpu varchar(200) NOT NULL,
            base_ram varchar(100) NOT NULL,
            base_gpu varchar(200) NOT NULL,
            base_storage varchar(200) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_model (brand, model, release_year)
        ) $charset_collate;";
        
        // Table for parts prices
        $table_parts = $wpdb->prefix . 'ulp_parts_prices';
        $sql_parts = "CREATE TABLE $table_parts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            part_type varchar(50) NOT NULL,
            part_name varchar(200) NOT NULL,
            part_specs text NOT NULL,
            price decimal(15,2) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_part (part_type, part_name, part_specs)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_models);
        dbDelta($sql_parts);
    }
    
    /**
     * Get all laptop models
     */
    public static function get_laptop_models($filters = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_laptop_models';
        $where_clauses = array();
        $where_values = array();
        
        if (!empty($filters['brand'])) {
            $where_clauses[] = 'brand = %s';
            $where_values[] = $filters['brand'];
        }
        
        if (!empty($filters['year'])) {
            $where_clauses[] = 'release_year = %d';
            $where_values[] = $filters['year'];
        }
        
        if (!empty($filters['min_price'])) {
            $where_clauses[] = 'base_price >= %f';
            $where_values[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $where_clauses[] = 'base_price <= %f';
            $where_values[] = $filters['max_price'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $sql = "SELECT * FROM $table $where_sql ORDER BY brand, model, release_year DESC";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get laptop model by ID
     */
    public static function get_laptop_model($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_laptop_models';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Get laptop model by brand and model
     */
    public static function get_laptop_model_by_brand_model($brand, $model, $year = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_laptop_models';
        
        if ($year) {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE brand = %s AND model = %s AND release_year = %d",
                $brand, $model, $year
            ));
        } else {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE brand = %s AND model = %s ORDER BY release_year DESC LIMIT 1",
                $brand, $model
            ));
        }
    }
    
    /**
     * Insert or update laptop model
     */
    public static function save_laptop_model($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_laptop_models';
        
        $model_data = array(
            'brand' => sanitize_text_field($data['brand']),
            'model' => sanitize_text_field($data['model']),
            'release_year' => intval($data['release_year']),
            'base_price' => floatval($data['base_price']),
            'base_cpu' => sanitize_text_field($data['base_cpu']),
            'base_ram' => sanitize_text_field($data['base_ram']),
            'base_gpu' => sanitize_text_field($data['base_gpu']),
            'base_storage' => sanitize_text_field($data['base_storage'])
        );
        
        if (isset($data['id']) && $data['id']) {
            // Update existing model
            return $wpdb->update(
                $table,
                $model_data,
                array('id' => intval($data['id']))
            );
        } else {
            // Insert new model
            return $wpdb->insert($table, $model_data);
        }
    }
    
    /**
     * Delete laptop model
     */
    public static function delete_laptop_model($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_laptop_models';
        return $wpdb->delete($table, array('id' => intval($id)));
    }
    
    /**
     * Get all brands
     */
    public static function get_brands() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_laptop_models';
        return $wpdb->get_col("SELECT DISTINCT brand FROM $table ORDER BY brand");
    }
    
    /**
     * Get models by brand
     */
    public static function get_models_by_brand($brand) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_laptop_models';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT model, release_year FROM $table WHERE brand = %s ORDER BY model, release_year DESC",
            $brand
        ));
    }
    
    /**
     * Get parts prices
     */
    public static function get_parts_prices($part_type = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_parts_prices';
        
        if ($part_type) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE part_type = %s ORDER BY part_name",
                $part_type
            ));
        } else {
            return $wpdb->get_results("SELECT * FROM $table ORDER BY part_type, part_name");
        }
    }
    
    /**
     * Get part price by name and specs
     */
    public static function get_part_price($part_type, $part_name, $part_specs) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_parts_prices';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE part_type = %s AND part_name = %s AND part_specs = %s",
            $part_type, $part_name, $part_specs
        ));
    }
    
    /**
     * Save part price
     */
    public static function save_part_price($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_parts_prices';
        
        $part_data = array(
            'part_type' => sanitize_text_field($data['part_type']),
            'part_name' => sanitize_text_field($data['part_name']),
            'part_specs' => sanitize_textarea_field($data['part_specs']),
            'price' => floatval($data['price'])
        );
        
        if (isset($data['id']) && $data['id']) {
            // Update existing part
            return $wpdb->update(
                $table,
                $part_data,
                array('id' => intval($data['id']))
            );
        } else {
            // Insert new part
            return $wpdb->insert($table, $part_data);
        }
    }
    
    /**
     * Delete part price
     */
    public static function delete_part_price($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_parts_prices';
        return $wpdb->delete($table, array('id' => intval($id)));
    }
    
    /**
     * Get part types
     */
    public static function get_part_types() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ulp_parts_prices';
        return $wpdb->get_col("SELECT DISTINCT part_type FROM $table ORDER BY part_type");
    }
}