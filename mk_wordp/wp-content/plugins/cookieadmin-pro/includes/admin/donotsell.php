<?php

namespace CookieAdminPro\Admin;

if(!defined('COOKIEADMIN_PRO_VERSION') || !defined('ABSPATH')){
	die('Hacking Attempt');
}

class DoNotSell{
	
	// Do Not Sell requests table (admin)
	static function do_not_sell_requests(){
		global $wpdb, $cookieadmin_msg, $cookieadmin_error;
		
		$settings = get_option('cookieadmin_do_not_sell', []);
		
		$table_name = $wpdb->prefix . 'cookieadmin_do_not_sell';
		$current_tab = !empty($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'requests';
		
		if(!empty($_POST['cookieadmin_dns_save_settings'])){
			check_admin_referer('cookieadmin_dns_settings', 'cookieadmin_pro_security');

			// Process from data
			$dns_settings = !empty($_POST['cookieadmin_dns']) ? map_deep(wp_unslash($_POST['cookieadmin_dns']), 'sanitize_text_field') : [];
			
			// create Do not sell table, when enabling the feature.
			if(!\CookieAdminPro\Admin::cookieadmin_pro_table_exists($table_name)){
				self::create_do_not_sell_table($table_name);
			}

			if(!empty($settings['custom_page'])){
				$dns_settings['custom_page'] = $settings['custom_page'];
			}
			$dns_settings['exp_content'] = !empty($_POST['cookieadmin_dns_exp_content']) ? wp_kses_post($_POST['cookieadmin_dns_exp_content']) : '';
			$dns_settings['selected_page'] = !empty($_POST['cookieadmin_dns_form_page']) ? (int) sanitize_text_field(wp_unslash($_POST['cookieadmin_dns_form_page'])) : 0;
			
			// Add the shorcode to the selected page at the end of the page
			if(($dns_settings['selected_page'] > 0) && ($dns_settings['selected_page'] !== (int) $settings['selected_page'])){
				$selected_page = get_post($dns_settings['selected_page']);

				// Add the shortcode on the selected page
				if(!empty($selected_page) && !empty($selected_page->ID)){
					if(strpos($selected_page->post_content, '[cookieadmin_opt_out_consent]') === false){
						$result = wp_update_post(array(
							'ID' => $selected_page->ID,
							'post_content' => $selected_page->post_content . '<!-- wp:shortcode -->[cookieadmin_opt_out_consent]<!-- /wp:shortcode -->'
						));

						if($result instanceof WP_Error){
							$cookieadmin_error = __('Error updating the shorcode on selected page', 'cookieadmin');
						}
					}
				}
			}
			// Remove the existing shortcode from the prev saved page
			$prev_shortcode_page = !empty($settings['selected_page']) ? get_post((int) $settings['selected_page']) : 0;
			if(!empty($prev_shortcode_page) && ($dns_settings['selected_page'] !== (int) $settings['selected_page'])){
				if(!empty($prev_shortcode_page->post_content) && (strpos($prev_shortcode_page->post_content, '[cookieadmin_opt_out_consent]') !== false)){
					$result = wp_update_post(array(
						'ID' => $prev_shortcode_page->ID,
						'post_content' => str_replace('<!-- wp:shortcode -->[cookieadmin_opt_out_consent]<!-- /wp:shortcode -->', '', $prev_shortcode_page->post_content)
					));

					if($result instanceof WP_Error){
						$cookieadmin_error = __('Error updating the shorcode on selected page', 'cookieadmin');
					}
				}
			}

			$dns_settings;
			if(empty($cookieadmin_error)){
				update_option('cookieadmin_do_not_sell', $dns_settings);
				$cookieadmin_msg = __('Settings saved successfully', 'cookieadmin');
			}
		}
		
		// handle bulk actions
		if(!empty($_POST['cookieadmin_dns_bulk_action']) && !empty($_POST['cookieadmin_dns_ids'])){
			check_admin_referer('cookieadmin_pro_admin_nonce', 'cookieadmin_pro_security');
			
			$ids = map_deep(wp_unslash($_POST['cookieadmin_dns_ids']), 'intval');
			
			// Mark proccess as Processed
			if($_POST['cookieadmin_dns_bulk_action'] === 'processed'){
				foreach($ids as $id){
					$wpdb->update(
						$table_name,
						array('status' => 'Processed', 'processed_at' => time()),
						array('id' => $id),
						array('%s', '%d'),
						array('%d')
					);
				}
				$cookieadmin_msg = __('Requests marked as processed', 'cookieadmin');
			}
		}
		
		\CookieAdmin\Admin::header_theme(__('Do Not Sell', 'cookieadmin'));
		
		echo '<div class="cookieadmin-nav-tabs">';
		
		$active_requests = ($current_tab === 'requests') ? ' nav-tab-active' : '';
		$active_settings = ($current_tab === 'settings') ? ' nav-tab-active' : '';
		
		echo '
				<a class="'.esc_attr($active_requests).'" href="'.esc_url(admin_url('admin.php?page=cookieadmin-do-not-sell&tab=requests')).'">'.esc_html__('Requests', 'cookieadmin').'</a>
				<a class="'.esc_attr($active_settings).'" href="'.esc_url(admin_url('admin.php?page=cookieadmin-do-not-sell&tab=settings')).'">'.esc_html__('Settings', 'cookieadmin').'</a>
			</div>';
		
		if($current_tab === 'settings'){
			self::render_settings_tab();
		}else{
			self::render_requests_tab($table_name);
		}
		
		\CookieAdmin\Admin::footer_theme();
	}
	
	static function generate_do_not_sell_page(){

		$settings = get_option('cookieadmin_do_not_sell', []);
		
		check_ajax_referer('cookieadmin_pro_admin_js_nonce', 'cookieadmin_pro_security');
		if(!current_user_can('administrator')){
			wp_send_json_error(__('Invalid user', 'cookieadmin'));
		}

		// check if the page is alredy generated
		if(!empty($settings['custom_page'])){
			$custom_page = get_post((int) $settings['custom_page']);
			if(!empty($custom_page && $custom_page->ID)){
				wp_send_json_error(__('Page already generated', 'cookieadmin'));
			}
		}

		$page_content = '<!-- wp:shortcode -->[cookieadmin_opt_out_consent]<!-- /wp:shortcode -->';
		$page_title = __('Do Not Sell My Personal Information', 'cookieadmin');

		$post_args = array(
			'post_type' => 'page',
			'post_title' => $page_title,
			'post_content' => $page_content,
			'post_status' => 'publish',
		);

		$page_id = wp_insert_post($post_args, true);
		if(!($page_id instanceof WP_Error)){
			$permalink = get_permalink($page_id);
			if(!empty($permalink)){
				$settings['selected_page'] = $page_id;
				$settings['custom_page'] = $page_id;
				$settings['enabled'] = '1';
				update_option('cookieadmin_do_not_sell', $settings);
				wp_send_json_success();
			}
		}

		wp_send_json_error(__('Something went wrong', 'cookieadmin'));
	}
	
	// Export Consent Logs from the Database
	static function export_dns_requests() {
		global $wpdb;
		
		$cookieadmin_export_type = !empty($_REQUEST['cookieadmin_export_type']) ? sanitize_text_field(wp_unslash($_REQUEST['cookieadmin_export_type'])) : '';
		
		if(!empty($cookieadmin_export_type)){
			if($cookieadmin_export_type == 'dns_requests'){

				$table_name = esc_sql($wpdb->prefix . 'cookieadmin_do_not_sell');
				
				//First will check if the table in the database exists or not?
				if(!\CookieAdminPro\Admin::cookieadmin_pro_table_exists($table_name)){
					echo -1;
					echo esc_html__('Configure Do Not Sell settings', 'cookieadmin');
					wp_die();
				}
				$requests = $wpdb->get_results("SELECT * from $table_name WHERE status = 'processed'", ARRAY_N);
			}
		}
		
		if(empty($requests)){
			echo -1;
			echo esc_html__('No data to export', 'cookieadmin');
			wp_die();
		}
		
		// Export to CSV
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=cookieadmin-do-not-sell-requests.csv');
		
		// Create file handle for output
		$output = fopen('php://output', 'w');
		
		// Add column headers
		fputcsv($output, array('ID', 'Email', 'FirstName', 'LastName', 'Phone', 'ZIP', 'Status', 'Received', 'Processed'));
		
		// Add data rows
		foreach($requests as $request){
			fputcsv($output, $request);
		}
		fclose($output);
		
		wp_die();
	}
	
	static function do_not_sell_requests_pagination(){
		global $wpdb;
		
		if($_POST && count($_POST) > 0){
			$nonce_slug = (wp_doing_ajax() ? 'cookieadmin_pro_admin_js_nonce' : 'cookieadmin_pro_admin_nonce');
			check_admin_referer($nonce_slug, 'cookieadmin_pro_security');
		}
	 
		if(!current_user_can('administrator')){
			wp_send_json_error(array('message' => __('Sorry, but you do not have permissions to perform this action', 'cookieadmin')));
		}
		
		$num_items = 0;
		$current_page = isset($_POST['current_page']) ? intval($_POST['current_page']) : 1;
		$table_name = esc_sql($wpdb->prefix . 'cookieadmin_do_not_sell');

		if (!\CookieAdminPro\Admin::cookieadmin_pro_table_exists($table_name)) {
			// wp_send_json_error(['message' => 'Table does not exist']);
			return array();
		}

		// Get total number of logs
		$total_dns_req = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");	
		$req_per_page = 20;

		// Calculate max pages
		$max_page = ceil($total_dns_req / $req_per_page);
		$max_page = max(1, $max_page);
		
		// Ensure current page is within valid range
		if ($current_page > $max_page) {
			$current_page = $max_page;
		} elseif ($current_page < 1) {
			$current_page = 1;
		}

		// Calculate pagination offset
		$offset = ($current_page - 1) * $req_per_page;
		
		// Fetch paginated requets
		$dns_request = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name ORDER BY id DESC LIMIT %d OFFSET %d",
				$req_per_page,
				$offset
			),
			ARRAY_A
		);
		
