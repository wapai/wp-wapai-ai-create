<?php

/**
 * 创作列表
 */
class Wapai_Record
{
    /**
     * 创作列表
     * @return void
     */
    public static function home()
    {
        $action = '';
        if (!empty($_GET['action'])) {
            $action = sanitize_title($_GET['action']);
        }
        if ('insert' === $action) {
            // 插入
            self::record_insert();
        } else if ('create_aritcle' === $action) {
            // 创建文章
            self::record_create_aritcle($_GET['record_id']);
            self::record_list();
        } else if ('detail' === $action) {
            // 详情
            self::record_detail();
        } else if ('delete' === $action) {
            // 删除
            if (!empty($_GET['record_id'])) {
                $id = (int)$_GET['record_id'];
                self::record_delete($id);
            } else if (!empty($_GET['ids']) && is_array($_GET['ids'])) {
                $ids = array_map('absint', array_values(wp_unslash($_GET['ids'])));
                self::record_delete(0, $ids);
            } else {
                wp_die('删除失败');
            }
        } else if ('delete_1' === $action) {
            self::record_delete(0, array(), 1);
        } else if ('delete_3' === $action) {
            self::record_delete(0, array(), 3);
        } else if ('delete_30' === $action) {
            self::record_delete(0, array(), 30);
        } else if ('delete_all' === $action) {
            self::record_delete(0, array(), 0);
        } else {
            self::record_list();
        }
    }

    /**
     * 列表
     * @return void
     */
    public static function record_list()
    {
?>
        <div class="wrap">
            <h1 class="wp-heading-inline">创作列表</h1>
            <p>生成文章根据文章难度需要几十秒时间，请耐心等待</p>
            <form method="get">
                <input type="hidden" name="page" value="wapai-record" />
                <?php
                $wapai_record_table = new Wapai_Record_Table();
                $wapai_record_table->prepare_items();
                $wapai_record_table->display();
                ?>
            </form>
        </div>
    <?php
    }

    public static function record_create_aritcle($record_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wapai_records';
        $sql = 'select * from `' . $table_name . '` where `record_id` = %d limit 1';
        $query = $wpdb->prepare(
            $sql,
            intval($record_id)
        );
        $results = $wpdb->get_results($query, ARRAY_A);
        $record_data = array();
        if (!empty($results)) {
            foreach ($results as $result) {
                $record_data = $result;
            }
        }
        if (empty($record_data)) {
            echo '<p style="color: red;">暂无数据</p>';
            return;
        }
        $api_key = Wapai_Plugin::get_option('gpt_key');
        $model_type = Wapai_Plugin::get_option('model_type');
        $gpt_API_url = Wapai_Plugin::get_option('gpt_API_url');
        $proto_text = Wapai_Plugin::get_option('proto_text');
        if (empty($api_key) || empty($model_type) || empty($gpt_API_url) || empty($proto_text)) {
            echo '<p style="color: red;">请先在插件设置中配置AI信息。</p>';
            return;
        }

        $question = str_replace("{article_title}", $record_data['article_title'], $proto_text);

        $url = $gpt_API_url . '/v1/chat/completions';
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json'
        );

        $data = array(
            'model' => $model_type,
            'messages' => array(
                array('role' => 'system', 'content' => "确保你返回内容是HTML格式。但不要出现meta，title的html标签"),
                array('role' => 'user', 'content' => $question)
            ),
            //'max_tokens' => 600,
        );

        $response = wp_remote_post($url, array(
            'method'    => 'POST',
            'body'      => json_encode($data),
            'headers'   => $headers,
            'timeout'   => 120,
        ));

