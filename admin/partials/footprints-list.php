<?php
/**
 * 足迹列表页面
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'footprints';

// 处理消息显示
$message = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'success':
            $message = '<div class="wp-map-notice wp-map-notice-success">足迹保存成功！</div>';
            break;
        case 'deleted':
            $message = '<div class="wp-map-notice wp-map-notice-success">足迹删除成功！</div>';
            break;
        case 'error':
            $message = '<div class="wp-map-notice wp-map-notice-error">操作失败，请重试。</div>';
            break;
    }
}

// 获取分页参数
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($paged - 1) * $per_page;

// 获取总记录数
$total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

// 获取足迹列表
$footprints = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $table_name ORDER BY visit_date DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    )
);

// 计算总页数
$total_pages = ceil($total_items / $per_page);

// 获取设置
require_once WP_MAP_PLUGIN_DIR . 'includes/class-wp-map-frontend.php';
$frontend = new WP_Map_Frontend($this->plugin_name, $this->version);
$settings = $frontend->get_settings();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('足迹列表', 'wp-map'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=wp-map-add'); ?>" class="page-title-action"><?php _e('添加足迹', 'wp-map'); ?></a>
    <?php if (!empty($settings['amap_js_key'])) : ?>
        <?php 
        $map_path = isset($settings['map_path']) ? $settings['map_path'] : 'map';
        ?>
        <a href="<?php echo esc_url(home_url('/' . $map_path)); ?>" class="page-title-action" target="_blank"><?php _e('查看地图', 'wp-map'); ?></a>
    <?php endif; ?>
    <hr class="wp-header-end">

    <?php if ($message) : ?>
        <?php echo $message; ?>
    <?php endif; ?>

    <div class="tablenav top">
        <div class="alignleft actions">
            <span class="displaying-num"><?php printf(__('共 %s 条记录', 'wp-map'), number_format_i18n($total_items)); ?></span>
        </div>
        <br class="clear">
    </div>

    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-primary"><?php _e('地点名称', 'wp-map'); ?></th>
                <th scope="col" class="manage-column"><?php _e('描述', 'wp-map'); ?></th>
                <th scope="col" class="manage-column"><?php _e('访问日期', 'wp-map'); ?></th>
                <th scope="col" class="manage-column"><?php _e('坐标', 'wp-map'); ?></th>
    
                <th scope="col" class="manage-column"><?php _e('操作', 'wp-map'); ?></th>
            </tr>
        </thead>

        <tbody>
            <?php if ($footprints) : ?>
                <?php foreach ($footprints as $footprint) : ?>
                    <tr>
                        <td class="title column-primary has-row-actions" data-colname="<?php _e('地点名称', 'wp-map'); ?>">
                            <strong class="row-title"><?php echo esc_html($footprint->title); ?></strong>
                            <?php if ($footprint->link_url) : ?>
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="<?php echo esc_url($footprint->link_url); ?>" target="_blank">
                                            <?php
                                            if ($footprint->link_type === 'post') {
                                                _e('查看文章', 'wp-map');
                                            } else {
                                                _e('查看页面', 'wp-map');
                                            }
                                            ?>
                                        </a>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <button type="button" class="toggle-row">
                                <span class="screen-reader-text"><?php _e('显示更多详情', 'wp-map'); ?></span>
                            </button>
                        </td>
                        <td data-colname="<?php _e('描述', 'wp-map'); ?>"><?php echo esc_html(mb_substr($footprint->description, 0, 16, 'UTF-8')); ?><?php if (mb_strlen($footprint->description, 'UTF-8') > 16) echo '...'; ?></td>
                        <td data-colname="<?php _e('访问日期', 'wp-map'); ?>"><?php echo esc_html($footprint->visit_date); ?></td>
                        <td data-colname="<?php _e('坐标', 'wp-map'); ?>"><?php echo esc_html($footprint->latitude) . ', ' . esc_html($footprint->longitude); ?></td>

                        <td data-colname="<?php _e('操作', 'wp-map'); ?>">
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo admin_url('admin.php?page=wp-map-add&footprint_id=' . $footprint->id); ?>">
                                        <?php _e('编辑', 'wp-map'); ?>
                                    </a>
                                </span>
                                |
                                <span class="trash">
                                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=delete_footprint&footprint_id=' . $footprint->id), 'wp_map_delete_footprint', 'wp_map_nonce'); ?>" class="submitdelete" onclick="return confirm('<?php _e('确定要删除这个足迹吗？', 'wp-map'); ?>')">
                                        <?php _e('删除', 'wp-map'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr class="no-items">
                    <td colspan="6" class="colspanchange"><?php _e('暂无足迹记录', 'wp-map'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                $current_url = admin_url('admin.php?page=wp-map');
                $page_links = paginate_links(array(
                    'base' => add_query_arg('paged', '%#%', $current_url),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $paged,
                    'type' => 'array'
                ));
                
                if ($page_links) {
                    echo '<span class="displaying-num">' . sprintf(__('第 %d 页，共 %d 页', 'wp-map'), $paged, $total_pages) . '</span>';
                    echo '<span class="pagination-links">' . implode(' ', $page_links) . '</span>';
                }
                ?>
            </div>
        <br class="clear">
    </div>
<?php endif; ?>
</div>