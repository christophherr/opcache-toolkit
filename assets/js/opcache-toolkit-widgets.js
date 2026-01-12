document.addEventListener('DOMContentLoaded', () => {
	/* -------------------------------------------------------------
	 * 1. SIDEBAR SMOOTH SCROLLING
	 * ------------------------------------------------------------- */
	const navLinks = document.querySelectorAll('.opcache-toolkit-sidebar a');

	navLinks.forEach((link) => {
		link.addEventListener('click', (e) => {
			e.preventDefault();
			const target = document.querySelector(link.getAttribute('href'));
			if (!target) return;

			window.scrollTo({
				top: target.offsetTop - 20,
				behavior: 'smooth'
			});
		});
	});

	/* -------------------------------------------------------------
	 * 2. SCROLL‑SPY ACTIVE LINK
	 * ------------------------------------------------------------- */
	function activateLink(id) {
		navLinks.forEach((link) => {
			link.classList.toggle('active', link.getAttribute('href') === '#' + id);
		});
	}

	/* -------------------------------------------------------------
	 * 3. HIGHLIGHT BAR MOVEMENT
	 * ------------------------------------------------------------- */
	const highlightBar = document.querySelector(
		'.opcache-toolkit-sidebar .highlight-bar'
	);

	function moveHighlight(link) {
		if (!highlightBar || !link) return;

		const rect = link.getBoundingClientRect();
		const sidebarRect = link
			.closest('.opcache-toolkit-sidebar')
			.getBoundingClientRect();

		highlightBar.style.top = rect.top - sidebarRect.top + 'px';
		highlightBar.style.height = rect.height + 'px';
	}

	/* -------------------------------------------------------------
	 * 4. INTERSECTION OBSERVER ON META BOXES
	 * ------------------------------------------------------------- */
	const observer = new IntersectionObserver(
		(entries) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					activateLink(entry.target.id);
					const activeLink = document.querySelector(
						'.opcache-toolkit-sidebar a.active'
					);
					if (activeLink) moveHighlight(activeLink);
				}
			});
		},
		{
			rootMargin: '-40% 0px -40% 0px',
			threshold: 0
		}
	);

	// Observe WordPress meta boxes (postboxes) with IDs
	document
		.querySelectorAll('.postbox[id]')
		.forEach((section) => observer.observe(section));

	/* -------------------------------------------------------------
	 * 5. INITIAL HIGHLIGHT POSITION
	 * ------------------------------------------------------------- */
	let firstActive = document.querySelector('.opcache-toolkit-sidebar a.active');
	if (!firstActive && navLinks.length > 0) {
		firstActive = navLinks[0];
		firstActive.classList.add('active');
	}
	if (firstActive) moveHighlight(firstActive);
});
