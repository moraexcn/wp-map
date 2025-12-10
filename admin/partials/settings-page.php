<?php
/**
 * 插件设置页面
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 获取设置
require_once WP_MAP_PLUGIN_DIR . 'includes/class-wp-map-frontend.php';
$frontend = new WP_Map_Frontend($this->plugin_name, $this->version);
$settings = $frontend->get_settings();
?>

<div class="wrap">
    <h1><?php _e('足迹设置', 'wp-map'); ?> <a href="https://www.moraex.com/wp-map" target="_blank" class="page-title-action"><?php _e('使用文档', 'wp-map'); ?></a></h1>

    <div class="wp-map-add-layout">
        <div class="wp-map-add-form-column">
            <form method="post" action="options.php" class="wp-map-settings-form">
                <?php settings_fields('wp_map_settings_group'); ?>

                <div class="wp-map-form">
                    <div class="wp-map-form-group">
                        <label for="amap_js_key"><?php _e('JavaScript API密钥', 'wp-map'); ?></label>
                        <input type="text" id="amap_js_key" name="wp_map_settings[amap_js_key]" value="<?php echo esc_attr($settings['amap_js_key']); ?>" class="regular-text">
                        <p class="description">
                            <?php _e('用于在页面中显示地图。获取方式：', 'wp-map'); ?>
                            <a href="https://lbs.amap.com/api/javascript-api/guide/abc/prepare" target="_blank"><?php _e('高德开放平台', 'wp-map'); ?></a>
                        </p>
                    </div>

                    <div class="wp-map-form-group">
                        <label for="amap_web_key"><?php _e('Web服务API密钥', 'wp-map'); ?></label>
                        <input type="text" id="amap_web_key" name="wp_map_settings[amap_web_key]" value="<?php echo esc_attr($settings['amap_web_key']); ?>" class="regular-text">
                        <p class="description">
                            <?php _e('用于地址搜索和地理编码。', 'wp-map'); ?>
                        </p>
                    </div>

                    <div class="wp-map-form-group">
                        <label for="map_path"><?php _e('地图路径', 'wp-map'); ?></label>
                        <input type="text" id="map_path" name="wp_map_settings[map_path]" value="<?php echo esc_attr(isset($settings['map_path']) ? $settings['map_path'] : 'map'); ?>" class="regular-text">
                        <p class="description"><?php _e('前端地图页面的URL路径，例如：map、travel-map、footprints等', 'wp-map'); ?></p>
                        <p class="description">
                            <?php 
                            $map_path = isset($settings['map_path']) ? $settings['map_path'] : 'map';
                            echo sprintf(__('地图链接：<a href="%s" target="_blank">%s</a>', 'wp-map'), esc_url(home_url('/' . $map_path)), home_url('/' . $map_path));
                            ?>
                        </p>
                    </div>

                    <div class="wp-map-form-group">
                        <label for="page_title"><?php _e('页面标题', 'wp-map'); ?></label>
                        <input type="text" id="page_title" name="wp_map_settings[page_title]" value="<?php echo esc_attr(isset($settings['page_title']) ? $settings['page_title'] : '足迹地图'); ?>" class="regular-text">
                        <p class="description"><?php _e('显示在前端页面标题栏的标题', 'wp-map'); ?></p>
                    </div>

                    <div class="wp-map-form-group">
                        <label for="page_description"><?php _e('页面描述摘要', 'wp-map'); ?></label>
                        <textarea id="page_description" name="wp_map_settings[page_description]" class="large-text" rows="3" placeholder="<?php _e('输入页面描述，用于SEO优化和个性化展示...', 'wp-map'); ?>"><?php echo esc_textarea(isset($settings['page_description']) ? $settings['page_description'] : ''); ?></textarea>
                        <p class="description"><?php _e('设置前端地图页面的描述信息，用于SEO优化和个性化展示。建议控制在150个字符以内。', 'wp-map'); ?></p>
                    </div>

                    <div class="wp-map-form-group">
                        <label for="map_center_lat"><?php _e('地图中心点', 'wp-map'); ?></label>
                        <div class="wp-map-coord-inputs">
                            <div class="wp-map-coord-group">
                                <label for="map_center_lat" class="wp-map-coord-label"><?php _e('纬度', 'wp-map'); ?></label>
                                <input type="text" id="map_center_lat" name="wp_map_settings[map_center_lat]" value="<?php echo esc_attr($settings['map_center_lat']); ?>" class="wp-map-coord-input" placeholder="39.9042">
                            </div>
                            <div class="wp-map-coord-group">
                                <label for="map_center_lng" class="wp-map-coord-label"><?php _e('经度', 'wp-map'); ?></label>
                                <input type="text" id="map_center_lng" name="wp_map_settings[map_center_lng]" value="<?php echo esc_attr($settings['map_center_lng']); ?>" class="wp-map-coord-input" placeholder="116.4074">
                            </div>
                        </div>
                        <p class="description"><?php _e('设置地图默认显示的中心点坐标，例如：北京 (39.9042, 116.4074)', 'wp-map'); ?></p>
                    </div>

                    <div class="wp-map-form-group">
                        <label for="map_zoom"><?php _e('默认缩放级别', 'wp-map'); ?></label>
                        <input type="number" id="map_zoom" name="wp_map_settings[map_zoom]" value="<?php echo esc_attr($settings['map_zoom']); ?>" min="1" max="18" step="1" class="small-text">
                        <p class="description"><?php _e('范围：1-18，数值越大地图越详细', 'wp-map'); ?></p>
                    </div>

                    <div class="wp-map-form-group">
                        <label for="marker_color_default"><?php _e('标记颜色设置', 'wp-map'); ?></label>
                        <div class="wp-map-color-settings">
                            <div class="wp-map-color-group">
                                <label for="marker_color_default" class="wp-map-color-label"><?php _e('默认标记颜色', 'wp-map'); ?></label>
                                <input type="color" id="marker_color_default" name="wp_map_settings[marker_color_default]" value="<?php echo esc_attr(isset($settings['marker_color_default']) ? $settings['marker_color_default'] : '#FF5722'); ?>">
                            </div>
                            
                            <div class="wp-map-color-group">
                                <label for="marker_color_with_link" class="wp-map-color-label"><?php _e('有关联链接的标记颜色', 'wp-map'); ?></label>
                                <input type="color" id="marker_color_with_link" name="wp_map_settings[marker_color_with_link]" value="<?php echo esc_attr(isset($settings['marker_color_with_link']) ? $settings['marker_color_with_link'] : '#2196F3'); ?>">
                            </div>
                            
                            <div class="wp-map-color-group">
                                <label for="marker_color_no_link" class="wp-map-color-label"><?php _e('没有关联链接的标记颜色', 'wp-map'); ?></label>
                                <input type="color" id="marker_color_no_link" name="wp_map_settings[marker_color_no_link]" value="<?php echo esc_attr(isset($settings['marker_color_no_link']) ? $settings['marker_color_no_link'] : '#9E9E9E'); ?>">
                            </div>
                            
                            <div class="wp-map-color-group">
                                <label for="button_color_detail" class="wp-map-color-label"><?php _e('查看详情按钮颜色', 'wp-map'); ?></label>
                                <input type="color" id="button_color_detail" name="wp_map_settings[button_color_detail]" value="<?php echo esc_attr(isset($settings['button_color_detail']) ? $settings['button_color_detail'] : '#3498db'); ?>">
                            </div>
                        </div>

                    </div>

                    <div class="wp-map-form-group">
                        <label for="map_theme"><?php _e('地图主题', 'wp-map'); ?></label>
                        <select id="map_theme" name="wp_map_settings[map_theme]" class="regular-text">
                            <option value="normal" <?php selected(isset($settings['map_theme']) ? $settings['map_theme'] : 'whitesmoke', 'normal'); ?>>标准</option>
                            <option value="dark" <?php selected(isset($settings['map_theme']) ? $settings['map_theme'] : 'whitesmoke', 'dark'); ?>>幻影黑</option>
                            <option value="light" <?php selected(isset($settings['map_theme']) ? $settings['map_theme'] : 'whitesmoke', 'light'); ?>>月光银</option>
                            <option value="whitesmoke" <?php selected(isset($settings['map_theme']) ? $settings['map_theme'] : 'whitesmoke', 'whitesmoke'); ?>>远山黛</option>
                            <option value="fresh" <?php selected(isset($settings['map_theme']) ? $settings['map_theme'] : 'whitesmoke', 'fresh'); ?>>草色青</option>
                            <option value="grey" <?php selected(isset($settings['map_theme']) ? $settings['map_theme'] : 'whitesmoke', 'grey'); ?>>雅士灰</option>
                            <option value="graffiti" <?php selected(isset($settings['map_theme']) ? $settings['map_theme'] : 'whitesmoke', 'graffiti'); ?>>涂鸦</option>
                            <option value="macaron" <?php selected(isset($settings['map_theme']) ? $settings['map_theme'] : 'whitesmoke', 'macaron'); ?>>马卡龙</option>
                            <option value="blue" <?php selected(isset($settings['map_theme']) ? $settings['map_theme'] : 'whitesmoke', 'blue'); ?>>靛青蓝</option>
                            <option value="darkblue" <?php selected(isset($settings['map_theme']) ? $settings['map_theme'] : 'whitesmoke', 'darkblue'); ?>>极夜蓝</option>
                            <option value="wine" <?php selected(isset($settings['map_theme']) ? $settings['map_theme'] : 'whitesmoke', 'wine'); ?>>酱籽</option>
                        </select>
                        <p class="description"><?php _e('选择地图的显示样式，设置后会在前端地图和预览中生效', 'wp-map'); ?></p>
                    </div>

                    <div class="wp-map-form-actions">
                        <?php submit_button(__('保存设置', 'wp-map'), 'primary', 'submit', false); ?>
                        <a href="<?php echo admin_url('admin.php?page=wp-map'); ?>" class="wp-map-button wp-map-button-secondary"><?php _e('返回列表', 'wp-map'); ?></a>
                    </div>
                </div>
            </form>
        </div>

        <div class="wp-map-add-map-column">
            <div class="wp-map-form">
                <div class="wp-map-form-group">
                    <label><?php _e('前端地图预览', 'wp-map'); ?></label>
                    <?php 
                    $map_path = isset($settings['map_path']) ? $settings['map_path'] : 'map';
                    $frontend_map_url = home_url('/' . $map_path);
                    ?>
                    <div class="wp-map-frontend-preview-container">
                        <iframe 
                            src="<?php echo esc_url($frontend_map_url); ?>" 
                            class="wp-map-frontend-preview-iframe"
                            title="<?php _e('前端地图预览', 'wp-map'); ?>"
                            style="width: 100%; height: 600px; border: 1px solid #ddd; border-radius: 4px;">
                        </iframe>
                    </div>
                    <p class="description">
                        <?php _e('显示前端实际地图页面，保存设置后点击下方链接查看完整效果：', 'wp-map'); ?>
                        <a href="<?php echo esc_url($frontend_map_url); ?>" target="_blank">
                            <?php echo esc_url($frontend_map_url); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($settings['amap_js_key'])) : ?>
<!-- 加载高德地图API -->
<script src="https://webapi.amap.com/maps?v=2.0&key=<?php echo esc_attr($settings['amap_js_key']); ?>"></script>
<?php endif; ?>

<script>
// 如果高德地图API未加载，显示错误
if (typeof AMap === 'undefined') {
    jQuery(document).ready(function($) {
        $('#wp-map-preview-map').after('<div class="wp-map-notice wp-map-notice-error">高德地图API加载失败，请检查API密钥是否正确设置。</div>');
    });
}
</script>