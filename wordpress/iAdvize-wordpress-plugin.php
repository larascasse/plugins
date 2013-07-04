<?php
/*
Plugin Name: iAdvize Wodpress Plugin
Plugin URI: http://www.iadvize.com/
Description: A plugin that ease the integration of the <a href="http://www.iadvize.com/">iAdvize</a> livechat application.
Version: 1.0
Author: Jonathan Gueron
Author URI: http://www.iadvize.com/

=== VERSION HISTORY ===
  09.14.10 - v1.0 - Initial release
 
=== LEGAL INFORMATION ===
  Copyright (C) 2010 iAdvize <contact@iadvize.com> - www.iadvize.com

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program. If not, see <http://www.gnu.org/licenses/>.
*/
function getiAdvizeID() {
	$url = 'http://www.iadvize.com/api/getcode.php?&out=wp&url=' . str_replace("http://" , "",get_option('siteurl'));
	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// User agent that mimics a browser
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; WindowsNT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
		$result = curl_exec($ch);
		curl_close($ch);
	} else {
	   // curl library is not compiled in so use file_get_contents
	   $result = file_get_contents($url);
	}
	return intval($result);
}

register_activation_hook(__FILE__,'idzPlugin_activated');
function idzPlugin_activated(){
	if(!get_option('idzID')) {
		$idzID = getiAdvizeID();
		if ($idzID > 0) {
			update_option('idzID',$idzID);
		}
	}
}

$idz_domain = 'iAdvizeWPPlugin';
load_plugin_textdomain($idz_domain, 'wp-content/plugins/iadvize-wordpres-plugin');
add_action('init', 'idz_init');
function idz_init() {
	if(function_exists('current_user_can') && current_user_can('manage_options'))
		add_action('admin_menu', 'idz_add_settings_page');
}

add_action('wp_footer', 'idz_insert');
function idz_insert() {
	if(get_option('idzID')) {
		echo "<!-- START IADVIZE LIVECHAT -->\n<script type=\"text/javascript\">\nvar iproto = ((\"https:\" == document.location.protocol) ? \"https://\" : \"http://\");\ndocument.write(unescape(\"%3Cscript src='\" + iproto + \"livechat.iadvize.com/chat_init.js?sid=".get_option('idzID')."' type='text/javascript'%3E%3C/script%3E\"));\n</script>\n<!-- END IADVIZE LIVECHAT -->";
	}
}

add_action('admin_notices', 'idz_admin_notice');
function idz_admin_notice() {
	if(!get_option('idzID')) echo('<div class="error"><p><strong>'.sprintf(__('iAdvize plugin is disabled. Please go to the <a href="%s">plugin page</a> and enter a valid account ID to enable it.' ), admin_url('options-general.php?page=iadvize-wordpress-plugin')).'</strong></p></div>');
}

add_filter('plugin_action_links', 'idz_plugin_actions', 10, 2);
function idz_plugin_actions($links, $file) {
	static $this_plugin;
	if(!$this_plugin) $this_plugin = plugin_basename(__FILE__);
	if($file == $this_plugin && function_exists('admin_url')) {
		$settings_link = '<a href="'.admin_url('options-general.php?page=iadvize-wordpress-plugin').'">'.__('Settings', $idz_domain).'</a>';
		array_unshift($links, $settings_link);
	}
	return($links);
}

function idz_add_settings_page() {
	function idz_settings_page() {
		global $idz_domain; ?>
		<div class="wrap">
			<?php screen_icon() ?>
			<h2><?php _e('iAdvize Wordpress Plugin', $idz_domain) ?></h2>
			<form method="post" action="options.php">
				<?php wp_nonce_field('update-options') ?>
				<p><label for="idzID"><?php _e('Enter your iAdvize ID', $idz_domain) ?></label><br />
				<input type="text" name="idzID" id="idzID" value="<?php echo(get_option('idzID')) ?>" />
				</p><p class="submit" style="padding:0">
				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="idzID" />
				<input type="submit" name="idzSubmit" id="idzSubmit" value="<?php _e('Save ID', $idz_domain) ?>" class="button-primary" /> 
				</p>
			</form>
		</div>
	<?php }
	add_submenu_page('options-general.php', __('iAdvize Plugin', $idz_domain), __('iAdvize Plugin', $idz_domain), 'manage_options', 'iadvize-wordpress-plugin', 'idz_settings_page');
}
?>
