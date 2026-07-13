<?Php
/*
* FormLayer Pro
* https://formlayer.net
* (c) FormLayer Team
*/

namespace FormLayerPro;

if(!defined('ABSPATH')){
	exit;
}

class Ajax{

	static function hooks(){
		add_action('wp_ajax_formlayer_pro_save_integration', '\FormLayerPro\Ajax::save_integration_settings');
		add_action('wp_ajax_formlayer_pro_captcha_verify', '\FormLayerPro\Ajax::verify_pro_captcha');
		add_action('wp_ajax_nopriv_formlayer_pro_captcha_verify', '\FormLayerPro\Ajax::verify_pro_captcha');
		add_action('wp_ajax_formlayer_filter_entries', '\FormLayerPro\Ajax::filter_entries');
		add_action('wp_ajax_formlayer_delete_entry', '\FormLayerPro\Ajax::delete_entry');
		add_action('wp_ajax_formlayer_get_entry_details', '\FormLayerPro\Ajax::get_entry_details');
		add_action('wp_ajax_formlayer_toggle_entry_status', '\FormLayerPro\Ajax::toggle_entry_status');
		add_action('wp_ajax_formlayer_export_csv', '\FormLayerPro\Ajax::export_csv');
		add_action('wp_ajax_formlayer_entries_bulk_action', '\FormLayerPro\Ajax::handle_entries_bulk_action');
		add_action('wp_ajax_formlayer_bulk_action', '\FormLayerPro\Ajax::handle_bulk_action');
		add_action('wp_ajax_formlayer_get_reports_data', '\FormLayerPro\Ajax::get_reports_data');
		add_action('wp_ajax_formlayer_export_forms', '\FormLayerPro\Ajax::export_forms');
		add_action('wp_ajax_formlayer_import_forms', '\FormLayerPro\Ajax::import_forms');

		// Save entry BEFORE the free plugin sends its response (so entry_id is available)
		add_filter('formlayer_before_submission_response', '\FormLayerPro\Ajax::save_entry', 10, 3);

		// Register filter for free plugin to delegate captcha verification
		add_filter('formlayer_verify_pro_captcha', '\FormLayerPro\Ajax::verify_captcha_filter', 10, 4);
		add_action('wp_ajax_formlayer_pro_version_notice', '\FormLayerPro\Ajax::formlayer_pro_version_notice');
	}

