/**
 * WordPress 足迹地图 - 前台脚本
 */

(function($) {
    'use strict';

    // 足迹地图类
    function FootprintsMap(element, options) {
        this.element = $(element);
        this.options = $.extend({
            id: 'wp-map',
            zoom: 4, // 默认显示整个中国地图
            center: '34.0000,104.0000' // 中国中心点
        }, options);
        
        this.map = null;
        this.markers = [];
        this.footprints = [];
        this.init();
    }
    
    FootprintsMap.prototype = {
        init: function() {
            // 检查是否有高德地图API
            if (typeof AMap === 'undefined') {
                this.element.find('.wp-map-map').html('<div class="wp-map-error">' + (wp_map_frontend.strings.error_loading || '加载出错') + ' - 高德地图API未加载</div>');
                return;
            }
            
            // 直接初始化地图，不显示加载状态
            
            // 解析中心点坐标
            var centerParts = this.options.center.split(',');
            var centerLng = parseFloat(centerParts[1]);
            var centerLat = parseFloat(centerParts[0]);
            
            // 获取地图主题设置
            var settings = typeof wp_map_frontend !== 'undefined' ? wp_map_frontend.settings : {};
            var mapTheme = settings.map_theme || 'whitesmoke';
            var mapStyle = "amap://styles/" + mapTheme;
            
            // 初始化地图
            var self = this;
            var mapContainer = this.element.find('.wp-map-map')[0];
            
            // 检查地图容器是否存在
            if (!mapContainer) {
                console.error('地图容器元素不存在，请检查HTML结构');
                return;
            }
            
            // 设置地图容器的背景色，避免灰屏闪烁
            $(mapContainer).css('background-color', '#f8f9fa');
            
            this.map = new AMap.Map(mapContainer, {
                center: [centerLng, centerLat],
                zoom: parseInt(this.options.zoom),
                resizeEnable: true,
                viewMode: '2D',
                features: ['bg', 'point', 'road', 'building'],
                mapStyle: mapStyle,
                // 添加地图加载完成的回调
                mapEvents: ['complete']
            });
            
            // 地图加载完成后显示地图
            this.map.on('complete', function() {
                $(mapContainer).css('background-color', '');
                $(mapContainer).addClass('loaded');
            });
            

            
            // 添加控件 - 使用更安全的方式
            var self = this;
            
            // 等待地图完全初始化后再添加控件
            var checkMapReady = function(retryCount) {
                retryCount = retryCount || 0;
                
                // 检查地图是否已完全初始化
                if (self.map && typeof self.map.addControl === 'function') {
                    try {
                        // 添加缩放控件
                        if (typeof AMap.Scale === 'function') {
                            var scale = new AMap.Scale({
                                position: 'LB'
                            });
                            self.map.addControl(scale);
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
                            self.map.addControl(toolbar);
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
            this.loadFootprints();
        },
        

        
        loadFootprints: function() {
            var self = this;
            
            $.ajax({
                url: wp_map_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_footprints',
                    nonce: wp_map_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.footprints = response.data;
                        self.renderMap();
                        self.hideLoading();
                    } else {
                        self.showError(wp_map_frontend.strings.error_loading);
                    }
                },
                error: function() {
                    self.showError(wp_map_frontend.strings.error_loading);
                }
            });
        },
        
        hideLoading: function() {
            this.element.find('.wp-map-loading').hide();
        },
        
        showError: function(message) {
            var errorMessage = message || '加载数据时出错';
            if (typeof wp_map_frontend !== 'undefined' && wp_map_frontend.strings && wp_map_frontend.strings.error_loading) {
                errorMessage = wp_map_frontend.strings.error_loading;
            }
            this.element.find('.wp-map-map').html('<div class="wp-map-error">' + errorMessage + '</div>');
        },
        
        renderMap: function() {
            // 清除现有标记
            this.clearMarkers();
            
            if (this.footprints.length === 0) {
                var noFootprintsText = '暂无足迹数据';
                if (typeof wp_map_frontend !== 'undefined' && wp_map_frontend.strings && wp_map_frontend.strings.no_footprints) {
                    noFootprintsText = wp_map_frontend.strings.no_footprints;
                }
                this.element.find('.wp-map-wrapper').after('<div class="wp-map-notice">' + noFootprintsText + '</div>');
                return;
            }
            
            // 创建标记
            var self = this;
            var infoWindows = [];
            
            this.footprints.forEach(function(footprint) {
                // 创建信息窗口内容
                var infoContent = self.createInfoWindowContent(footprint);
                
                // 根据足迹是否有链接设置标记颜色
                var markerColor = '#FF5722'; // 默认颜色
                
                // 获取设置中的颜色值
                var settings = typeof wp_map_frontend !== 'undefined' ? wp_map_frontend.settings : {};
                var defaultColor = settings.marker_color_default || '#FF5722';
                var withLinkColor = settings.marker_color_with_link || '#2196F3';
                var noLinkColor = settings.marker_color_no_link || '#9E9E9E';
                
                // 判断足迹是否有链接
                if (footprint.link_url) {
                    markerColor = withLinkColor; // 有关联链接的颜色
                } else {
                    markerColor = noLinkColor; // 没有关联链接的颜色
                }
                
                // 创建标记
                var marker = new AMap.Marker({
                    position: [footprint.longitude, footprint.latitude],
                    title: footprint.title,
                    icon: new AMap.Icon({
                        size: new AMap.Size(24, 24),
                        image: self.createMarkerIcon(markerColor),
                        imageSize: new AMap.Size(24, 24)
                    })
                });
                
                // 创建信息窗口
                var infoWindow = new AMap.InfoWindow({
                    content: infoContent,
                    offset: new AMap.Pixel(0, -30)
                });
                
                // 点击标记显示信息窗口
                marker.on('click', function() {
                    infoWindow.open(self.map, marker.getPosition());
                });
                
                self.markers.push(marker);
                infoWindows.push(infoWindow);
            });
            
            // 添加标记到地图
            self.map.add(self.markers);
        },
        
        createInfoWindowContent: function(footprint) {
            var content = '<div class="wp-map-info-window">';
            content += '<div class="wp-map-info-title">' + footprint.title + '</div>';
            content += '<div class="wp-map-info-date">' + footprint.visit_date + '</div>';
            
            if (footprint.description) {
                content += '<div class="wp-map-info-description">' + footprint.description + '</div>';
            }
            

            
            if (footprint.link_url) {
                // 获取设置中的按钮颜色
                var settings = typeof wp_map_frontend !== 'undefined' ? wp_map_frontend.settings : {};
                var buttonColor = settings.button_color_detail || '#3498db';
                var buttonHoverColor = this.lightenColor(buttonColor, -20);
                
                content += '<a href="' + footprint.link_url + '" class="wp-map-info-link" target="_blank" style="background-color: ' + buttonColor + ';" onmouseover="this.style.backgroundColor=\'' + buttonHoverColor + '\'" onmouseout="this.style.backgroundColor=\'' + buttonColor + '\'">查看详情</a>';
            }
            
            content += '</div>';
            return content;
        },
        
        createMarkerIcon: function(color) {
            // 创建圆形标记SVG图标
            var svg = '<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">';
            svg += '<circle cx="12" cy="12" r="10" fill="' + color + '" stroke="white" stroke-width="2"/>';
            svg += '<circle cx="12" cy="12" r="4" fill="white"/>';
            svg += '</svg>';
            
            // 将SVG转换为Data URL
            return 'data:image/svg+xml;base64,' + btoa(svg);
        },
        
        clearMarkers: function() {
            if (this.markers.length > 0) {
                this.map.remove(this.markers);
                this.markers = [];
            }
        },
        
        // 颜色处理辅助函数
        lightenColor: function(color, percent) {
            var num = parseInt(color.replace("#", ""), 16),
                amt = Math.round(2.55 * percent),
                R = (num >> 16) + amt,
                G = (num >> 8 & 0x00FF) + amt,
                B = (num & 0x0000FF) + amt;
            return "#" + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
                (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
                (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
        }
    };



    // jQuery插件
    $.fn.wpFootprintsMap = function(options) {
        return this.each(function() {
            // 创建地图容器
            var mapId = options.id || 'wp-map-' + Math.floor(Math.random() * 1000000);
            var container = '<div class="wp-map-container" id="' + mapId + '">';
            
            if (options.title) {
                container += '<h2 class="wp-map-title">' + options.title + '</h2>';
            }
            
            container += '<div class="wp-map-wrapper">';
            container += '<div class="wp-map-map"></div>';
            container += '</div>';
            container += '</div>';
            
            $(this).html(container);
            
            // 初始化地图
            var mapContainer = $('#' + mapId);
            mapContainer.data('wp-footprints-map', new FootprintsMap(mapContainer, options));
        });
    };

    // 文档就绪时初始化
    $(document).ready(function() {
        // 监听自定义初始化事件
        $(document).on('wp_map_init', function() {
            // 直接初始化现有的地图容器，不创建新的HTML结构
            var existingContainer = $('.wp-map-map-container');
            if (existingContainer.length > 0) {
                // 获取设置中的中心点和缩放级别
                var settings = typeof wp_map_frontend !== 'undefined' ? wp_map_frontend.settings : {};
                var mapCenter = '34.0000,104.0000'; // 中国中心点默认值
                var mapZoom = 4; // 显示整个中国地图的默认缩放级别
                
                // 如果设置了自定义中心点，使用设置的值
                if (settings.map_center_lat && settings.map_center_lng) {
                    mapCenter = settings.map_center_lat + ',' + settings.map_center_lng;
                }
                
                // 如果设置了自定义缩放级别，使用设置的值
                if (settings.map_zoom) {
                    mapZoom = parseInt(settings.map_zoom);
                }
                
                new FootprintsMap(existingContainer, {
                    id: existingContainer.attr('id') || 'wp-map-front-map',
                    zoom: mapZoom,
                    center: mapCenter
                });
            }
        });
        
        // 如果AMap已经加载，直接触发初始化
        if (typeof AMap !== 'undefined') {
            $(document).trigger('wp_map_init');
        } else {
            // 等待AMap加载完成
            document.addEventListener('amap_ready', function() {
                $(document).trigger('wp_map_init');
            });
        }
        
        // 自动初始化所有带有 wp-map-container 类的元素（用于向后兼容）
        $('.wp-map-container').each(function() {
            var $this = $(this);
            var id = $this.attr('id') || 'wp-map-' + Math.floor(Math.random() * 1000000);
            var zoom = parseInt($this.data('zoom')) || 4; // 默认显示整个中国地图
            var center = $this.data('center') || '34.0000,104.0000'; // 中国中心点
            var title = $this.data('title') || '';
            
            $this.wpFootprintsMap({
                id: id,
                zoom: zoom,
                center: center,
                title: title
            });
        });
    });

})(jQuery);