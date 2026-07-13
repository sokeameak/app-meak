<?php

namespace CookieAdminPro;

if(!defined('COOKIEADMIN_VERSION') || !defined('ABSPATH')){
	die('Hacking Attempt');
}

class DoNotSell{
	
	static function enqueue_assets(){
		$form_script = self::form_script();
		wp_add_inline_script('cookieadmin_pro_js', $form_script);

		// Adding the style
		wp_register_style('cookieadmin-do-not-sell', false);
		wp_enqueue_style('cookieadmin-do-not-sell');

		$form_style = self::form_style();
		wp_add_inline_style('cookieadmin-do-not-sell', $form_style);
	}

	// Submit Do Not Sell request form
	static function submit_do_not_sell_form(){
		global $wpdb, $cookieadmin;

		if(empty($_POST['cookieadmin_dns_req'])){
			wp_send_json_error(__('Invalid request', 'cookieadmin'));
		}
		

		$form_data = json_decode(sanitize_text_field(wp_unslash($_POST['cookieadmin_dns_req'])), true);
		
		// return if user hasn't confirmed the submission
		if(empty($form_data['confirmation'])){
			wp_send_json_error(esc_html(cookieadmin__($cookieadmin['default']['dns_confirm_err'])));
		}

		$settings = get_option('cookieadmin_do_not_sell', []);

		// check if the requiered fields are filled
		if(
			(empty($form_data['first_name']) || empty($form_data['last_name']) || empty($form_data['email'])) || 
			(!empty($settings['phone_enabled']) && !empty($settings['phone_required']) && empty($form_data['phone'])) || 
			(!empty($settings['zip_enabled']) && !empty($settings['zip_required']) && empty($form_data['zip_code']))
		){
			wp_send_json_error(esc_html(cookieadmin__($cookieadmin['default']['dns_empty_fields_err'])));
		}

		if(!preg_match('/^[\p{L}\s\'-]{2,50}$/u', $form_data['first_name']) || !preg_match('/^[\p{L}\s\'-]{2,50}$/u', $form_data['last_name'])){
			wp_send_json_error(esc_html(cookieadmin__($cookieadmin['default']['dns_name_err'])));
		}

		if(!is_email($form_data['email'])){
			wp_send_json_error(esc_html(cookieadmin__($cookieadmin['default']['dns_email_err'])));
		}
		
		// Making sure the email is clean
		$form_data['email'] = sanitize_email($form_data['email']);

		if(!empty($form_data['zip_code']) && !preg_match('/^[a-zA-Z0-9-\s]{3,10}$/', $form_data['zip_code'])){
			wp_send_json_error(esc_html(cookieadmin__($cookieadmin['default']['dns_zip_err'])));
		}

		if(!empty($form_data['phone']) && !preg_match('/^\+?[0-9\s\-\.\(\)]{7,20}$/', $form_data['phone'])){
			wp_send_json_error(esc_html(cookieadmin__($cookieadmin['default']['dns_phone_err'])));
		}

		// insert the entries in the database
		$table_name = $wpdb->prefix . 'cookieadmin_do_not_sell';
		$email_exist = $wpdb->get_var(
			$wpdb->prepare("SELECT user_email FROM $table_name WHERE user_email = %s", trim($form_data['email']))
		);

		// check if the email exists
		if(!empty($email_exist)){
			wp_send_json_error(esc_html(cookieadmin__($cookieadmin['default']['dns_request_in_progress'])));
		}

		$result = $wpdb->insert(
			$table_name,
			array(
				'user_email' => $form_data['email'],
				'first_name' => $form_data['first_name'],
				'last_name' => $form_data['last_name'],
				'phone' => !empty($form_data['phone']) ? $form_data['phone'] : '',
				'zip' => !empty($form_data['zip_code']) ? $form_data['zip_code'] : '',
				'status' => 'Recieved',
				'created_at' => time(),
			),
			array('%s', '%s', '%s', '%s', '%s', '%s', '%d')
		);
		
		// check if any error has occured
		if($result === false){
			wp_send_json_error(esc_html(cookieadmin__($cookieadmin['default']['dns_submission_err'])));
		}

		wp_send_json_success(esc_html(cookieadmin__($cookieadmin['default']['dns_submission_success'])));
	}

