<?php
/*
* FormLayer Pro
* https://formlayer.net
* (c) FormLayer Team
*/

namespace FormLayerPro;

if(!defined('ABSPATH')){
	exit;
}

class Admin{

	static function init(){
		add_action('admin_enqueue_scripts', '\FormLayerPro\Admin::admin_enqueue');
		add_action('formlayer_render_license_page', '\FormLayerPro\Settings\License::template');
		add_action('formlayer_render_reports_tab', '\FormLayerPro\Settings\UI::reports');
		add_action('formlayer_render_tools_tab', '\FormLayerPro\Settings\UI::tools');
		add_action('formlayer_render_integrations', '\FormLayerPro\Settings\UI::integrations');
		add_action('formlayer_render_entries_tab', '\FormLayerPro\Settings\UI::entries');
		add_action('formlayer_render_form_integrations', '\FormLayerPro\Settings\UI::render_form_integrations');
		add_action('formlayer_render_modals', '\FormLayerPro\Settings\UI::render_modal');
		add_action('formlayer_render_turnstile_settings', '\FormLayerPro\Settings\UI::turnstile', 10, 1);
		add_action('formlayer_render_recaptcha_settings', '\FormLayerPro\Settings\UI::recaptcha', 10, 1);

		// Filter for dynamic field types and categories
		add_filter('formlayer_builder_categories', '\FormLayerPro\Fields::add_categories');
		add_filter('formlayer_field_types', '\FormLayerPro\Fields::add_field_types');
		add_action('admin_notices', '\FormLayerPro\Admin::formlayer_pro_free_version_nag');
	}
	
	static function admin_enqueue($hook){
		if(false === strpos($hook, 'formlayer')){
			return;
		}

		wp_enqueue_style('formlayer-pro-admin', FORMLAYER_PRO_PLUGIN_URL.'assets/css/admin.css', [], FORMLAYER_PRO_VERSION);

		wp_enqueue_script('formlayer-pro-admin', FORMLAYER_PRO_PLUGIN_URL.'assets/js/admin.js', ['jquery'], FORMLAYER_PRO_VERSION, true);
		
		wp_enqueue_script('formlayer-chart-js', FORMLAYER_PRO_PLUGIN_URL.'assets/js/chart.min.js', [], FORMLAYER_PRO_VERSION, true);

		wp_enqueue_script('formlayer-reports-chart', FORMLAYER_PRO_PLUGIN_URL.'assets/js/reports-chart.js', ['jquery', 'formlayer-chart-js'], FORMLAYER_PRO_VERSION, true);

		$all_settings = get_option('formlayer_integration_settings', []);
		$js_settings = [];
		
		// Map saved settings to the structure expected by the JS
		foreach($all_settings as $slug => $data){
			if(!empty($data['settings'])){
				$js_settings[$slug] = $data['settings'];
			}
		}

		wp_localize_script('formlayer-pro-admin', 'formlayer_pro', [
			'nonce' => wp_create_nonce('formlayer_pro_admin_nonce'),
			'ajax_url' => admin_url('admin-ajax.php'),
			'admin_page_url' => admin_url('admin.php?page=formlayer'),
			'settings' => $js_settings,
			'html_templates' => \FormLayerPro\Templates::js_templates()
		]);
	}
	
	static function formlayer_pro_free_version_nag(){
		if(!defined('FORMLAYER_VERSION')){
			return;
		}

		$dismissed_free = (int) get_option('formlayer_version_free_nag');
		$dismissed_pro = (int) get_option('formlayer_version_pro_nag');

		// Checking if time has passed since the dismiss.
		if(!empty($dismissed_free) && time() < $dismissed_pro && !empty($dismissed_pro) && time() < $dismissed_pro){
			return;
		}

		$showing_error = false;
		if(version_compare(FORMLAYER_VERSION, FORMLAYER_PRO_VERSION) > 0 && (empty($dismissed_pro) || time() > $dismissed_pro)){
			$showing_error = true;

			echo '<div class="notice notice-warning is-dismissible" id="formlayer-pro-version-notice" onclick="formlayer_pro_dismiss_notice(event)" data-type="pro">
			<p style="font-size:16px;">'.esc_html__('You are using an older version of FormLayer Pro. We recommend updating to the latest version to ensure seamless and uninterrupted use of the application.', 'formlayer-pro').'</p>
		</div>';
		}elseif(version_compare(FORMLAYER_VERSION, FORMLAYER_PRO_VERSION) < 0 && (empty($dismissed_free) || time() > $dismissed_free)){
			$showing_error = true;

			echo '<div class="notice notice-warning is-dismissible" id="formlayer-pro-version-notice" onclick="formlayer_pro_dismiss_notice(event)" data-type="free">
			<p style="font-size:16px;">'.esc_html__('You are using an older version of FormLayer. We recommend updating to the latest free version to ensure smooth and uninterrupted use of the application.', 'formlayer-pro').'</p>
		</div>';
		}

		if(!empty($showing_error)){
			wp_register_script('formlayer-pro-version-notice', '', array('jquery'), FORMLAYER_PRO_VERSION, true );
			wp_enqueue_script('formlayer-pro-version-notice');
			wp_add_inline_script('formlayer-pro-version-notice', '
		function formlayer_pro_dismiss_notice(e){
			e.preventDefault();
			let target = jQuery(e.target);

			if(!target.hasClass("notice-dismiss")){
				return;
			}

			let jEle = target.closest("#formlayer-pro-version-notice"),
			type = jEle.data("type");

			jEle.slideUp();
			
			jQuery.post("'.admin_url('admin-ajax.php').'", {
				security : "'.wp_create_nonce('formlayer_version_notice').'",
				action: "formlayer_pro_version_notice",
				type: type
			}, function(res){
				if(!res["success"]){
					alert(res["data"]);
				}
			}).fail(function(data){
				alert("There seems to be some issue dismissing this alert");
			});
		}');
		}
	}
}