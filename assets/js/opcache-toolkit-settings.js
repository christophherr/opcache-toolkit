// OPcache Toolkit – Settings Page JS

document.addEventListener('DOMContentLoaded', () => {
	/* -------------------------------------------------------------
	 * TABS
	 * ------------------------------------------------------------- */
	const tabs = document.querySelectorAll('.opcache-toolkit-tabs a');
	const sections = document.querySelectorAll('.opcache-toolkit-tab');

	tabs.forEach((tab) => {
		tab.addEventListener('click', (e) => {
			e.preventDefault();

			tabs.forEach((t) => t.classList.remove('active'));
			sections.forEach((s) => s.classList.remove('active'));

			tab.classList.add('active');
			document
				.getElementById('opcache-toolkit-tab-' + tab.dataset.tab)
				.classList.add('active');
		});
	});
});
