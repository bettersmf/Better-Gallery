(() => {
	'use strict';

	const form = document.getElementById('bg_media_uploader');
	if (!form || !window.FormData || !window.XMLHttpRequest)
		return;

	const dropzone = document.getElementById('bg_upload_dropzone');
	const input = document.getElementById('bg_media_files');
	const queueEl = document.getElementById('bg_upload_queue');
	const notice = document.getElementById('bg_upload_notice');
	const startButton = document.getElementById('bg_upload_start');
	const startButtonTop = document.getElementById('bg_upload_start_top');
	const topActions = document.getElementById('bg_upload_top_actions');
	const bottomActions = document.getElementById('bg_upload_bottom_actions');
	const clearButton = document.getElementById('bg_upload_clear');
	const overall = document.getElementById('bg_upload_overall');
	const overallProgress = document.getElementById('bg_upload_overall_progress');
	const overallBar = document.getElementById('bg_upload_overall_bar');
	const overallStatus = document.getElementById('bg_upload_overall_status');
	const overallCount = document.getElementById('bg_upload_overall_count');

	if (!dropzone || !input || !queueEl || !startButton || !startButtonTop || !topActions || !bottomActions || !clearButton || !overall || !overallProgress || !overallBar || !overallStatus || !overallCount)
		return;

	form.classList.add('is-enhanced');

	const progressMode = form.dataset.progressMode === 'per_file' ? 'per_file' : 'compact';
	form.classList.toggle('is-compact-progress', progressMode === 'compact');
	form.classList.toggle('is-per-file-progress', progressMode === 'per_file');

	const maxFiles = Math.max(1, parseInt(form.dataset.maxFiles || '10', 10));
	const maxImageBytes = Math.max(1, parseInt(form.dataset.maxImageBytes || '10485760', 10));
	const maxVideoBytes = Math.max(1, parseInt(form.dataset.maxVideoBytes || '262144000', 10));
	const maxFileBytes = Math.max(1, parseInt(form.dataset.maxFileBytes || '262144000', 10));
	const allowedExtensions = new Set((form.dataset.allowedExtensions || '')
		.toLowerCase()
		.split(',')
		.map((ext) => ext.trim().replace(/^\./, ''))
		.filter(Boolean));
	const phpFileCeiling = Math.max(0, parseInt(form.dataset.phpFileCeiling || '0', 10));
	const storageQuotaBytes = Math.max(0, parseInt(form.dataset.storageQuotaBytes || '0', 10));
	const storageUnlimited = form.dataset.storageUnlimited === '1' || storageQuotaBytes === 0;
	let storageRemainingBytes = Math.max(0, parseInt(form.dataset.storageRemainingBytes || '0', 10));

	let queue = [];
	let running = false;
	let sequence = 0;

	const formatBytes = (bytes) => {
		if (!Number.isFinite(bytes) || bytes < 1)
			return '0 B';
		const units = ['B', 'KB', 'MB', 'GB'];
		let value = bytes;
		let unit = 0;
		while (value >= 1024 && unit < units.length - 1) {
			value /= 1024;
			unit++;
		}
		return `${value >= 10 || unit === 0 ? value.toFixed(0) : value.toFixed(1)} ${units[unit]}`;
	};

	const getExtension = (file) => {
		const name = (file.name || '').toLowerCase();
		const dot = name.lastIndexOf('.');
		return dot >= 0 ? name.slice(dot + 1) : '';
	};

	const imageExtensions = new Set(['jpg', 'jpeg', 'png', 'gif', 'webp']);
	const videoExtensions = new Set(['mp4', 'webm', 'mov', 'mkv']);
	const audioExtensions = new Set(['mp3', 'wav', 'ogg', 'flac']);
	const archiveExtensions = new Set(['zip', 'rar', '7z']);

	const fileKind = (file) => {
		const ext = getExtension(file);
		if (imageExtensions.has(ext)) return 'Image';
		if (videoExtensions.has(ext)) return 'Video';
		if (audioExtensions.has(ext)) return 'Audio';
		if (archiveExtensions.has(ext)) return 'Archive';
		return 'File';
	};

	const isVideo = (file) => videoExtensions.has(getExtension(file));
	const isImage = (file) => imageExtensions.has(getExtension(file));

	const validateFile = (file) => {
		const ext = getExtension(file);

		if (!ext || !allowedExtensions.has(ext))
			return `.${ext || '?'} files are not supported by Better Gallery. Images and video only.`;

		const configuredLimit = isVideo(file)
			? maxVideoBytes
			: (isImage(file) ? maxImageBytes : maxFileBytes);
		let effectiveLimit = configuredLimit;

		if (phpFileCeiling > 0)
			effectiveLimit = Math.min(effectiveLimit, phpFileCeiling);

		if (file.size > effectiveLimit)
			return `File is ${formatBytes(file.size)}; the current limit is ${formatBytes(effectiveLimit)}.`;

		return '';
	};

	const setNotice = (message, isError = false) => {
		notice.textContent = message || '';
		notice.classList.toggle('errorbox', !!isError);
		notice.classList.toggle('information', !!message && !isError);
	};

	const setOverallProgress = (percent, status = '', completed = 0, total = 0) => {
		const safePercent = Math.max(0, Math.min(100, Math.round(Number(percent) || 0)));
		overallBar.style.width = `${safePercent}%`;
		overallProgress.setAttribute('aria-valuenow', String(safePercent));
		overallStatus.textContent = status || 'Ready to upload.';
		overallCount.textContent = total > 0 ? `${completed} / ${total} complete` : '';
	};

	const updateButtons = () => {
		const uploadable = queue.some((item) => item.state === 'queued' || item.state === 'error');
		const disabled = running || !uploadable;
		startButton.disabled = disabled;
		startButtonTop.disabled = disabled;
		clearButton.disabled = running || queue.length === 0;
		const hasQueue = queue.length > 0;
		topActions.hidden = !hasQueue;
		bottomActions.hidden = !hasQueue;
		overall.hidden = !hasQueue || progressMode !== 'compact';
	};

	const renderItem = (item) => {
		const row = document.createElement('div');
		row.className = 'bg_upload_item';
		row.dataset.queueId = String(item.id);

		const name = document.createElement('div');
		name.className = 'bg_upload_item_name';
		name.textContent = item.file.name;

		const meta = document.createElement('div');
		meta.className = 'bg_upload_item_meta';
		meta.textContent = `${fileKind(item.file)} · ${formatBytes(item.file.size)}`;

		const progress = document.createElement('div');
		progress.className = 'bg_upload_progress';
		progress.setAttribute('role', 'progressbar');
		progress.setAttribute('aria-valuemin', '0');
		progress.setAttribute('aria-valuemax', '100');
		progress.setAttribute('aria-valuenow', '0');

		const bar = document.createElement('div');
		bar.className = 'bg_upload_progress_bar';
		progress.appendChild(bar);

		const status = document.createElement('div');
		status.className = 'bg_upload_item_status';
		status.textContent = item.message || 'Queued';

		row.append(name, meta, progress, status);
		queueEl.appendChild(row);

		item.row = row;
		item.bar = bar;
		item.progress = progress;
		item.status = status;
	};

	const setItemState = (item, state, message, percent = null) => {
		item.state = state;
		item.message = message || '';

		if (!item.row)
			return;

		item.row.classList.toggle('is-uploading', state === 'uploading');
		item.row.classList.toggle('is-complete', state === 'complete');
		item.row.classList.toggle('is-error', state === 'error');

		if (item.status)
			item.status.textContent = item.message || state;

		if (percent !== null && item.bar && item.progress) {
			const safePercent = Math.max(0, Math.min(100, Math.round(percent)));
			item.bar.style.width = `${safePercent}%`;
			item.progress.setAttribute('aria-valuenow', String(safePercent));
		}
	};

	const addFiles = (files) => {
		if (running)
			return;

		setNotice('');

		const incoming = Array.from(files || []);
		if (!incoming.length)
			return;

		const remainingSlots = Math.max(0, maxFiles - queue.length);
		if (remainingSlots === 0) {
			setNotice(`This upload queue is limited to ${maxFiles} files.`, true);
			return;
		}

		if (incoming.length > remainingSlots)
			setNotice(`Only the first ${remainingSlots} file${remainingSlots === 1 ? '' : 's'} were added because this queue is limited to ${maxFiles}.`, true);

		let queuedBytes = queue
			.filter((item) => item.state === 'queued' || item.state === 'uploading')
			.reduce((sum, item) => sum + (item.file.size || 0), 0);

		incoming.slice(0, remainingSlots).forEach((file) => {
			let error = validateFile(file);

			if (!error && !storageUnlimited && file.size > Math.max(0, storageRemainingBytes - queuedBytes))
				error = `This file would exceed your remaining gallery storage (${formatBytes(Math.max(0, storageRemainingBytes - queuedBytes))} available).`;

			const item = {
				id: ++sequence,
				file,
				state: error ? 'error' : 'queued',
				message: error || 'Queued',
				row: null,
				bar: null,
				progress: null,
				status: null,
			};

			queue.push(item);
			renderItem(item);

			if (!error)
				queuedBytes += file.size || 0;

			if (error)
				setItemState(item, 'error', error, 0);
		});

		input.value = '';
		updateButtons();
	};

	const uploadOne = (item, itemIndex, totalItems, completedBefore) => new Promise((resolve) => {
		const data = new FormData();
		data.append('bg_media_files[]', item.file, item.file.name);
		data.append('ajax', '1');

		const sessionInput = form.querySelector('input[type="hidden"]');
		if (sessionInput && sessionInput.name)
			data.append(sessionInput.name, sessionInput.value);

		const xhr = new XMLHttpRequest();
		const separator = form.action.includes('?') ? '&' : '?';
		xhr.open('POST', `${form.action}${separator}ajax=1`, true);
		xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		xhr.responseType = 'json';

		xhr.upload.addEventListener('progress', (event) => {
			if (!event.lengthComputable)
				return;
			const filePercent = (event.loaded / event.total) * 100;
			setItemState(item, 'uploading', 'Uploading…', filePercent);
			if (progressMode === 'compact') {
				const overallPercent = ((completedBefore + (filePercent / 100)) / totalItems) * 100;
				setOverallProgress(
					overallPercent,
					`Processing ${itemIndex + 1} of ${totalItems}: ${item.file.name}`,
					completedBefore,
					totalItems
				);
			}
		});

		xhr.addEventListener('load', () => {
			let response = xhr.response;

			if (!response && xhr.responseText) {
				try {
					response = JSON.parse(xhr.responseText);
				} catch (e) {
					response = null;
				}
			}

			if (xhr.status >= 200 && xhr.status < 300 && response && response.success) {
				setItemState(item, 'complete', 'Complete', 100);

				if (response.storage && !response.storage.unlimited)
					storageRemainingBytes = Math.max(0, parseInt(response.storage.remaining_bytes || '0', 10));

				resolve(true);
				return;
			}

			const message = response && response.message
				? response.message
				: `Upload failed (HTTP ${xhr.status || 0}).`;
			setItemState(item, 'error', message, 0);
			resolve(false);
		});

		xhr.addEventListener('error', () => {
			setItemState(item, 'error', 'Network error while uploading.', 0);
			resolve(false);
		});

		xhr.addEventListener('abort', () => {
			setItemState(item, 'error', 'Upload cancelled.', 0);
			resolve(false);
		});

		setItemState(item, 'uploading', 'Starting upload…', 0);
		if (progressMode === 'compact')
			setOverallProgress((completedBefore / totalItems) * 100, `Processing ${itemIndex + 1} of ${totalItems}: ${item.file.name}`, completedBefore, totalItems);
		xhr.send(data);
	});

	const runQueue = async () => {
		if (running)
			return;

		const pending = queue.filter((item) => item.state === 'queued' || item.state === 'error');
		const valid = pending.filter((item) => !validateFile(item.file));

		if (!valid.length) {
			setNotice('There are no valid files ready to upload.', true);
			return;
		}

		running = true;
		updateButtons();
		setNotice(`Uploading ${valid.length} file${valid.length === 1 ? '' : 's'}…`);
		if (progressMode === 'compact')
			setOverallProgress(0, `Preparing ${valid.length} file${valid.length === 1 ? '' : 's'}…`, 0, valid.length);

		let completed = 0;
		let failed = 0;

		for (let itemIndex = 0; itemIndex < valid.length; itemIndex++) {
			const item = valid[itemIndex];
			const validationError = validateFile(item.file);
			if (validationError) {
				setItemState(item, 'error', validationError, 0);
				failed++;
				continue;
			}

			const processedBefore = completed + failed;
			const ok = await uploadOne(item, itemIndex, valid.length, processedBefore);
			if (ok)
				completed++;
			else
				failed++;

			if (progressMode === 'compact') {
				const processed = completed + failed;
				setOverallProgress(
					(processed / valid.length) * 100,
					processed < valid.length
						? `${completed} complete, ${failed} failed.`
						: `${completed} complete, ${failed} failed.`,
					processed,
					valid.length
				);
			}
		}

		running = false;
		updateButtons();

		if (failed === 0) {
			setNotice(`${completed} file${completed === 1 ? '' : 's'} uploaded successfully. Refreshing album…`);
			window.setTimeout(() => window.location.reload(), 650);
		} else {
			setNotice(`${completed} completed, ${failed} failed. Failed files remain in the queue so you can review the error and retry.`, true);
		}
	};

	dropzone.addEventListener('click', () => {
		if (!running)
			input.click();
	});

	dropzone.addEventListener('keydown', (event) => {
		if ((event.key === 'Enter' || event.key === ' ') && !running) {
			event.preventDefault();
			input.click();
		}
	});

	input.addEventListener('change', () => addFiles(input.files));

	['dragenter', 'dragover'].forEach((eventName) => {
		dropzone.addEventListener(eventName, (event) => {
			event.preventDefault();
			event.stopPropagation();
			if (!running)
				dropzone.classList.add('is-dragover');
		});
	});

	['dragleave', 'drop'].forEach((eventName) => {
		dropzone.addEventListener(eventName, (event) => {
			event.preventDefault();
			event.stopPropagation();
			dropzone.classList.remove('is-dragover');
		});
	});

	dropzone.addEventListener('drop', (event) => {
		if (!running && event.dataTransfer)
			addFiles(event.dataTransfer.files);
	});

	form.addEventListener('submit', (event) => {
		event.preventDefault();
		runQueue();
	});

	startButtonTop.addEventListener('click', () => {
		runQueue();
	});

	clearButton.addEventListener('click', () => {
		if (running)
			return;
		queue = [];
		queueEl.textContent = '';
		setNotice('');
		setOverallProgress(0, 'Ready to upload.', 0, 0);
		updateButtons();
	});

	updateButtons();
})();
