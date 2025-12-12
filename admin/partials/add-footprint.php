<?php
/**
 * 添加/编辑足迹页面
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 获取要编辑的足迹ID
$footprint_id = isset($_GET['footprint_id']) ? intval($_GET['footprint_id']) : 0;
$footprint = null;

// 如果是编辑模式，获取足迹数据
if ($footprint_id > 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'footprints';
    $footprint = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $footprint_id));
    
    if (!$footprint) {
        echo '<div class="wrap"><div class="notice notice-error"><p>' . __('足迹不存在', 'wp-map') . '</p></div></div>';
        return;
    }
}

// 获取设置
require_once WP_MAP_PLUGIN_DIR . 'includes/class-wp-map-frontend.php';
$frontend = new WP_Map_Frontend($this->plugin_name, $this->version);
$settings = $frontend->get_settings();

// 表单默认值
$defaults = array(
    'title' => '',
    'description' => '',
    'latitude' => '',
    'longitude' => '',
    'visit_date' => date('Y-m-d'),
    'tags' => '',
    'link_url' => '',
    'link_type' => 'page'
);

// 如果有足迹数据，使用足迹数据；否则使用默认值
if ($footprint) {
    $values = array(
        'title' => $footprint->title,
        'description' => $footprint->description,
        'latitude' => $footprint->latitude,
        'longitude' => $footprint->longitude,
        'visit_date' => $footprint->visit_date,
        'tags' => $footprint->tags,
        'link_url' => $footprint->link_url,
        'link_type' => $footprint->link_type
    );
} else {
    $values = $defaults;
}
?>

<div class="wrap wp-map-container">
    <h1>
        <?php 
        if ($footprint_id > 0) {
            _e('编辑足迹', 'wp-map');
        } else {
            _e('添加足迹', 'wp-map');
        }
        ?>
        <a href="<?php echo admin_url('admin.php?page=wp-map'); ?>" class="page-title-action">
            <?php _e('足迹列表', 'wp-map'); ?>
        </a>
    </h1>

    <?php if (empty($settings['amap_js_key'])) : ?>
        <div class="wp-map-notice wp-map-notice-error">
            <?php _e('重要：您还没有设置高德地图API密钥。请到<a href="' . admin_url('admin.php?page=wp-map-settings') . '">设置页面</a>配置API密钥。', 'wp-map'); ?>
        </div>
    <?php endif; ?>

    <div class="wp-map-add-layout">
        <div class="wp-map-add-form-column">
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="wp-map-save-footprint">
                <input type="hidden" name="action" value="save_footprint">
                <?php if ($footprint_id > 0) : ?>
                    <input type="hidden" name="footprint_id" value="<?php echo $footprint_id; ?>">
                <?php endif; ?>
                <?php wp_nonce_field('wp_map_save_footprint', 'wp_map_nonce'); ?>

                <div class="wp-map-form">
                    <div class="wp-map-form-group">
                        <label for="footprint_title"><?php _e('地点名称', 'wp-map'); ?> <span class="required">*</span></label>
                        <input type="text" id="footprint_title" name="footprint_title" value="<?php echo esc_attr($values['title']); ?>" required>
                    </div>

                    <div class="wp-map-form-group">
                        <label for="footprint_description"><?php _e('地点描述', 'wp-map'); ?></label>
                        <textarea id="footprint_description" name="footprint_description"><?php echo esc_textarea($values['description']); ?></textarea>
                    </div>

                    <div class="wp-map-form-group">
                        <label for="footprint_visit_date"><?php _e('访问日期', 'wp-map'); ?> <span class="required">*</span></label>
                        <input type="date" id="footprint_visit_date" name="footprint_visit_date" value="<?php echo esc_attr($values['visit_date']); ?>" required>
                    </div>

                    <div class="wp-map-form-group">
                        <label for="footprint_address"><?php _e('地址搜索', 'wp-map'); ?></label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="footprint_address" name="footprint_address" style="flex: 1;" placeholder="<?php _e('输入地址搜索位置', 'wp-map'); ?>">
                            <button type="button" id="wp-map-search-address" class="wp-map-button"><?php _e('搜索', 'wp-map'); ?></button>
                        </div>
                        <p class="description"><?php _e('输入地址然后点击搜索，地图会自动定位到该位置', 'wp-map'); ?></p>
                    </div>

                    <div id="wp-map-coords-display" class="wp-map-coords-display">
                        <?php 
                        if (!empty($values['latitude']) && !empty($values['longitude'])) {
                            printf(__('当前选择位置：经度: %s, 纬度: %s', 'wp-map'), $values['longitude'], $values['latitude']);
                        } else {
                            _e('请在地址搜索中输入位置', 'wp-map');
                        }
                        ?>
                    </div>
                    <input type="hidden" id="footprint_latitude" name="footprint_latitude" value="<?php echo esc_attr($values['latitude']); ?>">
                    <input type="hidden" id="footprint_longitude" name="footprint_longitude" value="<?php echo esc_attr($values['longitude']); ?>">

                    <div class="wp-map-form-group">
                        <label for="footprint_link_type"><?php _e('关联链接类型', 'wp-map'); ?></label>
                        <select id="footprint_link_type" name="footprint_link_type" class="wp-map-link-type-select">
                            <option value="none" <?php selected($values['link_type'], 'none'); ?>><?php _e('无关联链接', 'wp-map'); ?></option>
                            <option value="page" <?php selected($values['link_type'], 'page'); ?>><?php _e('页面', 'wp-map'); ?></option>
                            <option value="post" <?php selected($values['link_type'], 'post'); ?>><?php _e('文章', 'wp-map'); ?></option>
                            <option value="custom" <?php selected($values['link_type'], 'custom'); ?>><?php _e('自定义链接', 'wp-map'); ?></option>
                        </select>
                    </div>

                    <div class="wp-map-form-group wp-map-link-container" id="wp-map-page-container" style="display: <?php echo ($values['link_type'] === 'page') ? 'block' : 'none'; ?>;">
                        <label for="footprint_link_page"><?php _e('选择页面', 'wp-map'); ?></label>
                        <select id="footprint_link_page" name="footprint_link_page" class="wp-map-page-select">
                            <option value=""><?php _e('-- 选择页面 --', 'wp-map'); ?></option>
                            <?php
                            $pages = get_pages(array('post_status' => 'publish'));
                            foreach ($pages as $page) {
                                $selected = ($values['link_url'] === get_permalink($page->ID)) ? 'selected' : '';
                                echo '<option value="' . esc_attr($page->ID) . '" ' . $selected . '>' . esc_html($page->post_title) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="wp-map-form-group wp-map-link-container" id="wp-map-post-container" style="display: <?php echo ($values['link_type'] === 'post') ? 'block' : 'none'; ?>;">
                        <label for="footprint_link_post"><?php _e('选择文章', 'wp-map'); ?></label>
                        <select id="footprint_link_post" name="footprint_link_post" class="wp-map-post-select">
                            <option value=""><?php _e('-- 选择文章 --', 'wp-map'); ?></option>
                            <?php
                            $posts = get_posts(array('post_status' => 'publish', 'numberposts' => -1));
                            foreach ($posts as $post) {
                                $selected = ($values['link_url'] === get_permalink($post->ID)) ? 'selected' : '';
                                echo '<option value="' . esc_attr($post->ID) . '" ' . $selected . '>' . esc_html($post->post_title) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="wp-map-form-group wp-map-link-container" id="wp-map-custom-container" style="display: <?php echo ($values['link_type'] === 'custom') ? 'block' : 'none'; ?>;">
                        <label for="footprint_link_url"><?php _e('自定义链接', 'wp-map'); ?></label>
                        <input type="url" id="footprint_link_url" name="footprint_link_url" value="<?php echo esc_attr($values['link_url']); ?>" placeholder="https://example.com">
                        <p class="description"><?php _e('输入完整的URL链接地址', 'wp-map'); ?></p>
                    </div>

                    <input type="hidden" id="footprint_final_link" name="footprint_link_url" value="<?php echo esc_attr($values['link_url']); ?>">

                    <div class="wp-map-form-actions">
                        <?php if ($footprint_id > 0) : ?>
                            <button type="submit" class="wp-map-button"><?php _e('更新足迹', 'wp-map'); ?></button>
                        <?php else : ?>
                            <button type="submit" class="wp-map-button"><?php _e('添加足迹', 'wp-map'); ?></button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="wp-map-add-map-column">
            <div class="wp-map-form">
                <div class="wp-map-form-group">
                    <label><?php _e('足迹位置预览', 'wp-map'); ?> <span class="required">*</span></label>
                    <div class="wp-map-map-container" id="wp-map-admin-map"></div>
                    <p class="description"><?php _e('在地址搜索中输入位置后，即可在此处预览', 'wp-map'); ?></p>
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
// 设置页面标题
document.title = '<?php echo $footprint_id > 0 ? __('编辑足迹', 'wp-map') : __('添加足迹', 'wp-map'); ?> ‹ ' + document.title.replace(/^[^‹]*‹\s*/, '');

