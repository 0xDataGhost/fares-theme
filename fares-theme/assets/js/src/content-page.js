/**
 * Content-page enhancements — table of contents, scroll-spy, reading progress.
 *
 * Progressive enhancement: the page is fully usable without JS. When the
 * article has enough sections, we build a TOC from its <h2>s, highlight the
 * section in view (IntersectionObserver), and drive a top reading-progress bar.
 */

(function () {
	'use strict';

	const MIN_HEADINGS = 3;

	const body = document.querySelector('[data-page-body]');
	const article = document.querySelector('[data-page-article]');
	const toc = document.querySelector('[data-page-toc]');
	const tocList = document.querySelector('[data-page-toc-list]');
	const progress = document.querySelector('[data-page-progress]');

	if (article) {
		buildToc();
	}
	if (progress) {
		initProgress();
	}

	/** Build the TOC and wire scroll-spy, or leave the page untouched. */
	function buildToc() {
		if (!toc || !tocList) {
			return;
		}

		const headings = Array.from(article.querySelectorAll('h2'));
		if (headings.length < MIN_HEADINGS) {
			return;
		}

		const links = new Map();

		headings.forEach(function (heading, index) {
			if (!heading.id) {
				heading.id = 'section-' + (index + 1);
			}

			const li = document.createElement('li');
			const link = document.createElement('a');
			link.className = 'fares-page__toc-link';
			link.href = '#' + heading.id;
			link.textContent = heading.textContent;
			li.appendChild(link);
			tocList.appendChild(li);
			links.set(heading.id, link);
		});

		toc.hidden = false;
		if (body) {
			body.classList.add('fares-page__body--with-toc');
		}

		spy(headings, links);
	}

	/** Highlight the TOC entry for the section nearest the top of the viewport. */
	function spy(headings, links) {
		if (!('IntersectionObserver' in window)) {
			return;
		}

		const visible = new Set();

		const observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						visible.add(entry.target.id);
					} else {
						visible.delete(entry.target.id);
					}
				});

				let activeId = null;
				for (const heading of headings) {
					if (visible.has(heading.id)) {
						activeId = heading.id;
						break;
					}
				}

				links.forEach(function (link, id) {
					link.classList.toggle('is-active', id === activeId);
				});
			},
			{ rootMargin: '-20% 0px -70% 0px', threshold: 0 }
		);

		headings.forEach(function (heading) {
			observer.observe(heading);
		});
	}

	/** Drive the reading-progress bar from scroll position (rAF-throttled). */
	function initProgress() {
		let ticking = false;

		function update() {
			const doc = document.documentElement;
			const scrollable = doc.scrollHeight - doc.clientHeight;
			const ratio = scrollable > 0 ? doc.scrollTop / scrollable : 0;
			progress.style.inlineSize = Math.min(100, Math.max(0, ratio * 100)) + '%';
			ticking = false;
		}

		function onScroll() {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(update);
			}
		}

		window.addEventListener('scroll', onScroll, { passive: true });
		window.addEventListener('resize', onScroll, { passive: true });
		update();
	}
})();
