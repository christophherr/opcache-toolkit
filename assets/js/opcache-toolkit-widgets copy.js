document.addEventListener('DOMContentLoaded', () => {
	/* -------------------------------------------------------------
	 * 0. RESTORE WIDGET ORDER FIRST
	 * ------------------------------------------------------------- */
	const widgetContainer = document.querySelector('#opcache-toolkit-widgets');

	if (widgetContainer) {
		const savedOrder = JSON.parse(
			localStorage.getItem('opcache-toolkit-widget-order') || '[]'
		);

		if (savedOrder.length) {
			savedOrder.forEach((id) => {
				const el = document.getElementById(id);
				if (el) widgetContainer.appendChild(el);
			});
		}
	}

	/* -------------------------------------------------------------
	 * 1. COLLAPSIBLE WIDGETS + LOCALSTORAGE
	 * ------------------------------------------------------------- */
	document.querySelectorAll('[data-widget]').forEach((widget) => {
		const id = widget.id;
		const header = widget.querySelector('.opcache-toolkit-widget-header');
		const toggleBtn = widget.querySelector('.opcache-toolkit-toggle');
		const body = widget.querySelector('.opcache-toolkit-widget-body');

		// Restore collapsed state
		if (localStorage.getItem('opcache-toolkit-widget-' + id) === 'collapsed') {
			widget.classList.add('collapsed');
			body.style.maxHeight = '0px';
		}

		function toggleWidget() {
			if (widget.classList.contains('collapsed')) {
				// EXPAND
				widget.classList.remove('collapsed');
				body.style.maxHeight = body.scrollHeight + 'px';

				setTimeout(() => {
					body.style.maxHeight = '1000px';
				}, 250);

				localStorage.removeItem('opcache-toolkit-widget-' + id);
			} else {
				// COLLAPSE
				body.style.maxHeight = body.scrollHeight + 'px';
				void body.offsetHeight; // force reflow
				body.style.maxHeight = '0px';

				widget.classList.add('collapsed');
				localStorage.setItem('opcache-toolkit-widget-' + id, 'collapsed');
			}
		}

		// Toggle button only
		toggleBtn.addEventListener('click', (e) => {
			e.stopPropagation();
			toggleWidget();
		});

		// Clicking header (except handle) toggles
		header.addEventListener('click', (e) => {
			if (e.target.closest('.opcache-toolkit-drag-handle')) return;
			if (e.target.closest('.opcache-toolkit-toggle')) return;
			toggleWidget();
		});
	});

	/* -------------------------------------------------------------
	 * 2. SIDEBAR SMOOTH SCROLLING
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
	 * 3. SCROLL‑SPY ACTIVE LINK
	 * ------------------------------------------------------------- */
	const sections = document.querySelectorAll('.opcache-toolkit-widget[id]');

	function activateLink(id) {
		navLinks.forEach((link) => {
			link.classList.toggle('active', link.getAttribute('href') === '#' + id);
		});
	}

	/* -------------------------------------------------------------
	 * 4. HIGHLIGHT BAR MOVEMENT
	 * ------------------------------------------------------------- */
	const highlightBar = document.querySelector(
		'.opcache-toolkit-sidebar .highlight-bar'
	);

	function moveHighlight(link) {
		const rect = link.getBoundingClientRect();
		const sidebarRect = link
			.closest('.opcache-toolkit-sidebar')
			.getBoundingClientRect();

		highlightBar.style.top = rect.top - sidebarRect.top + 'px';
		highlightBar.style.height = rect.height + 'px';
	}

	/* -------------------------------------------------------------
	 * 5. INTERSECTION OBSERVER
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

	sections.forEach((section) => observer.observe(section));

	/* -------------------------------------------------------------
	 * 6. INITIAL HIGHLIGHT POSITION
	 * ------------------------------------------------------------- */
	const firstActive = document.querySelector(
		'.opcache-toolkit-sidebar a.active'
	);
	if (firstActive) moveHighlight(firstActive);

	/* -------------------------------------------------------------
	 * 7. DRAG‑AND‑DROP WIDGET ORDER + LOCALSTORAGE
	 * ------------------------------------------------------------- */
	if (widgetContainer) {
		let dragAllowed = false;
		let draggingEl = null;

		const widgets = widgetContainer.querySelectorAll('.opcache-toolkit-widget');

		widgets.forEach((widget) => {
			const handle = widget.querySelector('.opcache-toolkit-drag-handle');

			// Disable dragging by default
			widget.setAttribute('draggable', 'false');

			// Enable dragging ONLY when grabbing the handle
			handle.addEventListener('mousedown', () => {
				dragAllowed = true;
				widget.setAttribute('draggable', 'true');
			});

			widget.addEventListener('dragstart', (e) => {
				if (!dragAllowed) {
					e.preventDefault();
					return;
				}
				draggingEl = widget;
				widget.classList.add('dragging');
			});

			widget.addEventListener('dragend', () => {
				widget.classList.remove('dragging');
				widget.setAttribute('draggable', 'false');
				dragAllowed = false;
				draggingEl = null;

				// Save order
				const order = [
					...widgetContainer.querySelectorAll('.opcache-toolkit-widget')
				].map((w) => w.id);
				localStorage.setItem(
					'opcache-toolkit-widget-order',
					JSON.stringify(order)
				);
			});
		});

		widgetContainer.addEventListener('dragover', (e) => {
			e.preventDefault();
			const dragging = document.querySelector('.dragging');
			if (!dragging) return;

			const widgets = [
				...widgetContainer.querySelectorAll(
					'.opcache-toolkit-widget:not(.dragging)'
				)
			];

			let closest = null;
			let closestDistance = Infinity;

			widgets.forEach((widget) => {
				const rect = widget.getBoundingClientRect();
				const dx = e.clientX - (rect.left + rect.width / 2);
				const dy = e.clientY - (rect.top + rect.height / 2);
				const distance = Math.sqrt(dx * dx + dy * dy);

				if (distance < closestDistance) {
					closestDistance = distance;
					closest = widget;
				}
			});

			if (!closest) return;

			const rect = closest.getBoundingClientRect();
			const isAfter = e.clientY > rect.top + rect.height / 2;

			if (isAfter) {
				widgetContainer.insertBefore(dragging, closest.nextSibling);
			} else {
				widgetContainer.insertBefore(dragging, closest);
			}
		});
	}

	/* -------------------------------------------------------------
	 * 8. RESET LAYOUT BUTTON
	 * ------------------------------------------------------------- */
	const resetBtn = document.getElementById('opcache-toolkit-reset-layout');
	if (resetBtn) {
		resetBtn.addEventListener('click', () => {
			localStorage.removeItem('opcache-toolkit-widget-order');

			document.querySelectorAll('[data-widget]').forEach((widget) => {
				localStorage.removeItem('opcache-toolkit-widget-' + widget.id);
			});

			location.reload();
		});
	}
});
