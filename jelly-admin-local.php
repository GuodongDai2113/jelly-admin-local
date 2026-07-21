<?php

/**
 * Plugin Name:  Jelly Admin Local
 * Plugin URI:   https://jellydai.com/
 * Description:  Jelly Admin Local 插件，提供后台管理界面增强功能。
 * Version:      1.0.0
 * Author:       JellyDai
 * Author URI:   https://jellydai.com/
 * Text Domain:  jelly-admin-local
 * Domain Path:  /languages/
 * Requires PHP: 7.4
 * Requires at least: 6.6
 */

define('JELLY_ADMIN_LOCAL_VERSION', '1.0.0');
define('JELLY_ADMIN_LOCAL_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('JELLY_ADMIN_LOCAL_LANGUAGES', JELLY_ADMIN_LOCAL_PLUGIN_PATH . 'languages/');

register_activation_hook(__FILE__, 'jal_activate');

add_action('admin_init', 'jal_maybe_download_zh_cn');

if (is_admin()) {
	add_filter('load_textdomain_mofile', 'jal_load_mofile', 10, 2);
	add_filter('load_script_translation_file', 'jal_load_translation_file', 10, 3);
	add_filter('locale', 'jal_set_chinese_locale');
}

function jal_activate()
{
	jal_download_zh_cn();
	update_option('jal_zh_cn_downloaded', true);
}

function jal_maybe_download_zh_cn()
{
	if (!get_option('jal_zh_cn_downloaded')) {
		jal_download_zh_cn();
		update_option('jal_zh_cn_downloaded', true);
	}
}

function jal_download_zh_cn()
{
	if (!function_exists('wp_download_language_pack')) {
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	wp_download_language_pack('zh_CN');
}

function jal_set_chinese_locale($locale)
{
	return 'zh_CN';
}

function jal_get_mofile($domain, $file_name)
{
	return JELLY_ADMIN_LOCAL_LANGUAGES . $domain . '/' . $file_name . '.mo';
}

function jal_load_mofile($mofile, $domain)
{
	if ($domain == 'seo-by-rank-math') {
		return jal_get_mofile('seo-by-rank-math', 'seo-by-rank-math-zh_CN');
	}

	if ($domain == 'elementor-pro') {
		return jal_get_mofile('elementor-pro', 'elementor-pro-zh_CN');
	}

	return $mofile;
}

function jal_load_translation_file($file, $handle, $domain)
{
	if (!in_array($domain, ['seo-by-rank-math', 'elementor-pro'])) {
		return $file;
	}

	$local = JELLY_ADMIN_LOCAL_LANGUAGES . $domain .'/';
	$basename = basename($file);
	if (file_exists($local . $basename)) {
		return $local . $basename;
	}

	return $file;
}
