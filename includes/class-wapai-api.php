<?php

/**
 * 推送核心处理类
 */
class Wapai_Api
{

    /**
     * 获取请求超时时间
     * @return int
     */
    public static function get_request_timeout()
    {
        return Wapai_Plugin::get_option('push_timeout', 30);
    }



    /**
     * 格式化推送方式
     * @param int $record_mode
     * @return string
     */
    public static function format_ai_platform($record_mode)
    {
        switch ($record_mode) {
            case 1:
                return 'ChatGPT';
        }
        return $record_mode;
    }

    /**
     * 格式化推送结果
     * @param int $result_status
     * @return string
     */
    public static function format_result_status($result_status)
    {
        switch ($result_status) {
            case 0:
                return '未生成';
            case 1:
                return '成功';
            case 2:
                return '失败';
            case 3:
                return '未知';
        }
        return $result_status;
    }

    /**
     * 查询指定字段的文章数据
     * @param string $field 查询字段
     * @param int $num 查询数量
     * @param int $type 1 最新 2 随机 3 伪随机
     * @param int $offset 过滤多少条数据
     * @return array
     */
    public static function get_post_data($field, $num, $type, $offset = 0)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'posts';
        $order = 'DESC';
        $orderby = 'ID';
        $where = '';
        if (2 == $type) {
            $orderby = 'rand()';
            $order = '';
        } else if (3 == $type) {
            $max_id = $wpdb->get_var('SELECT MAX(ID) FROM `' . $table_name . '` where `post_status` = "publish"');
            $order = 'ASC';
            if ($max_id > $num) {
                $start_id = mt_rand(0, $max_id - $num);
                $where = '`ID` > ' . $start_id . ' AND';
            }
        }
        $sql = 'SELECT ' . $field . ' FROM `' . $table_name . '` WHERE ' . $where . ' `post_status` = %s ORDER BY ' . $orderby . ' ' . $order . ' LIMIT %d, %d';
        $query = $wpdb->prepare(
            $sql,
            'publish',
            $offset,
            $num
        );
        $results = $wpdb->get_results($query, ARRAY_A);
        $data = array();
        if (!empty($results)) {
            foreach ($results as $result) {
                $data[] = $result;
            }
        }
        return $data;
    }


    
}