jQuery(document).ready(function($){
	/* Reports Dashboard Charts & Logic */
	let trend_chart = null,
	form_chart = null,
	device_chart = null,
	_reports_raw_data = null,
	_reports_trend_mode = 'daily';

	function load_reports_data() {
		let form_id = $('#formlayer-reports-filter-form').val();
		let days = $('#formlayer-reports-filter-range').val();

		let $container = $('.formlayer-reports-view');
		if (!$container.length) return;

		$.post(formlayer_admin.ajax_url, {
			action: 'formlayer_get_reports_data',
			nonce: formlayer_admin.nonce,
			form_id: form_id,
			days: days
		}, function(response) {
			if (response.success) {
				let data = response.data;
				_reports_raw_data = data;

				animate_number_counter('#reports-stat-total', data.total_entries);
				animate_number_counter('#reports-stat-read', data.read_count);
				animate_number_counter('#reports-stat-unread', data.unread_count);
				$('#reports-stat-conversion').text(data.conversion_rate + '%');

				render_trend_chart(data.time_data, parseInt(days), _reports_trend_mode);

				if (form_id == 0) {
					$('#reports-chart-form-card').show();
					render_form_bar_chart(data.form_data);
				} else {
					$('#reports-chart-form-card').hide();
				}

				render_device_chart(data.device_data);
				render_heatmap(data.heatmap_data);
				render_country_list(data.country_data);
				render_form_table(data.form_data);
			} else {
				show_toast('Error loading reports data', 'error');
			}
		});
	}

	function animate_number_counter(selector, target) {
		let $el = $(selector);
		if (!$el.length) return;
		let current = parseInt($el.text()) || 0;
		if (current === target) return;
		
		$({ countNum: current }).animate({ countNum: target }, {
			duration: 600,
			easing: 'swing',
			step: function() {
				$el.text(Math.floor(this.countNum));
			},
			complete: function() {
				$el.text(this.countNum);
			}
		});
	}

	function render_trend_chart(time_data, days, mode) {
		let canvas = document.getElementById('reports-chart-trend');
		if (!canvas) return;
		if (trend_chart) { trend_chart.destroy(); }

		mode = mode || 'daily';
		let labels = [];
		let dataValues = [];

		if (mode === 'daily') {
			if (days > 0) {
				for (let i = days - 1; i >= 0; i--) {
					let d = new Date();
					d.setDate(d.getDate() - i);
					let dateStr = d.toISOString().split('T')[0];
					labels.push(d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
					dataValues.push(time_data[dateStr] || 0);
				}
			} else {
				Object.keys(time_data).sort().forEach(dateStr => {
					let d = new Date(dateStr);
					labels.push(d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
					dataValues.push(time_data[dateStr]);
				});
			}
		} else if (mode === 'weekly') {
			let weekly = {};
			Object.keys(time_data).sort().forEach(dateStr => {
				let d = new Date(dateStr);
				let yr = d.getFullYear();
				let wk = Math.ceil(((d - new Date(yr, 0, 1)) / 86400000 + 1) / 7);
				let key = yr + '-W' + String(wk).padStart(2, '0');
				weekly[key] = (weekly[key] || 0) + (time_data[dateStr] || 0);
			});
			Object.keys(weekly).sort().forEach(k => { labels.push(k); dataValues.push(weekly[k]); });
		} else if (mode === 'monthly') {
			let monthly = {};
			Object.keys(time_data).sort().forEach(dateStr => {
				let key = dateStr.substring(0, 7);
				monthly[key] = (monthly[key] || 0) + (time_data[dateStr] || 0);
			});
			Object.keys(monthly).sort().forEach(k => {
				let [yr, mo] = k.split('-');
				let d = new Date(yr, parseInt(mo) - 1, 1);
				labels.push(d.toLocaleDateString('en-US', { month: 'short', year: 'numeric' }));
				dataValues.push(monthly[k]);
			});
		}

		let ctx = canvas.getContext('2d');
		let gradient = ctx.createLinearGradient(0, 0, 0, 260);
		gradient.addColorStop(0, 'rgba(85, 37, 214, 0.25)');
		gradient.addColorStop(1, 'rgba(85, 37, 214, 0.0)');

		trend_chart = new Chart(ctx, {
			type: 'line',
			data: {
				labels: labels,
				datasets: [{
					label: 'Submissions',
					data: dataValues,
					borderColor: '#5525d6',
					borderWidth: 3,
					backgroundColor: gradient,
					fill: true,
					tension: 0.4,
					pointBackgroundColor: '#5525d6',
					pointHoverRadius: 7,
					pointHoverBackgroundColor: '#ffffff',
					pointHoverBorderColor: '#5525d6',
					pointHoverBorderWidth: 3
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					tooltip: {
						backgroundColor: '#1e293b',
						padding: 12,
						titleFont: { size: 13, weight: '600' },
						bodyFont: { size: 13 },
						cornerRadius: 8,
						displayColors: false
					}
				},
				scales: {
					x: {
						grid: { display: false },
						ticks: { 
							color: '#64748b', 
							font: { size: 11 },
							autoSkip: true,
							maxTicksLimit: 10,
							maxRotation: 0,
							minRotation: 0
						}
					},
					y: {
						grid: { color: '#f1f5f9', borderDash: [5, 5] },
						ticks: { 
							color: '#64748b', 
							font: { size: 11 },
							precision: 0
						},
						beginAtZero: true
					}
				}
			}
		});
	}

	function render_form_bar_chart(form_data) {
		let canvas = document.getElementById('reports-chart-form');
		if (!canvas) return;
		if (form_chart) { form_chart.destroy(); }
		if (!form_data || form_data.length === 0) return;

		let labels = form_data.map(d => d.label);
		let values = form_data.map(d => d.value);
		let ctx = canvas.getContext('2d');
		form_chart = new Chart(ctx, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: [{
					label: 'Submissions',
					data: values,
					backgroundColor: '#5525d6',
					borderRadius: 6,
					maxBarThickness: 30
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					tooltip: { backgroundColor: '#1e293b', padding: 12, cornerRadius: 8 }
				},
				scales: {
					x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 11 } } },
					y: { grid: { color: '#f1f5f9' }, ticks: { color: '#64748b', precision: 0 }, beginAtZero: true }
				}
			}
		});
	}

	function render_device_chart(device_data) {
		let canvas = document.getElementById('reports-chart-device');
		if (!canvas) return;
		if (device_chart) { device_chart.destroy(); }

		let labels = device_data.length ? device_data.map(d => d.label) : ['No Data'];
		let values = device_data.length ? device_data.map(d => d.value) : [1];
		let colors = ['#5525d6', '#10b981', '#f59e0b'];
		let ctx = canvas.getContext('2d');
		device_chart = new Chart(ctx, {
			type: 'doughnut',
			data: {
				labels: labels,
				datasets: [{ data: values, backgroundColor: colors.slice(0, labels.length), borderWidth: 3, borderColor: '#fff' }]
			},
			options: {
				responsive: true, maintainAspectRatio: false, cutout: '68%',
				plugins: {
					legend: { display: false },
					tooltip: { backgroundColor: '#1e293b', padding: 12, cornerRadius: 8 }
				}
			}
		});

		let total = values.reduce((a, b) => a + b, 0);
		let $legend = $('#reports-device-legend').empty();
		labels.forEach((lbl, i) => {
			let pct = total > 0 ? Math.round((values[i] / total) * 100) : 0;
			$legend.append(`<div class="formlayer-device-legend-item"><span class="formlayer-device-dot" style="background:${colors[i] || '#94a3b8'}"></span><span>${lbl}</span><strong>${pct}%</strong></div>`);
		});
	}

	function render_heatmap(heatmap_data) {
		let $wrap = $('#reports-heatmap-container').empty();
		if (!$wrap.length) return;

		let days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
		hours = [0,3,6,9,12,15,18,21],
		grid = {},
		maxVal = 0;

		(heatmap_data || []).forEach(h => {
			let dow = h.dow - 1; // convert 1-7 to 0-6
			let hr = Math.floor(h.hour / 3) * 3;
			let key = dow + '_' + hr;
			grid[key] = (grid[key] || 0) + h.count;
			if (grid[key] > maxVal) maxVal = grid[key];
		});

		let html = '<div class="formlayer-heatmap-grid">';
		html += '<div class="formlayer-heatmap-row formlayer-heatmap-header"><div class="formlayer-heatmap-day-label"></div>';
		hours.forEach(h => { html += `<div class="formlayer-heatmap-hour-label">${h}:00</div>`; });
		html += '</div>';

		days.forEach((day, di) => {
			html += `<div class="formlayer-heatmap-row"><div class="formlayer-heatmap-day-label">${day}</div>`;
			hours.forEach(h => {
				let val = grid[di + '_' + h] || 0;
				let intensity = maxVal > 0 ? val / maxVal : 0;
				let alpha = (0.08 + intensity * 0.92).toFixed(2);
				let bg = val > 0 ? `rgba(85,37,214,${alpha})` : '#f1f5f9';
				let color = intensity > 0.5 ? '#fff' : '#475569';
				html += `<div class="formlayer-heatmap-cell" style="background:${bg};color:${color}" title="${day} ${h}:00 — ${val} submissions">${val > 0 ? val : ''}</div>`;
			});
			html += '</div>';
		});
		html += '</div>';
		$wrap.html(html);
	}

	function render_country_list(country_data) {
		let $wrap = $('#reports-country-list').empty();
		if (!$wrap.length) return;

		if (!country_data || country_data.length === 0) {
			$wrap.html('<p style="color:#94a3b8;font-size:13px;">No country data available.</p>');
			return;
		}

		let max = country_data[0].value;
		let html = '';
		country_data.forEach(c => {
			let pct = max > 0 ? Math.round((c.value / max) * 100) : 0;
			html += `<div class="formlayer-country-row">
				<span class="formlayer-country-name">${c.label}</span>
				<div class="formlayer-country-bar-wrap"><div class="formlayer-country-bar" style="width:${pct}%"></div></div>
				<span class="formlayer-country-count">${c.value}</span>
			</div>`;
		});
		$wrap.html(html);
	}

	function render_form_table(form_data) {
		let $tbody = $('#reports-form-table-body').empty();
		if (!$tbody.length) return;

		if (!form_data || form_data.length === 0) {
			$tbody.html('<tr><td colspan="5" style="text-align:center;padding:30px;color:#94a3b8;">No form data available.</td></tr>');
			return;
		}

		form_data.forEach(f => {
			let conv = f.conversion || 0;
			let barColor = conv >= 15 ? '#10b981' : conv >= 8 ? '#f59e0b' : '#5525d6';
			$tbody.append(`<tr>
				<td style="font-weight:600;">${f.label}</td>
				<td>${f.views || 0}</td>
				<td>${f.value}</td>
				<td><strong style="color:${barColor}">${conv}%</strong></td>
				<td><div class="formlayer-conv-bar-wrap"><div class="formlayer-conv-bar" style="width:${Math.min(conv,100)}%;background:${barColor}"></div></div></td>
			</tr>`);
		});
	}

	function handle_reports_tab_activation() {
		let hash = window.location.hash.trim().replace('#', '').split('/')[0];
		if (hash === 'reports') {
			load_reports_data();
		}
	}

	$(window).on('hashchange', handle_reports_tab_activation);
	setTimeout(handle_reports_tab_activation, 150);

	$('#formlayer-reports-filter-form, #formlayer-reports-filter-range').on('change', function(){
		load_reports_data();
	});

	$('.formlayer-trend-tab').on('click', function(){
		$('.formlayer-trend-tab').removeClass('active');
		$(this).addClass('active');
		_reports_trend_mode = $(this).data('mode');
		if (_reports_raw_data) {
			let days = parseInt($('#formlayer-reports-filter-range').val());
			render_trend_chart(_reports_raw_data.time_data, days, _reports_trend_mode);
		}
	});
});