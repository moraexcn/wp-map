<?php
/**
 * 前端地图页面模板
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 如果WordPress未加载，手动加载必要的WordPress函数
if (!function_exists('wp_head')) {
    // 加载WordPress
    $wp_config_path = dirname(dirname(dirname(__FILE__)));
    if (file_exists($wp_config_path . '/wp-config.php')) {
        require_once($wp_config_path . '/wp-config.php');
    } else if (file_exists(dirname($wp_config_path) . '/wp-config.php')) {
        require_once(dirname($wp_config_path) . '/wp-config.php');
    } else if (file_exists(dirname(dirname($wp_config_path)) . '/wp-config.php')) {
        require_once(dirname(dirname($wp_config_path)) . '/wp-config.php');
    }
}

// 获取插件设置
require_once WP_MAP_PLUGIN_DIR . 'includes/class-wp-map-frontend.php';
$frontend = new WP_Map_Frontend('wp-map', WP_MAP_VERSION);
$settings = $frontend->get_settings();

// 获取足迹数据
$footprints = $frontend->get_footprints();

// 设置页面标题和描述
$page_title = isset($settings['page_title']) ? $settings['page_title'] : '足迹地图';
$page_description = isset($settings['page_description']) ? $settings['page_description'] : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($page_title); ?></title>
    <?php if (!empty($page_description)) : ?>
    <meta name="description" content="<?php echo esc_attr($page_description); ?>">
    <?php endif; ?>
    <?php 
    wp_head(); 
    
    // 如果设置了高德地图API密钥，手动加载高德地图API
    if (!empty($settings['amap_js_key'])) {
        ?>
        <script src="https://webapi.amap.com/maps?v=2.0&key=<?php echo esc_attr($settings['amap_js_key']); ?>"></script>
        <script>
        // 监听高德地图API加载完成
        window.onAmapLoaded = function() {
            var event = new CustomEvent('amap_ready');
            document.dispatchEvent(event);
        };
        
        // 如果AMap已经加载，直接触发事件
        if (typeof AMap !== 'undefined') {
            window.onAmapLoaded();
        }
        </script>
        <?php
    }
    ?>
    <style>
        * {
            box-sizing: border-box;
        }
        
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            overflow: hidden;
        }
        
        .wp-map-page {
            height: 100vh;
            width: 100%;
            margin: 0;
            padding: 0;
            position: relative;
        }
        
        .wp-map-map-container {
            height: 100%;
            width: 100%;
            position: relative;
        }
        
        .wp-map-map-wrapper {
            height: 100%;
            width: 100%;
            position: relative;
        }
        
        .wp-map-map {
            height: 100% !important;
            width: 100%;
            display: block;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        
        .wp-map-map.loaded {
            opacity: 1;
        }
        

        
        .wp-map-notice {
            padding: 20px;
            background: #f8d7da;
            color: #721c24;
            border-radius: 4px;
            text-align: center;
            margin: 20px;
        }
    </style>

<div class="wp-map-page">
    <?php if (empty($settings['amap_js_key'])) : ?>
        <div class="wp-map-notice wp-map-notice-error">
            <?php _e('地图API未配置，请联系网站管理员设置高德地图API密钥。', 'wp-map'); ?>
        </div>
    <?php else: ?>
        <div class="wp-map-map-container" id="wp-map-front-map">
            <div class="wp-map-map-wrapper">
                <div class="wp-map-map"></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
// 手动加载必要的脚本和样式
$frontend = new WP_Map_Frontend('wp-map', WP_MAP_VERSION);
$frontend->enqueue_styles();
$frontend->enqueue_scripts();

// 打印已加载的脚本和样式
wp_print_styles();
wp_print_scripts();
wp_footer(); 
?>

    <script>
        // 确保jQuery已加载
        if (typeof jQuery === 'undefined') {
            console.error('jQuery未加载，请检查WordPress核心资源是否正确加载');
        }
        
        // 初始化地图
        jQuery(document).ready(function($) {
            var mapInitialized = false;
            
            // 高德地图API加载完成后初始化
            function initMap() {
                if (mapInitialized) return;
                mapInitialized = true;
                
                // 触发自定义事件，通知frontend.js初始化地图
                $(document).trigger('wp_map_init');
            }
            
            // 如果AMap已经加载，直接初始化
            if (typeof AMap !== 'undefined') {
                setTimeout(initMap, 100);
            } else {
                // 等待AMap加载完成
                document.addEventListener('amap_ready', function() {
                    setTimeout(initMap, 100);
                });
            }
            
            // 添加安全机制，确保地图初始化
            setTimeout(function() {
                if (!mapInitialized && typeof AMap !== 'undefined') {
                    initMap();
                }
            }, 2000);
        });
    </script>
</body>
</html>