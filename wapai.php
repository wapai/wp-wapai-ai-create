<?php
/**
 * Plugin Name:wapai-ai-create文章自动创作
 * Plugin URI:https://shop.neiwangchuantou.com
 * Description:导入文章内容，使用Chatgpt等AI来自动创作文章。
 * Version:0.1
 * Requires at least:5.3
 * Requires PHP:5.6
 * Author:wapai
 * Author URI:https://shop.neiwangchuantou.com
 * License:GPL v2 or later
 */

// 直接访问报404错误
if (!function_exists('add_action')) {
    http_response_code(404);
    exit;
}
// 插件目录后面有 /
const WAPAI_PLUGIN_FILE = __FILE__;
define('WAPAI_PLUGIN_DIR', plugin_dir_path(WAPAI_PLUGIN_FILE));
// 定义配置
$wapai_options = get_option('wapai_options', array());
// 定义上次错误信息
$wapai_error = '';
/**
 * 自动加载
 * @param string $class
 * @return void
 */
function wapai_autoload($class)
{
    $class_file = WAPAI_PLUGIN_DIR . 'includes/class-' . strtolower(str_replace('_', '-', $class)) . '.php';
    if (file_exists($class_file)) {
        require_once $class_file;
    }
}
spl_autoload_register('wapai_autoload');
// 启用插件
register_activation_hook(WAPAI_PLUGIN_FILE, array('Wapai_Plugin', 'plugin_activation'));
// 删除插件
register_uninstall_hook(WAPAI_PLUGIN_FILE, array('Wapai_Plugin', 'plugin_uninstall'));
// 禁用插件
register_deactivation_hook(WAPAI_PLUGIN_FILE, array('Wapai_Plugin', 'plugin_deactivation'));
// 添加页面
add_action('admin_init', array('Wapai_Plugin', 'admin_init'));
// 添加菜单

add_action('admin_menu', array('Wapai_Plugin', 'admin_menu'));
