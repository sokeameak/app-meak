<?php

namespace FormLayerPro;

if(!defined('ABSPATH')){
	exit;
}

class Block{
	// Initialize Block hooks
	static function init(){
		add_action('init', '\FormLayerPro\Block::register_block', 20);
		add_action('enqueue_block_editor_assets', '\FormLayerPro\Block::enqueue_editor_assets');
	}

	//Register the Gutenberg block
	static function register_block(){
		register_post_type('formlayer_form', [
			'labels' => [
				'name' => __('FormLayer Forms', 'formlayer-pro'),
				'singular_name' => __('FormLayer Form', 'formlayer-pro'),
			],
			'public' => false,
			'show_ui' => false,
			'show_in_rest' => true,
			'supports' => ['title', 'editor'],
		]);

		register_block_type('formlayer/form-selector', [
			'attributes' => [
				'formId' => [
					'type' => 'string',
					'default' => '',
				],
				'width' => [
					'type' => 'string',
					'default' => 'none',
				],
				'alignment' => [
					'type' => 'string',
					'default' => 'left',
				],
			],
			'render_callback' => '\FormLayerPro\Block::render_callback',
		]);
	}
	// Dynamic rendering callback for the block on frontend
	static function render_callback($attributes) {
		if(empty($attributes['formId'])){
			return '';
		}

		$form_id = intval($attributes['formId']);

		// Explicitly enqueue form frontend assets for flawless frontend interactive behaviors
		wp_enqueue_style('formlayer-frontend');
		wp_enqueue_script('formlayer-frontend');

		// Get form HTML
		$form_html = \FormLayer\Frontend::render_shortcode(['id' => $form_id]);

		// Extract display settings
		$width = isset($attributes['width']) ? sanitize_text_field($attributes['width']) : 'none';
		$align = isset($attributes['alignment']) ? sanitize_text_field($attributes['alignment']) : 'left';

		// Apply inline styles based on width & alignment options
		$style = '';
		if($width !== 'none' && !empty($width)){
			$style .= 'width: ' . esc_attr($width) . '; max-width: 100%; ';
		}

		if($align === 'center'){
			$style .= 'margin-left: auto; margin-right: auto; ';
		} elseif ($align === 'right'){
			$style .= 'margin-left: auto; margin-right: 0; ';
		} else {
			$style .= 'margin-left: 0; margin-right: auto; ';
		}

		// Return fully styled wrapper containing the sanitized/escaped form HTML
		return '<div class="formlayer-block-container-wrap" style="' . esc_attr($style) . '">' . $form_html . '</div>';
	}

	//Enqueue and localize block
	static function enqueue_editor_assets(){
		wp_enqueue_style('formlayer-frontend', FORMLAYER_PLUGIN_URL . 'assets/css/frontend.css', [], FORMLAYER_VERSION);
		wp_enqueue_style('formlayer-block-editor-css', FORMLAYER_PRO_PLUGIN_URL . 'assets/css/block-editor.css', [], FORMLAYER_PRO_VERSION);
		wp_enqueue_script('formlayer-block-editor-js', FORMLAYER_PRO_PLUGIN_URL . 'assets/js/block.js', ['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render'], FORMLAYER_PRO_VERSION, true);

		// Query all published FormLayer forms to show in editor SelectControl dropdown
		$forms = get_posts([
			'post_type' => 'formlayer_form',
			'posts_per_page' => -1,
			'post_status' => 'publish',
		]);

		$options = [];
		if(!empty($forms) && is_array($forms)){
			foreach($forms as $form){
				$display_id = get_post_meta($form->ID, '_formlayer_display_id', true);
				if(!$display_id){
					$display_id = $form->ID;
				}

				$options[] = [
					'value' => (string) $display_id,
					'label' => get_the_title($form->ID) ? get_the_title($form->ID) : '#' . $display_id . ' (Untitled)',
				];
			}
		}

		// Inject forms data into Gutenberg block editor script
		wp_localize_script('formlayer-block-editor-js', 'formlayer_block_data', $options);
	}
}