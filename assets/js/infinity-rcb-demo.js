/**
 * Infinity RCB Pro — Démo publique interactive ([rcb_demo]).
 * Autonome : aucune requête serveur, aucun suivi envoyé.
 * Les protections de la démo sont scopées à la zone #rcb-demo ; les tests
 * clavier/DevTools s'arment au clic pour ne jamais gêner le reste du site.
 */
(function () {
	'use strict';

	var root = document.getElementById('rcb-demo');
	if (!root) { return; }

	var TYPES = {
		right_click: { label: 'Clic droit', color: '#ef4444' },
		selection:   { label: 'Sélection', color: '#06b6d4' },
		copy:        { label: 'Copie', color: '#10b981' },
		drag_image:  { label: 'Glisser image', color: '#8b5cf6' },
		drag_link:   { label: 'Glisser lien', color: '#a855f7' },
		source_code: { label: 'Code source', color: '#3b82f6' },
		save_as:     { label: 'Enregistrer', color: '#14b8a6' },
		print:       { label: 'Impression', color: '#ec4899' },
		devtools:    { label: 'DevTools', color: '#6366f1' },
		screenshot:  { label: 'Capture', color: '#f43f5e' }
	};
	var ANIM = { st1: 'fade', st2: 'slide', st3: 'bounce', st4: 'zoom', st5: 'flip', st6: 'slide', st7: 'zoom', st8: 'fade', st9: 'slide', st10: 'bounce' };
	var SHIELD = '<svg width="24" height="24" viewBox="0 0 48 48" aria-hidden="true"><path d="M13 10h22v13c0 8.6-4.7 14.7-11 17.3C17.7 37.7 13 31.6 13 23Z" fill="currentColor"/><rect x="19.6" y="15.6" width="8.8" height="14.2" rx="4.4" fill="#0B0F1E"/><path d="M15 18.4 35.4 27.8" stroke="#fff" stroke-width="4.4" stroke-linecap="round"/><path d="M15 18.4 35.4 27.8" stroke="#F43F5E" stroke-width="2.8" stroke-linecap="round"/></svg>';

	var count = 0;
	var logUl = document.getElementById('rcb-demo-log');
	var empty = document.getElementById('rcb-demo-empty');
	var counterEl = document.getElementById('rcb-demo-count');

	/* ---------- Toast (mêmes classes que le plugin réel) ---------- */
	var toast = null;
	var hideTimer = null;

	function showToast(style, message, typeLabel) {
		if (!toast) {
			toast = document.createElement('div');
			toast.className = 'rcb-toast rcb-pos-top';
			toast.innerHTML = '<div class="rcb-inner"><span class="rcb-ico">' + SHIELD + '</span>'
				+ '<span class="rcb-body"><span class="rcb-title"></span><span class="rcb-copy">© ' + new Date().getFullYear() + ' — Démonstration Infinity RCB Pro</span></span>'
				+ '<button type="button" class="rcb-close" aria-label="Fermer">&times;</button>'
				+ '<span class="rcb-bar"><i style="animation-duration:2600ms"></i></span></div>';
			document.body.appendChild(toast);
			toast.querySelector('.rcb-close').addEventListener('click', function () { toast.classList.remove('rcb-show'); });
		}
		var inner = toast.firstChild;
		toast.querySelector('.rcb-title').textContent = (typeLabel ? '⛔ ' + typeLabel + ' — ' : '') + message;
		inner.className = 'rcb-inner rcb-st-' + style + ' rcb-a-' + (ANIM[style] || 'fade');
		void inner.offsetWidth;
		toast.classList.add('rcb-show');
		clearTimeout(hideTimer);
		hideTimer = setTimeout(function () { toast.classList.remove('rcb-show'); }, 2600);
	}

	/* ---------- Journal + compteur (avec anti-spam par type) ---------- */
	var lastLog = {};

	function log(type) {
		var now = Date.now();
		if (now - (lastLog[type] || 0) < 1200) { return; }
		lastLog[type] = now;

		var meta = TYPES[type] || { label: type, color: '#64748b' };
		count++;
		if (counterEl) { counterEl.textContent = String(count); }
		if (empty) { empty.style.display = 'none'; }
		if (logUl) {
			var li = document.createElement('li');
			var now = new Date();
			var hh = ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2) + ':' + ('0' + now.getSeconds()).slice(-2);
			li.innerHTML = '<time>' + hh + '</time><span class="rcb-demo-badge" style="background:' + meta.color + '1a;color:' + meta.color + ';">' + meta.label + '</span> tentative bloquée';
			logUl.insertBefore(li, logUl.firstChild);
			while (logUl.children.length > 8) { logUl.removeChild(logUl.lastChild); }
		}
	}

	function block(e, type, message) {
		if (e.cancelable) { e.preventDefault(); }
		if (e.stopPropagation) { e.stopPropagation(); }
		log(type);
		showToast('st2', message || 'Action interdite — protection active !', (TYPES[type] || {}).label);
	}

	/* ---------- Tests directs (scopés à la zone de démo) ---------- */
	var zone = root.querySelector('.rcb-demo-grid') || root;

	zone.addEventListener('contextmenu', function (e) {
		if (inside(e, '[data-test="right_click"]')) { block(e, 'right_click', 'Clic droit désactivé sur ce site !'); }
	});
	zone.addEventListener('selectstart', function (e) {
		if (inside(e, '[data-test="selection"]')) { block(e, 'selection', 'La sélection de texte est désactivée !'); }
	});
	zone.addEventListener('copy', function (e) {
		if (inside(e, '[data-test="copy"]')) { block(e, 'copy', 'La copie est désactivée !'); }
	});
	zone.addEventListener('cut', function (e) {
		if (inside(e, '[data-test="copy"]')) { block(e, 'copy', 'La coupe est désactivée !'); }
	});
	zone.addEventListener('dragstart', function (e) {
		var card = e.target && e.target.closest ? e.target.closest('[data-test]') : null;
		if (!card) { return; }
		var t = card.getAttribute('data-test');
		if (t === 'drag_image') { block(e, 'drag_image', 'Le glisser-déposer d’image est désactivé !'); }
		if (t === 'drag_link') { block(e, 'drag_link', 'Le glisser-déposer de lien est désactivé !'); }
	});

	function inside(e, selector) {
		var card = e.target && e.target.closest ? e.target.closest(selector) : null;
		return !!card && zone.contains(card);
	}

	/* ---------- Tests armés (clavier, capture, DevTools) ---------- */
	var armed = null;   // 'keyboard' | 'devtools' | null
	var armTimer = null;
	var dtInterval = null;

	function setArmed(test, on) {
		armed = on ? test : null;
		var card = root.querySelector('[data-test="' + test + '"]');
		if (!card) { return; }
		var tag = card.querySelector('.rcb-demo-arm');
		if (tag) { tag.textContent = on ? '⏺ Test actif 20 s… (Échap pour arrêter)' : '▶ Activer le test'; }
		card.classList.toggle('is-armed', !!on);
	}

	function arm(test) {
		disarm();
		setArmed(test, true);
		if (test === 'keyboard') {
			document.addEventListener('keydown', onArmedKey, true);
			document.addEventListener('keyup', onArmedKeyUp, true);
		} else if (test === 'screenshot') {
			document.addEventListener('keyup', onArmedKeyUp, true);
		} else if (test === 'devtools') {
			dtInterval = setInterval(checkDevtools, 700);
		}
		armTimer = setTimeout(disarm, 20000);
	}

	function disarm() {
		if (armed) { setArmed(armed, false); }
		armed = null;
		clearTimeout(armTimer);
		clearInterval(dtInterval);
		document.removeEventListener('keydown', onArmedKey, true);
		document.removeEventListener('keyup', onArmedKeyUp, true);
	}

	function onArmedKey(e) {
		var k = e.keyCode || e.which;
		var ctrl = e.ctrlKey || e.metaKey;
		if (e.key === 'Escape') { disarm(); return; }
		if (k === 123 || (ctrl && e.shiftKey && [73, 74, 67, 75].indexOf(k) !== -1)) {
			block(e, 'devtools', 'Les outils de développement sont interdits !');
		} else if (ctrl && !e.shiftKey && k === 85) {
			block(e, 'source_code', 'La consultation du code source est interdite !');
		} else if (ctrl && k === 83) {
			block(e, 'save_as', 'L’enregistrement de la page est désactivé !');
		} else if (ctrl && k === 80) {
			block(e, 'print', 'L’impression est désactivée !');
		} else if (armed === 'keyboard') {
			return; // les autres touches ne concernent pas la démo
		}
	}

	function onArmedKeyUp(e) {
		if (e.key === 'PrintScreen' || e.keyCode === 44) {
			log('screenshot');
			showToast('st2', 'Captures d’écran désactivées — presse-papiers vidé !', 'Capture');
			try { if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText('').catch(function () {}); } } catch (err) {}
		}
	}

	function checkDevtools() {
		var open = (window.outerWidth - window.innerWidth > 160) || (window.outerHeight - window.innerHeight > 160);
		if (open) {
			log('devtools');
			showToast('st8', 'Outils de développement détectés — contenu flouté sur un vrai site !', 'DevTools');
			disarm();
		}
	}

	root.addEventListener('click', function (e) {
		var card = e.target && e.target.closest ? e.target.closest('[data-test]') : null;
		if (!card) { return; }
		var t = card.getAttribute('data-test');
		if (['key_u', 'key_s', 'key_p', 'key_f12', 'screenshot'].indexOf(t) !== -1) {
			e.preventDefault();
			arm('keyboard');
			showToast('st4', 'Test clavier ACTIF : essayez ' + card.querySelector('p').textContent.trim(), null);
		} else if (t === 'devtools') {
			e.preventDefault();
			arm('devtools');
			showToast('st4', 'Détection armée : ouvrez les outils de développement maintenant…', null);
		}
	});

	/* ---------- Galerie des 10 styles ---------- */
	var gallery = root.querySelector('.rcb-demo-gallery');
	if (gallery) {
		gallery.addEventListener('click', function (e) {
			var btn = e.target && e.target.closest ? e.target.closest('.rcb-demo-style-btn') : null;
			if (!btn) { return; }
			var st = btn.getAttribute('data-style');
			var msg = 'Message d’avertissement — style ' + st.replace('st', '') + ' : c’est ce que verra le visiteur.';
			if (st === 'st5') { msg = '⚠️ Contenu protégé — style Premium Or'; }
			if (st === 'st7') { msg = '⛔ ACCÈS REFUSÉ — style Néon Cyber'; }
			showToast(st, msg, null);
		});
	}
})();

