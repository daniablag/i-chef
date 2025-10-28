document.addEventListener('DOMContentLoaded', function () {
	const section = document.querySelector('.related.products');
	const list = section?.querySelector('ul.products');
	const items = Array.from(list?.querySelectorAll('li.product') || []);

	if (!section || !list || items.length === 0) return;

	const leftArrow = document.createElement('div');
	const rightArrow = document.createElement('div');
	leftArrow.className = 'related-nav left';
	rightArrow.className = 'related-nav right';
	leftArrow.innerHTML = '&#10094;';
	rightArrow.innerHTML = '&#10095;';
	section.appendChild(leftArrow);
	section.appendChild(rightArrow);

	let scrollLocked = false;

	function getCurrentVisibleIndex() {
		const scrollLeft = list.scrollLeft;
		let closestIndex = 0;
		let minDiff = Infinity;

		items.forEach((item, index) => {
			const diff = Math.abs(item.offsetLeft - scrollLeft);
			if (diff < minDiff) {
				minDiff = diff;
				closestIndex = index;
			}
		});
		return closestIndex;
	}

	function scrollToItem(index) {
		if (!items[index]) return;
		scrollLocked = true;
		list.scrollTo({
			left: items[index].offsetLeft,
			behavior: 'smooth'
		});
		setTimeout(() => {
			scrollLocked = false;
		}, 400);
	}

	leftArrow.addEventListener('click', () => {
		if (scrollLocked) return;
		const current = getCurrentVisibleIndex();
		let prev = current - 1;
		if (prev < 0) prev = items.length - 1;
		scrollToItem(prev);
	});

	rightArrow.addEventListener('click', () => {
		if (scrollLocked) return;

		const currentScroll = list.scrollLeft;
		const maxScroll = list.scrollWidth - list.clientWidth;

		if (currentScroll + 10 >= maxScroll) {
			scrollToItem(0);
			return;
		}

		const current = getCurrentVisibleIndex();
		const next = Math.min(current + 1, items.length - 1);
		scrollToItem(next);
	});
});
