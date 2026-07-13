(function(blocks, element, components, blockEditor, serverSideRender) {
	let el = element.createElement;
	let registerBlockType = blocks.registerBlockType;
	let SelectControl = components.SelectControl;
	let InspectorControls = blockEditor.InspectorControls;
	let BlockControls = blockEditor.BlockControls;
	let PanelBody = components.PanelBody;
	let ToolbarGroup = components.ToolbarGroup || components.Toolbar;
	let ToolbarButton = components.ToolbarButton;
	let ServerSideRender = serverSideRender || window.wp.serverSideRender;

	registerBlockType('formlayer/form-selector', {
		title: 'FormLayer Form',
		description: 'Embed an interactive FormLayer form inside your page with live preview.',
		icon: 'feedback',
		category: 'widgets',

		attributes: {
			formId: {
				type: 'string',
				default: ''
			},
			alignment: {
				type: 'string',
				default: 'left'
			}
		},

		edit: function(props){
			let attributes = props.attributes;
			let setAttributes = props.setAttributes;

			// Get localized forms list
			let forms = window.formlayer_block_data || [];

			function onChangeForm(newFormId) {
			setAttributes({ formId: newFormId });
			}

			// Fallback if no forms exist
			if(forms.length === 0){
				return el(
					'div',
					{ className: 'formlayer-block-editor-placeholder' },
					el('p', {}, 'No FormLayer forms found. Please create a form first.')
				);
			}

            // Display settings panel
			let displaySettingsPanel = el(
			PanelBody,
				{ title: 'Form Display Settings', initialOpen: true },
				el(SelectControl, {
					label: 'Alignment',
					value: attributes.alignment || 'left',
					options: [
						{ label: 'Left', value: 'left' },
						{ label: 'Center', value: 'center' },
						{ label: 'Right', value: 'right' }
					],
					onChange: function(newAlign) {
						setAttributes({ alignment: newAlign });
					}
				})
			);

            // If no form selected
			if(!attributes.formId){
				return [
                    // Sidebar settings
					el(InspectorControls, { key: 'inspector' },
						el(PanelBody, {
							title: 'Form Selection',
							initialOpen: true
						},
							el(SelectControl, {
								label: 'Select Form',
								value: attributes.formId,
								options: forms,
								onChange: onChangeForm
							})
						),
						displaySettingsPanel
					),

					// Form selector UI
					el('div', {
						key: 'preview',
						className: 'formlayer-block-editor-placeholder'
					},	
						el('div', {
							className: 'formlayer-block-placeholder-header'
						},
							el('span', {
								className: 'dashicons dashicons-feedback'
							}),
							el('h4', {}, 'FormLayer Form')
						),

						el('p', {
							className: 'formlayer-block-placeholder-description'
						}, 'Choose a saved form to display from the list below:'),

						el('div', {
							className: 'formlayer-block-buttons-container'
						},
							forms.map(function(form) {
								return el('button', {
									key: form.value,
									className: 'formlayer-block-selection-button',
									onClick: function() {
										onChangeForm(form.value);
									}
								}, form.label);
							})
						)
					)
				];
			}

			// Default preview style (none width behavior)
			let livePreviewStyle = {
				maxWidth: '100%',
				boxSizing: 'border-box'
			};

			// Alignment handling only for preview
			if (attributes.alignment === 'center') {
				livePreviewStyle.marginLeft = 'auto';
				livePreviewStyle.marginRight = 'auto';
			} else if (attributes.alignment === 'right') {
				livePreviewStyle.marginLeft = 'auto';
				livePreviewStyle.marginRight = '0';
			} else {
				livePreviewStyle.marginLeft = '0';
				livePreviewStyle.marginRight = 'auto';
			}

			return [
				// Sidebar settings
				el(InspectorControls, { key: 'inspector' },
					el(PanelBody, {
						title: 'Form Selection',
						initialOpen: true
					},
						el(SelectControl, {
							label: 'Select Form',
							value: attributes.formId,
							options: forms,
							onChange: onChangeForm
						})
					),
					displaySettingsPanel
				),

        // Toolbar
				el(BlockControls, { key: 'controls' },
					el(ToolbarGroup, {},
						el(ToolbarButton, {
							icon: 'edit',
							label: 'Change Form',
                            onClick: function() {
								setAttributes({ formId: '' });
							}
						})
					)
				),

        // Live preview
				el('div', {
					key: 'preview',
					className: 'formlayer-block-editor-preview-live',
					style: livePreviewStyle
				},
					el(ServerSideRender, {
						block: 'formlayer/form-selector',
						attributes: attributes
					})
				)
			];
		},

		save: function(){
			return null;
		}
	});

})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor || window.wp.editor, window.wp.serverSideRender);