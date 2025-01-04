<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * 推送记录表类
 */
class Wapai_Record_Table extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'wapai_record',
            'plural' => 'wapai_records',
            'ajax' => false
        ));
    }

    public function get_columns()
    {
        return array(
            'cb' => '<input type="checkbox" />',
            'record_id' => 'Id',
            'ai_platform' => 'AI平台',
            'model_type' => '模型名',
            'article_title' => '标题名',
            'post_category_title' => '文章分类',
            'record_result_status' => '状态',
            'record_date' => '创作时间',
            'mod' => '操作',
        );
    }

    public function column_default($item, $column_name)
    {
        if (isset($item[$column_name])) {
            return $item[$column_name];
        }
        return '';
    }

    public function column_mod($item)
    {
        $page = sanitize_title($_REQUEST['page']);
        $actions = array(
            'create_aritcle' => sprintf('<a href="?page=%s&action=%s&record_id=%d&_wpnonce=%s">生成</a>', $page, 'create_aritcle', $item['record_id'], wp_create_nonce('bulk-wapai_records')),
            //'detail' => sprintf('<a href="?page=%s&action=%s&record_id=%d&_wpnonce=%s">详情</a>', $page, 'detail', $item['record_id'], wp_create_nonce('bulk-wapai_records')),
            'delete' => sprintf('<a href="?page=%s&action=%s&record_id=%d&_wpnonce=%s">删除</a>', $page, 'delete', $item['record_id'], wp_create_nonce('bulk-wapai_records')),
        );

        return sprintf('%1$s',  $this->row_actions($actions,true));
    }

    public function column_article_title($item)
    {
        $str = '';
        if (!empty($item['post_id'])) {
            $str = '<a href="' . get_permalink($item['post_id']) . '" target="_blank">查看文章</a>';
        }
        return sprintf('%1$s %2$s',  $item['article_title'],$str);
    }

    public function column_ai_platform($item)
    {
        return Wapai_Api::format_ai_platform($item['ai_platform']);
    }
    public function column_record_result_status($item)
    {
        return Wapai_Api::format_result_status($item['record_result_status']);
    }


    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="ids[]" value="%d" />', $item['record_id']
        );
    }

    public function get_bulk_actions()
    {
        return array(
            'delete' => '删除勾选的数据',
            'delete_1' => '删除1天之前的数据',
            'delete_3' => '删除3天之前的数据',
            'delete_30' => '删除30天之前的数据',
            'delete_all' => '删除所有的数据'
        );
    }

    public function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->_args['plural'];
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $per_page = 10;
        $current_page = $this->get_pagenum();
        if (1 < $current_page) {
            $offset = $per_page * ($current_page - 1);
        } else {
            $offset = 0;
        }
        $map = array();
        if (!empty($_GET['ai_platform'])) {
            $map[] = '`ai_platform` = ' . intval($_GET['ai_platform']);
        }

        if (!empty($_GET['record_status'])) {
            $map[] = '`record_result_status` = ' . intval($_GET['record_status']);
        }
        $where = '';
        if (!empty($map)) {
            $where = 'WHERE ' . implode(' AND ', $map);
        }
        $sql = 'SELECT * FROM `' . $table_name . '` ' . $where . ' ORDER BY `record_id` DESC LIMIT %d, %d';
        $items = $wpdb->get_results($wpdb->prepare($sql, $offset, $per_page), ARRAY_A);
        $count = $wpdb->get_var('SELECT COUNT(`record_id`) FROM `' . $table_name . '` ' . $where);
        $this->items = $items;
        $this->set_pagination_args(array(
            'total_items' => $count,
            'per_page' => $per_page,
            'total_pages' => ceil($count / $per_page)
        ));
    }

    /**
     * 没有数据
     * @return void
     */
    public function no_items()
    {
        ?>
        暂无推送记录
        <?php
    }

    public function extra_tablenav($which)
    {
        if ('top' === $which) {
            $ai_platform = Wapai_Plugin::get('ai_platform', 0);
            ?>
            <div class="alignleft actions">
                <label for="filter-by-platform" class="screen-reader-text">按AI平台筛选</label>
                <select name="ai_platform" id="filter-by-platform">
                    <option value="0" <?php selected($ai_platform,0);?>>AI平台</option>
                    <option value="1" <?php selected($ai_platform,1);?>>ChatGPT</option>
                </select>

                
                <input type="submit" name="filter_action" id="post-query-submit" class="button" value="筛选">
                <a class="button button-primary" href="/wp-admin/admin.php?page=wapai-record&action=insert">新增</a>
            </div>
            <?php
        }
    }
}