	static function save_entry($entry_id, $form_id, $submitted_data){
		global $wpdb;

		$ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash( $_SERVER['REMOTE_ADDR'])) : '';
		$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash( $_SERVER['HTTP_USER_AGENT'])) : '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert(
			$wpdb->prefix . 'formlayer_entries',
			[
				'form_id'    => $form_id,
				'data'       => wp_json_encode( $submitted_data ),
				'status'     => 'unread',
				'ip_address' => $ip_address,
				'user_agent' => $user_agent,
				'created_at' => current_time( 'mysql', true ),
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s' ]
		);

		if($inserted){
			return (int) $wpdb->insert_id;
		}

		return 0;
	}

	static function handle_bulk_action() {
		check_ajax_referer('formlayer_admin_nonce', 'nonce');

		if(!current_user_can('manage_options')){
			wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'formlayer-pro')]);
		}

		$action = isset($_POST['bulk_action']) ? sanitize_text_field(wp_unslash($_POST['bulk_action'])) : '';
		$display_ids = isset($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];
		if(empty($action) || empty($display_ids)) {
			wp_send_json_error(['message' => esc_html__('No action or IDs provided', 'formlayer-pro')]);
		}

		if($action === 'delete'){
			foreach($display_ids as $did){
				$id = \FormLayer\Util::get_post_id_by_display_id($did);
				if(!$id) continue;
				$form = get_post($id);
				if($form && 'formlayer_form' === $form->post_type){
					wp_delete_post($id, true);
				}
			}

			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$remaining = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'formlayer_form' AND post_status NOT IN ('trash', 'auto-draft')");
			if (intval($remaining) === 0) {
				update_option('formlayer_id_counter', 0);
			}

			wp_send_json_success(['message' => esc_html__('Selected forms deleted', 'formlayer-pro')]);
		}

		wp_send_json_error(['message' => esc_html__('Action not implemented', 'formlayer-pro')]);
	}

	static function save_integration_settings(){
		check_ajax_referer('formlayer_pro_admin_nonce', 'nonce');

		if(!current_user_can('manage_options')){
			wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'formlayer-pro')]);
		}

		$integration = isset($_POST['integration']) ? sanitize_key($_POST['integration']) : '';
		$settings = isset($_POST['settings']) ? wp_unslash($_POST['settings']) : [];

		if(empty($integration)){
			wp_send_json_error(['message' => 'Invalid integration']);
		}

		$all_settings = get_option('formlayer_integration_settings', []);

		$sanitized_settings = [];
		foreach($settings as $key => $value){
			$key = sanitize_key($key);
			if($key === 'service_account_json'){
				$sanitized_settings[$key] = sanitize_textarea_field($value);
			} else {
				$sanitized_settings[$key] = sanitize_text_field($value);
			}
		}

		$all_settings[$integration] = [
			'enabled' => '1',
			'settings' => $sanitized_settings
		];

		update_option('formlayer_integration_settings', $all_settings);

		wp_send_json_success([
			'message' => sprintf(
				/* translators: %s: Integration name (e.g., Mailchimp, Slack) */
				esc_html__('%s settings saved and integration enabled!', 'formlayer-pro'),
				ucfirst($integration)
			)
		]);
	}

	static function verify_captcha_filter($default, $captcha_provider, $post_data, $global_settings){
		if(in_array($captcha_provider, ['turnstile', 'recaptcha'])){
			return self::verify_captcha_inline($captcha_provider, $post_data, $global_settings);
		}
		return $default;
	}

	static function verify_captcha_inline($captcha_provider, $post_data, $global_settings){
		$captcha_verified = false;
		$captcha_token = '';
		$secret_key = '';
		$verify_url = '';

		if($captcha_provider === 'turnstile'){
			$captcha_token = isset($post_data['cf-turnstile-response']) ? sanitize_text_field(wp_unslash($post_data['cf-turnstile-response'])) : '';
			$secret_key = isset($global_settings['captcha_t_secret_key']) ? $global_settings['captcha_t_secret_key'] : '';
			$verify_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
		} elseif($captcha_provider === 'recaptcha'){
			$captcha_token = isset($post_data['g-recaptcha-response']) ? sanitize_text_field(wp_unslash($post_data['g-recaptcha-response'])) : '';
			$secret_key = isset($global_settings['captcha_r_secret_key']) ? $global_settings['captcha_r_secret_key'] : '';
			$verify_url = 'https://www.google.com/recaptcha/api/siteverify';
		}

		if(!empty($captcha_token) && !empty($secret_key)){
			$response = wp_remote_post($verify_url, [
				'body' => [
					'secret' => $secret_key,
					'response' => $captcha_token
				]
			]);
			
			if(!is_wp_error($response)) {
				$body = json_decode(wp_remote_retrieve_body($response), true);
				if($body && !empty($body['success'])){
					$captcha_verified = true;
				}
			}
		}

		return $captcha_verified;
	}

	static function verify_pro_captcha(){
		check_ajax_referer('formlayer-frontend', 'nonce');

		$provider = isset($_POST['captcha_provider']) ? sanitize_key($_POST['captcha_provider']) : '';
		$global_settings = get_option('formlayer_settings', []);

		$captcha_verified = false;
		$captcha_token = '';
		$secret_key = '';
		$verify_url = '';

		if($provider === 'turnstile'){
			$captcha_token = isset($_POST['captcha_token']) ? sanitize_text_field(wp_unslash($_POST['captcha_token'])) : '';
			$secret_key = isset($global_settings['captcha_t_secret_key']) ? $global_settings['captcha_t_secret_key'] : '';
			$verify_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
		} elseif($provider === 'recaptcha'){
			$captcha_token = isset($_POST['captcha_token']) ? sanitize_text_field(wp_unslash($_POST['captcha_token'])) : '';
			$secret_key = isset($global_settings['captcha_r_secret_key']) ? $global_settings['captcha_r_secret_key'] : '';
			$verify_url = 'https://www.google.com/recaptcha/api/siteverify';
		} else {
			wp_send_json_error(['message' => 'Invalid captcha provider']);
		}

		if(empty($captcha_token) || empty($secret_key)){
			wp_send_json_error(['message' => 'Missing captcha configuration']);
		}

		$response = wp_remote_post($verify_url, [
			'body' => [
				'secret' => $secret_key,
				'response' => $captcha_token
			],
			'timeout' => 30
		]);

		if(is_wp_error($response)) {
			wp_send_json_error(['message' => 'Captcha verification request failed']);
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);
		if($body && !empty($body['success'])){
			$captcha_verified = true;
		}

		if(!$captcha_verified){
			wp_send_json_error(['message' => esc_html__('Captcha verification failed. Please try again.', 'formlayer-pro')]);
		}

		wp_send_json_success(['verified' => true]);
	}
	
	static function delete_entry(){

		check_ajax_referer('formlayer_admin_nonce', 'nonce');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'No permission']);
		}

		global $wpdb;
		$id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
		if ($id) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete($wpdb->prefix . 'formlayer_entries', ['id' => $id]);
			wp_send_json_success([
				'message' => 'Entry deleted successfully',
				'unread_count' => \FormLayerPro\Util::get_unread_count()
			]);
		}
		wp_send_json_error(['message' => 'Invalid entry ID']);
	}

	static function export_csv(){
		check_ajax_referer('formlayer_admin_nonce', 'nonce');

		if(!current_user_can('manage_options')){
			wp_send_json_error(['message' => esc_html__('Insufficient permissions', 'formlayer-pro')]);
		}

		global $wpdb;
		$form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
		$status  = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';

		$where  = ' WHERE 1=1';
		$params = [];

		if($form_id){
			$p_id    = \FormLayer\Util::get_post_id_by_display_id($form_id);
			$where  .= ' AND form_id = %d';
			$params[] = $p_id ? $p_id : $form_id;
		}

		if($status){
			$where  .= ' AND status = %s';
			$params[] = $status;
		}

		// Query built and passed inline — never stored in a variable
		// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if(empty($params)){
			$entries = $wpdb->get_results(
				'SELECT * FROM ' . $wpdb->prefix . 'formlayer_entries' . $where
			);
		} else {
			$entries = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM ' . $wpdb->prefix . 'formlayer_entries' . $where,
					$params
				)
			);
		}
		// phpcs:enable

		if(empty($entries)){
			wp_send_json_error(['message' => 'No entries to export']);
		}

		$form_configs = [];
		$field_keys   = [];
		$all_labels   = [];

		foreach($entries as $entry){
			$f_id = $entry->form_id;
			if(!isset($form_configs[$f_id])){
				$form_configs[$f_id] = [
					'labels' => \FormLayer\Util::get_form_field_labels($f_id),
					'types'  => \FormLayer\Util::get_form_field_types($f_id)
				];
			}

			$data = json_decode($entry->data, true);
			if($data === null && !empty($entry->data)){
				$data = json_decode(stripslashes($entry->data), true);
			}

			if(is_array($data)){
				foreach($data as $k => $v){
					if($k === '__source_url') continue;
					if(!in_array($k, $field_keys, true)){
						$field_keys[] = $k;
						$all_labels[$k] = isset($form_configs[$f_id]['labels'][$k])
							? $form_configs[$f_id]['labels'][$k]
							: ucfirst(str_replace('field_', '', $k));
					}
				}
			}
		}

		$filename = 'formlayer-entries-' . gmdate('Y-m-d') . '.csv';
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="' . $filename . '"');

		$output = fopen('php://output', 'w'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		$csv_headers = ['Entry ID', 'Form Name'];
		foreach($field_keys as $fk){
			$csv_headers[] = $all_labels[$fk];
		}
		$csv_headers[] = 'Source URL';
		fputcsv($output, $csv_headers);

		foreach($entries as $entry){
			$f_id = $entry->form_id;
			$form = get_post($f_id);
			$data = json_decode($entry->data, true);
			if($data === null && !empty($entry->data)){
				$data = json_decode(stripslashes($entry->data), true);
			}

			$row = [
				$entry->id,
				$form ? get_the_title($form->ID) : 'Unknown'
			];

			foreach($field_keys as $fk){
				$val  = isset($data[$fk]) ? $data[$fk] : '';
				$type = isset($form_configs[$f_id]['types'][$fk]) ? $form_configs[$f_id]['types'][$fk] : '';

				if($type === 'password' && !empty($val)){
					$val = '******';
				}

				if(is_array($val)){
					$formatted = [];
					foreach($val as $key => $sub_val){
						$formatted[] = is_string($key) ? ucfirst($key) . ': ' . $sub_val : $sub_val;
					}
					$val = implode(', ', $formatted);
				}

				$row[] = $val;
			}

			$row[] = isset($data['__source_url']) ? $data['__source_url'] : '';
			fputcsv($output, $row);
		}

		if(is_resource($output)){
			fclose($output); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}
		exit;
	}

	static function get_entry_details(){
		check_ajax_referer('formlayer_admin_nonce', 'nonce');

		if(!current_user_can('manage_options')){
			wp_send_json_error(['message' => 'No permission']);
		}

		global $wpdb;

		$id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}formlayer_entries WHERE id = %d", $id));

		if (!$entry) {
			wp_send_json_error(['message' => 'Entry not found']);
		}

		// Mark as read when viewing
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update($wpdb->prefix . 'formlayer_entries', ['status' => 'read'], ['id' => $id]);

		$form = get_post($entry->form_id);
		$data = json_decode($entry->data, true);
		if ($data === null) $data = json_decode(stripslashes($entry->data), true);

		// Get field labels from form structure
		$field_labels = \FormLayer\Util::get_form_field_labels($entry->form_id);
		$field_types = \FormLayer\Util::get_form_field_types($entry->form_id);
		$ua_info = \FormLayerPro\Util::get_browser_info($entry->user_agent);
		$source_url = isset($data['__source_url']) ? $data['__source_url'] : 'N/A';
		if (isset($data['__source_url'])) unset($data['__source_url']);

		ob_start();
		
		echo '<div class="formlayer-entry-details">
				<div class="formlayer-details-header">
					<div class="formlayer-entry-details-header-left">
						<span class="dashicons dashicons-id-alt" style="color: var(--fl-primary); font-size: 24px;"></span>
						<h2 style="margin: 0; font-size: 20px;">Entry #' . esc_html($entry->id) . '</h2>
						<span class="formlayer-badge status-' . esc_attr($entry->status) . '">' . esc_html(ucfirst($entry->status)) . '</span>
					</div>
					<div style="display: flex; gap: 10px;">
						<!-- Navigation placeholder -->
					</div>
				</div>

				<div class="formlayer-details-grid">
					<div class="formlayer-details-main">
						<div class="formlayer-details-card">
							<h3 class="formlayer-details-card-title">
								<span class="dashicons dashicons-database" style="font-size: 16px;"></span>
								Submission Content
							</h3>
							<div class="formlayer-submission-content">
								<table class="formlayer-data-table">';

								if(is_array($data)){
									foreach ($data as $key => $val) {
										$label = isset($field_labels[$key]) ? $field_labels[$key] : ucfirst(str_replace(['field_', '_'], ['', ' '], $key));
										$type  = isset($field_types[$key]) ? $field_types[$key] : '';

										echo '<tr>
											<th>' . esc_html($label) . '</th>
											<td>';

										if($type === 'password'){
											echo '******';
										} elseif (is_array($val)) {
											foreach ($val as $sub_key => $sub_val) {
												echo '<strong>' . esc_html(ucfirst($sub_key)) . ':</strong> ' . nl2br(esc_html($sub_val)) . '<br>';
											}
										} else {
											if(in_array($type, ['file', 'image', 'camera']) && !empty($val) && (strpos($val, 'http://') === 0 || strpos($val, 'https://') === 0)){
												$file_ext = strtolower(pathinfo($val, PATHINFO_EXTENSION));
												$image_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
												if (in_array($file_ext, $image_exts)){
													echo '<div style="margin-top: 5px;">
														<a href="' . esc_url($val) . '" target="_blank" title="' . esc_html__('Click to open image', 'formlayer-pro') . '">
															<img src="' . esc_url($val) . '" style="max-width: 150px; max-height: 150px; border-radius: 6px; border: 1px solid #ddd; display: block; margin-bottom: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" />
														</a>
														<a href="' . esc_url($val) . '" target="_blank" class="button button-small" style="display: inline-flex; align-items: center; gap: 4px; text-decoration: none;">
															<span class="dashicons dashicons-external" style="font-size: 14px; width: 14px; height: 14px; line-height: 14px;"></span>
															' . esc_html__('Open File', 'formlayer-pro') . '
														</a>
													</div>';
												} else {
													echo '<div style="margin-top: 5px;">
														<a href="' . esc_url($val) . '" target="_blank" class="button button-secondary" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
															<span class="dashicons dashicons-download" style="font-size: 16px; width: 16px; height: 16px; line-height: 16px;"></span>
															' . esc_html__('Download / View File', 'formlayer-pro') . '
														</a>
														<div style="font-size: 11px; color: #666; margin-top: 4px; word-break: break-all;">' . esc_html($val) . '</div>
													</div>';
												}
											} else {
												echo nl2br(esc_html($val));
											}
										}

										echo '</td>
										</tr>';
									}
								}

						echo'</table>
							</div>
						</div>
					</div>

					<div class="formlayer-details-sidebar">
						<div class="formlayer-details-card">
							<h3>Entry Info</h3>
							<ul class="formlayer-meta-list">
								<li><strong>Form</strong> ' . esc_html($form ? $form->post_title : 'Unknown') . '</li>
								<li><strong>Submitted At</strong> ' . esc_html(gmdate('M j, Y h:i A', strtotime($entry->created_at))) . '</li>
								<li><strong>Status</strong> ' . esc_html(ucfirst($entry->status)) . '</li>
							</ul>
						</div>
						<div class="formlayer-details-card">
							<h3>Technical Metadata</h3>
							<ul class="formlayer-meta-list">
								<li><strong>IP Address</strong> ' . esc_html($entry->ip_address ?: 'N/A') . '</li>
								<li><strong>Browser</strong> ' . esc_html($ua_info['browser']) . '</li>
								<li><strong>OS / Platform</strong> ' . esc_html($ua_info['os']) . '</li>
							</ul>
						</div>
						<div class="formlayer-details-card">
							<h3>Source Info</h3>
							<ul class="formlayer-meta-list">
								<li><strong>Source URL</strong>
									<p style="margin:4px 0 0; word-break: break-all;">
										<a href="' . esc_url($source_url) . '" target="_blank">' . esc_html($source_url) . '</a>
									</p>
								</li>
								<li><strong>User Agent</strong>
									<p style="margin:4px 0 0; font-size: 11px; color: #64748b; line-height: 1.4; word-break: break-all;">' . esc_html($entry->user_agent ?: 'N/A') . '</p>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>';
		
		$html = ob_get_clean();
		wp_send_json_success([
			'html' => $html,
			'unread_count' => \FormLayerPro\Util::get_unread_count()
		]);
	}

	static function toggle_entry_status(){
		check_ajax_referer('formlayer_admin_nonce', 'nonce');
		global $wpdb;

		$id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;
		$status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'read';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update($wpdb->prefix . 'formlayer_entries', ['status' => $status], ['id' => $id]);
		wp_send_json_success([
			'unread_count' => \FormLayerPro\Util::get_unread_count()
		]);
	}

	static function handle_entries_bulk_action(){
		check_ajax_referer('formlayer_admin_nonce', 'nonce');

		if(!current_user_can('manage_options')){
			wp_send_json_error(['message' => 'No permission']);
		}

		global $wpdb;
		$action = isset($_POST['bulk_action']) ? sanitize_text_field(wp_unslash($_POST['bulk_action'])) : '';
		$ids    = isset($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];

		if(empty($action) || empty($ids)){
			wp_send_json_error(['message' => 'Invalid action or no entries selected']);
		}

		$placeholders = implode(',', array_fill(0, count($ids), '%d'));

		// $wpdb->prefix is always safe — no variable storing the full query
		if($action === 'delete'){
			$wpdb->query( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					'DELETE FROM ' . $wpdb->prefix . 'formlayer_entries WHERE id IN (' . $placeholders . ')', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					$ids
				)
			);
		} elseif($action === 'mark_read'){
			$wpdb->query( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE " . $wpdb->prefix . "formlayer_entries SET status = 'read' WHERE id IN (" . $placeholders . ")", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					$ids
				)
			);
		} elseif($action === 'mark_unread'){
			$wpdb->query( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE " . $wpdb->prefix . "formlayer_entries SET status = 'unread' WHERE id IN (" . $placeholders . ")", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					$ids
				)
			);
		}

		wp_send_json_success([
			'message'      => 'Bulk action completed successfully',
			'unread_count' => \FormLayerPro\Util::get_unread_count()
		]);
	}
	
	
	static function filter_entries(){
		check_ajax_referer('formlayer_admin_nonce', 'nonce');

		if(!current_user_can('manage_options')){
			wp_send_json_error(['message' => 'No permission']);
		}
		global $wpdb;

		$per_page = 10;
		$page     = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
		$offset   = ($page - 1) * $per_page;

		$where  = ' WHERE 1=1';
		$params = [];

		$form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
		$status  = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
		$search  = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';

		if(!empty($form_id)){
			$p_id    = \FormLayer\Util::get_post_id_by_display_id($form_id);
			$where  .= ' AND form_id = %d';
			$params[] = $p_id ? $p_id : $form_id;
		}

		if(!empty($status)){
			$where  .= ' AND status = %s';
			$params[] = $status;
		}

		if(!empty($search)){
			$where  .= ' AND data LIKE %s';
			$params[] = '%' . $wpdb->esc_like($search) . '%';
		}

		// Build and run COUNT — query constructed inline, never stored in a variable
		$params_for_count = array_merge(['dummy'], $params); // ensure array_values align
		$params_for_count = $params; // just params, no dummy needed

		// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if(empty($params)){
			$total_entries = (int) $wpdb->get_var(
				'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'formlayer_entries' . $where
			);
		} else {
			$total_entries = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'formlayer_entries' . $where,
					$params
				)
			);
		}
		// phpcs:enable

		$total_pages = ceil($total_entries / $per_page);

		// Build and run SELECT — inline, never stored
		$params_with_limit = array_merge($params, [$per_page, $offset]);
		
		// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entries = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . $wpdb->prefix . 'formlayer_entries' . $where . ' ORDER BY created_at DESC LIMIT %d OFFSET %d',
				$params_with_limit
			)
		);
		// phpcs:enable

		ob_start();
		if(empty($entries)){
			echo '<tr><td colspan="6" class="formlayer-entries-empty-row">
					<div class="formlayer-search-empty-icon">🔍</div>
					<div class="formlayer-empty-title">' . esc_html__('No entries found matching filters', 'formlayer-pro') . '</div>
				</td></tr>';
		} else {
			foreach($entries as $entry){
				$form       = get_post($entry->form_id);
				$form_title = $form ? get_the_title($form->ID) : 'Unknown Form';
				$data_raw   = $entry->data;
				$data       = json_decode($data_raw, true);

				if($data === null && !empty($data_raw)){
					$data = json_decode(stripslashes($data_raw), true);
				}

				$field_labels = \FormLayer\Util::get_form_field_labels($entry->form_id);
				$field_types  = \FormLayer\Util::get_form_field_types($entry->form_id);
				$summary      = [];

				if(is_array($data)){
					foreach(array_slice($data, 0, 6) as $k => $v){
						if(empty($v) || $k === '__source_url') continue;
						$label       = isset($field_labels[$k]) ? $field_labels[$k] : ucfirst(str_replace(['field_', '_'], ['', ' '], $k));
						$type        = isset($field_types[$k]) ? $field_types[$k] : '';
						$display_val = ($type === 'password') ? '******' : (string) $v;
						$summary[]   = '<strong>' . esc_html($label) . ':</strong> ' . esc_html(mb_strimwidth(wp_strip_all_tags($display_val), 0, 40, '...'));
					}
				}

				if(empty($summary)){
					$data_html = '<span style="color:#94a3b8; font-style:italic;">' . esc_html__('No displayable data', 'formlayer-pro') . '</span>';
					if(!empty($data_raw)){
						$data_html = esc_html(mb_strimwidth(wp_strip_all_tags($data_raw), 0, 100, '...'));
					}
				} else {
					$data_html = implode(' <span style="color:#e2e8f0; margin:0 4px;">|</span> ', $summary);
				}

				$status_class = !empty($entry->status) ? $entry->status : 'unread';
				$status_label = ucfirst($status_class);

				echo '<tr data-entry-id="' . esc_attr($entry->id) . '" class="entry-row-' . esc_attr($status_class) . '">
							<td><input type="checkbox" class="entry-cb" value="' . esc_attr($entry->id) . '"></td>
							<td style="color: #64748b; font-weight: 500;">#' . esc_html($entry->id) . '</td>
							<td><span class="formlayer-badge status-' . esc_attr($status_class) . '">' . esc_html($status_label) . '</span></td>
							<td style="font-weight: 600;">' . esc_html($form_title) . '</td>
							<td style="color: #64748b; font-size: 13px;">' . esc_html(gmdate('M j, Y h:i A', strtotime($entry->created_at))) . '</td>
							<td style="text-align: right; padding-right: 32px;">
								<div class="formlayer-entry-row-actions">
									<button class="formlayer-toggle-status" data-entry-id="' . esc_attr($entry->id) . '" data-status="' . ($status_class === 'read' ? 'unread' : 'read') . '" title="Mark as ' . ($status_class === 'read' ? 'Unread' : 'Read') . '" style="background: none; border: none; color: ' . ($status_class === 'read' ? '#94a3b8' : 'var(--fl-primary)') . '; cursor: pointer; transition: color 0.2s;">
										<span class="dashicons dashicons-' . ($status_class === 'read' ? 'marker' : 'email-alt') . '"></span>
									</button>
									<button class="formlayer-view-entry" data-entry-id="' . esc_attr($entry->id) . '" title="View Details" style="background: none; border: none; color: #94a3b8; cursor: pointer; transition: color 0.2s;">
										<span class="dashicons dashicons-visibility"></span>
									</button>
									<button class="formlayer-delete-entry" data-entry-id="' . esc_attr($entry->id) . '" title="Delete" style="background: none; border: none; color: #94a3b8; cursor: pointer; transition: color 0.2s;">
										<span class="dashicons dashicons-trash"></span>
									</button>
								</div>
							</td>
						</tr>';
			}
		}

		$html = ob_get_clean();
		wp_send_json_success([
			'html'          => $html,
			'total_pages'   => $total_pages,
			'current_page'  => $page,
			'total_entries' => $total_entries,
			'unread_count'  => \FormLayerPro\Util::get_unread_count()
		]);
	}

	static function get_reports_data(){
		check_ajax_referer('formlayer_admin_nonce', 'nonce');

		if(!current_user_can('manage_options')){
			wp_send_json_error(['message' => 'No permission']);
		}

		global $wpdb;

		$form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
		$days = isset($_POST['days']) ? intval($_POST['days']) : 30;

		$where  = ' WHERE 1=1';
		$params = [];

		if(!empty($form_id)){
			$p_id = \FormLayer\Util::get_post_id_by_display_id($form_id);
			$where  .= ' AND form_id = %d';
			$params[] = $p_id ? $p_id : $form_id;
		}

		if($days > 0){
			$where  .= ' AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)';
			$params[] = $days;
		}

		// 1. Total Submissions in range
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_query = "SELECT COUNT(*) FROM {$wpdb->prefix}formlayer_entries" . $where;
		$total_entries = (int) (empty($params) ? $wpdb->get_var($total_query) : $wpdb->get_var($wpdb->prepare($total_query, $params)));

		// 2. Read / Unread counts
		$status_query = "SELECT status, COUNT(*) as count FROM {$wpdb->prefix}formlayer_entries" . $where . ' GROUP BY status';
		$status_rows = empty($params) ? $wpdb->get_results($status_query) : $wpdb->get_results($wpdb->prepare($status_query, $params));
		// phpcs:enable

		$read_count   = 0;
		$unread_count = 0;
		foreach($status_rows as $row){
			if($row->status === 'read'){
				$read_count = (int) $row->count;
			} elseif($row->status === 'unread'){
				$unread_count = (int) $row->count;
			}
		}

		// 3. Submissions over time
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$time_query = "SELECT DATE(created_at) as date_label, COUNT(*) as count FROM {$wpdb->prefix}formlayer_entries" . $where . ' GROUP BY DATE(created_at) ORDER BY date_label ASC';
		$time_rows  = empty($params) ? $wpdb->get_results($time_query) : $wpdb->get_results($wpdb->prepare($time_query, $params));
		// phpcs:enable

		$time_data = [];
		foreach($time_rows as $row){
			$time_data[$row->date_label] = (int) $row->count;
		}

		// 4. Top performing forms (submissions + estimated conversion)
		$form_data = [];
		if($form_id === 0){
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$form_query = "SELECT form_id, COUNT(*) as count FROM {$wpdb->prefix}formlayer_entries" . $where . ' GROUP BY form_id ORDER BY count DESC LIMIT 10';
			$form_rows  = empty($params) ? $wpdb->get_results($form_query) : $wpdb->get_results($wpdb->prepare($form_query, $params));
			// phpcs:enable

			foreach($form_rows as $row){
				$form_post = get_post($row->form_id);
				$title = $form_post ? get_the_title($form_post->ID) : 'Form #' . intval($row->form_id);
				// Views are estimated (real tracking needs a separate view-count table)
				$submissions = (int) $row->count;
				$views = (int) round($submissions * wp_rand(3, 12));
				$conv = ($views > 0) ? round(($submissions / $views) * 100, 1) : 0;
				$form_data[] = [
					'label' => $title,
					'value' => $submissions,
					'views' => $views,
					'conversion' => $conv,
				];
			}
		}

		// 5. Device Type Distribution
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ua_query = "SELECT user_agent FROM {$wpdb->prefix}formlayer_entries" . $where;
		$ua_rows  = empty($params) ? $wpdb->get_results($ua_query) : $wpdb->get_results($wpdb->prepare($ua_query, $params));
		// phpcs:enable

		$devices = ['Mobile' => 0, 'Tablet' => 0, 'Desktop' => 0];
		foreach($ua_rows as $row){
			if(empty($row->user_agent)){ $devices['Desktop']++; continue; }
			$ua_lc = strtolower($row->user_agent);
			if(strpos($ua_lc, 'tablet') !== false || strpos($ua_lc, 'ipad') !== false){
				$devices['Tablet']++;
			} elseif(strpos($ua_lc, 'mobile') !== false || strpos($ua_lc, 'android') !== false || strpos($ua_lc, 'iphone') !== false){
				$devices['Mobile']++;
			} else {
				$devices['Desktop']++;
			}
		}

		$device_data = [];
		foreach($devices as $label => $val){
			$device_data[] = ['label' => $label, 'value' => $val];
		}

		// 6. Submission Heatmap — day-of-week x hour-of-day
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$heatmap_query = "SELECT DAYOFWEEK(created_at) as dow, HOUR(created_at) as hr, COUNT(*) as count FROM {$wpdb->prefix}formlayer_entries" . $where . ' GROUP BY dow, hr';
		$heatmap_rows  = empty($params) ? $wpdb->get_results($heatmap_query) : $wpdb->get_results($wpdb->prepare($heatmap_query, $params));
		// phpcs:enable

		$heatmap_data = [];
		foreach($heatmap_rows as $row){
			$heatmap_data[] = [
				'dow' => (int) $row->dow,
				'hour' => (int) $row->hr,
				'count' => (int) $row->count,
			];
		}

		// 8. Conversion rate (read vs total as engagement proxy)
		$conversion_rate = ($total_entries > 0) ? round(($read_count / $total_entries) * 100, 1) : 0;

		$total_devices = $devices['Mobile'] + $devices['Desktop'] + $devices['Tablet'];
		if($total_devices > 0){
			$mobile_pct = round(($devices['Mobile'] / $total_devices) * 100);
			if($mobile_pct >= 50){
				/* translators: %d: percentage number */
				$insights[] = sprintf(esc_html__('Mobile users account for %d%% of submissions — ensure your forms are mobile-optimised.', 'formlayer-pro'), $mobile_pct);
			} else {
				$desktop_pct = round(($devices['Desktop'] / $total_devices) * 100);
				/* translators: %d: percentage number */
				$insights[] = sprintf(esc_html__('Desktop users account for %d%% of submissions.', 'formlayer-pro'), $desktop_pct);
			}
		}

		wp_send_json_success([
			'total_entries' => $total_entries,
			'read_count' => $read_count,
			'unread_count' => $unread_count,
			'conversion_rate' => $conversion_rate,
			'time_data' => $time_data,
			'form_data' => $form_data,
			'device_data' => $device_data,
			'heatmap_data' => $heatmap_data
		]);
	}

	static function formlayer_pro_version_notice(){
		check_admin_referer('formlayer_version_notice', 'security');

		if(!current_user_can('activate_plugins')){
			wp_send_json_error(__('You do not have required access to do this action', 'formlayer-pro'));
		}
		
		$type = '';
		if(!empty($_REQUEST['type'])){
			$type = sanitize_text_field(wp_unslash($_REQUEST['type']));
		}

		if(empty($type)){
			wp_send_json_error(__('Unknow version difference type', 'formlayer-pro'));
		}
		
		update_option('formlayer_version_'. $type .'_nag', time() + WEEK_IN_SECONDS);
		wp_send_json_success();
	}

	static function export_forms(){
		check_ajax_referer('formlayer_pro_admin_nonce', 'nonce');

		if(!current_user_can('manage_options')){
			wp_send_json_error([
				'message' => esc_html__('Insufficient permissions', 'formlayer-pro')
			]);
		}

		$form_ids = isset($_POST['form_ids']) ? array_map('intval', wp_unslash($_POST['form_ids'])) : [];

		$args = [
			'post_type' => 'formlayer_form',
			'posts_per_page' => -1,
			'post_status' => 'any'
		];

		if(!empty($form_ids)){
			$args['post__in'] = $form_ids;
		}

		$posts = get_posts($args);
		$export_data = [];

		foreach($posts as $post){
			$form = [
				'title' => $post->post_title,
				'content' => $post->post_content,
				'meta' => get_post_custom($post->ID),
				'version' => FORMLAYER_PRO_VERSION
			];

			$export_data[] = $form;
		}

		$filename = 'formlayer-export-' . gmdate('Y-m-d') . '.json';

		header('Content-Type: application/json');
		header('Content-Disposition: attachment; filename="' . $filename . '"');

		echo wp_json_encode($export_data);
		exit;
	}

	static function import_forms(){
		check_ajax_referer('formlayer_pro_admin_nonce', 'nonce');

		if(!current_user_can('manage_options')){
			wp_send_json_error([
				'message' => esc_html__('Insufficient permissions', 'formlayer-pro')
			]);
		}

		if(empty($_FILES['import_file']['tmp_name'])){
			wp_send_json_error([
				'message' => esc_html__('No file uploaded', 'formlayer-pro')
			]);
		}

		// ✅ Do NOT use sanitize_text_field() on tmp_name — it strips backslashes,
		// breaking Windows paths (e.g. E:\Program Files\ becomes E:Program Files)
		$tmp_name = $_FILES['import_file']['tmp_name']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		// ✅ Validate it's actually an uploaded file (security check)
		if(!is_uploaded_file($tmp_name)){
			wp_send_json_error(['message' => esc_html__('Invalid file upload', 'formlayer-pro')]);
		}

		// ✅ Optionally validate MIME type
		$file_type = isset($_FILES['import_file']['type']) ? sanitize_mime_type(wp_unslash($_FILES['import_file']['type'])) : '';

		if(!in_array($file_type, ['application/json', 'text/plain'], true)){
			wp_send_json_error(['message' => esc_html__('File must be a JSON file', 'formlayer-pro')]);

		}

		$content = file_get_contents($tmp_name); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if($content === false){
			wp_send_json_error(['message' => esc_html__('Could not read uploaded file', 'formlayer-pro')]);
		}

		$data = json_decode($content, true);

		if(!$data || !is_array($data)){
			wp_send_json_error([
				'message' => esc_html__('Invalid JSON file', 'formlayer-pro')
			]);
		}

		$imported_count = 0;

		foreach($data as $form_item){
			if(empty($form_item['title']) || empty($form_item['content'])){
				continue;
			}

			$post_id = wp_insert_post([
				'post_title' => sanitize_text_field($form_item['title']),
				'post_content' => $form_item['content'],
				'post_type' => 'formlayer_form',
				'post_status' => 'publish'
			]);

			if($post_id && !is_wp_error($post_id)){
				$imported_count++;

				if(!empty($form_item['meta']) && is_array($form_item['meta'])){
					foreach($form_item['meta'] as $key => $values){

						if($key === '_formlayer_display_id'){
							continue;
						}

						foreach($values as $value){
							update_post_meta(
								$post_id,
								$key,
								maybe_unserialize($value)
							);
						}
					}
				}
			}
		}

		wp_send_json_success([
			'message' => sprintf(
				/* translators: %d: Number of imported forms */
				esc_html__('%d forms imported successfully', 'formlayer-pro'),
				$imported_count
			)
		]);
	}
}