	// Generate Do Not Sell form html
	static function get_dns_shortcode_html(){
		global $cookieadmin;

		$settings = get_option('cookieadmin_do_not_sell', []);

		$output = '<div class="cookieadmin-do-not-sell-form">
			<div class="cookieadmin-do-not-sell-form-header">
				<h3>'.esc_html(cookieadmin__($cookieadmin['default']['dns_heading'])).'</h3>
			</div>
			<form method="post" id="cookieadmin_dns_form" onsubmit="cookieadmin_pro_submit_dns_form(event)">

				<div id="cookieadmin-dns-form-error" style="display:none;"></div>

				<div class="cookieadmin-do-not-sell-text">
					'.(!empty($settings['exp_content']) ? wp_kses_post($settings['exp_content']) 
					: esc_html__('Users have the right to request that their personal information not be sold or shared with third parties for advertising or marketing purposes, and businesses must honor this choice and stop such data use upon request.', 'cookieadmin')).'
				</div>
				
				<div class="cookieadmin-do-not-sell-form-field">
					<label for="cookieadmin_dns_first_name">'.esc_html(cookieadmin__($cookieadmin['default']['dns_fname'])).'<span  class="cookieadmin-do-not-sell-req-input">*</span></label>
					<input type="text" name="cookieadmin_dns_first_name" id="cookieadmin_dns_first_name" required>
				</div>
				
				<div class="cookieadmin-do-not-sell-form-field">
					<label for="cookieadmin_dns_last_name">'.esc_html(cookieadmin__($cookieadmin['default']['dns_lname'])).'<span  class="cookieadmin-do-not-sell-req-input">*</span></label>
					<input type="text" name="cookieadmin_dns_last_name" id="cookieadmin_dns_last_name" required>
				</div>
				
				<div class="cookieadmin-do-not-sell-form-field">
					<label for="cookieadmin_dns_email">'.esc_html(cookieadmin__($cookieadmin['default']['dns_email'])).'<span  class="cookieadmin-do-not-sell-req-input">*</span></label>
					<input type="email" name="cookieadmin_dns_email" id="cookieadmin_dns_email" required>
				</div>';

				// Insert input for the ZIP if enabled
				if(!empty($settings['zip_enabled'])){
					$output .= '<div class="cookieadmin-do-not-sell-form-field">
						<label for="cookieadmin_dns_zip_code">'.esc_html(cookieadmin__($cookieadmin['default']['dns_zip'])).'<span>'.(!empty($settings['zip_required']) ? '<span class="cookieadmin-do-not-sell-req-input">*</span>' : '').'</span></label>
						<input type="text" name="cookieadmin_dns_zip_code" id="cookieadmin_dns_zip_code" '.(esc_attr(!empty($settings['zip_required']) ? 'required' : '')).'>
					</div>';
				}
				// Insert input for the Phone if enabled
				if(!empty($settings['phone_enabled'])){
					$output .= '<div class="cookieadmin-do-not-sell-form-field">
						<label for="cookieadmin_dns_phone">'.esc_html(cookieadmin__($cookieadmin['default']['dns_phone'])).'<span>'.(!empty($settings['phone_required']) ? '<span class="cookieadmin-do-not-sell-req-input">*</span>' : '').'</span></label>
						<input type="text" name="cookieadmin_dns_phone" id="cookieadmin_dns_phone" '.(esc_attr(!empty($settings['phone_required']) ? 'required' : '')).'>
					</div>';
				}

				// Consent checkbox
				$output .= '<div class="cookieadmin-do-not-sell-form-field">
					<span><input type="checkbox" name="cookieadmin_dns_confirmation" id="cookieadmin_dns_confirmation" required>
					<span class="cookieadmin-do-not-sell-req-input">*</span>'.esc_html(cookieadmin__($cookieadmin['default']['dns_confirm_msg'])).'</span>
				</div>
				<div class="cookieadmin-do-not-sell-form-field cookieadmin-do-not-sell-save-btn" style="flex-direction:row;gap:10px; align-items:center;">
					<input type="submit" name="cookieadmin_dns_submit" class="button button-primary btn btn-primary wp-element-button cookieadmin_dns_submit" id="cookieadmin_dns_submit" value="'.esc_html(cookieadmin__($cookieadmin['default']['dns_submit'])).'">
					<span class="cookieadmin-dns-spinner"></span>
				</div>
				<div id="cookieadmin-dns-form-success" style="display:none;"></div>
			</form>
		</div>';
		
