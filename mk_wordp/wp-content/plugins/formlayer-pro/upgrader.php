<?php

// Are we being accessed directly ?
if(!defined('FORMLAYER_PRO_VERSION')){
	exit('Hacking Attempt !');
}

add_action('plugins_loaded', 'formlayer_pro_updater_load_plugin');

function formlayer_pro_updater_load_plugin(){
	// Show the text to install the license key
	add_filter('puc_manual_final_check_link-formlayer-pro', 'formlayer_pro_updater_check_link', 10, 1);

	// Nag informing the user to install the free version.
	if(current_user_can('activate_plugins')){
		add_action('admin_notices', 'formlayer_pro_free_version_nag', 9);
		add_action('admin_menu', 'formlayer_pro_add_menu', 9);
	}

	$is_network_wide = formlayer_pro_is_network_active('formlayer-pro');
	$_do_version = get_option('formlayer_version');
	$req_free_update = !empty($_do_version) && version_compare($_do_version, '1.0.7', '<'); 

	if($is_network_wide){
		$free_installed = get_site_option('formlayer_free_installed');
	} else{
		$free_installed = get_option('formlayer_free_installed');
	}
	
	if(!empty($free_installed)){
		return;
	}
	
	// Include the necessary stuff
	include_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
	include_once(ABSPATH . 'wp-admin/includes/plugin.php');
	include_once(ABSPATH . 'wp-admin/includes/file.php');
	
	if(file_exists(WP_PLUGIN_DIR . '/formlayer/formlayer.php') && is_plugin_inactive('formlayer/formlayer.php') && empty($req_free_update)) {

		if($is_network_wide){
			update_site_option('formlayer_free_installed', time());
		}else{
			update_option('formlayer_free_installed', time());
		}

		return;
	}
	
	// Includes necessary for Plugin_Upgrader and Plugin_Installer_Skin
	include_once(ABSPATH . 'wp-admin/includes/misc.php');
	include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

	// Filter to prevent the activate text
	add_filter('install_plugin_complete_actions', 'formlayer_pro_prevent_activation_text', 10, 3);

	$upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
	
	// Upgrade the plugin to the latest version of free already installed.
	if(!empty($req_free_update)){
		$installed = $upgrader->upgrade('formlayer/formlayer.php');
	}else{
		$installed = $upgrader->install('https://downloads.wordpress.org/plugin/formlayer.zip');
	}
	
	if(!is_wp_error($installed) && $installed){
		
		if($is_network_wide){
			update_site_option('formlayer_free_installed', time());
		}else{
			update_option('formlayer_free_installed', time());
		}
		
		activate_plugin('formlayer/formlayer.php', '', $is_network_wide);
		remove_action('admin_notices', 'formlayer_pro_free_version_nag', 9);
		remove_action('admin_menu', 'formlayer_pro_add_menu', 9);
	}
}

// Do not shows the activation text if 
function formlayer_pro_prevent_activation_text($install_actions, $api, $plugin_file){
	if($plugin_file == 'formlayer/formlayer.php'){
		return array();
	}

	return $install_actions;
}

function formlayer_pro_free_version_nag(){

	if(file_exists(WP_PLUGIN_DIR . '/formlayer/formlayer.php')){
		$message = __('FormLayer Free version is installed but not active. FormLayer Pro depends on the free version, so you must activate it first in order to use FormLayer Pro.');
		$button_text = __('Go to Plugins', 'formlayer-pro');
		$button_url = admin_url('plugins.php');
	} else {
		$message = __('You have not installed the free version of FormLayer. FormLayer Pro depends on the free version, so you must install it first in order to use FormLayer Pro.');
		$button_text = __('Install Now', 'formlayer-pro');
		$button_url = admin_url('plugin-install.php?s=FormLayer&tab=search');
	}

	echo '<div class="notice notice-error">
		<p style="font-size:16px;">'.esc_html($message).' <a href="'.esc_url($button_url).'" class="button button-primary">'.esc_html($button_text).'</a></p>
	</div>';
}

function formlayer_pro_add_menu(){
	add_menu_page('FormLayer Settings', 'FormLayer', 'activate_plugins', 'formlayer', 'formlayer_pro_menu_page');
}

function formlayer_pro_menu_page(){
	$free_installed = file_exists(WP_PLUGIN_DIR . '/formlayer/formlayer.php');
	echo '<div style="color: #333;padding: 50px;text-align: center;">
		<h1 style="font-size: 2em;margin-bottom: 10px;">'. ($free_installed ? esc_html__('FormLayer Free version is not active!', 'formlayer-pro') : esc_html__('FormLayer Free version is not installed / outdated!', 'formlayer-pro')) .'</h1>
		<p style=" font-size: 16px;margin-bottom: 20px; font-weight:400;">'. ($free_installed ? esc_html__('FormLayer Pro depends on the free version of FormLayer, so you need to activate the free version first.', 'formlayer-pro') : esc_html__('FormLayer Pro depends on the free version of FormLayer, so you need to install / update the free version first.', 'formlayer-pro')) .'</p>
		<a href="'. ($free_installed ? admin_url('plugins.php') : admin_url('plugin-install.php?s=FormLayer&tab=search')) .'" style="text-decoration: none;font-size:16px;">'. ($free_installed ? esc_html__('Go to Plugins', 'formlayer-pro') : esc_html__('Install/Update Now', 'formlayer-pro')) .'</a>
	</div>';
}