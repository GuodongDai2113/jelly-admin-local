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
	add_filter('locale', 'jal_set_chinese_locale');
	add_action('admin_bar_menu', 'jal_add_admin_bar_menu', 100);

	add_action('plugins_loaded', function () {
		if (!jal_is_translation_disabled()) {
			add_filter('load_textdomain_mofile', 'jal_load_mofile', 10, 2);
			add_filter('load_script_translation_file', 'jal_load_translation_file', 10, 3);
		}
	}, 0);
}

add_action('admin_post_jal_disable_translation', 'jal_handle_disable_translation');
add_action('admin_post_jal_enable_translation', 'jal_handle_enable_translation');

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

/**
 * 检查当前用户是否禁用了翻译
 */
function jal_is_translation_disabled()
{
	if (!function_exists('wp_get_current_user')) {
		return false;
	}
	$user = wp_get_current_user();
	if (!$user || !$user->ID) {
		return false;
	}
	$disabled_until = get_user_meta($user->ID, 'jal_translation_disabled_until', true);
	if (!$disabled_until) {
		return false;
	}
	if ($disabled_until > time()) {
		return true;
	}
	delete_user_meta($user->ID, 'jal_translation_disabled_until');
	return false;
}

function jal_set_chinese_locale($locale)
{
	if (jal_is_translation_disabled()) {
		return $locale;
	}
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

	$local = JELLY_ADMIN_LOCAL_LANGUAGES . $domain . '/';
	$basename = basename($file);
	if (file_exists($local . $basename)) {
		return $local . $basename;
	}

	return $file;
}

/**
 * 处理禁用翻译请求（admin-post）
 */
function jal_handle_disable_translation()
{
	if (!wp_verify_nonce($_GET['_wpnonce'], 'jal_disable_translation') || !is_user_logged_in()) {
		wp_die('Unauthorized.');
	}

	$duration = isset($_GET['duration']) ? (int) $_GET['duration'] : 0;

	if (!in_array($duration, array(30, 120), true)) {
		wp_die('Invalid duration.');
	}

	$expires_at = time() + $duration * 60;
	update_user_meta(get_current_user_id(), 'jal_translation_disabled_until', $expires_at);

	wp_safe_redirect(wp_get_referer() ?: admin_url());
	exit;
}

/**
 * 处理恢复翻译请求（admin-post）
 */
function jal_handle_enable_translation()
{
	if (!wp_verify_nonce($_GET['_wpnonce'], 'jal_enable_translation') || !is_user_logged_in()) {
		wp_die('Unauthorized.');
	}

	delete_user_meta(get_current_user_id(), 'jal_translation_disabled_until');

	wp_safe_redirect(wp_get_referer() ?: admin_url());
	exit;
}

/**
 * 在右上角用户菜单（注销上方）添加禁用翻译选项
 */
function jal_add_admin_bar_menu($wp_admin_bar)
{
	if (!is_user_logged_in()) {
		return;
	}

	$is_disabled = jal_is_translation_disabled();
	$disabled_until = get_user_meta(get_current_user_id(), 'jal_translation_disabled_until', true);

	$base_url = admin_url('admin-post.php');


	if ($is_disabled) {
		$remaining = $disabled_until - time();
		$minutes = ceil($remaining / 60);
		$title = sprintf(' (%d分钟)', $minutes);
		$wp_admin_bar->add_node(array(
			'parent' => 'user-actions',
			'id' => 'jal-enable-translation',
			'title' => '恢复翻译'.$title,
			'href' => wp_nonce_url(add_query_arg('action', 'jal_enable_translation', $base_url), 'jal_enable_translation'),
			'position' => 5,
		));
	} else {

		$wp_admin_bar->add_node(array(
			'parent' => 'user-actions',
			'id' => 'jal-disable-translation-30',
			'title' => '禁用翻译30分钟',
			'href' => wp_nonce_url(add_query_arg(array('action' => 'jal_disable_translation', 'duration' => 30), $base_url), 'jal_disable_translation'),
			'position' => 20,
		));

		$wp_admin_bar->add_node(array(
			'parent' => 'user-actions',
			'id' => 'jal-disable-translation-120',
			'title' => '禁用翻译2小时',
			'href' => wp_nonce_url(add_query_arg(array('action' => 'jal_disable_translation', 'duration' => 120), $base_url), 'jal_disable_translation'),
			'position' => 30,
		));
	}
}
