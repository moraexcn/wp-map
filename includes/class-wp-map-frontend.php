<?php
/**
 * 前台显示功能类
 * 
 * @package Wp_Map
 */

// 防止直接访问
if (!defined('WPINC')) {
    die;
}

class WP_Map_Frontend {
    /**
     * 插件名称
     */
    private $plugin_name;

    /**
     * 插件版本
     */
    private $version;

    /**
     * 初始化类
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * 注册样式文件
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            WP_MAP_PLUGIN_URL . 'public/css/frontend.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * 注册脚本文件
     */
    public function enqueue_scripts() {
        // 获取插件设置
        $settings = $this->get_settings();
        
        // 只在自定义地图页面的情况下加载脚本
        $load_scripts = false;
        
        // 检查是否是我们的自定义页面
        if (get_query_var('wp_map_page')) {
            $load_scripts = true;
        }
        
        // 为直接访问模板页面的情况加载脚本
        $trace = debug_backtrace();
        foreach ($trace as $call) {
            if (isset($call['file']) && strpos($call['file'], 'map-page.php') !== false) {
                $load_scripts = true;
                break;
            }
        }
        
        if ($load_scripts) {
            // 加载高德地图API
            if (!empty($settings['amap_js_key'])) {
                wp_enqueue_script(
                    'amap-js-api',
                    'https://webapi.amap.com/maps?v=2.0&key=' . esc_attr($settings['amap_js_key']),
                    array(),
                    '2.0',
                    true
                );
            }
            
            wp_enqueue_script(
                $this->plugin_name,
                WP_MAP_PLUGIN_URL . 'public/js/frontend.js',
                array('jquery', !empty($settings['amap_js_key']) ? 'amap-js-api' : 'jquery'),
                $this->version,
                true
            );

            // 传递数据到前端
            wp_localize_script(
                $this->plugin_name,
                'wp_map_frontend',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wp_map_frontend_nonce'),
                    'settings' => $settings,
                    'strings' => array(
                        'loading' => __('加载中...', 'wp-map'),
                        'no_footprints' => __('暂无足迹数据', 'wp-map'),
                        'error_loading' => __('加载数据时出错', 'wp-map')
                    )
                )
            );
        }
    }

    /**
     * 注册自定义重写规则
     */
    public function register_rewrite_rules() {
        // 获取设置中的自定义路径
        $settings = $this->get_settings();
        
        // 检查地图路径功能是否启用
        if (isset($settings['map_path_enabled']) && $settings['map_path_enabled'] === 'yes') {
            $map_path = isset($settings['map_path']) ? $settings['map_path'] : 'map';
            
            // 只添加主地图页面重写规则，删除单个足迹页面规则
            add_rewrite_rule(
                '^' . $map_path . '/?$',
                'index.php?wp_map_page=map',
                'top'
            );
            
            // 刷新重写规则（仅在插件激活时执行）
            if (!get_option('wp_map_rewrite_rules_flushed')) {
                flush_rewrite_rules();
                update_option('wp_map_rewrite_rules_flushed', 1);
            }
        }
    }
    
    /**
     * 添加查询变量
     */
    public function add_query_vars($query_vars) {
        $query_vars[] = 'wp_map_page';
        return $query_vars;
    }
    
    /**
     * 处理模板重定向
     */
    public function template_redirect() {
        $page = get_query_var('wp_map_page');
        
        if ($page === 'map') {
            // 检查地图路径功能是否启用
            $settings = $this->get_settings();
            if (isset($settings['map_path_enabled']) && $settings['map_path_enabled'] === 'yes') {
                // 只处理主地图页面
                include_once WP_MAP_PLUGIN_DIR . 'templates/map-page.php';
                exit;
            } else {
                // 如果功能未启用，显示404页面
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                get_template_part(404);
                exit;
            }
        }
    }



    /**
     * 获取所有足迹数据
     */
    public function get_footprints($filters = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'footprints';
        
        // 构建查询
        $sql = "SELECT * FROM $table_name WHERE 1=1";
        

        
        if (!empty($filters['year'])) {
            $sql .= $wpdb->prepare(" AND YEAR(visit_date) = %d", $filters['year']);
        }
        
        $sql .= " ORDER BY visit_date DESC";
        
        $results = $wpdb->get_results($sql);
        
        return $results;
    }



    /**
     * 获取插件设置
     */
    public function get_settings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'footprints_settings';
        
        $results = $wpdb->get_results("SELECT setting_key, setting_value FROM $table_name");
        
        $settings = array();
        foreach ($results as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }
        
        // 设置默认值
        $defaults = array(
            'amap_js_key' => '',
            'amap_web_key' => '',
            'map_zoom' => '10',
            'map_center_lat' => '39.9042',
            'map_center_lng' => '116.4074',
            'marker_color_default' => '#FF5722',
            'marker_color_with_link' => '#2196F3',
            'marker_color_no_link' => '#9E9E9E',
            'button_color_detail' => '#3498db',
            'map_theme' => 'whitesmoke',
            'map_height' => '500px',
            'show_filter' => 'yes',
            'enable_clustering' => 'yes',
            'shortcode_enabled' => 'no',
            'map_path_enabled' => 'no'
        );
        
        return wp_parse_args($settings, $defaults);
    }


}