        if (is_wp_error($response)) {
            echo '<p style="color: red;">请求失败: ' . $response->get_error_message() . '</p>';
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (isset($data['choices'][0]['message']['content'])) {
            $answer = $data['choices'][0]['message']['content'];
            // 创建分类，检查是否存在
            $category_id = get_cat_ID($record_data['post_category_title']); // 获取现有分类的 ID
            if ($category_id == 0) {
                // 分类不存在，创建新分类
                $category_id = wp_create_category($record_data['post_category_title']);
            }

            // 创建文章
            $post_data = array(
                'post_title'   => sanitize_text_field($record_data['article_title']),  // 使用用户的问题作为文章标题
                'post_content' => $answer,           // 使用生成的回答作为文章内容
                'post_status'  => 'publish',                        // 发布文章
                'post_author'  => get_current_user_id(),            // 设置当前用户为文章作者
                'post_type'    => 'post',                           // 设置文章类型为常规文章
                'post_category' => array($category_id),              // 将文章关联到指定分类
            );
            // 插入文章并获取文章 ID
            $post_id = wp_insert_post($post_data);
            $record_result_status = 0;
            if ($post_id) {
                $record_result_status = 1;
                echo '<p>文章已成功发布！<a href="' . get_permalink($post_id) . '" target="_blank">查看文章</a></p>';
            } else {
                $record_result_status = 2;
                echo '<p style="color: red;">文章创建失败。</p>';
            }
        } else {
            $record_result_status = 3;
            echo '<p style="color: red;">未能获取有效的回答。</p>';
        }
        //更新记录状态
        $wpdb->update(
            $table_name,
            array('record_result_status' => 1, 'post_id' => $post_id, 'record_result' => $body, 'record_date' => wp_date('Y-m-d H:i:s'), 'record_result_status' => $record_result_status),
            array('record_id' => $record_id)
        );
    }

