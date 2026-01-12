/**
 * Widgets Entry Point (Scroll-spy, Tooltips, etc).
 *
 * @package
 */

import { initSmoothScrolling, initScrollSpy, initTooltips } from '../utils/dom';

document.addEventListener('DOMContentLoaded', () => {
	const highlightBar = document.querySelector(
		'.opcache-toolkit-sidebar .highlight-bar'
	);

	initSmoothScrolling('.opcache-toolkit-sidebar a');
	initScrollSpy('.postbox[id]', '.opcache-toolkit-sidebar a', (link) => {
		if (!highlightBar) return;
		const rect = link.getBoundingClientRect();
		const sidebarRect = link
			.closest('.opcache-toolkit-sidebar')
			.getBoundingClientRect();
		highlightBar.style.top = rect.top - sidebarRect.top + 'px';
		highlightBar.style.height = rect.height + 'px';
	});
	initTooltips('[data-opcache-tooltip]');
});
