/**
 * DOM Utility Helpers.
 *
 * @package
 */

/**
 * Initialize smooth scrolling for sidebar links.
 *
 * @param {string} selector - Sidebar links selector.
 * @return {void}
 */
export function initSmoothScrolling(selector) {
	const navLinks = document.querySelectorAll(selector);

	navLinks.forEach((link) => {
		link.addEventListener('click', (e) => {
			e.preventDefault();
			const targetId = link.getAttribute('href');
			const target = document.querySelector(targetId);
			if (!target) return;

			window.scrollTo({
				top: target.offsetTop - 20,
				behavior: 'smooth'
			});
		});
	});
}

/**
 * Format bytes into human-readable string.
 *
 * @param {number} bytes - Bytes to format.
 * @return {string} Formatted string.
 */
export function formatBytes(bytes) {
	if (bytes === 0) return '0 B';
	const k = 1024;
	const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
	const i = Math.floor(Math.log(bytes) / Math.log(k));
	return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Smoothly scroll to a selector.
 *
 * @param {string} selector - Target selector.
 * @param {number} offset   - Optional vertical offset.
 * @return {void}
 */
export function smoothScrollTo(selector, offset = 0) {
	const element = document.querySelector(selector);
	if (!element) return;

	const top = element.getBoundingClientRect().top + window.pageYOffset - offset;
	window.scrollTo({
		top,
		behavior: 'smooth'
	});
}

/**
 * Initialize scroll-spy for highlighting active sidebar links.
 *
 * @param {string}   sectionSelector - Meta boxes selector.
 * @param {string}   linkSelector    - Sidebar links selector.
 * @param {Function} onActivate      - Callback when a link is activated.
 * @return {void}
 */
export function initScrollSpy(sectionSelector, linkSelector, onActivate) {
	const navLinks = document.querySelectorAll(linkSelector);

	const activateLink = (id) => {
		navLinks.forEach((link) => {
			const isActive = link.getAttribute('href') === '#' + id;
			link.classList.toggle('active', isActive);
			if (isActive && onActivate) {
				onActivate(link);
			}
		});
	};

	const observer = new IntersectionObserver(
		(entries) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					activateLink(entry.target.id);
				}
			});
		},
		{
			rootMargin: '-40% 0px -40% 0px',
			threshold: 0
		}
	);

	document
		.querySelectorAll(sectionSelector)
		.forEach((section) => observer.observe(section));

	// Initial position.
	let firstActive = document.querySelector(linkSelector + '.active');
	if (!firstActive && navLinks.length > 0) {
		firstActive = navLinks[0];
		firstActive.classList.add('active');
	}
	if (firstActive && onActivate) {
		onActivate(firstActive);
	}
}

/**
 * Initialize tooltips based on data attributes.
 *
 * @param {string} selector - Tooltip trigger selector.
 * @return {void}
 */
export function initTooltips(selector) {
	const tooltip = document.createElement('div');
	tooltip.className = 'opcache-toolkit-tooltip';
	document.body.appendChild(tooltip);

	document.querySelectorAll(selector).forEach((el) => {
		el.addEventListener('mouseenter', (e) => {
			tooltip.textContent = e.target.getAttribute('data-opcache-tooltip');
			tooltip.style.display = 'block';
			const rect = e.target.getBoundingClientRect();
			tooltip.style.top =
				rect.top - tooltip.offsetHeight - 10 + window.scrollY + 'px';
			tooltip.style.left =
				rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + 'px';
		});

		el.addEventListener('mouseleave', () => {
			tooltip.style.display = 'none';
		});
	});
}
