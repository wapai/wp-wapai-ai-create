<?php

/**
 * 插件启用、删除、禁用等
 */
class Wapai_Plugin
{
    // 启用插件
    public static function plugin_activation()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wapai_records';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = <<<SQL
CREATE TABLE {$table_name} (
  `record_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ai_platform` tinyint unsigned NOT NULL COMMENT 'ai平台',
  `model_type` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '模型名字',
  `article_title` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '文章标题',
  `record_result` text COLLATE utf8mb4_unicode_520_ci COMMENT '推送结果',
  `record_date` datetime DEFAULT NULL COMMENT '时间',
  `post_id` int DEFAULT NULL COMMENT '文章id',
  `post_category_title` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT '分类名',
  `record_result_status` int DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`record_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        // 如果表不存在才会执行创建
        maybe_create_table($table_name, $sql);
        // 创建默认配置
        global $wapai_options;
        if (empty($wapai_options)) {
            add_option('wapai_options', array(
                'menu_position' => 100,
                'push_timeout' => 30,
            ));
        }
    }

    // 删除插件执行的代码
    public static function plugin_uninstall()
    {
        // 删除表
        global $wpdb;
        global $wapai_options;
        $wapai_options = get_option('wapai_options', array());
        $table_name = $wpdb->prefix . 'wapai_records';
        $wpdb->query('DROP TABLE IF EXISTS `' . $table_name . '`');
        // 删除IndexNow密钥文件
        // 删除配置
        delete_option('wapai_options');
    }

    // 初始化
    public static function admin_init()
    {
        // 注册设置页面
        if (!empty($_REQUEST['page'])) {
            if ('wapai-settings' === $_REQUEST['page']) {
                // 推送设置
                Wapai_Settings::init_page();
            }
        }
    }

    // 添加菜单
    public static function admin_menu()
    {
        global $wapai_options;
        $position = null;
        if (!empty($wapai_options['menu_position'])) {
            $position = intval($wapai_options['menu_position']);
        }

        // 父菜单
        add_menu_page(
            'WAPAI创作',
            'WAPAI创作',
            'manage_options',
            '#wapai',
            null,
            'dashicons-admin-links',
            $position
        );

        // 创作列表页面
        add_submenu_page(
            '#wapai',
            '创作列表',
            '创作列表',
            'manage_options',
            'wapai-record',
            array('Wapai_Record', 'home')
        );

        // 推送设置
        add_submenu_page(
            '#wapai',
            '创作设置',
            '创作设置',
            'manage_options',
            'wapai-settings',
            array('Wapai_Settings', 'show_page')
        );

        remove_submenu_page('#wapai', '#wapai');
    }

    /**
     * 在插件页面添加设置链接
     * @param $links
     * @return mixed
     */
    public static function setups($links)
    {
        $business_link = '<a href="https://www.ggdoc.cn/plugin/1.html" target="_blank">商业版</a>';
        array_unshift($links, $business_link);

        array_unshift($links, '<a href="https://www.ggdoc.cn/archives/product_type/theme" target="_blank">主题</a>');
        array_unshift($links, '<a href="https://www.ggdoc.cn/archives/product_type/plugin" target="_blank">插件</a>');

        $setups = '<a href="admin.php?page=wapai-settings">设置</a>';
        array_unshift($links, $setups);

        return $links;
    }

    /**
     * 表单输入框回调
     * @param array $args 这数据就是add_settings_field方法中第6个参数（$args）的数据
     */
    public static function field_callback($args)
    {
        // 表单的id或name字段
        $id = $args['label_for'];
        // 表单的名称
        $input_name = 'wapai_options[' . $id . ']';
        // 获取表单选项中的值
        global $wapai_options;
        // 表单的值
        $input_value = isset($wapai_options[$id]) ? $wapai_options[$id] : '';
        // 表单的类型
        $form_type = isset($args['form_type']) ? $args['form_type'] : 'input';
        // 输入表单说明
        $form_desc = isset($args['form_desc']) ? $args['form_desc'] : '';
        // 输入表单type
        $type = isset($args['type']) ? $args['type'] : 'text';
        // 输入表单placeholder
        $form_placeholder = isset($args['form_placeholder']) ? $args['form_placeholder'] : '';
        // 下拉框等选项值
        $form_data = isset($args['form_data']) ? $args['form_data'] : array();
        // 扩展form表单属性
        $form_extend = isset($args['form_extend']) ? $args['form_extend'] : array();
        switch ($form_type) {
            case 'input':
                self::generate_input(
                    array_merge(
                        array(
                            'id' => $id,
                            'type' => $type,
                            'placeholder' => $form_placeholder,
                            'name' => $input_name,
                            'value' => $input_value,
                            'class' => 'regular-text',
                        ),
                        $form_extend
                    ));
                break;
            case 'select':
                self::generate_select(
                    array_merge(
                        array(
                            'id' => $id,
                            'placeholder' => $form_placeholder,
                            'name' => $input_name
                        ),
                        $form_extend
                    ),
                    $form_data,
                    $input_value
                );
                break;
            case 'checkbox':
                self::generate_checkbox(
                    array_merge(
                        array(
                            'name' => $input_name . '[]',
                            'input_name' => $input_name,
                        ),
                        $form_extend
                    ),
                    $form_data,
                    $input_value
                );
                break;
            case 'textarea':
                self::generate_textarea(
                    array_merge(
                        array(
                            'id' => $id,
                            'placeholder' => $form_placeholder,
                            'name' => $input_name,
                            'class' => 'large-text code',
                            'rows' => 5,
                        ),
                        $form_extend
                    ),
                    $input_value
                );
                break;
        }
        if (!empty($form_desc)) {
            ?>
            <p class="description"><?php echo esc_html($form_desc); ?></p>
            <?php
        }
    }

    /**
     * 生成textarea表单
     * @param array $form_data 标签上的属性数组
     * @param string $value 默认值
     * @return void
     */
    public static function generate_textarea($form_data, $value = '')
    {
        ?><textarea <?php
        foreach ($form_data as $k => $v) {
            echo esc_attr($k); ?>="<?php echo esc_attr($v); ?>" <?php
        } ?>><?php echo esc_textarea($value); ?></textarea>
        <?php
    }

    /**
     * 生成checkbox表单
     * @param array $form_data 标签上的属性数组
     * @param array $checkboxs 下拉列表数据
     * @param string|array $value 选中值，单个选中字符串，多个选中数组
     * @return void
     */
    public static function generate_checkbox($form_data, $checkboxs, $value = '')
    {
        ?>
        <fieldset>
            <p>
                <input type="hidden" name="<?php echo esc_attr($form_data['input_name']); ?>" value="">
                <?php
                $len = count($checkboxs);
                foreach ($checkboxs as $k => $checkbox) {
                    $checked = '';
                    if (!empty($value)) {
                        if (is_array($value)) {
                            if (in_array($checkbox['value'], $value)) {
                                $checked = 'checked';
                            }
                        } else {
                            if ($checkbox['value'] == $value) {
                                $checked = 'checked';
                            }
                        }
                    }
                    ?>
                    <label>
                        <input type="checkbox" <?php checked($checked, 'checked'); ?><?php
                        foreach ($form_data as $k2 => $v2) {
                            echo esc_attr($k2); ?>="<?php echo esc_attr($v2); ?>" <?php
                        } ?> value="<?php echo esc_attr($checkbox['value']); ?>"
                        ><?php echo esc_html($checkbox['title']); ?>
                    </label>
                    <?php
                    if ($k < ($len - 1)) {
                        ?>
                        <br>
                        <?php
                    }
                }
                ?>
            </p>
        </fieldset>
        <?php
    }

    /**
     * 生成input表单
     * @param array $form_data 标签上的属性数组
     * @return void
     */
    public static function generate_input($form_data)
    {
        ?><input <?php
        foreach ($form_data as $k => $v) {
            echo esc_attr($k); ?>="<?php echo esc_attr($v); ?>" <?php
        } ?>><?php
    }

    /**
     * 生成select表单
     * @param array $form_data 标签上的属性数组
     * @param array $selects 下拉列表数据
     * @param string|array $value 选中值，单个选中字符串，多个选中数组
     * @return void
     */
    public static function generate_select($form_data, $selects, $value = '')
    {
        ?><select <?php
        foreach ($form_data as $k => $v) {
            echo esc_attr($k); ?>="<?php echo esc_attr($v); ?>" <?php
        } ?>><?php
        foreach ($selects as $select) {
            $selected = '';
            if (!empty($value)) {
                if (is_array($value)) {
                    if (in_array($select['value'], $value)) {
                        $selected = 'selected';
                    }
                } else {
                    if ($select['value'] == $value) {
                        $selected = 'selected';
                    }
                }
            }
            ?>
            <option <?php selected($selected, 'selected'); ?>
                    value="<?php echo esc_attr($select['value']); ?>"><?php echo esc_html($select['title']); ?></option>
            <?php
        }
        ?>
        </select>
        <?php
    }

    /**
     * 统一设置保存变量
     * @param $input
     * @return array
     */
    public static function sanitize($input)
    {
        global $wapai_options;
        if (empty($input)) {
            return $wapai_options;
        }
   
        if (empty($wapai_options)) {
            return $input;
        } else {
            return array_merge($wapai_options, $input);
        }
    }

    /**
     * 更新配置
     * @param array $options
     * @return void
     */
    public static function update_options($options)
    {
        global $wapai_options;
        $wapai_options = self::sanitize($options);
        update_option('wapai_options', $wapai_options);
    }


    /**
     * 获取存储的设置值
     * @param string $option 设置的键
     * @param mixed $def_value 默认值
     * @return mixed|string
     */
    public static function get_option($option, $def_value = '')
    {
        global $wapai_options;
        if (!empty($wapai_options[$option])) {
            return $wapai_options[$option];
        }
        return $def_value;
    }

    /**
     * 获取GET中的参数值
     * @param string $name
     * @param mixed $defValue
     * @return mixed
     */
    public static function get($name, $defValue = '')
    {
        if (isset($_GET[$name])) {
            return $_GET[$name];
        }
        return $defValue;
    }

    /**
     * 设置上一次错误信息
     * @param string $error
     * @return void
     */
    public static function set_error($error)
    {
        global $wapai_error;
        $wapai_error = $error;
    }

    /**
     * 获取上一次的错误信息
     * @return string
     */
    public static function get_error()
    {
        global $wapai_error;
        return $wapai_error;
    }



}