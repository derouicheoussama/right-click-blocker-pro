/**
 * Infinity RCB Pro — Protection front (v2)
 * Blocage du clic droit, raccourcis clavier, copie, sélection, glisser-déposer,
 * impression, captures d'écran et détection des outils de développement.
 *
 * JavaScript natif, aucune dépendance. Configuration via window.InfinityRCB.
 * Les écouteurs sont posés en PHASE DE CAPTURE afin de passer avant les
 * gestionnaires des thèmes et page builders (compatibilité maximale).
 */
(function () {
	'use strict';

	var C = window.InfinityRCB;
	if (!C || !C.master) { return; }

	var t = C.prot || {};

	/* ------------------------------------------------------------------
	 * Anti-clickjacking : si la page est affichée dans une iframe tierce,
	 * on reprend la main (option « frame_bust »).
	 * ------------------------------------------------------------------ */
	if (C.fb && window.top !== window.self) {
		try { window.top.location = window.self.location; } catch (e) { /* bloqué par le navigateur */ }
	}

	/* ------------------------------------------------------------------
	 * Filigrane : chaque image (hors exemption, hors miniatures) est
	 * enveloppée d'un span .rcb-wm portant le texte en attribut ; le CSS
	 * injecté par le PHP affiche le texte via ::after.
	 * ------------------------------------------------------------------ */
	if (C.wm && C.wm.on && C.wm.text) {
		var wmApply = function () {
			var imgs = document.querySelectorAll('img:not([data-rcb-exempt]):not([data-rcb-wm])');
			for (var i = 0; i < imgs.length; i++) {
				var img = imgs[i];
				if ((img.width && img.width < 80) || (img.height && img.height < 80)) { continue; }
				if (img.parentNode && img.parentNode.classList && img.parentNode.classList.contains('rcb-wm')) { continue; }
				img.setAttribute('data-rcb-wm', '1');
				var wrap = document.createElement('span');
				wrap.className = 'rcb-wm';
				wrap.setAttribute('data-wm', C.wm.text);
				img.parentNode.insertBefore(wrap, img);
				wrap.appendChild(img);
			}
		};
		document.addEventListener('DOMContentLoaded', wmApply);
		window.addEventListener('load', function () { window.setTimeout(wmApply, 400); });
	}

	var SHIELD_SVG = '<svg width="24" height="24" viewBox="0 0 48 48" aria-hidden="true">'
		+ '<path d="M13 10h22v13c0 8.6-4.7 14.7-11 17.3C17.7 37.7 13 31.6 13 23Z" fill="currentColor"/>'
		+ '<rect x="19.6" y="15.6" width="8.8" height="14.2" rx="4.4" fill="#0B0F1E"/>'
		+ '<rect x="23.2" y="17.2" width="1.6" height="3.6" rx=".8" fill="#fff" opacity=".92"/>'
		+ '<path d="M15 18.4 35.4 27.8" stroke="#fff" stroke-width="4.4" stroke-linecap="round"/>'
		+ '<path d="M15 18.4 35.4 27.8" stroke="#F43F5E" stroke-width="2.8" stroke-linecap="round"/>'
		+ '</svg>';
	var ANIM = { st1: 'fade', st2: 'slide', st3: 'bounce', st4: 'zoom', st5: 'flip', st6: 'slide', st7: 'zoom', st8: 'fade', st9: 'slide', st10: 'bounce' };

	/* ------------------------------------------------------------------
	 * Notification enrichie (titre + copyright + fermeture + progression)
	 * ------------------------------------------------------------------ */
	var toast = null;
	var hideTimer = null;

	function on(el, type, fn) { el.addEventListener(type, fn); }

	function ensureToast() {
		if (toast) { return; }
		toast = document.createElement('div');
		toast.id = 'rcb-toast';
		toast.className = 'rcb-toast rcb-pos-' + (C.pos || 'top');
		toast.setAttribute('role', 'alert');
		toast.setAttribute('aria-live', 'assertive');

		var inner = document.createElement('div');
		inner.className = 'rcb-inner';

		var ico = document.createElement('span');
		ico.className = 'rcb-ico';
		ico.innerHTML = SHIELD_SVG;

		var body = document.createElement('span');
		body.className = 'rcb-body';

		var title = document.createElement('span');
		title.className = 'rcb-title';

		var copy = document.createElement('span');
		copy.className = 'rcb-copy';

		body.appendChild(title);
		body.appendChild(copy);

		var close = document.createElement('button');
		close.type = 'button';
		close.className = 'rcb-close';
		close.setAttribute('aria-label', 'Fermer le message');
		close.innerHTML = '&times;';
		on(close, 'click', function () {
			toast.classList.remove('rcb-show');
			window.clearTimeout(hideTimer);
		});

		var bar = document.createElement('span');
		bar.className = 'rcb-bar';
		bar.appendChild(document.createElement('i'));

		inner.appendChild(ico);
		inner.appendChild(body);
		inner.appendChild(close);
		inner.appendChild(bar);
		toast.appendChild(inner);
		document.body.appendChild(toast);
	}

	function show(type) {
		ensureToast();
		var msg = (C.msg || {})[type] || 'Action interdite !';
		var inner = toast.firstChild;
		var dur = C.dur || 2600;

		toast.querySelector('.rcb-title').textContent = msg;

		var copy = toast.querySelector('.rcb-copy');
		var hasCopy = C.copy && C.copy.on && C.copy.text;
		copy.textContent = hasCopy ? C.copy.text : '';
		copy.style.display = hasCopy ? '' : 'none';

		toast.querySelector('.rcb-ico').style.display = (C.icon === false) ? 'none' : '';
		toast.querySelector('.rcb-close').style.display = C.close ? '' : 'none';

		var bar = toast.querySelector('.rcb-bar');
		if (C.bar) {
			bar.style.display = '';
			var fill = bar.firstChild;
			fill.style.animation = 'none';
			void fill.offsetWidth; // relance l'animation
			fill.style.animation = '';
			fill.style.animationDuration = dur + 'ms';
		} else {
			bar.style.display = 'none';
		}

		var style = C.style || 'st1';
		inner.className = 'rcb-inner rcb-st-' + style + ' rcb-a-' + (ANIM[style] || 'fade');
		inner.style.background = C.bg || '';
		inner.style.color = C.tx || '';

		// Finitions personnalisables (rayon, taille, ombre).
		if (C.look) {
			if (C.look.radius !== undefined) { inner.style.borderRadius = C.look.radius + 'px'; }
			if (C.look.font) { inner.style.setProperty('font-size', parseInt(C.look.font, 10) + 'px', 'important'); }
			if (C.look.shadow === false) { inner.style.boxShadow = 'none'; }
		}

		// Relance l'animation d'apparition à chaque affichage.
		void inner.offsetWidth;
		toast.classList.add('rcb-show');

		if (C.sound) { beep(); }

		window.clearTimeout(hideTimer);
		hideTimer = window.setTimeout(function () {
			toast.classList.remove('rcb-show');
		}, dur);
	}

	/* ------------------------------------------------------------------
	 * CSS personnalisé (injecté une seule fois)
	 * ------------------------------------------------------------------ */
	if (C.look && C.look.css) {
		var custom = document.createElement('style');
		custom.appendChild(document.createTextNode(String(C.look.css)));
		document.head.appendChild(custom);
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

	/* ------------------------------------------------------------------
	 * Remontée AJAX optimisée : file d'attente groupée.
	 * Les tentatives sont mises en file puis envoyées en une seule
	 * requête (toutes les 6 s maximum) via sendBeacon si disponible.
	 * ------------------------------------------------------------------ */
	var queue = {};
	var queuedTotal = 0;
	var flushTimer = null;
	var lastSent = {};

	function track(type) {
		var now = Date.now();
		if (now - (lastSent[type] || 0) < 5000) { return; }
		lastSent[type] = now;

		if (!C.track) { return; }
		queue[type] = (queue[type] || 0) + 1;
		queuedTotal++;

		if (queuedTotal >= 50) {
			flush(); // Garde-fou : au-delà de 50 évènements en attente, on vide.
		} else if (!flushTimer) {
			flushTimer = window.setTimeout(flush, 6000);
		}
	}

	function flush(useBeacon) {
		flushTimer = null;
		if (!queuedTotal) { return; }

		var payload = 'action=infinity_rcb_track&nonce=' + encodeURIComponent(C.nonce) +
			'&data=' + encodeURIComponent(JSON.stringify(queue));
		queue = {};
		queuedTotal = 0;

		try {
			if (useBeacon && navigator.sendBeacon) {
				navigator.sendBeacon(C.ajax, new Blob([payload], { type: 'application/x-www-form-urlencoded' }));
				return;
			}
			window.fetch(C.ajax, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: payload
			}).catch(function () {});
		} catch (e) { /* Silencieux : le blocage local reste effectif. */ }
	}

	// Envoi résiduel avant fermeture / masquage de l'onglet.
	document.addEventListener('visibilitychange', function () {
		if (document.hidden) { flush(true); }
	});
	window.addEventListener('pagehide', function () { flush(true); });

	/* ------------------------------------------------------------------
	 * Utilitaires
	 * ------------------------------------------------------------------ */
	function inField(e) {
		var el = e.target || e.srcElement;
		if (!el || !el.tagName) { return false; }
		var name = el.tagName.toUpperCase();
		return name === 'INPUT' || name === 'TEXTAREA' || name === 'SELECT' || el.isContentEditable === true;
	}

	function stop(e, type) {
		if (e.preventDefault) { e.preventDefault(); }
		if (e.stopPropagation) { e.stopPropagation(); }
		if (e.stopImmediatePropagation) { e.stopImmediatePropagation(); }
		show(type);
		track(type);
	}

	// Enregistre un écouteur en phase de capture (priorité sur les thèmes).
	function guard(target, type, fn) {
		(target.addEventListener ? target : document).addEventListener(type, fn, true);
	}

	/* ------------------------------------------------------------------
	 * Clic droit
	 * ------------------------------------------------------------------ */
	guard(document, 'contextmenu', function (e) {
		if (t.right_click) { stop(e, 'right_click'); }
	});

	/* ------------------------------------------------------------------
	 * Raccourcis clavier (mappés par protection active)
	 * ------------------------------------------------------------------ */
	guard(document, 'keydown', function (e) {
		var k = e.keyCode || e.which;
		var ctrl = e.ctrlKey || e.metaKey;

		// Outils de développement : F12, Ctrl+Shift+I / J / C / K.
		if (t.keyboard && (k === 123 || (ctrl && e.shiftKey && (k === 73 || k === 74 || k === 67)))) {
			stop(e, 'devtools');
			return;
		}
		// Console : Ctrl+Shift+J / K lorsque la protection console est active seule.
		if (!t.keyboard && t.console && ctrl && e.shiftKey && (k === 74 || k === 75)) {
			stop(e, 'console');
			return;
		}
		// Code source : Ctrl+U.
		if (t.source_code && ctrl && !e.shiftKey && k === 85) {
			stop(e, 'source_code');
			return;
		}
		// Enregistrer : Ctrl+S (et Ctrl+Shift+S sous Firefox).
		if (t.save_as && ctrl && k === 83) {
			stop(e, 'save_as');
			return;
		}
		// Impression : Ctrl+P.
		if (t.print && ctrl && k === 80) {
			stop(e, 'print');
			return;
		}
		// Tout sélectionner : Ctrl+A (hors champs de saisie).
		if (t.copy && ctrl && k === 65 && !inField(e)) {
			stop(e, 'copy');
		}
	});

	/* ------------------------------------------------------------------
	 * Touches système (PrintScreen)
	 * ------------------------------------------------------------------ */
	guard(document, 'keyup', function (e) {
		if (t.screenshot && (e.key === 'PrintScreen' || e.keyCode === 44)) {
			show('screenshot');
			track('screenshot');
			// Vide le presse-papiers lorsque l'API est disponible.
			try {
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText('').catch(function () {});
				}
			} catch (err) { /* Silencieux. */ }
		}
	});

	/* ------------------------------------------------------------------
	 * Copier / couper / coller
	 * ------------------------------------------------------------------ */
	['copy', 'cut', 'paste'].forEach(function (evtName) {
		guard(document, evtName, function (e) {
			if (t.copy && !inField(e)) { stop(e, 'copy'); }
		});
	});

	/* ------------------------------------------------------------------
	 * Sélection et glisser-déposer
	 * ------------------------------------------------------------------ */
	if (t.selection) {
		guard(document, 'selectstart', function (e) {
			if (!inField(e)) { stop(e, 'selection'); }
		});
		ready(function () {
			document.body.classList.add('rcb-no-select');
		});
	}

	if (t.drag_drop) {
		guard(document, 'dragstart', function (e) {
			stop(e, 'drag_drop');
		});
	}

	function ready(fn) {
		if (document.readyState !== 'loading') { fn(); }
		else { document.addEventListener('DOMContentLoaded', fn); }
	}

	/* ------------------------------------------------------------------
	 * Impression (évènement navigateur) + page d'avertissement à l'impression
	 * ------------------------------------------------------------------ */
	if (t.print) {
		window.addEventListener('beforeprint', function () {
			show('print');
			track('print');
		});

		var guardStyle = document.createElement('style');
		guardStyle.media = 'print';
		guardStyle.appendChild(document.createTextNode(
			'body > *:not(#rcb-print-warning){display:none !important}' +
			'#rcb-print-warning{display:block !important;position:static !important;font:16px/1.6 system-ui,sans-serif;padding:40px;text-align:center}'
		));
		document.head.appendChild(guardStyle);

		var warning = document.createElement('div');
		warning.id = 'rcb-print-warning';
		warning.style.display = 'none';
		warning.textContent = (C.msg || {}).print || 'L’impression est désactivée sur ce site.';
		ready(function () {
			document.body.appendChild(warning);
		});
	}

	/* ------------------------------------------------------------------
	 * Console : avertissement et nettoyage périodique
	 * ------------------------------------------------------------------ */
	if (t.console) {
		try {
			console.log('%c🛡 Infinity RCB Pro', 'color:#2E7BF6;font-size:16px;font-weight:bold');
			console.log('%cLa console de ce site est sous surveillance. Toute tentative d’inspection est enregistrée.', 'color:#94a3b8;font-size:12px');
		} catch (e) { /* Console bloquée par le navigateur. */ }
	}

	/* ------------------------------------------------------------------
	 * Protection mobile tactile : blocage du menu contextuel au long-press
	 * iOS/Android (touchstart + durée) et des gestes de sauvegarde d'image.
	 * ------------------------------------------------------------------ */
	if (C.adv && C.adv.touch) {
		var longPressTimer = null;
		var touchStartPos = null;

		document.addEventListener('touchstart', function (e) {
			if (e.touches.length !== 1) { return; }
			touchStartPos = { x: e.touches[0].clientX, y: e.touches[0].clientY, target: e.target };
			longPressTimer = setTimeout(function () {
				// Long-press détecté : bloque le menu contextuel natif iOS/Android.
				if (touchStartPos) { show('right_click'); track('right_click'); }
				touchStartPos = null;
			}, 500);
		}, { passive: true, capture: true });

		document.addEventListener('touchmove', function (e) {
			if (!longPressTimer || !touchStartPos || !e.touches[0]) { return; }
			var dx = Math.abs(e.touches[0].clientX - touchStartPos.x);
			var dy = Math.abs(e.touches[0].clientY - touchStartPos.y);
			if (dx > 10 || dy > 10) { clearTimeout(longPressTimer); touchStartPos = null; }
		}, { passive: true });

		document.addEventListener('touchend', function () {
			clearTimeout(longPressTimer);
			touchStartPos = null;
		}, { passive: true });

		// iOS Safari : blocage du menu natif d'image au long-press (callout).
		document.addEventListener('contextmenu', function (e) {
			if (e.target && e.target.tagName === 'IMG') { e.preventDefault(); }
		}, true);
	}

	/* ------------------------------------------------------------------
	 * Garde sans JavaScript : le bandeau noscript est injecté par le PHP.
	 * ------------------------------------------------------------------ */

	/* ------------------------------------------------------------------
	 * Détection des outils de développement (heuristique de dimensions)
	 * ------------------------------------------------------------------ */
	if (t.devtools) {
		var wasOpen = false;
		var lastAlert = 0;

		window.setInterval(function () {
			// Économie CPU : aucune vérification quand l'onglet est en arrière-plan.
			if (document.hidden) { return; }

			var open = (window.outerWidth - window.innerWidth > 160) ||
				(window.outerHeight - window.innerHeight > 160);

			if (open && !wasOpen) {
				wasOpen = true;
				var now = Date.now();
				if (now - lastAlert > 10000) {
					lastAlert = now;
					show('devtools');
					track('devtools');

					if (C.adv && C.adv.redirect && document.referrer !== C.adv.redirect) {
						window.location.href = C.adv.redirect;
					}
				}
				if (C.adv && C.adv.blur) {
					document.body.classList.add('rcb-guard-blur');
				}
			} else if (!open && wasOpen) {
				wasOpen = false;
				document.body.classList.remove('rcb-guard-blur');
			}
		}, (C.adv && C.adv.interval) || 800);
	}
})();
