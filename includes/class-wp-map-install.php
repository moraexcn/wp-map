<?php
/**
 * 插件安装和卸载处理类
 * 
 * @package Wp_Map
 */

// 防止直接访问
if (!defined('WPINC')) {
    die;
}

class WP_Map_Install {
    /**
     * 插件激活时调用
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        flush_rewrite_rules();
    }

    /**
     * 插件卸载时调用
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * 插件删除时调用
     */
    public static function uninstall() {
        self::drop_tables();
        self::cleanup_options();
    }

    /**
     * 插件更新时调用
     */
    public static function update() {
        self::migrate_tables();
    }

    /**
     * 数据库迁移
     */
    private static function migrate_tables() {
        global $wpdb;
        
        // 检查是否需要迁移（移除tags字段）
        $table_name = $wpdb->prefix . 'footprints';
        $columns = $wpdb->get_col("DESC $table_name", 0);
        
        if (in_array('tags', $columns)) {
            // 移除tags字段
            $wpdb->query("ALTER TABLE $table_name DROP COLUMN tags");
        }
    }

    /**
     * 创建数据库表
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // 足迹表
        $table_name = $wpdb->prefix . 'footprints';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text DEFAULT NULL,
            latitude decimal(10, 8) NOT NULL,
            longitude decimal(11, 8) NOT NULL,
            visit_date date NOT NULL,
            link_url varchar(255) DEFAULT NULL,
            link_type varchar(20) DEFAULT 'page',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY latitude (latitude),
            KEY longitude (longitude),
            KEY visit_date (visit_date)
        ) $charset_collate;";
        
        // 设置表
        $settings_table = $wpdb->prefix . 'footprints_settings';
        $settings_sql = "CREATE TABLE $settings_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($settings_sql);
    }

    /**
     * 设置默认选项
     */
    private static function set_default_options() {
        $default_settings = array(
            'amap_js_key' => '',
            'amap_web_key' => '',
            'map_path' => 'maps',
            'page_title' => 'WP-Map 足迹地图',
            'page_description' => '基于高德地图 API 的 WordPress 足迹地图插件，支持添加地点标记、描述和前端地图自定义展示。',
            'map_zoom' => '4',
            'map_center_lat' => '39.9042',
            'map_center_lng' => '116.4074',
            'marker_color_default' => '#FF5722',
            'marker_color_with_link' => '#2196F3',
            'marker_color_no_link' => '#9E9E9E',
            'button_color_detail' => '#3498db',
            'map_theme' => 'whitesmoke',
            'map_height' => '500px',
            'show_filter' => 'yes',
            'enable_clustering' => 'yes'
        );

        global $wpdb;
        $table_name = $wpdb->prefix . 'footprints_settings';

        foreach ($default_settings as $key => $value) {
            $wpdb->replace(
                $table_name,
                array(
                    'setting_key' => $key,
                    'setting_value' => $value
                ),
                array('%s', '%s')
            );
        }
    }

    /**
     * 删除数据库表
     */
    private static function drop_tables() {
        global $wpdb;

        // 足迹表
        $footprints_table = $wpdb->prefix . 'footprints';
        $wpdb->query("DROP TABLE IF EXISTS $footprints_table");

        // 设置表
        $settings_table = $wpdb->prefix . 'footprints_settings';
        $wpdb->query("DROP TABLE IF EXISTS $settings_table");
    }

    /**
     * 清理WordPress选项
     */
    private static function cleanup_options() {
        // 删除插件版本选项
        delete_option('wp_map_version');
    }
}