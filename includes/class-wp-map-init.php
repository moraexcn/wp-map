<?php
/**
 * 初始化插件的主类
 * 
 * @package Wp_Map
 */

// 防止直接访问
if (!defined('WPINC')) {
    die;
}

class WP_Map_Init {
    /**
     * 插件加载器
     */
    private $loader;

    /**
     * 插件名称
     */
    private $plugin_name;

    /**
     * 插件版本
     */
    private $version;

    /**
     * 初始化插件
     */
    public function __construct() {
        $this->plugin_name = 'wp-map';
        $this->version = WP_MAP_VERSION;
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * 加载依赖
     */
    private function load_dependencies() {
        require_once WP_MAP_PLUGIN_DIR . 'includes/class-wp-map-loader.php';
        require_once WP_MAP_PLUGIN_DIR . 'includes/class-wp-map-admin.php';
        require_once WP_MAP_PLUGIN_DIR . 'includes/class-wp-map-frontend.php';
        require_once WP_MAP_PLUGIN_DIR . 'includes/class-wp-map-api.php';

        $this->loader = new WP_Map_Loader();
    }

    /**
     * 设置本地化
     */
    private function set_locale() {
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
    }

    /**
     * 加载插件文本域
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'wp-map',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    /**
     * 注册管理员钩子
     */
    private function define_admin_hooks() {
        $plugin_admin = new WP_Map_Admin($this->get_plugin_name(), $this->get_version());

        // 添加管理菜单
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // 注册设置
        $this->loader->add_action('admin_init', $plugin_admin, 'register_setting');
        
        // 加载管理脚本和样式
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // 保存足迹
        $this->loader->add_action('admin_post_save_footprint', $plugin_admin, 'save_footprint');
        
        // 删除足迹
        $this->loader->add_action('admin_post_delete_footprint', $plugin_admin, 'delete_footprint');
    }

    /**
     * 注册前台钩子
     */
    private function define_public_hooks() {
        $plugin_public = new WP_Map_Frontend($this->get_plugin_name(), $this->get_version());

        // 加载前台脚本和样式
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // 注册自定义重写规则
        $this->loader->add_action('init', $plugin_public, 'register_rewrite_rules');
        
        // 处理模板重定向
        $this->loader->add_action('template_redirect', $plugin_public, 'template_redirect');
        
        // 添加查询变量
        $this->loader->add_filter('query_vars', $plugin_public, 'add_query_vars');
        

    }

    /**
     * 运行插件
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * 获取插件名称
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * 获取加载器
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * 获取版本
     */
    public function get_version() {
        return $this->version;
    }
}