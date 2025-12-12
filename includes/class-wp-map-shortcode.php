<?php
/**
 * 短代码处理类
 * 
 * @package Wp_Map
 */

// 防止直接访问
if (!defined('WPINC')) {
    die;
}

class WP_Map_Shortcode {
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
     * 注册短代码
     */
    public function register_shortcode() {
        add_shortcode('wp_map', array($this, 'render_shortcode'));
    }

    /**
     * 渲染短代码
     */
    public function render_shortcode($atts) {
        // 获取插件设置
        $settings = $this->get_settings();
        
        // 检查短代码功能是否启用
        if ($settings['shortcode_enabled'] !== 'yes') {
            return '<div class="wp-map-notice wp-map-notice-error">' . __('短代码功能未启用，请在插件设置中启用。', 'wp-map') . '</div>';
        }

        // 检查高德地图API密钥
        if (empty($settings['amap_js_key'])) {
            return '<div class="wp-map-notice wp-map-notice-error">' . __('地图API未配置，请联系网站管理员设置高德地图API密钥。', 'wp-map') . '</div>';
        }

        // 处理短代码属性
        $atts = shortcode_atts(array(
            'height' => isset($settings['map_height']) ? $settings['map_height'] : '500px'
        ), $atts, 'wp_map');

        // 生成唯一ID
        $map_id = 'wp-map-shortcode-' . uniqid();

        // 输出HTML - 直接复制前端地图的代码结构
        ob_start();
        ?>
        <div class="wp-map-container" id="<?php echo esc_attr($map_id); ?>">
            <div class="wp-map-wrapper">
                <div class="wp-map-map"></div>
            </div>
        </div>
        
        <style>
        .wp-map-container {
            margin: 0;
            position: relative;
            height: <?php echo esc_attr($atts['height']); ?>;
            width: 100%;
        }

        .wp-map-wrapper {
            position: relative;
            border: none;
            border-radius: 0;
            overflow: hidden;
            background: #f5f5f5;
            height: 100%;
            width: 100%;
        }

        .wp-map-map {
            width: 100%;
            height: 100%;
            display: block;
        }

        .wp-map-error {
            padding: 20px;
            background: #f8d7da;
            color: #721c24;
            border-radius: 4px;
            text-align: center;
        }

        /* 信息窗口样式 */
        .wp-map-info-window {
            max-width: 300px;
        }

        .wp-map-info-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .wp-map-info-date {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .wp-map-info-description {
            font-size: 14px;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .wp-map-info-link {
            display: inline-block;
            padding: 8px 16px;
            background-color: <?php echo esc_attr($settings['button_color_detail']); ?>;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .wp-map-info-link:hover {
            background-color: <?php echo esc_attr($this->lighten_color($settings['button_color_detail'], -20)); ?>;
            color: white;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // 直接复制前端地图的初始化代码
            function loadAmapAPI(callback) {
                if (typeof AMap !== 'undefined') {
                    callback();
                    return;
                }
                
                var script = document.createElement('script');
                script.src = 'https://webapi.amap.com/maps?v=2.0&key=<?php echo esc_js($settings['amap_js_key']); ?>';
                script.onload = callback;
                document.head.appendChild(script);
            }

            loadAmapAPI(function() {
                initShortcodeMap('<?php echo esc_js($map_id); ?>', <?php echo json_encode($atts); ?>);
            });

            function initShortcodeMap(mapId, atts) {
                var $container = $('#' + mapId);
                var $mapElement = $container.find('.wp-map-map');
                
                // 检查地图容器是否存在
                if (!$mapElement.length) {
                    console.error('地图容器元素不存在，请检查HTML结构');
                    return;
                }
                
                // 设置地图容器的背景色，避免灰屏闪烁
                $mapElement.css('background-color', '#f8f9fa');
                
                // 创建地图实例 - 使用前端地图的配置
                var map = new AMap.Map($mapElement[0], {
                    center: [parseFloat('<?php echo esc_js($settings['map_center_lng']); ?>'), parseFloat('<?php echo esc_js($settings['map_center_lat']); ?>')],
                    zoom: parseInt('<?php echo esc_js($settings['map_zoom']); ?>'),
                    resizeEnable: true,
                    viewMode: '2D',
                    features: ['bg', 'point', 'road', 'building'],
                    mapStyle: 'amap://styles/<?php echo esc_js($settings['map_theme']); ?>',
                    mapEvents: ['complete']
                });
                
                // 地图加载完成后显示地图
                map.on('complete', function() {
                    $mapElement.css('background-color', '');
                    $mapElement.addClass('loaded');
                });
                
                // 添加控件 - 使用前端地图的安全方式
                var checkMapReady = function(retryCount) {
                    retryCount = retryCount || 0;
                    
                    // 检查地图是否已完全初始化
                    if (map && typeof map.addControl === 'function') {
                        try {
                            // 添加缩放控件
                            if (typeof AMap.Scale === 'function') {
                                var scale = new AMap.Scale({
                                    position: 'LB'
                                });
                                map.addControl(scale);
                            }
                        } catch (e) {
                            console.warn('Scale控件初始化失败:', e);
                        }
                        
                        try {
                            // 添加工具栏控件
                            if (typeof AMap.ToolBar === 'function') {
                                var toolbar = new AMap.ToolBar({
                                    position: 'RT'
                                });
                                map.addControl(toolbar);
                            }
                        } catch (e) {
                            console.warn('ToolBar控件初始化失败:', e);
                        }
                    } else if (retryCount < 10) {
                        // 如果地图未准备好，重试
                        setTimeout(function() {
                            checkMapReady(retryCount + 1);
                        }, 100);
                    } else {
                        console.warn('地图控件添加超时，地图可能未完全初始化');
                    }
                };
                
                // 延迟检查地图状态
                setTimeout(function() {
                    checkMapReady(0);
                }, 500);
                
                // 加载足迹数据
                loadFootprints(map);
            }
            
            function loadFootprints(map) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'get_footprints',
                        nonce: '<?php echo wp_create_nonce('wp_map_frontend_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            renderMap(map, response.data);
                        } else {
                            showError(map, '加载数据时出错');
                        }
                    },
                    error: function() {
                        showError(map, '加载数据时出错');
                    }
                });
            }
            