// 设置地图中心点（从设置中获取）
var map_center_lat = <?php echo json_encode($settings['map_center_lat']); ?>;
var map_center_lng = <?php echo json_encode($settings['map_center_lng']); ?>;
var map_zoom = <?php echo json_encode($settings['map_zoom']); ?>;

        // 确保jQuery已加载
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ready(function($) {
                // 关联链接类型切换功能
                function initLinkTypeSwitcher() {
                    // 初始状态设置
                    updateLinkContainers();
                    
                    // 监听链接类型变化
                    $('#footprint_link_type').on('change', function() {
                        updateLinkContainers();
                        updateFinalLink();
                    });
                    
                    // 监听页面选择变化
                    $('#footprint_link_page').on('change', function() {
                        updateFinalLink();
                    });
                    
                    // 监听文章选择变化
                    $('#footprint_link_post').on('change', function() {
                        updateFinalLink();
                    });
                    
                    // 监听自定义链接变化
                    $('#footprint_link_url').on('input', function() {
                        updateFinalLink();
                    });
                }
                
                // 更新链接容器显示状态
                function updateLinkContainers() {
                    var linkType = $('#footprint_link_type').val();
                    
                    // 隐藏所有容器
                    $('.wp-map-link-container').hide();
                    
                    // 显示对应的容器
                    if (linkType === 'page') {
                        $('#wp-map-page-container').show();
                    } else if (linkType === 'post') {
                        $('#wp-map-post-container').show();
                    } else if (linkType === 'custom') {
                        $('#wp-map-custom-container').show();
                    }
                }
                
                // 更新最终链接值
                function updateFinalLink() {
                    var linkType = $('#footprint_link_type').val();
                    var finalLink = '';
                    
                    if (linkType === 'page') {
                        var pageId = $('#footprint_link_page').val();
                        if (pageId) {
                            finalLink = '<?php echo home_url(); ?>/?p=' + pageId;
                        }
                    } else if (linkType === 'post') {
                        var postId = $('#footprint_link_post').val();
                        if (postId) {
                            finalLink = '<?php echo home_url(); ?>/?p=' + postId;
                        }
                    } else if (linkType === 'custom') {
                        finalLink = $('#footprint_link_url').val();
                    }
                    
                    $('#footprint_final_link').val(finalLink);
                }
                
                // 初始化链接类型切换功能
                initLinkTypeSwitcher();
                
                // 高德地图API加载完成后初始化地图
                function initMap() {
            if (typeof AMap === 'undefined') {
                $('#wp-map-admin-map').after('<div class="wp-map-notice wp-map-notice-error">高德地图API加载失败，请检查API密钥是否正确设置。</div>');
                return;
            }
            
            // 创建地图实例
            var map = new AMap.Map('wp-map-admin-map', {
                center: [map_center_lng, map_center_lat],
                zoom: map_zoom
            });
            
            // 添加控件
            map.addControl(new AMap.Scale());
            map.addControl(new AMap.ToolBar());
            
            var marker = null;
            
            // 如果有现成的经纬度，设置标记
            var lat = parseFloat($('#footprint_latitude').val());
            var lng = parseFloat($('#footprint_longitude').val());
            
            if (lat && lng) {
                setMarker(map, lng, lat);
            }
            
            // 添加点击事件
            map.on('click', function(e) {
                updateLocation(map, e.lnglat.lng, e.lnglat.lat);
            });
            
            // 设置标记函数
            function setMarker(map, lng, lat) {
                // 移除现有标记
                if (marker) {
                    map.remove(marker);
                }
                
                // 创建新标记
                marker = new AMap.Marker({
                    position: [lng, lat],
                    draggable: true
                });
                
                // 添加拖拽事件
                marker.on('dragend', function(e) {
                    updateLocation(map, e.lnglat.lng, e.lnglat.lat);
                });
                
                // 添加到地图
                map.add(marker);
                
                // 设置地图中心点
                map.setCenter([lng, lat]);
            }
            
            // 更新位置信息函数
            function updateLocation(map, lng, lat) {
                // 更新表单值
                $('#footprint_longitude').val(lng);
                $('#footprint_latitude').val(lat);
                
                // 显示坐标
                $('#wp-map-coords-display').html('<strong>选择位置：</strong>经度: ' + lng.toFixed(6) + ', 纬度: ' + lat.toFixed(6));
                
                // 如果还没有标记，设置一个
                if (!marker) {
                    setMarker(map, lng, lat);
                }
            }
            
            // 地址搜索功能
            $('#wp-map-search-address').on('click', function() {
                var address = $('#footprint_address').val();
                
                if (!address) {
                    alert('请输入要搜索的地址');
                    return;
                }
                
                // 使用高德地图地理编码API
                var geocoder = new AMap.Geocoder({
                    city: '全国'
                });
                
                geocoder.getLocation(address, function(status, result) {
                    if (status === 'complete' && result.geocodes.length > 0) {
                        var lng = result.geocodes[0].location.lng;
                        var lat = result.geocodes[0].location.lat;
                        
                        // 设置标记并更新位置
                        setMarker(map, lng, lat);
                        updateLocation(map, lng, lat);
                        
                        // 填充地址输入框
                        $('#footprint_address').val(result.geocodes[0].formattedAddress);
                    } else {
                        alert('地址搜索失败，请检查地址是否正确');
                    }
                });
            });
        }
        
        // 初始化地图
        if (typeof AMap !== 'undefined') {
            initMap();
        } else {
            // 等待高德地图API加载完成
            var checkCount = 0;
            var checkInterval = setInterval(function() {
                checkCount++;
                if (typeof AMap !== 'undefined') {
                    clearInterval(checkInterval);
                    initMap();
                } else if (checkCount > 10) {
                    clearInterval(checkInterval);
                    $('#wp-map-admin-map').after('<div class="wp-map-notice wp-map-notice-error">高德地图API加载超时，请刷新页面重试。</div>');
                }
            }, 500);
        }
    });
} else {
    // 如果jQuery未加载，显示错误
    document.addEventListener('DOMContentLoaded', function() {
        var mapContainer = document.getElementById('wp-map-admin-map');
        if (mapContainer) {
            mapContainer.innerHTML = '<div class="wp-map-notice wp-map-notice-error">jQuery未加载，地图功能无法使用。</div>';
        }
    });
}
</script>