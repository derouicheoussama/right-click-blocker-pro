/**
 * Bloc Gutenberg « Démo de protection — Infinity RCB Pro ».
 * Rendu dynamique côté serveur (render_callback → [rcb_demo]) : ce script
 * ne sert que dans l'éditeur. JavaScript natif wp.blocks, sans build.
 */
(function (wp) {
	'use strict';

	if (!wp || !wp.blocks || !wp.element) { return; }

	var el = wp.element.createElement;

	wp.blocks.registerBlockType('infinity-rcb/demo', {
		title: 'Démo de protection — Infinity RCB',
		description: 'Démo interactive : 11 tests de protection réels, journal en direct et galerie des 10 styles de messages.',
		icon: 'shield',
		category: 'widgets',
		keywords: ['protection', 'right click', 'clic droit', 'securite', 'demo', 'infinity rcb'],
		supports: { html: false },
		edit: function () {
			return el('div', { style: {
				display: 'flex', alignItems: 'center', gap: '14px',
				padding: '22px 24px', border: '2px dashed #1E6FF0', borderRadius: '12px',
				background: 'linear-gradient(135deg, rgba(30,111,240,.06), rgba(124,58,237,.08))'
			} },
				el('span', { style: {
					display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
					width: '52px', height: '52px', borderRadius: '12px', flexShrink: '0',
					background: 'linear-gradient(135deg, #1E6FF0, #7C3AED)', color: '#fff', fontSize: '24px'
				} }, '🛡️'),
				el('div', null,
					el('strong', { style: { fontSize: '15px', color: '#0B0F1E' } }, 'Démo de protection — Infinity RCB Pro'),
					el('p', { style: { margin: '4px 0 0', color: '#64748B', fontSize: '13px' } },
						'11 tests réels (clic droit, copie, DevTools…), journal en direct et 10 styles de messages.')
				)
			);
		},
		save: function () { return null; } // Rendu dynamique côté serveur.
	});
})(window.wp);
