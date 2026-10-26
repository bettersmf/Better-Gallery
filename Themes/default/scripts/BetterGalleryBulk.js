(() => {
	'use strict';

	const form = document.getElementById('bg_bulk_manage_form');
	if (!form)
		return;

	const boxes = Array.from(document.querySelectorAll('.bg_bulk_media_checkbox[form="bg_bulk_manage_form"]'));
	const count = document.getElementById('bg_bulk_count');
	const selectAll = document.getElementById('bg_bulk_select_all');
	const clear = document.getElementById('bg_bulk_clear');
	const deleteButton = document.getElementById('bg_bulk_delete');
	const moveButton = document.getElementById('bg_bulk_move');
	const destination = document.getElementById('bg_bulk_destination');

	if (!boxes.length || !count || !selectAll || !clear || !deleteButton)
		return;

	const selected = () => boxes.filter((box) => box.checked);

	const update = () => {
		const total = selected().length;

		count.textContent = total === 0 ? 'None selected' : `${total} selected`;
		deleteButton.disabled = total === 0;
		if (moveButton)
			moveButton.disabled = total === 0 || !destination || !destination.value;
		clear.disabled = total === 0;
		selectAll.disabled = total === boxes.length;

		boxes.forEach((box) => {
			const card = box.closest('.bg_media_card');
			if (card)
				card.classList.toggle('is-selected', box.checked);
		});
	};

	boxes.forEach((box) => box.addEventListener('change', update));

	selectAll.addEventListener('click', () => {
		boxes.forEach((box) => {
			box.checked = true;
		});
		update();
	});

	clear.addEventListener('click', () => {
		boxes.forEach((box) => {
			box.checked = false;
		});
		update();
	});

	if (destination)
		destination.addEventListener('change', update);

	form.addEventListener('submit', (event) => {
		const total = selected().length;

		if (total === 0) {
			event.preventDefault();
			update();
			return;
		}

		const submitter = event.submitter || null;

		if (submitter && submitter.id === 'bg_bulk_move') {
			if (!destination || !destination.value) {
				event.preventDefault();
				update();
				return;
			}

			const albumName = destination.options[destination.selectedIndex]?.text || '';
			const template = form.dataset.moveConfirmTemplate || 'Move %d selected media items to %s?';
			const confirmation = template.replace('%d', String(total)).replace('%s', albumName);

			if (!window.confirm(confirmation))
				event.preventDefault();

			return;
		}

		const template = form.dataset.confirmTemplate || 'Delete %d selected media files? This cannot be undone.';
		const confirmation = template.replace('%d', String(total));

		if (!window.confirm(confirmation))
			event.preventDefault();
	});

	update();
})();
