<?php
/**
 * API相关功能类，处理AJAX请求
 * 
 * @package Wp_Map
 */

// 防止直接访问
if (!defined('WPINC')) {
    die;
}

class WP_Map_API {
    /**
     * 初始化类
     */
    public function __construct() {
        // 注册AJAX操作
        add_action('wp_ajax_get_footprints', array($this, 'get_footprints_ajax'));
        add_action('wp_ajax_nopriv_get_footprints', array($this, 'get_footprints_ajax'));
        
        add_action('wp_ajax_geocode_address', array($this, 'geocode_address_ajax'));
        add_action('wp_ajax_reverse_geocode', array($this, 'reverse_geocode_ajax'));
    }

    /**
     * 获取足迹数据 (AJAX)
     */
    public function get_footprints_ajax() {
        // 验证nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wp_map_frontend_nonce')) {
            wp_die(__('安全验证失败', 'wp-map'));
        }

        // 获取过滤参数
        $filters = array();
        if (!empty($_POST['year'])) {
            $filters['year'] = sanitize_text_field($_POST['year']);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'footprints';
        
        // 构建查询
        $sql = "SELECT * FROM $table_name WHERE 1=1";
        

        
        if (!empty($filters['year'])) {
            $sql .= $wpdb->prepare(" AND YEAR(visit_date) = %d", $filters['year']);
        }
        
        $sql .= " ORDER BY visit_date DESC";
        
        $results = $wpdb->get_results($sql);
        
        // 格式化数据
        $footprints = array();
        foreach ($results as $row) {
            $footprints[] = array(
                'id' => intval($row->id),
                'title' => $row->title,
                'description' => $row->description,
                'latitude' => floatval($row->latitude),
                'longitude' => floatval($row->longitude),
                'visit_date' => $row->visit_date,
                'tags' => $row->tags,
                'link_url' => $row->link_url,
                'link_type' => $row->link_type
            );
        }
        
        wp_send_json_success($footprints);
    }

    /**
     * 地址地理编码 (AJAX)
     */
    public function geocode_address_ajax() {
        // 验证nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wp_map_admin_nonce')) {
            wp_die(__('安全验证失败', 'wp-map'));
        }
        
        // 检查用户权限
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'wp-map'));
        }
        
        $address = sanitize_text_field($_POST['address']);
        if (empty($address)) {
            wp_send_json_error(array('message' => __('地址不能为空', 'wp-map')));
        }
        
        // 获取高德Web服务API密钥
        $settings = $this->get_settings();
        if (empty($settings['amap_web_key'])) {
            wp_send_json_error(array('message' => __('请先设置高德Web服务API密钥', 'wp-map')));
        }
        
        // 调用高德地理编码API
        $url = add_query_arg(array(
            'key' => $settings['amap_web_key'],
            'address' => $address,
            'output' => 'JSON'
        ), 'https://restapi.amap.com/v3/geocode/geo');
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => __('请求失败', 'wp-map')));
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data['status'] !== '1') {
            $error_message = isset($data['info']) ? $data['info'] : __('地理编码失败', 'wp-map');
            wp_send_json_error(array('message' => $error_message));
        }
        
        $geocodes = $data['geocodes'];
        if (empty($geocodes)) {
            wp_send_json_error(array('message' => __('未找到匹配的地址', 'wp-map')));
        }
        
        // 返回第一个结果
        $location = $geocodes[0];
        $coordinates = explode(',', $location['location']);
        
        wp_send_json_success(array(
            'longitude' => floatval($coordinates[0]),
            'latitude' => floatval($coordinates[1]),
            'formatted_address' => $location['formatted_address']
        ));
    }

    /**
     * 反向地理编码 (AJAX)
     */
    public function reverse_geocode_ajax() {
        // 验证nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wp_map_admin_nonce')) {
            wp_die(__('安全验证失败', 'wp-map'));
        }
        
        // 检查用户权限
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'wp-map'));
        }
        
        $longitude = floatval($_POST['longitude']);
        $latitude = floatval($_POST['latitude']);
        
        if (empty($longitude) || empty($latitude)) {
            wp_send_json_error(array('message' => __('经纬度不能为空', 'wp-map')));
        }
        
        // 获取高德Web服务API密钥
        $settings = $this->get_settings();
        if (empty($settings['amap_web_key'])) {
            wp_send_json_error(array('message' => __('请先设置高德Web服务API密钥', 'wp-map')));
        }
        
        // 调用高德反向地理编码API
        $url = add_query_arg(array(
            'key' => $settings['amap_web_key'],
            'location' => $longitude . ',' . $latitude,
            'output' => 'JSON'
        ), 'https://restapi.amap.com/v3/geocode/regeo');
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => __('请求失败', 'wp-map')));
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data['status'] !== '1') {
            $error_message = isset($data['info']) ? $data['info'] : __('反向地理编码失败', 'wp-map');
            wp_send_json_error(array('message' => $error_message));
        }
        
        $address = $data['regeocode'];
        $formatted_address = isset($address['formatted_address']) ? $address['formatted_address'] : '';
        
        wp_send_json_success(array(
            'formatted_address' => $formatted_address
        ));
    }

    /**
     * 获取插件设置
     */
    private function get_settings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'footprints_settings';
        
        $results = $wpdb->get_results("SELECT setting_key, setting_value FROM $table_name");
        
        $settings = array();
        foreach ($results as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }
        
        return $settings;
    }
}