/* ------------------------------------------------------------------
 * Modale de confirmation du formulaire de commande public (2.15.0)
 * ------------------------------------------------------------------ */
(function () {
	'use strict';
	var form = document.getElementById('rcb-commande');
	var modal = document.getElementById('rcb-shop-modal');
	if (!form || !modal || form.tagName !== 'FORM') { return; }

	var okBtn = document.getElementById('rcb-shop-confirm-ok');
	var cancelBtn = document.getElementById('rcb-shop-confirm-cancel');
	var bypass = false;

	function closeModal() {
		modal.hidden = true;
		var buyer = form.querySelector('[name="rcb_buyer"]');
		if (buyer) { buyer.focus(); }
	}

	form.addEventListener('submit', function (e) {
		if (bypass) { return; }
		if (!form.checkValidity()) { return; }
		e.preventDefault();
		var radio = form.querySelector('input[name="rcb_plan"]:checked');
		document.getElementById('rcb-shop-confirm-plan').textContent = radio ? (radio.getAttribute('data-label') || radio.value) : '—';
		var buyer = form.querySelector('[name="rcb_buyer"]');
		var email = form.querySelector('[name="rcb_email"]');
		document.getElementById('rcb-shop-confirm-buyer').textContent = buyer ? buyer.value : '—';
		document.getElementById('rcb-shop-confirm-email').textContent = email ? email.value : '—';
		document.getElementById('rcb-shop-confirm-price').textContent = radio ? (radio.getAttribute('data-price') || '—') : '—';
		modal.hidden = false;
		if (okBtn) { okBtn.focus(); }
	});

	if (okBtn) {
		okBtn.addEventListener('click', function () {
			modal.hidden = true;
			bypass = true;
			var submit = document.getElementById('rcb-shop-submit');
			if (submit) { submit.disabled = true; submit.textContent = '⏳ Envoi de la commande…'; }
			form.submit();
		});
	}
	if (cancelBtn) { cancelBtn.addEventListener('click', closeModal); }
	modal.addEventListener('click', function (e) { if (e.target === modal) { closeModal(); } });
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && !modal.hidden) { closeModal(); }
	});
})();
