/**
 * Infinity RCB Pro — JavaScript d'administration
 * Onglets, compteurs animés, graphiques canvas (courbe + anneau),
 * mise à jour temps réel, filtres des journaux et aperçu du message.
 *
 * Aucune dépendance : JavaScript natif uniquement.
 */
(function () {
	'use strict';

	var cfg = window.RCBAdmin || {};
	var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
	var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };

	/* ==================================================================
	 * Onglets des réglages
	 * ================================================================== */
	function initTabs() {
		var tabs = $$('.rcb-tab');
		if (!tabs.length) { return; }

		tabs.forEach(function (tab) {
			tab.addEventListener('click', function (e) {
				e.preventDefault();
				var key = tab.getAttribute('data-tab');
				activate(key);
			});
		});

		function activate(key) {
			tabs.forEach(function (t) {
				t.classList.toggle('is-active', t.getAttribute('data-tab') === key);
			});
			$$('.rcb-tab-panel').forEach(function (panel) {
				panel.hidden = panel.id !== 'rcb-tab-' + key;
			});
			var active = $('#rcb-active-tab');
			if (active) { active.value = key; }
		}

		// Onglet initial éventuellement présent dans l'URL (#rcb-tab-xxx).
		var hash = window.location.hash.replace('#rcb-tab-', '');
		if (hash && $('.rcb-tab[data-tab="' + hash + '"]')) {
			activate(hash);
		}
	}

	/* ==================================================================
	 * Compteurs animés
	 * ================================================================== */
	function animateCounters(scope) {
		$$('[data-count]', scope).forEach(function (el) {
			if (el.getAttribute('data-counted')) { return; }
			var target = parseFloat(el.getAttribute('data-count')) || 0;
			var isFloat = String(target).indexOf('.') !== -1;
			var start = null;
			var duration = 900;

			el.setAttribute('data-counted', '1');

			function step(ts) {
				if (!start) { start = ts; }
				var p = Math.min(1, (ts - start) / duration);
				var eased = 1 - Math.pow(1 - p, 3);
				var value = target * eased;
				el.textContent = isFloat
					? String(Math.round(value * 10) / 10).replace('.', ',')
					: formatInt(Math.round(value));
				if (p < 1) { window.requestAnimationFrame(step); }
			}
			window.requestAnimationFrame(step);
		});
	}

	function formatInt(n) {
		return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ').replace(/ /g, '\u202f');
	}

	/* ==================================================================
	 * Graphique en courbe (canvas, sans dépendance)
	 * ================================================================== */
	function LineChart(canvas, tipEl, data) {
		this.cv = canvas;
		this.tip = tipEl;
		this.data = data || [];
		this.hover = -1;
		this.draw();
		this.bindEvents();
	}

	LineChart.prototype.points = function () {
		var cv = this.cv;
		var w = cv.clientWidth, h = cv.clientHeight;
		var padL = 38, padR = 14, padT = 16, padB = 26;
		var max = 4;
		this.data.forEach(function (d) { max = Math.max(max, d.total); });
		var step = Math.pow(10, Math.floor(Math.log(max) / Math.LN10));
		max = Math.ceil(max / step) * step;

		var innerW = w - padL - padR;
		var innerH = h - padT - padB;
		var n = Math.max(1, this.data.length);

		return this.data.map(function (d, i) {
			return {
				x: padL + (n === 1 ? innerW / 2 : innerW * (i / (n - 1))),
				y: padT + innerH * (1 - (d.total / max)),
				d: d
			};
		}).concat([{ meta: true, padL: padL, padR: padR, padT: padT, padB: padB, w: w, h: h, max: max }]);
	};

	LineChart.prototype.draw = function () {
		var cv = this.cv;
		if (!cv || !cv.clientWidth) { return; }
		var dpr = window.devicePixelRatio || 1;
		cv.width = cv.clientWidth * dpr;
		cv.height = cv.clientHeight * dpr;

		var ctx = cv.getContext('2d');
		ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
		ctx.clearRect(0, 0, cv.clientWidth, cv.clientHeight);

		var pts = this.points();
		var meta = pts[pts.length - 1];
		var w = meta.w, h = meta.h;
		var labels = this.data.map(function (d) { return d.label; });
		var max = meta.max;
		var i;

		// Grille horizontale + libellés de l'axe Y.
		ctx.font = '11px system-ui, sans-serif';
		ctx.fillStyle = '#94a3b8';
		ctx.strokeStyle = '#eef2f7';
		ctx.lineWidth = 1;
		for (i = 0; i <= 4; i++) {
			var y = meta.padT + (h - meta.padT - meta.padB) * (i / 4);
			ctx.beginPath();
			ctx.moveTo(meta.padL, y);
			ctx.lineTo(w - meta.padR, y);
			ctx.stroke();
			var val = Math.round(max * (1 - i / 4));
			ctx.textAlign = 'right';
			ctx.textBaseline = 'middle';
			ctx.fillText(formatInt(val), meta.padL - 8, y);
		}

		// Libellés de l'axe X (un sur deux si beaucoup de points).
		ctx.textAlign = 'center';
		ctx.textBaseline = 'top';
		var every = Math.ceil(this.data.length / 8);
		pts.forEach(function (p, idx) {
			if (p.meta) { return; }
			if (idx % every !== 0 && idx !== this.data.length - 1) { return; }
			ctx.fillStyle = '#94a3b8';
			ctx.fillText(labels[idx], p.x, h - meta.padB + 8);
		}, this);

		if (!this.data.length || this.data.every(function (d) { return d.total === 0; })) {
			ctx.fillStyle = '#94a3b8';
			ctx.font = '600 13px system-ui, sans-serif';
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			ctx.fillText('Aucune activité sur la période', w / 2, h / 2);
			return;
		}

		var real = pts.slice(0, -1);

		// Aire dégradée sous la courbe.
		var grad = ctx.createLinearGradient(0, meta.padT, 0, h - meta.padB);
		grad.addColorStop(0, 'rgba(46, 123, 246, .28)');
		grad.addColorStop(1, 'rgba(46, 123, 246, 0)');
		ctx.beginPath();
		ctx.moveTo(real[0].x, h - meta.padB);
		real.forEach(function (p) { ctx.lineTo(p.x, p.y); });
		ctx.lineTo(real[real.length - 1].x, h - meta.padB);
		ctx.closePath();
		ctx.fillStyle = grad;
		ctx.fill();

		// Courbe lissée (points milieux + quadratiques).
		ctx.beginPath();
		ctx.moveTo(real[0].x, real[0].y);
		for (i = 1; i < real.length - 1; i++) {
			var mx = (real[i].x + real[i + 1].x) / 2;
			var my = (real[i].y + real[i + 1].y) / 2;
			ctx.quadraticCurveTo(real[i].x, real[i].y, mx, my);
		}
		ctx.lineTo(real[real.length - 1].x, real[real.length - 1].y);
		ctx.strokeStyle = '#2E7BF6';
		ctx.lineWidth = 2.4;
		ctx.lineJoin = 'round';
		ctx.lineCap = 'round';
		ctx.stroke();

		// Points.
		real.forEach(function (p, idx) {
			ctx.beginPath();
			ctx.arc(p.x, p.y, idx === this.hover ? 5.5 : 3, 0, Math.PI * 2);
			ctx.fillStyle = '#fff';
			ctx.fill();
			ctx.strokeStyle = '#2E7BF6';
			ctx.lineWidth = 2;
			ctx.stroke();
		}, this);
	};

	LineChart.prototype.bindEvents = function () {
		var self = this;
		this.cv.addEventListener('mousemove', function (e) {
			var rect = self.cv.getBoundingClientRect();
			var x = e.clientX - rect.left;
			var pts = self.points().slice(0, -1);
			var best = -1, bestDist = Infinity;
			pts.forEach(function (p, i) {
				var d = Math.abs(p.x - x);
				if (d < bestDist) { bestDist = d; best = i; }
			});
			if (best !== self.hover) {
				self.hover = best;
				self.draw();
			}
			if (self.tip && pts[best]) {
				var d = pts[best].d;
				var html = '<strong>' + d.label + '</strong> — ' + formatInt(d.total) + ' tentative' + (d.total > 1 ? 's' : '');
				var detail = d.detail || {};
				var keys = Object.keys(detail);
				if (keys.length && d.total > 0) {
					keys.sort(function (a, b) { return detail[b] - detail[a]; });
					html += '<br>' + keys.slice(0, 3).map(function (k) {
						var meta = (cfg.types || {})[k] || { emoji: '', label: k };
						return meta.emoji + ' ' + meta.label + ' : ' + detail[k];
					}).join('<br>');
				}
				self.tip.innerHTML = html;
				self.tip.hidden = false;
				var tx = Math.min(pts[best].x + 12, rect.width - self.tip.offsetWidth - 8);
				var ty = Math.max(8, pts[best].y - self.tip.offsetHeight - 12);
				self.tip.style.left = tx + 'px';
				self.tip.style.top = ty + 'px';
			}
		});
		this.cv.addEventListener('mouseleave', function () {
			self.hover = -1;
			if (self.tip) { self.tip.hidden = true; }
			self.draw();
		});
	};

	/* ==================================================================
	 * Graphique en anneau (canvas, sans dépendance)
	 * ================================================================== */
	function DonutChart(canvas, legendEl, entries) {
		this.cv = canvas;
		this.legend = legendEl;
		this.entries = entries || [];
		this.active = -1;
		this.draw();
		this.buildLegend();
		this.bindEvents();
	}

	DonutChart.prototype.total = function () {
		return this.entries.reduce(function (sum, e) { return sum + e.value; }, 0);
	};

	DonutChart.prototype.draw = function () {
		var cv = this.cv;
		if (!cv || !cv.clientWidth) { return; }
		var dpr = window.devicePixelRatio || 1;
		var size = cv.clientWidth;
		cv.width = size * dpr;
		cv.height = size * dpr;

		var ctx = cv.getContext('2d');
		ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
		ctx.clearRect(0, 0, size, size);

		var total = this.total();
		var cx = size / 2, cy = size / 2;
		var rOut = size / 2 - 8;
		var rIn = rOut - 22;

		if (!total) {
			ctx.strokeStyle = '#e2e8f0';
			ctx.lineWidth = rOut - rIn;
			ctx.beginPath();
			ctx.arc(cx, cy, (rOut + rIn) / 2, 0, Math.PI * 2);
			ctx.stroke();
			this.centerText('0');
			return;
		}

		var angle = -Math.PI / 2;
		this.entries.forEach(function (e, i) {
			var sweep = (e.value / total) * Math.PI * 2;
			ctx.beginPath();
			ctx.arc(cx, cy, i === this.active ? rOut + 3 : rOut, angle, angle + sweep);
			ctx.arc(cx, cy, i === this.active ? rIn + 3 : rIn, angle + sweep, angle, true);
			ctx.closePath();
			ctx.fillStyle = e.color;
			ctx.fill();
			angle += sweep;
		}, this);

		this.centerText(formatInt(total));
	};

	DonutChart.prototype.centerText = function (text) {
		var ctx = this.cv.getContext('2d');
		var size = this.cv.clientWidth;
		ctx.fillStyle = '#1e293b';
		ctx.font = '800 22px system-ui, sans-serif';
		ctx.textAlign = 'center';
		ctx.textBaseline = 'middle';
		ctx.fillText(text, size / 2, size / 2 - 6);
		ctx.fillStyle = '#94a3b8';
		ctx.font = '600 10.5px system-ui, sans-serif';
		ctx.fillText('TENTATIVES', size / 2, size / 2 + 14);
	};

	DonutChart.prototype.buildLegend = function () {
		if (!this.legend) { return; }
		var total = this.total();
		this.legend.innerHTML = this.entries.length
			? this.entries.map(function (e) {
				var pct = total ? Math.round(e.value / total * 1000) / 10 : 0;
				return '<div class="rcb-legend-item"><i style="background:' + e.color + '"></i>' +
					'<span>' + (e.emoji ? e.emoji + ' ' : '') + e.label + '</span>' +
					'<b>' + formatInt(e.value) + ' (' + String(pct).replace('.', ',') + ' %)</b></div>';
			}).join('')
			: '<div class="rcb-legend-item"><i style="background:#cbd5e1"></i><span>Aucune donnée</span><b>0</b></div>';
	};

	DonutChart.prototype.bindEvents = function () {
		var self = this;
		this.cv.addEventListener('mousemove', function (e) {
			var rect = self.cv.getBoundingClientRect();
			var size = rect.width;
			var x = e.clientX - rect.left - size / 2;
			var y = e.clientY - rect.top - size / 2;
			var dist = Math.sqrt(x * x + y * y);
			var rOut = size / 2 - 8, rIn = rOut - 22;
			var active = -1;

			if (dist >= rIn && dist <= rOut) {
				var angle = Math.atan2(y, x) + Math.PI / 2;
				if (angle < 0) { angle += Math.PI * 2; }
				var total = self.total();
				var acc = 0;
				self.entries.forEach(function (entry, i) {
					acc += (entry.value / total) * Math.PI * 2;
					if (angle <= acc && active === -1) { active = i; }
				});
			}
			if (active !== self.active) {
				self.active = active;
				self.draw();
				self.cv.style.cursor = active >= 0 ? 'pointer' : 'default';
			}
		});
		this.cv.addEventListener('mouseleave', function () {
			self.active = -1;
			self.draw();
		});
	};

	/* ==================================================================
	 * Initialisation des graphiques présents sur la page
	 * ================================================================== */
	var lineChart = null;
	var donutChart = null;

	function initCharts() {
		var lineCv = $('#rcb-line-chart') || $('#rcb-line-chart-30');
		if (lineCv) {
			var tip = $('#rcb-line-tip') || $('#rcb-line-tip-30');
			lineChart = new LineChart(lineCv, tip, cfg.chart);
		}
		var donutCv = $('#rcb-donut-chart');
		if (donutCv) {
			donutChart = new DonutChart(donutCv, $('#rcb-donut-legend'), cfg.donut);
		}

		// Redessin adaptatif lors du redimensionnement.
		var resizeTimer = null;
		window.addEventListener('resize', function () {
			window.clearTimeout(resizeTimer);
			resizeTimer = window.setTimeout(function () {
				if (lineChart) { lineChart.draw(); }
				if (donutChart) { donutChart.draw(); }
			}, 150);
		});
	}

	/* ==================================================================
	 * Mise à jour temps réel (tableau de bord, toutes les 30 s)
	 * ================================================================== */
	function initLive() {
		if (!cfg.live || cfg.page !== 'dashboard' || !cfg.ajax) { return; }

		window.setInterval(function () {
			var body = new window.FormData();
			body.append('action', 'infinity_rcb_live');
			body.append('nonce', cfg.nonce);

			window.fetch(cfg.ajax, { method: 'POST', credentials: 'same-origin', body: body })
				.then(function (res) { return res.json(); })
				.then(function (json) {
					if (!json || !json.success) { return; }
					var s = json.data.summary || {};
					var map = {
						total: s.total, today: s.today, unique: s.unique,
						right_click: (s.by_type || {}).right_click,
						keyboard: (s.by_type || {}).keyboard,
						devtools: (s.by_type || {}).devtools
					};
					Object.keys(map).forEach(function (key) {
						var el = $('[data-live="' + key + '"]');
						if (el) {
							el.setAttribute('data-count', map[key] || 0);
							el.removeAttribute('data-counted');
							el.textContent = formatInt(map[key] || 0);
						}
					});
					renderRecent(s.recent || []);

					if (lineChart && json.data.chart) {
						lineChart.data = json.data.chart;
						lineChart.draw();
					}
					if (donutChart && json.data.donut) {
						donutChart.entries = json.data.donut;
						donutChart.draw();
						donutChart.buildLegend();
					}
				})
				.catch(function () { /* Silencieux : la console admin reste propre. */ });
		}, 30000);
	}

	function renderRecent(events) {
		var body = $('#rcb-recent-body');
		var empty = $('#rcb-recent-empty');
		var table = $('#rcb-recent-table');
		if (!body) { return; }

		if (!events.length) { return; }
		if (empty) { empty.hidden = true; }
		if (table) { table.hidden = false; }

		var types = cfg.types || {};
		body.innerHTML = events.map(function (ev) {
			var meta = types[ev.type] || { emoji: '', label: ev.type, color: '#64748b' };
			return '<tr><td>' + escapeHtml(ev.time) + '</td>' +
				'<td><span class="rcb-badge" style="background:' + meta.color + '1a;color:' + meta.color + ';">' +
				escapeHtml(meta.emoji + ' ' + meta.label) + '</span></td>' +
				'<td><code>' + escapeHtml(ev.ip) + '</code></td></tr>';
		}).join('');

		var count = $('#rcb-recent-count');
		if (count) { count.textContent = String(events.length); }
	}

	function escapeHtml(str) {
		return String(str == null ? '' : str)
			.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;').replace(/'/g, '&#039;');
	}

	/* ==================================================================
	 * Confirmations avant actions destructrices
	 * ================================================================== */
	function initConfirms() {
		$$('form[data-confirm]').forEach(function (form) {
			form.addEventListener('submit', function (e) {
				if (!window.confirm(form.getAttribute('data-confirm'))) {
					e.preventDefault();
				}
			});
		});
	}

	/* ==================================================================
	 * « Tout sélectionner » dans un groupe d'interrupteurs
	 * ================================================================== */
	function initCheckAll() {
		$$('.rcb-check-all').forEach(function (master) {
			master.addEventListener('change', function () {
				var group = document.getElementById(master.getAttribute('data-group'));
				if (!group) { return; }
				$$('input[type="checkbox"]', group).forEach(function (cb) {
					cb.checked = master.checked;
				});
			});
		});
	}

	/* ==================================================================
	 * Aperçu du message (page Réglages → Apparence)
	 * ================================================================== */
	function initPreview() {
		var btn = $('#rcb-preview-btn');
		if (!btn) { return; }
		var ANIM = { st1: 'fade', st2: 'slide', st3: 'bounce', st4: 'zoom', st5: 'flip', st6: 'slide', st7: 'zoom', st8: 'fade', st9: 'slide', st10: 'bounce' };

		btn.addEventListener('click', function () {
			var style = ($('input[name="infinity_rcb[appearance][style]"]:checked') || {}).value || 'st1';
			var pos = ($('#rcb-pos') || {}).value || 'top';
			var dur = parseInt((($('#rcb-dur') || {}).value || '2600'), 10) || 2600;
			var bg = $('#rcb-custom-on') && $('#rcb-custom-on').checked ? (($('#rcb-bg') || {}).value || '') : '';
			var tx = $('#rcb-custom-on') && $('#rcb-custom-on').checked ? (($('#rcb-tx') || {}).value || '') : '';
			var msg = ($('#rcb-msg-right_click') || {}).value || 'Message d’avertissement';

			var copyOn = $('#rcb-copyright-on') && $('#rcb-copyright-on').checked;
			var copyText = ($('#rcb-copyright') || {}).value || '';
			copyText = copyText
				.replace(/\{annee\}/g, String(new Date().getFullYear()))
				.replace(/\{site\}/g, 'Mon site')
				.replace(/\{url\}/g, 'https://mon-site.example');

			var closeOn = $('#rcb-close-on') ? $('#rcb-close-on').checked : true;
			var barOn = $('#rcb-bar-on') ? $('#rcb-bar-on').checked : true;

			var toast = $('#rcb-preview-toast');
			if (!toast) {
				toast = document.createElement('div');
				toast.id = 'rcb-preview-toast';
				toast.innerHTML =
					'<div class="rcb-inner">' +
					'<span class="rcb-ico">' + shieldSvg() + '</span>' +
					'<span class="rcb-body"><span class="rcb-title"></span><span class="rcb-copy"></span></span>' +
					'<button type="button" class="rcb-close" aria-label="Fermer">&times;</button>' +
					'<span class="rcb-bar"><i></i></span>' +
					'</div>';
				document.body.appendChild(toast);
				toast.querySelector('.rcb-close').addEventListener('click', function () {
					toast.classList.remove('rcb-show');
					window.clearTimeout(toast._timer);
				});
			}

			toast.className = 'rcb-toast rcb-pos-' + pos;
			var inner = toast.firstChild;
			inner.className = 'rcb-inner rcb-st-' + style + ' rcb-a-' + (ANIM[style] || 'fade');
			inner.style.background = bg;
			inner.style.color = tx;
			toast.querySelector('.rcb-title').textContent = msg;

			var copy = toast.querySelector('.rcb-copy');
			copy.textContent = copyOn ? copyText : '';
			copy.style.display = copyOn && copyText ? '' : 'none';
			toast.querySelector('.rcb-close').style.display = closeOn ? '' : 'none';

			var bar = toast.querySelector('.rcb-bar');
			if (barOn) {
				bar.style.display = '';
				var fill = bar.firstChild;
				fill.style.animation = 'none';
				void fill.offsetWidth;
				fill.style.animation = '';
				fill.style.animationDuration = dur + 'ms';
			} else {
				bar.style.display = 'none';
			}

			// Relance l'animation puis masque après le délai configuré.
			toast.classList.remove('rcb-show');
			void inner.offsetWidth;
			toast.classList.add('rcb-show');

			window.clearTimeout(toast._timer);
			toast._timer = window.setTimeout(function () {
				toast.classList.remove('rcb-show');
			}, dur);
		});
	}

	function shieldSvg() {
		return '<svg width="24" height="24" viewBox="0 0 48 48" aria-hidden="true">'
			+ '<path d="M13 10h22v13c0 8.6-4.7 14.7-11 17.3C17.7 37.7 13 31.6 13 23Z" fill="currentColor"/>'
			+ '<rect x="19.6" y="15.6" width="8.8" height="14.2" rx="4.4" fill="#0B0F1E"/>'
			+ '<rect x="23.2" y="17.2" width="1.6" height="3.6" rx=".8" fill="#fff" opacity=".92"/>'
			+ '<path d="M15 18.4 35.4 27.8" stroke="#fff" stroke-width="4.4" stroke-linecap="round"/>'
			+ '<path d="M15 18.4 35.4 27.8" stroke="#F43F5E" stroke-width="2.8" stroke-linecap="round"/>'
			+ '</svg>';
	}

	/* ==================================================================
	 * Filtres des journaux
	 * ================================================================== */
	function initLogFilters() {
		var table = $('#rcb-log-table');
		if (!table) { return; }

		var typeFilter = $('#rcb-filter-type');
		var levelFilter = $('#rcb-filter-level');
		var search = $('#rcb-filter-search');

		function apply() {
			var type = typeFilter ? typeFilter.value : '';
			var level = levelFilter ? levelFilter.value : '';
			var q = search ? search.value.trim().toLowerCase() : '';
			var shown = 0;

			$$('tbody tr', table).forEach(function (row) {
				var ok = (!type || row.getAttribute('data-type') === type) &&
					(!level || row.getAttribute('data-level') === level) &&
					(!q || (row.getAttribute('data-search') || '').indexOf(q) !== -1);
				row.hidden = !ok;
				if (ok) { shown++; }
			});

			var counter = $('#rcb-log-count');
			if (counter) { counter.textContent = String(shown); }
		}

		if (typeFilter) { typeFilter.addEventListener('change', apply); }
		if (levelFilter) { levelFilter.addEventListener('change', apply); }
		if (search) { search.addEventListener('input', apply); }
	}

	/* ==================================================================
	 * Sélecteur de plan de licence (surbrillance en direct)
	 * ================================================================== */
	function initPlanPicker() {
		// Deux sélecteurs de plan : réglages (infinity_rcb[license][plan]) et
		// page Licence (.rcb-order-plan) — même surbrillance is-selected.
		var radios = $$('input[name="infinity_rcb[license][plan]"], input.rcb-order-plan');
		if (!radios.length) { return; }
		function sync() {
			radios.forEach(function (radio) {
				var label = radio.closest('.rcb-plan-option');
				if (label) { label.classList.toggle('is-selected', radio.checked); }
			});
		}
		radios.forEach(function (radio) {
			radio.addEventListener('change', sync);
		});
		sync();
	}

	/* ==================================================================
	 * Préréglages de protection (Doux / Équilibré / Maximum)
	 * ================================================================== */
	function initPresets() {
		var presets = {
			soft: ['right_click', 'copy', 'selection'],
			balanced: ['right_click', 'keyboard', 'copy', 'selection', 'drag_drop', 'source_code', 'devtools'],
			max: null // null = tout activer.
		};
		$$('.rcb-preset').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var list = presets[btn.getAttribute('data-preset')];
				$$('.rcb-fields input[type="checkbox"]').forEach(function (cb) {
					var name = (cb.name || '');
					var type = name.match(/\[protections\]\[([a-z_]+)\]$/);
					if (!type) { return; }
					cb.checked = list === null || list.indexOf(type[1]) !== -1;
				});
			});
		});
	}

	/* ==================================================================
	 * Page Licence & Achat : boutons commander + copie des clés
	 * ================================================================== */
	function initLicensePage() {
		// « Commander » : pré-sélectionne le plan et déplace le focus sur le formulaire.
		$$('.rcb-order-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var plan = btn.getAttribute('data-plan');
				var radio = $('.rcb-order-plan[value="' + plan + '"]');
				if (radio) {
					radio.checked = true;
					radio.dispatchEvent(new window.Event('change'));
				}
				var form = document.getElementById('rcb-commande');
				if (form) { form.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
				var buyer = document.getElementById('rcb-buyer');
				if (buyer) { buyer.focus(); }
			});
		});

		// Copie dans le presse-papiers (clé de licence, clés des commandes).
		$$('[data-copy], [data-copy-copy]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var text = btn.getAttribute('data-copy-copy');
				if (!text) {
					var target = $(btn.getAttribute('data-copy'));
					text = target ? target.textContent : '';
				}
				if (!text) { return; }
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text).then(function () { flashCopied(btn); }).catch(function () { flashCopied(btn); });
				} else {
					flashCopied(btn);
				}
			});
		});

		function flashCopied(btn) {
			var original = btn.textContent;
			btn.textContent = '✓ Copié';
			window.setTimeout(function () { btn.textContent = original; }, 1400);
		}
	}

	/* ==================================================================
	 * Modale de confirmation de commande + bouton de commande dynamique
	 * ================================================================== */
	function initOrderConfirm() {
		var form = document.getElementById('rcb-order-form');
		var modal = document.getElementById('rcb-confirm-modal');
		if (!form || !modal) { return; }

		var okBtn = document.getElementById('rcb-confirm-ok');
		var cancelBtn = document.getElementById('rcb-confirm-cancel');
		var submitBtn = document.getElementById('rcb-order-submit');
		var submitLabel = document.getElementById('rcb-order-submit-label');
		var bypass = false;

		// Bouton principal : suit le plan choisi (libellé + prix).
		function syncSubmitLabel() {
			if (!submitLabel) { return; }
			var radio = form.querySelector('.rcb-order-plan:checked');
			if (radio) {
				submitLabel.textContent = (radio.getAttribute('data-label') || radio.value) + ' · ' + (radio.getAttribute('data-price') || '');
			}
		}
		form.querySelectorAll('.rcb-order-plan').forEach(function (radio) {
			radio.addEventListener('change', syncSubmitLabel);
		});
		syncSubmitLabel();

		function closeModal() {
			modal.hidden = true;
			var buyer = document.getElementById('rcb-buyer');
			if (buyer) { buyer.focus(); }
		}

		form.addEventListener('submit', function (e) {
			if (bypass) { return; }
			if (!form.checkValidity()) { return; }

			e.preventDefault();
			var radio = form.querySelector('.rcb-order-plan:checked');
			var planEl = document.getElementById('rcb-confirm-plan');
			planEl.textContent = radio ? (radio.getAttribute('data-label') || radio.value) : '—';
			if (radio && radio.getAttribute('data-domains') && planEl) {
				planEl.textContent += ' (' + radio.getAttribute('data-domains') + ')';
			}
			document.getElementById('rcb-confirm-price').textContent = radio ? (radio.getAttribute('data-price') || '—') : '—';
			document.getElementById('rcb-confirm-buyer').textContent = (document.getElementById('rcb-buyer') || {}).value || '—';
			document.getElementById('rcb-confirm-email').textContent = (document.getElementById('rcb-order-email') || {}).value || '—';
			var promo = (document.getElementById('rcb-promo') || {}).value || '';
			var promoNote = document.getElementById('rcb-confirm-promo-note');
			document.getElementById('rcb-confirm-promo').textContent = promo ? promo.toUpperCase() : '—';
			if (promoNote) { promoNote.hidden = '' === promo; }
			modal.hidden = false;
			if (okBtn) { okBtn.focus(); }
		});

		okBtn.addEventListener('click', function () {
			modal.hidden = true;
			bypass = true;
			if (submitBtn) {
				submitBtn.disabled = true;
				submitBtn.textContent = '⏳ Envoi de la commande…';
			}
			form.submit();
		});
		cancelBtn.addEventListener('click', closeModal);
		modal.addEventListener('click', function (e) { if (e.target === modal) { closeModal(); } });
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && !modal.hidden) { closeModal(); }
		});
	}

	/* ==================================================================
	 * Carte « Aperçu du message » (dashboard : lecture seule ; réglages :
	 * synchronisée en direct avec le formulaire de l'onglet Apparence)
	 * ================================================================== */
	function initDashPreview() {
		var stage = $('#rcb-dash-preview');
		if (!stage) { return; }
		var stageEl = stage.querySelector('.rcb-preview-stage');
		var toast = $('#rcb-dash-toast');
		var inner = toast ? toast.querySelector('.rcb-inner') : null;
		if (!stageEl || !toast || !inner) { return; }

		var inSettings = stage.getAttribute('data-context') === 'settings';
		var ANIM = { st1: 'fade', st2: 'slide', st3: 'bounce', st4: 'zoom', st5: 'flip', st6: 'slide', st7: 'zoom', st8: 'fade', st9: 'slide', st10: 'bounce' };
		var hideTimer = null;

		/* --- Comportement réel : durée, disparition, croix, bip --- */

		function currentDuration() {
			var durEl = $('#rcb-dur');
			var d = durEl ? parseInt(durEl.value, 10) : parseInt(stageEl.getAttribute('data-dur'), 10);
			return (d && d > 0) ? d : 2600;
		}

		function soundOn() {
			var snd = document.querySelector('[name="infinity_rcb[appearance][sound]"]');
			if (snd) { return snd.checked; }
			return stageEl.getAttribute('data-sound') === '1';
		}

		function beep() {
			try {
				beep.ctx = beep.ctx || new (window.AudioContext || window.webkitAudioContext)();
				var osc = beep.ctx.createOscillator();
				var gain = beep.ctx.createGain();
				osc.type = 'sine';
				osc.frequency.value = 830;
				gain.gain.setValueAtTime(0.06, beep.ctx.currentTime);
				gain.gain.exponentialRampToValueAtTime(0.0001, beep.ctx.currentTime + 0.18);
				osc.connect(gain).connect(beep.ctx.destination);
				osc.start();
				osc.stop(beep.ctx.currentTime + 0.2);
			} catch (e) { /* Audio indisponible : silencieux. */ }
		}

		function hide() {
			window.clearTimeout(hideTimer);
			toast.classList.remove('rcb-show');
			stageEl.classList.add('is-idle');
		}

		function replay(userAction) {
			window.clearTimeout(hideTimer);
			stageEl.classList.remove('is-idle');
			toast.classList.remove('rcb-show');
			void toast.offsetWidth; // relance l'animation d'apparition.
			toast.classList.add('rcb-show');

			var fill = toast.querySelector('.rcb-bar i');
			var dur = currentDuration();
			if (fill) {
				fill.style.animation = 'none';
				void fill.offsetWidth;
				fill.style.animation = '';
				fill.style.animationDuration = dur + 'ms';
			}
			if (userAction && soundOn()) { beep(); }
			hideTimer = window.setTimeout(hide, dur);
		}

		function setPos(pos) {
			var css = 'position:absolute !important;left:50% !important;max-width:min(88%,520px) !important;';
			if (pos === 'center') {
				css += 'top:50% !important;bottom:auto !important;transform:translate(-50%,-50%) !important;';
			} else if (pos === 'bottom') {
				css += 'bottom:16px !important;top:auto !important;transform:translateX(-50%) !important;';
			} else {
				css += 'top:16px !important;bottom:auto !important;transform:translateX(-50%) !important;';
			}
			toast.setAttribute('style', css);
			var shown = toast.classList.contains('rcb-show');
			toast.className = 'rcb-toast rcb-pos-' + pos + (shown ? ' rcb-show' : '');
		}

		function markChip(selector, match) {
			stage.querySelectorAll(selector).forEach(function (c) {
				c.classList.toggle('is-active', match(c));
			});
		}

		/* --- Contrôles --- */
		var replayBtn = $('#rcb-dash-replay');
		if (replayBtn) { replayBtn.addEventListener('click', function () { replay(true); }); }
		var hintBtn = $('#rcb-dash-replay-hint');
		if (hintBtn) { hintBtn.addEventListener('click', function () { replay(true); }); }
		var closeBtn = inner.querySelector('.rcb-close');
		if (closeBtn) { closeBtn.addEventListener('click', hide); }

		// Onglet Apparence : l'aperçu suit le formulaire champ par champ.
		function formEl(name) { return document.querySelector('[name="infinity_rcb[appearance][' + name + ']"]'); }
		function checked(name) { var el = formEl(name); return !!(el && el.checked); }

		function syncFromForm() {
			var radio = document.querySelector('input[name="infinity_rcb[appearance][style]"]:checked');
			var style = radio ? radio.value : 'st1';
			var pos = ($('#rcb-pos') || {}).value || 'top';
			var dur = parseInt((($('#rcb-dur') || {}).value || '2600'), 10) || 2600;
			var radius = parseInt((($('#rcb-radius') || {}).value || '12'), 10);
			var font = parseInt((($('#rcb-font') || {}).value || '15'), 10);
			var customOn = $('#rcb-custom-on') && $('#rcb-custom-on').checked;

			inner.className = 'rcb-inner rcb-st-' + style + ' rcb-a-' + (ANIM[style] || 'fade');
			var css = '';
			if (customOn) {
				if (($('#rcb-bg') || {}).value) { css += 'background:' + $('#rcb-bg').value + ';'; }
				if (($('#rcb-tx') || {}).value) { css += 'color:' + $('#rcb-tx').value + ';'; }
			}
			css += 'border-radius:' + radius + 'px;font-size:' + font + 'px;';
			if (!checked('shadow')) { css += 'box-shadow:none;'; }
			inner.setAttribute('style', css);

			var ico = inner.querySelector('.rcb-ico');
			if (ico) { ico.style.display = checked('show_icon') ? '' : 'none'; }

			var copy = inner.querySelector('.rcb-copy');
			if (copy) {
				var raw = (($('#rcb-copyright') || {}).value || '');
				var text = checked('copyright_enable') ? raw
					.replace(/\{annee\}/g, String(new Date().getFullYear()))
					.replace(/\{site\}/g, stage.getAttribute('data-site') || 'Mon site')
					.replace(/\{url\}/g, stage.getAttribute('data-url') || '') : '';
				copy.textContent = text;
				copy.style.display = text ? '' : 'none';
			}

			var closeBtn = inner.querySelector('.rcb-close');
			if (closeBtn) { closeBtn.style.display = checked('show_close') ? '' : 'none'; }

			var bar = inner.querySelector('.rcb-bar');
			var fill = inner.querySelector('.rcb-bar i');
			if (bar) { bar.style.display = checked('show_progress') ? '' : 'none'; }
			if (fill) { fill.style.animationDuration = dur + 'ms'; }

			setPos(pos);
			markChip('.rcb-dash-style', function (c) { return c.getAttribute('data-st') === style; });
			markChip('.rcb-dash-pos', function (c) { return c.getAttribute('data-pos') === pos; });
			replay(false); // Temps réel : chaque réglage rejoue le comportement.
		}

		// Pastilles Styles : appliquent le thème ; en Apparence, cochent
		// aussi la case du formulaire (le cœur du réglage).
		stage.querySelectorAll('.rcb-dash-style').forEach(function (chip) {
			chip.addEventListener('click', function () {
				var st = chip.getAttribute('data-st');
				if (inSettings) {
					var radio = document.querySelector('input[name="infinity_rcb[appearance][style]"][value="' + st + '"]');
					if (radio) { radio.checked = true; }
					syncFromForm();
				} else {
					stage.querySelectorAll('.rcb-dash-style').forEach(function (c) { c.classList.remove('is-active'); });
					chip.classList.add('is-active');
					inner.className = 'rcb-inner rcb-st-' + st + ' rcb-a-' + chip.getAttribute('data-anim');
					inner.setAttribute('style', (inner.getAttribute('style') || '').replace(/background:[^;]+;?/g, '').replace(/color:[^;]+;?/g, ''));
					replay(true);
				}
			});
		});

		// Pastilles Position : idem, mettent à jour le sélecteur en Apparence.
		stage.querySelectorAll('.rcb-dash-pos').forEach(function (chip) {
			chip.addEventListener('click', function () {
				var pos = chip.getAttribute('data-pos');
				if (inSettings && $('#rcb-pos')) { $('#rcb-pos').value = pos; }
				markChip('.rcb-dash-pos', function (c) { return c === chip; });
				setPos(pos);
				replay(true);
			});
		});

		// Message : le sélecteur porte directement le texte en valeur.
		var typeSelect = $('#rcb-dash-type');
		if (typeSelect) {
			typeSelect.addEventListener('change', function () {
				var title = toast.querySelector('.rcb-title');
				if (title) { title.textContent = typeSelect.value; }
				replay(true);
			});
		}

		// Apparence : chaque champ du formulaire met l'aperçu à jour en direct.
		if (inSettings) {
			var fields = document.querySelectorAll('#rcb-settings-form [name^="infinity_rcb[appearance]"]');
			fields.forEach(function (el) {
				var evt = (el.type === 'radio' || el.type === 'checkbox' || el.tagName === 'SELECT') ? 'change' : 'input';
				el.addEventListener(evt, syncFromForm);
			});
			syncFromForm();
		} else {
			// Tableau de bord : démonstration du comportement réel au chargement.
			window.setTimeout(function () { replay(false); }, 350);
		}
	}


	/* ==================================================================
	 * Page Licence : popups de notification + surveillance temps réel
	 * de la commande (prévient le client quand sa clé arrive).
	 * ================================================================== */
	function initOrderWatch() {
		var watch = document.getElementById('rcb-order-watch');
		var noticeModal = document.getElementById('rcb-order-notice-modal');
		var arrivedModal = document.getElementById('rcb-key-arrived-modal');
		var arrivedKey = '';
		var noticeClose = document.getElementById('rcb-order-notice-close');

		// 1. Popup automatique après commande / déclaration de paiement.
		if (noticeModal) {
			noticeModal.hidden = false;
			if (noticeClose) { noticeClose.focus(); }
		}

		// 2. Boutons (liés avant tout retour anticipé).
		var copyBtn = document.getElementById('rcb-arrived-copy');
		if (copyBtn) {
			copyBtn.addEventListener('click', function () {
				if (navigator.clipboard && arrivedKey) { navigator.clipboard.writeText(arrivedKey); }
				copyBtn.textContent = '✓ Copié';
				window.setTimeout(function () { copyBtn.textContent = '📋 Copier la clé'; }, 1500);
			});
		}
		var activateBtn = document.getElementById('rcb-arrived-activate');
		if (activateBtn) {
			activateBtn.addEventListener('click', function () {
				if (arrivedModal) { arrivedModal.hidden = true; }
				var keyInput = document.getElementById('rcb-key');
				if (keyInput && arrivedKey) { keyInput.value = arrivedKey; }
				var lic = document.getElementById('rcb-licence');
				if (lic) { lic.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
				if (keyInput) { keyInput.focus(); }
			});
		}
		if (noticeClose && noticeModal) {
			noticeClose.addEventListener('click', function () { noticeModal.hidden = true; });
			noticeModal.addEventListener('click', function (e) { if (e.target === noticeModal) { noticeModal.hidden = true; } });
		}

		// 3. Surveillance temps réel (25 s, onglet visible uniquement).
		if (!watch || !window.RCBAdmin || !RCBAdmin.ajax) { return; }
		var ref = watch.getAttribute('data-ref');
		var lastStatus = watch.getAttribute('data-status');
		var timer = null;

		function showArrived() {
			if (!arrivedModal) { return; }
			document.getElementById('rcb-arrived-ref').textContent = ref;
			document.getElementById('rcb-arrived-key').textContent = arrivedKey;
			arrivedModal.hidden = false;
		}

		function poll() {
			if (document.visibilityState === 'hidden') { return; }
			var body = new URLSearchParams();
			body.append('action', 'infinity_rcb_order_status');
			body.append('nonce', RCBAdmin.nonce);
			body.append('ref', ref);
			window.fetch(RCBAdmin.ajax, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() })
				.then(function (r) { return r.json(); })
				.then(function (r) {
					if (!r || !r.success) { return; }
					var st = r.data.status;
					if (st !== lastStatus) {
						lastStatus = st;
						if (st === 'declared') {
							window.location.reload();
						} else if (st === 'paid' || st === 'delivered') {
							arrivedKey = r.data.key || '';
							window.clearInterval(timer);
							showArrived();
						}
					}
				})
				.catch(function () { /* silencieux : nouvelle tentative dans 25 s */ });
		}

		timer = window.setInterval(poll, 25000);
	}

	/* ==================================================================
	 * Lancement
	 * ================================================================== */
	function boot() {
		initTabs();
		initConfirms();
		initCheckAll();
		initPlanPicker();
		initPresets();
		initLicensePage();
		initOrderConfirm();
		initOrderWatch();
		initPreview();
		initDashPreview();
		initLogFilters();
		initCharts();
		animateCounters();
		initLive();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
