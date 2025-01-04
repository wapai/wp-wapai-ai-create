<?php

/**
 * 推送设置
 */
class Wapai_Settings
{
    /**
     * 初始化页面
     * @return void
     */
    public static function init_page()
    {
        self::add_settings_page();
    }

    /**
     * 显示设置页面
     * @return void
     */
    public static function show_page()
    {
        // 检查用户权限
        if (!current_user_can('manage_options')) {
            return;
        }
        $tab = Wapai_Plugin::get('tab', 'wapai-settings');

        // 显示错误/更新信息
        settings_errors('wapai_messages');
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
            <a style="font-size: 14px;margin-left: 10px;" href="https://shop.neiwangchuantou.com"
               target="_blank">
                使用教程
            </a>
            <nav class="nav-tab-wrapper wp-clearfix">
                <a href="admin.php?page=wapai-settings"
                   class="nav-tab<?php if (empty($tab) || 'wapai-settings' === $tab) {
                       echo ' nav-tab-active';
                   } ?>">基本设置</a>
            </nav>
            <form action="options.php" method="post">
                <input type="hidden" name="page" value="wapai-settings">
                <?php
                // 输出表单
                settings_fields($tab);
                do_settings_sections($tab);
                // 输出保存设置按钮
                submit_button('保存更改');
                ?>
            </form>
        </div>
        <?php
    }

    // 基本设置页面
    public static function add_settings_page()
    {
        // 注册一个新页面
        register_setting('wapai-settings', 'wapai_options', array('Wapai_Plugin', 'sanitize'));

        add_settings_section(
            'wapai_section_base',
            null,
            null,
            'wapai-settings'
        );

        // add_settings_field(
        //     'push_record',
        //     '总开关',
        //     array('Wapai_Plugin', 'field_callback'),
        //     'wapai-settings',
        //     'wapai_section_base',
        //     array(
        //         'label_for' => 'push_record',
        //         'form_type' => 'select',
        //         'form_data' => array(
        //             array(
        //                 'title' => '开启',
        //                 'value' => '0'
        //             ),
        //             array(
        //                 'title' => '关闭',
        //                 'value' => '1'
        //             )
        //         ),
        //         'form_desc' => '可以在创作列表页面查看进度'
        //     )
        // );

        // add_settings_field(
        //     'baidu_interval',
        //     '创作间隔',
        //     array('Wapai_Plugin', 'field_callback'),
        //     'wapai-settings',
        //     'wapai_section_base',
        //     array(
        //         'label_for' => 'baidu_interval',
        //         'form_type' => 'input',
        //         'type' => 'number',
        //         'form_desc' => '多少分钟自动创作一次，建议最短时间5分钟，小于1为不创作'
        //     )
        // );

        add_settings_field(
            'gpt_API_url',
            'ChatGPT API URL',
            array('Wapai_Plugin', 'field_callback'),
            'wapai-settings',
            'wapai_section_base',
            array(
                'label_for' => 'gpt_API_url',
                'form_type' => 'input',
                'type' => 'text',
                'form_desc' => '如:https://api.openai.com。不需要URL后面斜杠，允许第三方。'
            )
        );
        add_settings_field(
            'gpt_key',
            'ChatGPT KEY',
            array('Wapai_Plugin', 'field_callback'),
            'wapai-settings',
            'wapai_section_base',
            array(
                'label_for' => 'gpt_key',
                'form_type' => 'input',
                'type' => 'text',
                'form_desc' => '请填写接口调用地址中的ChatGPT KEY参数值'
            )
        );

        add_settings_field(
            'model_type',
            '模型名称',
            array('Wapai_Plugin', 'field_callback'),
            'wapai-settings',
            'wapai_section_base',
            array(
                'label_for' => 'model_type',
                'form_type' => 'input',
                'type' => 'text',
                'form_desc' => '模型名，如gpt-4o-mini'
            )
        );

        add_settings_field(
            'proto_text',
            '询问AI措辞调整',
            array('Wapai_Plugin', 'field_callback'),
            'wapai-settings',
            'wapai_section_base',
            array(
                'label_for' => 'proto_text',
                'form_type' => 'textarea',
                'type' => 'text',
                'form_desc' => '询问AI措辞，如不会写请复制使用如下措辞：请生成一篇五百字的文章，主题是:{article_title}'
            )
        );
    }

}