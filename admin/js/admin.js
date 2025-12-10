/**
 * WordPress 足迹地图 - 后台脚本
 */

jQuery(document).ready(function($) {
    // 初始化变量
    var map = null;
    var marker = null;
    var geocoder = null;
    
    // 初始化地图
    function initMap() {
        if ($('#wp-map-admin-map').length) {
            // 从设置中获取地图中心点
            var centerLat = parseFloat($('#map_center_lat').val()) || 39.9042;
            var centerLng = parseFloat($('#map_center_lng').val()) || 116.4074;
            var zoom = parseInt($('#map_zoom').val()) || 10;
            
            // 创建地图实例
            map = new AMap.Map('wp-map-admin-map', {
                center: [centerLng, centerLat],
                zoom: zoom
            });
            
            // 添加控件
            map.addControl(new AMap.Scale());
            map.addControl(new AMap.ToolBar());
            
            // 添加点击事件
            map.on('click', function(e) {
                updateLocation(e.lnglat.lng, e.lnglat.lat);
            });
            
            // 初始化地理编码器
            geocoder = new AMap.Geocoder({
                city: '全国'
            });
            
            // 如果有现成的经纬度，设置标记
            var lat = parseFloat($('#footprint_latitude').val());
            var lng = parseFloat($('#footprint_longitude').val());
            
            if (lat && lng) {
                setMarker(lng, lat);
            }
        }
    }
    
    // 设置地图标记
    function setMarker(lng, lat) {
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
            updateLocation(e.lnglat.lng, e.lnglat.lat);
        });
        
        // 添加到地图
        map.add(marker);
        
        // 更新表单值
        updateLocation(lng, lat);
        
        // 设置地图中心点
        map.setCenter([lng, lat]);
    }
    
    // 更新位置信息
    function updateLocation(lng, lat) {
        // 更新表单值
        $('#footprint_longitude').val(lng);
        $('#footprint_latitude').val(lat);
        
        // 显示坐标
        $('#wp-map-coords-display').text('经度: ' + lng + ', 纬度: ' + lat);
        
        // 反向地理编码获取地址
        if (geocoder) {
            geocoder.getAddress([lng, lat], function(status, result) {
                if (status === 'complete' && result.regeocode) {
                    $('#footprint_address').val(result.regeocode.formattedAddress);
                }
            });
        }
    }
    
    // 地址搜索
    $('#wp-map-search-address').on('click', function() {
        var address = $('#footprint_address').val();
        
        if (!address) {
            alert(wp_map_admin.strings.location_required);
            return;
        }
        
        // 确保高德地图API已加载
        if (typeof AMap === 'undefined') {
            alert('高德地图API未加载，请刷新页面重试');
            return;
        }
        
        $.ajax({
            url: wp_map_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'geocode_address',
                address: address,
                nonce: wp_map_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    setMarker(response.data.longitude, response.data.latitude);
                } else {
                    alert('地理编码失败: ' + response.data.message);
                }
            },
            error: function() {
                alert('请求失败，请稍后重试');
            }
        });
    });
    
    // 删除足迹确认
    $('.wp-map-delete-footprint').on('click', function(e) {
        if (!confirm(wp_map_admin.strings.confirm_delete)) {
            e.preventDefault();
            return false;
        }
    });
    
    // 保存足迹前验证
    $('#wp-map-save-footprint').on('submit', function(e) {
        var title = $('#footprint_title').val();
        var latitude = $('#footprint_latitude').val();
        var longitude = $('#footprint_longitude').val();
        var visitDate = $('#footprint_visit_date').val();
        
        if (!title) {
            alert(wp_map_admin.strings.title_required);
            $('#footprint_title').focus();
            e.preventDefault();
            return false;
        }
        
        if (!latitude || !longitude) {
            alert(wp_map_admin.strings.location_required);
            e.preventDefault();
            return false;
        }
        
        if (!visitDate) {
            alert('请选择访问日期');
            $('#footprint_visit_date').focus();
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    
    // 设置页面的地图预览
    function initPreviewMap() {
        if ($('#wp-map-preview-map').length) {
            var centerLat = parseFloat($('#map_center_lat').val()) || 39.9042;
            var centerLng = parseFloat($('#map_center_lng').val()) || 116.4074;
            var zoom = parseInt($('#map_zoom').val()) || 10;
            
            var previewMap = new AMap.Map('wp-map-preview-map', {
                center: [centerLng, centerLat],
                zoom: zoom
            });
            
            // 当设置更改时更新预览
            $('#map_center_lat, #map_center_lng, #map_zoom').on('change', function() {
                var newCenterLat = parseFloat($('#map_center_lat').val()) || 39.9042;
                var newCenterLng = parseFloat($('#map_center_lng').val()) || 116.4074;
                var newZoom = parseInt($('#map_zoom').val()) || 10;
                
                previewMap.setCenter([newCenterLng, newCenterLat]);
                previewMap.setZoom(newZoom);
            });
        }
    }
    
    // 颜色选择器预览
    $('#marker_color').on('change', function() {
        var color = $(this).val();
        $('.wp-map-marker-preview').css('background-color', color);
    });
    
// 初始化地图
if (typeof AMap !== 'undefined') {
    initMap();
    initPreviewMap();
} else {
    // 如果AMap未加载，等待一段时间再尝试
    setTimeout(function() {
        if (typeof AMap !== 'undefined') {
            initMap();
            initPreviewMap();
        } else {
            console.log('高德地图API未加载，请检查API密钥');
        }
    }, 2000);
}
    
    // 标签输入提示
    $('#footprint_tags').on('focus', function() {
        $(this).after('<span class="description">多个标签请用逗号分隔</span>');
    }).on('blur', function() {
        $(this).next('.description').remove();
    });
});