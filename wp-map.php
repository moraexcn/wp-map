<?php
/**
 * Plugin Name: WP-Map 足迹地图
 * Plugin URI: https://www.moraex.com/wp-map
 * Description: 基于高德地图API的WordPress足迹地图插件，支持添加地点标记、描述和前端地图自定义展示。
 * Version: 1.1.8
 * Author: MoraEX
 * Author URI: https://www.moraex.com/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-map
 * Domain Path: /languages
 * Requires at least: 6.8
 * Requires PHP: 8.3
 */

// 防止直接访问
if (!defined('WPINC')) {
    die;
}

// 定义插件常量
define('WP_MAP_VERSION', '1.0.0');
define('WP_MAP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_MAP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_MAP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// 加载必要的文件
require_once WP_MAP_PLUGIN_DIR . 'includes/class-wp-map-init.php';
require_once WP_MAP_PLUGIN_DIR . 'includes/class-wp-map-install.php';
require_once WP_MAP_PLUGIN_DIR . 'includes/class-wp-map-api.php';
require_once WP_MAP_PLUGIN_DIR . 'includes/class-wp-map-shortcode.php';

// 激活插件时的钩子
register_activation_hook(__FILE__, array('WP_Map_Install', 'activate'));
register_deactivation_hook(__FILE__, array('WP_Map_Install', 'deactivate'));
register_uninstall_hook(__FILE__, array('WP_Map_Install', 'uninstall'));

// 插件更新时的钩子
add_action('plugins_loaded', function() {
    $current_version = get_option('wp_map_version', '0');
    if ($current_version !== WP_MAP_VERSION) {
        WP_Map_Install::update();
        update_option('wp_map_version', WP_MAP_VERSION);
    }
});

// 初始化插件
function wp_map_run() {
    $plugin = new WP_Map_Init();
    $plugin->run();
}

// 初始化API类
new WP_Map_API();

// 初始化短代码类并注册短代码
function wp_map_register_shortcode() {
    $shortcode = new WP_Map_Shortcode('wp-map', WP_MAP_VERSION);
    $shortcode->register_shortcode();
}
add_action('init', 'wp_map_register_shortcode');

// 运行插件
wp_map_run();