		$num_items = (!empty($dns_request) && is_array($dns_request)) ? count($dns_request) : 1;
		$min_items = $offset + 1;
		$max_items = $min_items + ($num_items - 1);
		
		// Adding the human diff format of the processed_at timestamp
		foreach($dns_request as $key => $request){
			if(!empty($request['processed_at'])){
				$dns_request[$key]['processed_at_human'] = human_time_diff($request['processed_at']);
			}
			
			if(!empty($request['created_at'])){
				$dns_request[$key]['created_at'] = wp_date('Y-m-d H:i:s', $request['created_at']);
			}
		}

		$return = [
			'logs' => $dns_request,
			'total_logs' => $total_dns_req,
			'logs_per_page' => $req_per_page,
			'current_page' => $current_page,
			'total_pages' => $max_page,
			'min_items' => $min_items,
			'max_items' => $max_items
		];

		// Return logs as JSON response
		if (defined('DOING_AJAX') && DOING_AJAX) {
			wp_send_json_success($return);
		}
		
		// Return paginated data
		return $return;
	}
	
	static function create_do_not_sell_table($do_not_sell_table){
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$db_path = !defined('SITEPAD') ? ABSPATH . 'wp-admin/includes/upgrade.php' : ABSPATH . 'site-admin/includes/upgrade.php';
		require_once($db_path);

		$sql = 'CREATE TABLE ' . $do_not_sell_table . ' (
			id INT NOT NULL AUTO_INCREMENT,
			user_email VARCHAR(100) NOT NULL,
			first_name VARCHAR(100) DEFAULT NULL,
			last_name VARCHAR(100) DEFAULT NULL,
			phone VARCHAR(30) DEFAULT NULL,
			zip VARCHAR(10) DEFAULT NULL,
			status VARCHAR(20) NOT NULL DEFAULT \'pending\',
			created_at BIGINT NOT NULL,
			processed_at BIGINT DEFAULT NULL,
			PRIMARY KEY  (id)
		) ' . $charset_collate . ';';

		dbDelta($sql);
	}
	
	static function render_requests_tab($table_name){
		global $cookieadmin_msg;

		$log_data = self::do_not_sell_requests_pagination();
		$consent_logs = (!empty($log_data['logs']) ? $log_data['logs'] : array());
		
		echo '<div class="cookieadmin_pro_consent-wrap" style="max-width: 85vw;">
			<form action="" method="post">
				<div class="cookieadmin_consent-contents">
					<div class="cookieadmin_consent">
						<div class="contents cookieadmin_manager">
							<div class="cookieadmin-setting cookieadmin-manager-consent-logs">';
							wp_nonce_field('cookieadmin_pro_admin_nonce', 'cookieadmin_pro_security');
							echo '<div class="cookieadmin-dns-table-operations">
									<div class="cookieadmin-dns-bulk-action">
										<select name="cookieadmin_dns_bulk_action">
											<option value="">'.esc_html__('Bulk Actions', 'cookieadmin').'</option>
											<option value="processed">'.esc_html__('Mark as Processed', 'cookieadmin').'</option>
										</select>
										<input type="submit" class="cookieadmin-btn cookieadmin-btn-primary" value="'.esc_html__('Apply', 'cookieadmin').'">
									</div>
									<button type="button" class="cookieadmin-btn cookieadmin-btn-primary" id="cookieadmin_pro_export_dns_req">'
										.esc_html__('Export Proccessed Requests(CSV)', 'cookieadmin').
									'</button>
								</div>
								<div class="cookieadmin-manager-scan-result">
									<table class="cookieadmin-table cookieadmin-dns-requests">
										<thead>
											<tr>
												<th style="width: 40px;"><input type="checkbox" id="cookieadmin_dns_select_all"></th>
												<th>'.esc_html__('Email', 'cookieadmin').'</th>
												<th>'.esc_html__('Name', 'cookieadmin').'</th>
												<th>'.esc_html__('Status', 'cookieadmin').'</th>
												<th>'.esc_html__('Phone', 'cookieadmin').'</th>
												<th>'.esc_html__('ZIP code', 'cookieadmin').'</th>
												<th>'.esc_html__('Recieved', 'cookieadmin').'</th>
											</tr>
										</thead>
										<tbody>';
								
										if(empty($consent_logs)){
											echo '<tr><td colspan="6" class="cookieadmin-dns-empty">'.esc_html__('No requests found', 'cookieadmin').'</td></tr>';
										}else{
											foreach($consent_logs as $log){
											echo '<tr>
												<td><input type="checkbox" name="cookieadmin_dns_ids[]" value="'.esc_attr($log['id']).'"></td>
												<td>'.esc_html($log['user_email']).'</td>
												<td>'.esc_html($log['first_name'].' '.$log['last_name']).'</td>
												<td>
													<span>'.esc_html(ucfirst($log['status'])).'</span>
													'.((!empty($log['processed_at']) && (strtolower($log['status']) === 'processed')) 
													? '<br /><span>'.sprintf(esc_html__('%s ago', 'cookieadmin'), human_time_diff($log['processed_at'])).'</span>' : '').'
												</td>
												<td>'.(!empty($log['phone']) ? esc_html($log['phone']) : '-').'</td>
												<td>'.(!empty($log['zip']) ? esc_html($log['zip']) : '-').'</td>
												<td>'.esc_html($log['created_at']).'</td>
											</tr>';
											}
										}
										echo '</tbody>
									</table>
								</div>';
								// Pagination
								if(!empty($consent_logs)){
									echo '
									<div class="cookieadmin-dns-requests-pagination" style="text-align:right;">
											'.esc_html__('Displaying', 'cookieadmin').' <span class="displaying-num">'.esc_html($log_data['min_items'].' - '.$log_data['max_items']).'</span> '.esc_html__('of', 'cookieadmin').' <span class="max-num">'.esc_html($log_data['total_logs']).'</span> '.esc_html__('item(s)', 'cookieadmin').'
											&nbsp;
											<a class="first-page cookieadmin-dns-paginate" id="cookieadmin-first-dns-request" href="javascript:void(0)">
											<span aria-hidden="true">«</span>
											</a>
											&nbsp;
											<a class="prev-page cookieadmin-dns-paginate" id="cookieadmin-previous-dns-request" href="javascript:void(0)">
											<span aria-hidden="true">‹</span>
											</a>
											&nbsp;
											<span class="paging-input">
												<label for="current-page-selector" class="screen-reader-text">Current Page</label>
												<input class="current-page" id="current-page-selector" name="current-page-selector" value="'.(!empty($log_data['current_page']) ? esc_attr($log_data['current_page']) : '').'" size="3"  aria-describedby="table-paging" type="text" style="text-align: center;">
												<span class="tablenav-paging-text"> of 
													<span class="total-pages">'.esc_html($log_data['total_pages']).'</span>
												</span>
											</span>
											&nbsp;
											<a class="next-page cookieadmin-dns-paginate"  id="cookieadmin-next-dns-request" href="javascript:void(0)">
												<span aria-hidden="true">›</span>
											</a>
											&nbsp;
											<a class="last-page cookieadmin-dns-paginate" 
											id="cookieadmin-last-dns-request" href="javascript:void(0)">
												<span aria-hidden="true">»</span>
											</a>
											&nbsp;
									</div>';
								}
								echo '
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>';
	}
	
	static function render_settings_tab(){
		$settings = get_option('cookieadmin_do_not_sell', []);

		if(empty($settings['exp_content'])){
			$settings['exp_content'] = 'Users have the right to request that their personal information not be sold or shared with third parties for advertising or marketing purposes, and businesses must honor this choice and stop such data use upon request.';
		}

		$published_pages = get_pages();
		$custom_page = !empty($settings['custom_page']) ? get_post((int) $settings['custom_page']) : '';
		$preview_page_url = !empty($settings['selected_page']) ? get_permalink((int) $settings['selected_page']) : '';

		echo '<div class="cookieadmin_consent-wrap">
			<form method="post" id="cookieadmin_dns_settings_form">
				<div class="cookieadmin-card cookieadmin-mt-16" cookieadmin-pro-only="1">
					<div class="cookieadmin-card-header">
						<span class="cookieadmin-card-title"><span class="dashicons dashicons-admin-settings"></span>'.esc_html__('Do Not Sell Settings', 'cookieadmin').'</span>
					</div>
					<div class="cookieadmin-card-body">
						<div class="cookieadmin-setting">
							<label class="cookieadmin-title">'.esc_html__('Enable Do Not Sell Form', 'cookieadmin').'
								<span class="dashicons dashicons-info cookieadmin-tooltip-box" data-tip="'.esc_html__('Enable this settings to accept and manage do not sell requests', 'cookieadmin').'"></span>	
							</label>
							<div class="cookieadmin-setting-contents">
								<label class="cookieadmin-toggle-wrap">
									<input name="cookieadmin_dns[enabled]" type="checkbox" value="1" '.(checked(!empty($settings['enabled']), true, false)).'>
									<div class="cookieadmin-toggle-track">
										<div class="cookieadmin-toggle-thumb"></div>
									</div>
								</label>
							</div>
						</div>

						<div class="cookieadmin-setting">
							<label class="cookieadmin-title">'.esc_html__('Explanation text', 'cookieadmin').'
								<span class="dashicons dashicons-info cookieadmin-tooltip-box" data-tip="'.esc_html__('A conscise description of the user\'s right to opt out of selling or sharing of their personal information.', 'cookieadmin').'"></span>	
							</label>
							<div class="cookieadmin-setting-contents">
								<textarea name="cookieadmin_dns_exp_content" id="cookieadmin_dns_exp_content" rows="5" cols="100">'.esc_html($settings['exp_content'], 'cookieadmin').'</textarea>
							</div>
						</div>

						<div class="cookieadmin-setting">
							<label class="cookieadmin-title">'.esc_html__('First Name', 'cookieadmin').'
								<span class="dashicons dashicons-info cookieadmin-tooltip-box" data-tip="'.esc_html__('First name of the requester', 'cookieadmin').'"></span>	
							</label>
							<div class="cookieadmin-setting-contents">
								<label class="cookieadmin-toggle-wrap">
									<input type="checkbox" checked disabled>
									<div class="cookieadmin-toggle-track">
										<div class="cookieadmin-toggle-thumb"></div>
									</div>
								</label>
								<span class="cookieadmin-dns-toggle-label">'.esc_html__('Always enabled', 'cookieadmin').'</span>
							</div>
						</div>

						<div class="cookieadmin-setting">
							<label class="cookieadmin-title">'.esc_html__('Last Name', 'cookieadmin').'
								<span class="dashicons dashicons-info cookieadmin-tooltip-box" data-tip="'.esc_html__('Last name of the requester', 'cookieadmin').'"></span>	
							</label>
							<div class="cookieadmin-setting-contents">
								<label class="cookieadmin-toggle-wrap">
									<input type="checkbox" checked disabled>
									<div class="cookieadmin-toggle-track">
										<div class="cookieadmin-toggle-thumb"></div>
									</div>
								</label>
								<span class="cookieadmin-dns-toggle-label">'.esc_html__('Always enabled', 'cookieadmin').'</span>
							</div>
						</div>

						<div class="cookieadmin-setting">
							<label class="cookieadmin-title">'.esc_html__('Email', 'cookieadmin').'
								<span class="dashicons dashicons-info cookieadmin-tooltip-box" data-tip="'.esc_html__('The email address of the requester.', 'cookieadmin').'"></span>	
							</label>
							<div class="cookieadmin-setting-contents">
								<label class="cookieadmin-toggle-wrap">
									<input type="checkbox" checked disabled>
									<div class="cookieadmin-toggle-track">
										<div class="cookieadmin-toggle-thumb"></div>
									</div>
								</label>
								<span class="cookieadmin-dns-toggle-label">'.esc_html__('Always enabled', 'cookieadmin').'</span>
							</div>
						</div>

						<div class="cookieadmin-setting">
							<label class="cookieadmin-title">'.esc_html__('Phone Number', 'cookieadmin').'
								<span class="dashicons dashicons-info cookieadmin-tooltip-box" data-tip="'.esc_html__('Phone number of the requester', 'cookieadmin').'"></span>	
							</label>
							<div class="cookieadmin-setting-contents">
								<span class="cookieadmin-dns-toggle-group">
									<label class="cookieadmin-toggle-wrap">
										<input name="cookieadmin_dns[phone_enabled]" type="checkbox" value="1" '.(checked(!empty($settings['phone_enabled']), true, false)).'>
										<div class="cookieadmin-toggle-track">
											<div class="cookieadmin-toggle-thumb"></div>
										</div>
									</label>
									<span class="cookieadmin-dns-toggle-label">'.esc_html__('Enable', 'cookieadmin').'</span>
								</span>
								<span class="cookieadmin-dns-toggle-group">
									<label class="cookieadmin-toggle-wrap">
										<input name="cookieadmin_dns[phone_required]" type="checkbox" value="1" '.(checked(!empty($settings['phone_required']), true, false)).'>
										<div class="cookieadmin-toggle-track">
											<div class="cookieadmin-toggle-thumb"></div>
										</div>
									</label>
									<span class="cookieadmin-dns-toggle-label">'.esc_html__('Required', 'cookieadmin').'</span>
								</span>
							</div>
						</div>

						<div class="cookieadmin-setting">
							<label class="cookieadmin-title">'.esc_html__('ZIP Code', 'cookieadmin').'
								<span class="dashicons dashicons-info cookieadmin-tooltip-box" data-tip="'.esc_html__('ZIP or postal code of the requester', 'cookieadmin').'"></span>	
							</label>
							<div class="cookieadmin-setting-contents">
								<span class="cookieadmin-dns-toggle-group">
									<label class="cookieadmin-toggle-wrap">
										<input name="cookieadmin_dns[zip_enabled]" type="checkbox" value="1" '.(checked(!empty($settings['zip_enabled']), true, false)).'>
										<div class="cookieadmin-toggle-track">
											<div class="cookieadmin-toggle-thumb"></div>
										</div>
									</label>
									<span class="cookieadmin-dns-toggle-label">'.esc_html__('Enable', 'cookieadmin').'</span>
								</span>
								<span class="cookieadmin-dns-toggle-group">
									<label class="cookieadmin-toggle-wrap">
										<input name="cookieadmin_dns[zip_required]" type="checkbox" value="1" '.(checked(!empty($settings['zip_required']), true, false)).'>
										<div class="cookieadmin-toggle-track">
											<div class="cookieadmin-toggle-thumb"></div>
										</div>
									</label>
									<span class="cookieadmin-dns-toggle-label">'.esc_html__('Required', 'cookieadmin').'</span>
								</span>
							</div>
						</div>

						<div class="cookieadmin-setting">
							<label class="cookieadmin-title">'.esc_html__('Select Page', 'cookieadmin').'
								<span class="dashicons dashicons-info cookieadmin-tooltip-box" data-tip="'.esc_html__('Select the page to show the Do Not Sell form', 'cookieadmin').'"></span>	
							</label>
							<div class="cookieadmin-setting-contents">
								<select name="cookieadmin_dns_form_page" id="cookieadmin_dns_select_page">
									<option value="">'.esc_html__('--Select Existing Page--', 'cookieadmin').'</option>';
									if(!empty($published_pages) && is_array($published_pages)){
										foreach($published_pages as $page){
											echo '<option value="'.esc_attr($page->ID).'" '.selected((int) $settings['selected_page'], $page->ID, false).'>'.esc_html($page->post_title).'</option>';
										}
									}
								echo '</select>'.(empty($settings['custom_page']) || empty($custom_page) 
								? '<span>'.esc_html__('OR', 'cookieadmin').'</span>
								<input type="button" class="cookieadmin-btn" id="cookieadmin_dns_generate_page" value="'.esc_html__('Generate New Page', 'cookieadmin').'">' : '').'
								<span class="cookieadmin_dns_page_preview" '.(empty($preview_page_url) ? 'style="display:none;"' : '').'>
									<span class="dashicons dashicons-external"></span>&nbsp;
									<a href="'.(!empty($preview_page_url) ? esc_attr($preview_page_url) : '#').'" target="__blank">'.esc_html__('Preview Do Not Sell form', 'cookieadmin').'</a>
								</span>
								<span class="cookieadmin_desc cookieadmin-dns-shortcode-box">'.sprintf(
									esc_html__('The shortcode %s must be present on the selected page in order to display the "Do Not Sell" form correctly.', 'cookieadmin'), 
									'<span><strong>[cookieadmin_opt_out_consent]</strong></span>'
								).'</span>
							</div>
						</div>
					</div>
				</div>';

				wp_nonce_field('cookieadmin_dns_settings', 'cookieadmin_pro_security');

				echo '
				<div class="cookieadmin-save-bar">
					<input type="submit" name="cookieadmin_dns_save_settings" class="cookieadmin-btn cookieadmin-btn-primary" value="'.esc_html__('Save Settings', 'cookieadmin').'">
				</div>
			</form>
		</div>';
	}	
}