    /**
     * 详情
     * @return void
     */
    public static function record_detail()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wapai_records';
        $sql = 'select * from `' . $table_name . '` where `record_id` = %d limit 1';
        $query = $wpdb->prepare(
            $sql,
            intval(Wapai_Plugin::get('record_id', 0))
        );
        $results = $wpdb->get_results($query, ARRAY_A);
        $record_data = array();
        if (!empty($results)) {
            foreach ($results as $result) {
                $record_data = $result;
            }
        }
        if (empty($record_data)) {
            wp_die('暂无数据');
        }
    ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">详情</h1>
            <br class="clear">
            <br class="clear">
            <table class="wp-list-table widefat fixed">
                <tbody>
                    <tr>
                        <th scope="col">Id</th>
                        <td><?php echo esc_html($record_data['record_id']); ?></td>
                    </tr>
                    <tr>
                        <th scope="col">推送平台</th>
                        <td><?php echo esc_html(Wapai_Api::format_ai_platform($record_data['ai_platform'])); ?></td>
                    </tr>
                    <tr>
                        <th scope="col">推送链接数量</th>
                        <td><?php echo esc_html($record_data['record_num']); ?></td>
                    </tr>
                    <tr>
                        <th scope="col">推送状态</th>
                        <td><?php echo esc_html(Wapai_Api::format_result_status($record_data['record_result_status'])); ?></td>
                    </tr>
                    <tr>
                        <th scope="col">推送结果状态码</th>
                        <td><?php echo esc_html($record_data['record_result_code']); ?></td>
                    </tr>
                    <tr>
                        <th scope="col">推送时间</th>
                        <td><?php echo esc_html($record_data['record_date']); ?></td>
                    </tr>
                </tbody>
            </table>
            <br class="clear">
            <table class="wp-list-table widefat fixed">
                <tr>
                    <th>推送链接</th>
                </tr>
                <tr>
                    <td>
                        <textarea class="large-text code" readonly="readonly"
                            rows="5"><?php echo esc_html(implode(PHP_EOL, json_decode($record_data['record_urls'], true))); ?></textarea>
                    </td>
                </tr>
            </table>
            <br class="clear">
            <table class="wp-list-table widefat fixed">
                <tr>
                    <th>推送响应数据</th>
                </tr>
                <tr>
                    <td>
                        <textarea class="large-text code" readonly="readonly"
                            rows="5"><?php echo esc_html($record_data['record_result']); ?></textarea>
                    </td>
                </tr>
            </table>
            <br class="clear">
            <?php
            if (!empty($record_data['record_result_error'])) {
            ?>
                <table class="wp-list-table widefat fixed">
                    <tr>
                        <th>失败原因</th>
                    </tr>
                    <tr>
                        <td>
                            <textarea class="large-text code" readonly="readonly"
                                rows="5"><?php echo esc_html($record_data['record_result_error']); ?></textarea>
                        </td>
                    </tr>
                </table>
                <br class="clear">
            <?php
            }
            ?>
            <a class="button button-primary" href="javascript:void(0);"
                onclick="history.back();">返回</a>
        </div>
    <?php
    }

    public static function record_insert()
    {
        global $wpdb;

        // 检查用户是否已提交表单
        if (isset($_POST['post_category_title']) && isset($_POST['article_title'])) {
            // 获取表单数据
            $post_category_title = sanitize_textarea_field($_POST['post_category_title']);
            $article_title = sanitize_textarea_field($_POST['article_title']);

            // 插入数据到数据库
            $wpdb->insert(
                'wp_wapai_records', // 数据表名
                array(
                    'ai_platform' => 1,
                    'model_type' => Wapai_Plugin::get_option('model_type','gpt-4o-mini'),
                    'post_category_title' =>  $post_category_title,
                    'article_title' => $article_title,
                    'record_date' => wp_date('Y-m-d H:i:s')
                )

            );

            // 提示用户操作成功
            echo '<div class="updated"><p>记录已成功插入。</p></div>';
        }
        self::wapai_records_insert_page();
    }


    public static function wapai_records_insert_page()
    {
    ?>
        <div class="wrap">
            <h1>新增要AI创作的文章</h1>
            <a href="<?php echo admin_url('admin.php?page=wapai-record'); ?>" class="button">返回</a>
            <form method="POST" action="">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="post_category_title">文章分类名</label></th>
                        <td>
                            <input type="text" name="post_category_title" id="post_category_title" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="article_title">标题名</label></th>
                        <td>
                            <input type="text" name="article_title" id="article_title" class="regular-text" required>
                        </td>
                    </tr>
                </table>
                <?php submit_button('新增'); ?>

            </form>
        </div>
    <?php
    }


    /**
     * 删除
     * @param int $id 删除单条记录
     * @param array $ids 删除多条记录
     * @param int $day 删除在此之前多少天的数据
     * @return void
     */
    public static function record_delete($id = 0, $ids = array(), $day = -1)
    {
        $current_url = self_admin_url('admin.php?page=wapai-record');
        $result = false;
        $last_error = '非法操作';
        if (!empty($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'bulk-wapai_records')) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'wapai_records';
            $query = '';
            if (!empty($id)) {
                $sql = 'DELETE FROM `' . $table_name . '` WHERE `record_id` = %d';
                $query = $wpdb->prepare(
                    $sql,
                    $id
                );
            } else if (!empty($ids)) {
                $sql = 'DELETE FROM `' . $table_name . '` WHERE `record_id` in (' . implode(', ', array_fill(0, count($ids), '%d')) . ')';
                $query = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $ids));
            } else if ($day >= 0) {
                if ($day <= 0) {
                    $end_record_date_time = time();
                } else if (1 === $day) {
                    $end_record_date_time = strtotime('-1 day');
                } else {
                    $end_record_date_time = strtotime('-' . $day . ' days');
                }
                $end_record_date = wp_date('Y-m-d H:i:s', $end_record_date_time);
                $sql = 'DELETE FROM `' . $table_name . '` where `record_date` <= %s';
                $query = $wpdb->prepare(
                    $sql,
                    $end_record_date
                );
            } else {
                wp_die('删除失败');
            }
            if (!empty($query)) {
                $result = $wpdb->query($query);
                $last_error = $wpdb->last_error;
            }
        }
    ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">删除推送记录</h1>
            <p>
                <?php
                if ($result !== false) {
                ?>
                    删除<?php echo esc_html($result); ?>条推送记录成功
                <?php
                } else {
                ?>
                    删除推送记录失败：<?php echo esc_html($last_error); ?>
                <?php
                }
                ?>
            </p>
            <a class="button button-primary" href="<?php echo esc_url($current_url); ?>">返回</a>
        </div>
<?php
    }
}
