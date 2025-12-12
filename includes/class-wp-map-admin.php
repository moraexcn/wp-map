<?php
/**
 * 管理员后台相关功能类
 * 
 * @package Wp_Map
 */

// 防止直接访问
if (!defined('WPINC')) {
    die;
}

class WP_Map_Admin {
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
            WP_MAP_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * 注册脚本文件
     */
    public function enqueue_scripts($hook) {
        // 只在插件页面加载脚本
        if (strpos($hook, 'wp-map') !== false) {
            // 获取插件设置
            require_once WP_MAP_PLUGIN_DIR . 'includes/class-wp-map-frontend.php';
            $frontend = new WP_Map_Frontend($this->plugin_name, $this->version);
            $settings = $frontend->get_settings();
            
            // 如果设置了高德地图API密钥，加载高德地图API
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
                WP_MAP_PLUGIN_URL . 'admin/js/admin.js',
                array('jquery', !empty($settings['amap_js_key']) ? 'amap-js-api' : 'jquery'),
                $this->version,
                true
            );

            // 传递AJAX URL和nonce到前端
            wp_localize_script(
                $this->plugin_name,
                'wp_map_admin',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wp_map_admin_nonce'),
                    'strings' => array(
                        'confirm_delete' => __('确定要删除这个足迹吗？', 'wp-map'),
                        'location_required' => __('请选择地图上的位置', 'wp-map'),
                        'title_required' => __('请输入地点名称', 'wp-map')
                    )
                )
            );
        }
    }

    /**
     * 添加管理菜单
     */
    public function add_plugin_admin_menu() {
        // 添加主菜单
        add_menu_page(
            __('足迹地图', 'wp-map'),
            __('足迹', 'wp-map'),
            'manage_options',
            'wp-map',
            array($this, 'display_plugin_main_page'),
            'dashicons-location-alt',
            25
        );

        // 添加子菜单 - 足迹列表
        add_submenu_page(
            'wp-map',
            __('足迹列表', 'wp-map'),
            __('足迹列表', 'wp-map'),
            'manage_options',
            'wp-map',
            array($this, 'display_plugin_main_page')
        );

        // 添加子菜单 - 添加足迹
        add_submenu_page(
            'wp-map',
            __('添加足迹', 'wp-map'),
            __('添加足迹', 'wp-map'),
            'manage_options',
            'wp-map-add',
            array($this, 'display_add_footprint_page')
        );

        // 添加子菜单 - 设置
        add_submenu_page(
            'wp-map',
            __('足迹设置', 'wp-map'),
            __('足迹设置', 'wp-map'),
            'manage_options',
            'wp-map-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * 显示主页面 - 足迹列表
     */
    public function display_plugin_main_page() {
        include_once WP_MAP_PLUGIN_DIR . 'admin/partials/footprints-list.php';
    }

    /**
     * 显示添加足迹页面
     */
    public function display_add_footprint_page() {
        include_once WP_MAP_PLUGIN_DIR . 'admin/partials/add-footprint.php';
    }

    /**
     * 显示设置页面
     */
    public function display_settings_page() {
        include_once WP_MAP_PLUGIN_DIR . 'admin/partials/settings-page.php';
    }

    /**
     * 保存足迹
     */
    public function save_footprint() {
        // 验证nonce
        if (!isset($_POST['wp_map_nonce']) || !wp_verify_nonce($_POST['wp_map_nonce'], 'wp_map_save_footprint')) {
            wp_die(__('安全验证失败', 'wp-map'));
        }

        // 检查用户权限
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'wp-map'));
        }

        // 获取表单数据
        $title = sanitize_text_field($_POST['footprint_title']);
        $description = sanitize_textarea_field($_POST['footprint_description']);
        $latitude = floatval($_POST['footprint_latitude']);
        $longitude = floatval($_POST['footprint_longitude']);
        $visit_date = sanitize_text_field($_POST['footprint_visit_date']);
        $link_url = esc_url_raw($_POST['footprint_link_url']);
        $link_type = sanitize_text_field($_POST['footprint_link_type']);
        $footprint_id = isset($_POST['footprint_id']) ? intval($_POST['footprint_id']) : 0;

        // 验证必填字段
        if (empty($title) || empty($latitude) || empty($longitude) || empty($visit_date)) {
            wp_die(__('请填写所有必填字段', 'wp-map'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'footprints';

        $data = array(
            'title' => $title,
            'description' => $description,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'visit_date' => $visit_date,
            'link_url' => $link_url,
            'link_type' => $link_type
        );

        if ($footprint_id > 0) {
            // 更新现有足迹
            $where = array('id' => $footprint_id);
            $format = array('%s', '%s', '%f', '%f', '%s', '%s', '%s');
            $where_format = array('%d');
            $result = $wpdb->update($table_name, $data, $where, $format, $where_format);
        } else {
            // 插入新足迹
            $format = array('%s', '%s', '%f', '%f', '%s', '%s', '%s');
            $result = $wpdb->insert($table_name, $data, $format);
        }

        // 重定向到足迹列表页面
        wp_safe_redirect(admin_url('admin.php?page=wp-map&message=success'));
        exit;
    }

    /**
     * 删除足迹
     */
    public function delete_footprint() {
        // 验证nonce
        if (!isset($_GET['wp_map_nonce']) || !wp_verify_nonce($_GET['wp_map_nonce'], 'wp_map_delete_footprint')) {
            wp_die(__('安全验证失败', 'wp-map'));
        }

        // 检查用户权限
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'wp-map'));
        }

        $footprint_id = intval($_GET['footprint_id']);
        if ($footprint_id <= 0) {
            wp_die(__('无效的足迹ID', 'wp-map'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'footprints';
        $result = $wpdb->delete($table_name, array('id' => $footprint_id), array('%d'));

        // 重定向到足迹列表页面
        wp_safe_redirect(admin_url('admin.php?page=wp-map&message=deleted'));
        exit;
    }

    /**
     * 注册设置
     */
    public function register_setting() {
        register_setting(
            'wp_map_settings_group',
            'wp_map_settings',
            array($this, 'sanitize_settings')
        );
    }

    /**
     * 清理设置数据
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['amap_js_key'] = sanitize_text_field($input['amap_js_key']);
        $sanitized['amap_web_key'] = sanitize_text_field($input['amap_web_key']);
        $sanitized['map_path'] = sanitize_text_field($input['map_path']);
        $sanitized['page_title'] = sanitize_text_field($input['page_title']);
        $sanitized['page_description'] = sanitize_textarea_field($input['page_description']);
        $sanitized['map_zoom'] = absint($input['map_zoom']);
        $sanitized['map_center_lat'] = floatval($input['map_center_lat']);
        $sanitized['map_center_lng'] = floatval($input['map_center_lng']);
        $sanitized['marker_color_default'] = sanitize_hex_color($input['marker_color_default']);
        $sanitized['marker_color_with_link'] = sanitize_hex_color($input['marker_color_with_link']);
        $sanitized['marker_color_no_link'] = sanitize_hex_color($input['marker_color_no_link']);
        $sanitized['button_color_detail'] = sanitize_hex_color($input['button_color_detail']);
        $sanitized['map_theme'] = sanitize_text_field($input['map_theme']);
        $sanitized['map_height'] = sanitize_text_field($input['map_height']);
        $sanitized['shortcode_enabled'] = isset($input['shortcode_enabled']) && $input['shortcode_enabled'] === 'yes' ? 'yes' : 'no';
        $sanitized['map_path_enabled'] = isset($input['map_path_enabled']) && $input['map_path_enabled'] === 'yes' ? 'yes' : 'no';

        // 验证地图路径
        if (empty($sanitized['map_path'])) {
            $sanitized['map_path'] = 'map';
        }
        
        // 确保地图路径只包含字母、数字和连字符
        $sanitized['map_path'] = preg_replace('/[^a-z0-9\-]/i', '', $sanitized['map_path']);
        
        // 检查是否需要刷新重写规则
        $current_path = $this->get_setting('map_path');
        $current_enabled = $this->get_setting('map_path_enabled');
        
        // 如果路径改变了或者启用状态改变了，需要刷新重写规则
        if (($current_path && $current_path !== $sanitized['map_path']) || 
            ($current_enabled !== $sanitized['map_path_enabled'])) {
            // 重置重写规则标志
            update_option('wp_map_rewrite_rules_flushed', 0);
            // 触发重写规则刷新
            flush_rewrite_rules();
        }

        // 保存到数据库
        global $wpdb;
        $table_name = $wpdb->prefix . 'footprints_settings';

        foreach ($sanitized as $key => $value) {
            $wpdb->replace(
                $table_name,
                array(
                    'setting_key' => $key,
                    'setting_value' => $value
                ),
                array('%s', '%s')
            );
        }

        return $sanitized;
    }
    
    /**
     * 获取单个设置值
     */
    private function get_setting($key) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'footprints_settings';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM $table_name WHERE setting_key = %s",
            $key
        ));
    }
}