            function renderMap(map, footprints) {
                // 清除现有标记
                if (window.shortcodeMarkers && window.shortcodeMarkers.length > 0) {
                    map.remove(window.shortcodeMarkers);
                    window.shortcodeMarkers = [];
                }
                
                if (footprints.length === 0) {
                    return;
                }
                
                // 创建标记
                var infoWindows = [];
                window.shortcodeMarkers = [];
                
                footprints.forEach(function(footprint) {
                    // 根据足迹是否有链接设置标记颜色
                    var markerColor = '<?php echo esc_js($settings['marker_color_default']); ?>';
                    
                    if (footprint.link_url) {
                        markerColor = '<?php echo esc_js($settings['marker_color_with_link']); ?>';
                    } else {
                        markerColor = '<?php echo esc_js($settings['marker_color_no_link']); ?>';
                    }
                    
                    // 创建标记 - 使用前端地图的图标创建方式
                    var marker = new AMap.Marker({
                        position: [footprint.longitude, footprint.latitude],
                        title: footprint.title,
                        icon: new AMap.Icon({
                            size: new AMap.Size(24, 24),
                            image: createMarkerIcon(markerColor),
                            imageSize: new AMap.Size(24, 24)
                        })
                    });
                    
                    // 创建信息窗口
                    var infoWindow = new AMap.InfoWindow({
                        content: createInfoWindowContent(footprint),
                        offset: new AMap.Pixel(0, -30)
                    });
                    
                    // 点击标记显示信息窗口
                    marker.on('click', function() {
                        infoWindow.open(map, marker.getPosition());
                    });
                    
                    window.shortcodeMarkers.push(marker);
                    infoWindows.push(infoWindow);
                });
                
                // 添加标记到地图
                map.add(window.shortcodeMarkers);
            }
            
            function createInfoWindowContent(footprint) {
                var content = '<div class="wp-map-info-window">';
                content += '<div class="wp-map-info-title">' + footprint.title + '</div>';
                content += '<div class="wp-map-info-date">' + footprint.visit_date + '</div>';
                
                if (footprint.description) {
                    content += '<div class="wp-map-info-description">' + footprint.description + '</div>';
                }
                
                if (footprint.link_url) {
                    var linkText = footprint.link_type === 'external' ? '查看详情' : '查看文章';
                    content += '<a href="' + footprint.link_url + '" class="wp-map-info-link" target="_blank">' + linkText + '</a>';
                }
                
                content += '</div>';
                return content;
            }
            
            function createMarkerIcon(color) {
                // 创建圆形标记SVG图标
                var svg = '<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">';
                svg += '<circle cx="12" cy="12" r="10" fill="' + color + '" stroke="white" stroke-width="2"/>';
                svg += '<circle cx="12" cy="12" r="4" fill="white"/>';
                svg += '</svg>';
                
                // 将SVG转换为Data URL
                return 'data:image/svg+xml;base64,' + btoa(svg);
            }
            
            function showError(map, message) {
                console.error(message);
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * 颜色处理辅助函数
     */
    private function lighten_color($color, $percent) {
        $num = intval(str_replace('#', '', $color), 16);
        $amt = round(2.55 * $percent);
        $R = ($num >> 16) + $amt;
        $G = ($num >> 8 & 0x00FF) + $amt;
        $B = ($num & 0x0000FF) + $amt;
        
        $R = max(0, min(255, $R));
        $G = max(0, min(255, $G));
        $B = max(0, min(255, $B));
        
        return '#' . sprintf('%02x%02x%02x', $R, $G, $B);
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
            'shortcode_enabled' => 'no'
        );
        
        return wp_parse_args($settings, $defaults);
    }
}