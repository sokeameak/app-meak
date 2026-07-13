<?php

namespace CookieAdminPro;

if(!defined('COOKIEADMIN_PRO_VERSION') || !defined('ABSPATH')){
	die('Hacking Attempt');
}

class TranslateString {

	// Register strings to translate
	static function register_strings(){

		global $wpdb, $cookieadmin;
		$table_name = esc_sql($wpdb->prefix . 'cookieadmin_cookies');

		// String keys to translate
		$strings_to_translate = [
			'cookieadmin_notice_title',
			'cookieadmin_notice',
			'cookieadmin_preference_title',
			'cookieadmin_preference',
			'reConsent_title',
			'cookieadmin_customize_btn',
			'cookieadmin_reject_btn',
			'cookieadmin_accept_btn',
			'cookieadmin_save_btn',
			'powered_by',
			'reconsent',
			'cookie_preferences',
			'remark_standard',
			'remark',
			'none',
			'necessary_cookies',
			'necessary_cookies_desc',
			'functional_cookies',
			'functional_cookies_desc',
			'analytical_cookies',
			'analytical_cookies_desc',
			'advertisement_cookies',
			'advertisement_cookies_desc',
			'unclassified_cookies',
			'unclassified_cookies_desc',
		];

		// Register GPC messages
		if(!empty($cookieadmin['gpc_message_default']) && !empty($cookieadmin['gpc_override_warning_default'])){
			pll_register_string('gpc_message_default', $cookieadmin['gpc_message_default'], 'CookieAdmin');
			pll_register_string('gpc_override_warning_default', $cookieadmin['gpc_override_warning_default'], 'CookieAdmin', true);
		}

		// Strings saved in the options table
		$policy = cookieadmin_load_policy();
		$law = get_option('cookieadmin_law', 'cookieadmin_gdpr');
		foreach($policy[$law] as $key => $value){
			$multine_strings = [
				'cookieadmin_notice_title',
				'cookieadmin_notice',
				'cookieadmin_preference_title',
				'cookieadmin_preference',
			];
			if(in_array($key, $strings_to_translate)){
				pll_register_string($key, $value, 'CookieAdmin', in_array($key, $multine_strings));	
			}
		}

		// Strings for the cookie category and desc for consent banner
		$banner_strings = cookieadmin_load_strings($policy[$law]);
		foreach($banner_strings as $key => $value){
			if(in_array($key, $strings_to_translate)){
				pll_register_string($key, $value, 'CookieAdmin');
			}
		}

		// Translate cookieadmin categories saved in the database is exist any
		$cookies = $wpdb->get_results("SELECT cookie_name, category, expires, description, patterns FROM {$table_name}");
		if(!empty($cookies)){
			foreach($cookies as $cookie){
				if(!empty($cookie->description)){
					pll_register_string($cookie->cookie_name, $cookie->description, 'CookieAdmin');
				}
			}
		}

		// Translate language strings localized to js
		$language_strings = [
			'show_less' => 'Show less',
			'duration' => 'Duration',
			'session' => 'Session',
			'days' => 'Days',
			'gpc_alert' => 'Please accept override GPC before saving preference.',
			'gpc_alert_load_content' => 'Please accept override GPC from consent preferences to load this content.',
		];
		foreach($language_strings as $key => $value){
			pll_register_string($key, $value, 'CookieAdmin');
		}
		
		// Default language strings
		if(!empty($cookieadmin['default'])){
			foreach($cookieadmin['default'] as $key => $defaults){
				pll_register_string($key, $defaults, 'CookieAdmin');
			}
		}
		
	}

	static function translate_strings($strings = []){

		if(!cookieadmin_is_multilingual_active()){
			return $strings;
		}

		// Translate languages
		if(!empty($strings['lang'])){
			$strings['lang'] = map_deep($strings['lang'], 'pll__');
		}

		// Translate banner strings
		$banner_strings = [
			'cookieadmin_notice_title',
			'cookieadmin_notice',
			'cookieadmin_preference_title',
			'cookieadmin_preference',
			'cookieadmin_customize_btn',
			'cookieadmin_reject_btn',
			'cookieadmin_accept_btn',
			'cookieadmin_save_btn'
		];
		foreach($banner_strings as $key){
			if(!empty($strings[$key])){
				$strings[$key] = pll__($strings[$key]);
			}
		}

		// Translate categorized cookies
		if(!empty($strings['categorized_cookies'])){
			foreach($strings['categorized_cookies'] as $index => $cookie){
				
				if(!empty($cookie->description)){
					$strings['categorized_cookies'][$index]->description = pll__($cookie->description);
				}
			}
		}

		$exclude = [
			'powered_by',
			'reconsent',
			'cookie_preferences',
			'remark_standard',
			'remark',
			'none',
			'necessary_cookies',
			'necessary_cookies_desc',
			'functional_cookies',
			'functional_cookies_desc',
			'analytical_cookies',
			'analytical_cookies_desc',
			'advertisement_cookies',
			'advertisement_cookies_desc',
			'unclassified_cookies',
			'unclassified_cookies_desc',
			'gpc_message',
			'gpc_alert',
			'gpc_alert_load_content',
		];

		foreach($exclude as $key){
			if(!empty($strings[$key])){
				$strings[$key] = pll__($strings[$key]);
			}
		}

		return $strings;
	}
	
	static function string($string){
		return pll__($string);
	}
}