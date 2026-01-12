/**
 * Settings Entry Point.
 *
 * @package
 */

document.addEventListener('DOMContentLoaded', () => {
	// Logic from opcache-toolkit-settings.js.
	const tabs = document.querySelectorAll('.opcache-toolkit-tabs a');
	const tabContents = document.querySelectorAll('.opcache-toolkit-tab');

	tabs.forEach((tab) => {
		tab.addEventListener('click', (e) => {
			e.preventDefault();
			const target = tab.getAttribute('data-tab');

			tabs.forEach((t) => t.classList.remove('active'));
			tab.classList.add('active');

			tabContents.forEach((content) => {
				content.classList.toggle(
					'active',
					content.id === `opcache-toolkit-tab-${target}`
				);
			});
		});
	});

	// console.log( 'OPcache Toolkit Settings Initialized' );
});