		return $output;
	}

	static function form_script(){
		$nonce = wp_create_nonce('cookieadmin_pro_js_nonce');
		$ajax_url = admin_url('admin-ajax.php');
		return "
			// Submit Do Not Sell request
			function cookieadmin_pro_submit_dns_form(e){
				e.preventDefault();

				if(cookieadmin_pro_vars === 'undefined' || !cookieadmin_pro_vars.ajax_url){
					return;
				}

				var success_elm = document.getElementById('cookieadmin-dns-form-success'),
				error_elm = document.getElementById('cookieadmin-dns-form-error'),
				spinner = document.querySelector('.cookieadmin-dns-spinner'),
				submit_btn = document.querySelector('.cookieadmin_dns_submit');

				if(success_elm && error_elm){
					success_elm.style.display = 'none';
					error_elm.style.display = 'none';
				}
				
				spinner.style.display = 'inline-block';
				submit_btn.disabled = true;

				// Get the form elements;
				var form_element = e.target;
				var form_data = {
					confirmation : '',
					first_name : '',
					last_name : '',
					email : '',
					phone : '',
					zip_code : ''
				};

				for(let key in form_data){
					form_data[key] = form_element.elements['cookieadmin_dns_' + key] ? form_element.elements['cookieadmin_dns_' + key].value : '';
				}

				const data = 'action=cookieadmin_pro_ajax_handler&cookieadmin_act=submit_do_not_sell_form' +
					'&cookieadmin_pro_security={$nonce}&cookieadmin_dns_req=' + encodeURIComponent(JSON.stringify(form_data));

				// Make async request — don't block or wait
				const xhttp = new XMLHttpRequest();

				xhttp.open('POST', '{$ajax_url}');

				xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

				xhttp.onload = function(){
					if(this.status === 200){
						try {
							var response = JSON.parse(this.responseText);
							console.log(response.data);
							if(response.success){
								if(success_elm){
									success_elm.innerText = response.data;
									success_elm.style.display = 'flex';
									form_element.reset();
								}
							}else{
								if(error_elm){
									error_elm.innerText = response.data;
									error_elm.style.display = 'flex';
								}
							}
						} catch (e) {
							console.log(e);
						}
					}
					
					spinner.style.display = 'none';
					submit_btn.disabled = false;
				}

				xhttp.onerror = function(){
					console.log('Ajax request failed');
					submit_btn.disabled = false;
				}

				xhttp.send(data);

				return true;
			}
		";
	}

	// Add styling for the Do Not Sell form layout and basic styling
	static function form_style(){
		return '.cookieadmin-do-not-sell-form{padding:10px 20px;max-width:600px;border:.5px solid #cecece;border-radius:5px}#cookieadmin_dns_form{display:flex;flex-direction:column;gap:15px}#cookieadmin_dns_form .cookieadmin-do-not-sell-form-field{display:flex;flex-direction:column;gap:3px}.cookieadmin-do-not-sell-form-field input[type="text"],.cookieadmin-do-not-sell-form-field input[type="email"]{padding:5px;max-width:400px}.cookieadmin-do-not-sell-form-field input[type="submit"]{padding:8px 15px;width:fit-content}#cookieadmin-dns-form-error{padding:10px;color:#fa3d3d;border-left:3.5px solid #fa3d3d;background-color:#fef2f2}#cookieadmin-dns-form-success{padding:10px;text-align:center}.cookieadmin-do-not-sell-req-input{margin:5px;color:red}.cookieadmin-do-not-sell-form-header{text-align:center}.cookieadmin-do-not-sell-text{text-align:justify}.cookieadmin_dns_submit{cursor:pointer;}
		.cookieadmin-dns-spinner {
			display:none;
			width: 1em;
			height: 1em;
			border: 2px solid transparent;
			border-top-color: currentColor;
			border-radius: 50%;
			animation: cookieadmin-dns-spin .6s linear infinite;
		}

		@keyframes cookieadmin-dns-spin {
			to {
				transform: rotate(360deg);
			}
		}';
	}
}