(() => {
	'use strict';

	const box = document.getElementById('bg_lightbox');
	if (!box || !window.fetch)
		return;

	const openers = Array.from(document.querySelectorAll('.bg_media_open[data-media-id]'));
	if (!openers.length)
		return;

	const stage = document.getElementById('bg_lightbox_stage');
	const title = document.getElementById('bg_lightbox_title');
	const description = document.getElementById('bg_lightbox_description');
	const integrations = document.getElementById('bg_lightbox_integrations');
	const fullSizeLink = document.getElementById('bg_full_size_link');
	const downloadLink = document.getElementById('bg_download_link');
	const favoriteButton = document.getElementById('bg_lightbox_favorite');
	const reportButton = document.getElementById('bg_lightbox_report');
	const reportPanel = document.getElementById('bg_report_panel');
	const reportForm = document.getElementById('bg_report_form');
	const reportTargetType = document.getElementById('bg_report_target_type');
	const reportCommentId = document.getElementById('bg_report_comment_id');
	const reportReason = document.getElementById('bg_report_reason');
	const reportNote = document.getElementById('bg_report_note');
	const reportCancel = document.getElementById('bg_report_cancel');
	const likeWrap = document.getElementById('bg_lightbox_like_wrap');
	const likeButton = document.getElementById('bg_lightbox_like');
	const likeCount = document.getElementById('bg_like_count');
	const reactionSummary = document.getElementById('bg_lightbox_reaction_summary');
	const commentsPanel = document.getElementById('bg_comments_panel');
	const commentsList = document.getElementById('bg_comments_list');
	const commentForm = document.getElementById('bg_comment_form');
	const commentBody = document.getElementById('bg_comment_body');
	const message = document.getElementById('bg_lightbox_message');
	const prevButton = box.querySelector('[data-bg-prev]');
	const nextButton = box.querySelector('[data-bg-next]');
	const sessionVar = box.dataset.sessionVar || '';
	const sessionId = box.dataset.sessionId || '';

	let currentIndex = -1;
	let currentMediaId = 0;
	let lastFocus = null;
	let betterReactions = null;
	let mediaReactionTray = null;
	let mediaTrayCloseTimer = 0;

	const esc = (s) => String(s ?? '');

	const endpoint = (sa, media = 0) => {
		const base = window.location.pathname + window.location.search.split('?')[0];
		return `${base}?action=gallery;sa=${encodeURIComponent(sa)}${media ? `;media=${media}` : ''}`;
	};

	const setMessage = (text, error = false) => {
		message.textContent = text || '';
		message.classList.toggle('errorbox', !!text && error);
		message.classList.toggle('information', !!text && !error);
	};

	const post = async (sa, data) => {
		const body = new FormData();
		Object.entries(data || {}).forEach(([k, v]) => body.append(k, v));
		if (sessionVar)
			body.append(sessionVar, sessionId);

		const response = await fetch(endpoint(sa), {
			method: 'POST',
			body,
			credentials: 'same-origin',
			headers: {'X-Requested-With': 'XMLHttpRequest'}
		});
		return response.json();
	};




	const positionFloatingMediaTray = () => {
		if (!mediaReactionTray || !likeButton || box.hidden)
			return;

		const triggerRect = likeButton.getBoundingClientRect();
		const trayRect = mediaReactionTray.getBoundingClientRect();
		const edge = 8;
		let left = triggerRect.left;
		let top = triggerRect.top - trayRect.height - 6;

		if (left + trayRect.width > window.innerWidth - edge)
			left = Math.max(edge, window.innerWidth - trayRect.width - edge);
		else
			left = Math.max(edge, left);

		if (top < edge)
			top = Math.min(window.innerHeight - trayRect.height - edge, triggerRect.bottom + 6);

		mediaReactionTray.style.left = `${Math.round(left)}px`;
		mediaReactionTray.style.top = `${Math.round(top)}px`;
	};

	const openFloatingMediaTray = () => {
		if (!mediaReactionTray || box.hidden)
			return;

		window.clearTimeout(mediaTrayCloseTimer);
		if (mediaReactionTray.parentNode !== box)
			box.appendChild(mediaReactionTray);
		mediaReactionTray.classList.add('bg_br_tray_floating', 'is-open');
		mediaReactionTray.style.visibility = 'hidden';
		mediaReactionTray.style.opacity = '0';
		requestAnimationFrame(() => {
			positionFloatingMediaTray();
			mediaReactionTray.style.visibility = '';
			mediaReactionTray.style.opacity = '';
		});
	};

	const scheduleCloseFloatingMediaTray = () => {
		window.clearTimeout(mediaTrayCloseTimer);
		mediaTrayCloseTimer = window.setTimeout(() => {
			if (mediaReactionTray)
				mediaReactionTray.classList.remove('is-open');
		}, 140);
	};

	const wireFloatingMediaTray = (tray) => {
		if (!tray || tray.dataset.bgFloatingWired === '1')
			return;

		tray.dataset.bgFloatingWired = '1';
		likeWrap.addEventListener('mouseenter', openFloatingMediaTray);
		likeWrap.addEventListener('focusin', openFloatingMediaTray);
		likeWrap.addEventListener('mouseleave', scheduleCloseFloatingMediaTray);
		likeWrap.addEventListener('focusout', scheduleCloseFloatingMediaTray);
		tray.addEventListener('mouseenter', () => window.clearTimeout(mediaTrayCloseTimer));
		tray.addEventListener('mouseleave', scheduleCloseFloatingMediaTray);
	};


	const positionFloatingNear = (floating, anchor, gap = 6) => {
		if (!floating || !anchor || box.hidden)
			return;

		const anchorRect = anchor.getBoundingClientRect();
		const floatingRect = floating.getBoundingClientRect();
		const edge = 8;
		let left = anchorRect.left;
		let top = anchorRect.top - floatingRect.height - gap;

		if (left + floatingRect.width > window.innerWidth - edge)
			left = Math.max(edge, window.innerWidth - floatingRect.width - edge);
		else
			left = Math.max(edge, left);

		if (top < edge)
			top = Math.min(window.innerHeight - floatingRect.height - edge, anchorRect.bottom + gap);

		floating.style.left = `${Math.round(left)}px`;
		floating.style.top = `${Math.round(top)}px`;
	};

	const wireFloatingCommentTray = (react, trigger, tray) => {
		if (!react || !trigger || !tray || tray.dataset.bgCommentFloatingWired === '1')
			return;

		tray.dataset.bgCommentFloatingWired = '1';
		let closeTimer = 0;
		const open = () => {
			window.clearTimeout(closeTimer);
			if (tray.parentNode !== box)
				box.appendChild(tray);
			tray.classList.add('bg_br_comment_tray_floating', 'is-open');
			tray.style.visibility = 'hidden';
			tray.style.opacity = '0';
			requestAnimationFrame(() => {
				positionFloatingNear(tray, trigger, 6);
				tray.style.visibility = '';
				tray.style.opacity = '';
			});
		};
		const close = () => {
			window.clearTimeout(closeTimer);
			closeTimer = window.setTimeout(() => tray.classList.remove('is-open'), 140);
		};

		react.addEventListener('mouseenter', open);
		react.addEventListener('focusin', open);
		react.addEventListener('mouseleave', close);
		react.addEventListener('focusout', close);
		tray.addEventListener('mouseenter', () => window.clearTimeout(closeTimer));
		tray.addEventListener('mouseleave', close);
	};

	const reactionType = (key) => {
		if (!betterReactions || !Array.isArray(betterReactions.types)) return null;
		return betterReactions.types.find((type) => type.key === key) || null;
	};

	const reactionIcon = (type) => {
		if (!type) return document.createTextNode('👍');
		if (type.image) {
			const img = document.createElement('img');
			img.src = type.image;
			img.alt = '';
			img.className = 'bg_br_icon_image';
			return img;
		}
		const span = document.createElement('span');
		span.className = 'bg_br_icon_glyph';
		span.textContent = type.glyph || '👍';
		return span;
	};

	const dismissFloatingWho = () => {
		document.querySelectorAll('.bg_br_who_floating').forEach((node) => node.remove());
	};

	const showReactors = async (kind, id, reaction, anchor) => {
		if (!betterReactions || !betterReactions.display || !betterReactions.display.showWho)
			return;
		dismissFloatingWho();
		try {
			const sa = kind === 'media' ? 'mediareactors' : 'commentreactors';
			const param = kind === 'media' ? `media=${encodeURIComponent(id)}` : `comment=${encodeURIComponent(id)}`;
			const response = await fetch(`${endpoint(sa)}&${param}&reaction=${encodeURIComponent(reaction)}`, {credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}});
			const data = await response.json();
			if (!data.success) throw new Error(data.message || 'Unable to load reactions.');
			const pop = document.createElement('div');
			pop.className = 'bg_br_who';
			const type = reactionType(reaction);
			const title = document.createElement('div');
			title.className = 'bg_br_who_title';
			if (type) {
				title.appendChild(reactionIcon(type));
				const label = document.createElement('span');
				label.textContent = type.label || reaction;
				title.appendChild(label);
			} else
				title.textContent = reaction;
			pop.appendChild(title);

			const list = document.createElement('div');
			list.className = 'bg_br_who_list';
			const people = data.people || [];
			if (!people.length) {
				const row = document.createElement('div');
				row.className = 'bg_br_who_name';
				row.textContent = 'No reactions yet';
				list.appendChild(row);
			} else people.forEach((person) => {
				const row = document.createElement('div');
				row.className = 'bg_br_who_name';
				if (person.url) {
					const a = document.createElement('a');
					a.className = 'bg_br_who_profile';
					a.href = person.url;
					a.textContent = person.name || 'Member';
					row.appendChild(a);
				} else
					row.textContent = person.name || 'Member';
				list.appendChild(row);
			});
			pop.appendChild(list);

			if ((data.total || 0) > people.length) {
				const more = document.createElement('div');
				more.className = 'bg_br_who_more';
				more.textContent = `and ${data.total - people.length} more`;
				pop.appendChild(more);
			}
			pop.classList.add('bg_br_who_floating');
			box.appendChild(pop);
			pop.style.visibility = 'hidden';
			requestAnimationFrame(() => {
				positionFloatingNear(pop, anchor, 6);
				pop.style.visibility = '';
			});
		} catch (err) { setMessage(err.message || 'Unable to load reactions.', true); }
	};

	const renderSummary = (host, summary, kind, id) => {
		host.textContent = '';
		if (!betterReactions || !betterReactions.display || betterReactions.display.showCounts === false) return;
		const counts = (summary && summary.counts) || {};
		Object.keys(counts).forEach((key) => {
			const count = Number(counts[key]) || 0;
			if (!count) return;
			const type = reactionType(key);
			if (!type) return;
			const chip = document.createElement('button');
			chip.type = 'button';
			chip.className = 'bg_br_count';
			if (summary.mine === key) chip.classList.add('is-mine');
			chip.append(reactionIcon(type), document.createTextNode(` ${count}`));
			chip.title = `${type.label || key}: ${count}`;
			chip.addEventListener('click', () => showReactors(kind, id, key, chip));
			host.appendChild(chip);
		});
	};

	const buildTray = (host, kind, id, summary, onSelect) => {
		host.textContent = '';
		if (!betterReactions || !Array.isArray(betterReactions.types)) return;
		betterReactions.types.forEach((type) => {
			const b = document.createElement('button');
			b.type='button'; b.className='bg_br_choice'; b.dataset.reaction=type.key;
			if (summary && summary.mine === type.key) b.classList.add('is-selected');
			b.title=type.label || type.key; b.setAttribute('aria-label', type.label || type.key);
			b.appendChild(reactionIcon(type));
			b.addEventListener('click', (event) => { event.preventDefault(); event.stopPropagation(); onSelect(type.key); });
			host.appendChild(b);
		});
	};

	const setMediaReactionMode = (enabled) => {
		likeWrap.classList.toggle('bg_br_enabled', !!enabled);
		if (enabled) {
			likeButton.classList.remove('bg_icon_button', 'bg_icon_button_with_badge');
			likeButton.classList.add('button', 'bg_br_like_trigger');
		} else {
			likeButton.classList.remove('bg_br_like_trigger');
			likeButton.classList.add('button', 'bg_icon_button', 'bg_icon_button_with_badge');
		}
	};

	const updateMediaReactionUI = (payload) => {
		if (!payload || !payload.enabled) { betterReactions = null; setMediaReactionMode(false); if (reactionSummary) { reactionSummary.hidden = true; reactionSummary.textContent = ''; } return; }
		betterReactions = {types: payload.types || [], display: payload.display || {}};
		setMediaReactionMode(true);
		let tray = mediaReactionTray;
		const summaryHost = reactionSummary;
		if (!tray || !tray.isConnected) {
			tray=document.createElement('div');
			tray.className='bg_br_tray';
			likeWrap.appendChild(tray);
			mediaReactionTray = tray;
		}
		wireFloatingMediaTray(tray);
		if (summaryHost) {
			summaryHost.classList.add('bg_br_summary');
			summaryHost.hidden = !(payload.display && payload.display.showCounts !== false);
		}
		const native = reactionType('like');
		likeButton.innerHTML=''; likeButton.appendChild(reactionIcon(native));
		const text=document.createElement('span'); text.className='bg_br_like_text'; text.textContent=(payload.summary && payload.summary.mine==='like')?'Unlike':'Like'; likeButton.appendChild(text);
		likeButton.setAttribute('aria-pressed', payload.summary && payload.summary.mine === 'like' ? 'true' : 'false');
		likeButton.title = text.textContent;
		likeButton.setAttribute('aria-label', text.textContent);
		buildTray(tray,'media',currentMediaId,payload.summary,async (key)=>{
			try { const data=await post('mediareaction',{media:currentMediaId,reaction:key}); if(!data.success) throw new Error(data.message||'Unable to react.'); updateMediaReactionUI(data.better_reactions); }
			catch(err){ setMessage(err.message,true); }
		});
		renderSummary(summaryHost,payload.summary,'media',currentMediaId);
	};

	const renderComments = (comments, canComment) => {
		box.querySelectorAll('.bg_br_comment_tray_floating, .bg_br_who_floating').forEach((node) => node.remove());
		commentsList.textContent = '';

		if (!comments || comments.length === 0) {
			const empty = document.createElement('div');
			empty.className = 'smalltext';
			empty.textContent = 'No comments yet.';
			commentsList.appendChild(empty);
		} else {
			comments.forEach((comment) => {
				const item = document.createElement('div');
				item.className = 'bg_comment';

				const head = document.createElement('div');
				head.className = 'bg_comment_head';

				const name = document.createElement('strong');
				name.textContent = comment.real_name || 'Member';
				head.appendChild(name);

				const actions = document.createElement('div');
				actions.className = 'bg_comment_actions';

				if (comment.can_report) {
					const report = document.createElement('button');
					report.type = 'button';
					report.className = 'bg_comment_report';
					report.title = 'Report comment';
					report.setAttribute('aria-label', 'Report comment');
					report.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V4m0 1h11l-1.5 4L16 13H5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
					report.addEventListener('click', () => {
						reportTargetType.value = 'comment';
						reportCommentId.value = String(comment.id_comment || 0);
						reportPanel.hidden = false;
						reportReason.focus();
					});
					actions.appendChild(report);
				}

				if (comment.can_delete) {
					const del = document.createElement('button');
					del.type = 'button';
					del.className = 'bg_comment_delete';
					del.textContent = '×';
					del.title = 'Delete comment';
					del.setAttribute('aria-label', 'Delete comment');
					del.addEventListener('click', async () => {
						if (!window.confirm('Delete this comment?'))
							return;
						try {
							const result = await post('commentdelete', {comment: comment.id_comment});
							if (!result.success)
								throw new Error(result.message || 'Unable to delete comment.');
							await loadInfo(currentMediaId, false);
						} catch (err) {
							setMessage(err.message, true);
						}
					});
					actions.appendChild(del);
				}

				if (actions.childNodes.length)
					head.appendChild(actions);

				const body = document.createElement('div');
				body.textContent = comment.body || '';

				item.append(head, body);
				if (betterReactions) {
					const react=document.createElement('div'); react.className='bg_br_comment';
					const trigger=document.createElement('button'); trigger.type='button'; trigger.className='bg_br_comment_trigger';
					const tray=document.createElement('div'); tray.className='bg_br_tray';
					const summaryHost=document.createElement('div'); summaryHost.className='bg_br_summary bg_br_comment_summary';
					const refresh=(summary)=>{
						const mine=(summary && summary.mine) || '';
						trigger.innerHTML='';
						trigger.appendChild(reactionIcon(reactionType('like')));
						const label=document.createElement('span'); label.className='bg_br_comment_like_text'; label.textContent=mine==='like'?'Unlike':'Like'; trigger.appendChild(label);
						trigger.setAttribute('aria-pressed', mine==='like'?'true':'false');
						trigger.title=label.textContent;
						buildTray(tray,'comment',comment.id_comment,summary,async(key)=>{ try{ const data=await post('commentreaction',{comment:comment.id_comment,reaction:key}); if(!data.success) throw new Error(data.message||'Unable to react.'); refresh(data.summary); }catch(err){setMessage(err.message,true);} });
						renderSummary(summaryHost,summary,'comment',comment.id_comment);
						summaryHost.hidden = !(betterReactions.display && betterReactions.display.showCounts !== false) || !summaryHost.childNodes.length;
					};
					refresh(comment.reactions || {counts:{},mine:''});
					react.append(trigger,summaryHost);
					item.appendChild(react);
					wireFloatingCommentTray(react,trigger,tray);
				}
				commentsList.appendChild(item);
			});
		}

		commentForm.hidden = !canComment;
	};

	const updateFullSizeVisibility = (media, image) => {
		if (!fullSizeLink) return;

		fullSizeLink.hidden = true;
		fullSizeLink.removeAttribute('href');

		if (!media || media.media_type !== 'image' || !image)
			return;

		const originalWidth = Number(media.width) || image.naturalWidth || 0;
		const originalHeight = Number(media.height) || image.naturalHeight || 0;
		const shownWidth = Math.round(image.getBoundingClientRect().width);
		const shownHeight = Math.round(image.getBoundingClientRect().height);

		if (originalWidth > shownWidth + 1 || originalHeight > shownHeight + 1) {
			fullSizeLink.href = media.file_url;
			fullSizeLink.hidden = false;
		}
	};

	const loadInfo = async (mediaId, replaceStage = true) => {
		setMessage('');
		try {
			const response = await fetch(endpoint('mediainfo', mediaId), {
				credentials: 'same-origin',
				headers: {'X-Requested-With': 'XMLHttpRequest'}
			});
			const data = await response.json();

			if (!data.success)
				throw new Error(data.message || 'Unable to load media.');

			currentMediaId = Number(data.media.id_media) || mediaId;
			title.textContent = data.media.title || '';
			description.textContent = data.media.description || '';
			if (integrations) {
				integrations.innerHTML = data.media.integration_html || '';
				integrations.hidden = !data.media.integration_html;
			}

			if (fullSizeLink) {
				fullSizeLink.hidden = true;
				fullSizeLink.removeAttribute('href');
			}

			if (downloadLink) {
				downloadLink.hidden = !data.media.downloads_enabled;
				// dev2j: the browser already knows the active media ID. Build the
				// protected Better Gallery file endpoint locally instead of depending
				// on an optional download_url field in the mediainfo JSON payload.
				if (data.media.downloads_enabled)
					downloadLink.href = `${endpoint('file', currentMediaId)};download=1`;
				else
					downloadLink.removeAttribute('href');
			}

			if (replaceStage) {
				stage.textContent = '';
				if (data.media.media_type === 'video') {
					const video = document.createElement('video');
					video.controls = true;
					video.autoplay = true;
					video.preload = 'metadata';
					if (data.media.poster_url)
						video.poster = data.media.poster_url;

					const source = document.createElement('source');
					source.src = data.media.file_url;
					source.type = data.media.mime_type || 'video/mp4';
					video.appendChild(source);
					stage.appendChild(video);
				} else {
					const img = document.createElement('img');
					img.src = data.media.file_url;
					img.alt = data.media.title || '';
					img.addEventListener('load', () => {
						updateFullSizeVisibility(data.media, img);
					});
					stage.appendChild(img);
				}
			}

			const interactions = data.interactions || {};
			likeWrap.hidden = !interactions.likes_enabled;
			likeButton.disabled = !interactions.can_like;
			if (interactions.better_reactions && interactions.better_reactions.enabled) {
				updateMediaReactionUI(interactions.better_reactions);
			} else {
				betterReactions = null;
				setMediaReactionMode(false);
				likeWrap.querySelectorAll('.bg_br_tray, .bg_br_summary').forEach((node) => node.remove());
				likeButton.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20.5 4.7 13.7A5 5 0 0 1 12 6.9a5 5 0 0 1 7.3 6.8L12 20.5Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg><strong id="bg_like_count" class="bg_icon_badge"></strong>';
				likeButton.classList.toggle('is-liked', !!interactions.user_liked);
				likeButton.setAttribute('aria-pressed', interactions.user_liked ? 'true' : 'false');
				const restoredCount = likeButton.querySelector('#bg_like_count');
				if (restoredCount) restoredCount.textContent = String(interactions.like_count || 0);
			}

			if (favoriteButton) {
				favoriteButton.hidden = !interactions.can_favorite;
				favoriteButton.classList.toggle('is-favorited', !!interactions.user_favorited);
				favoriteButton.setAttribute('aria-pressed', interactions.user_favorited ? 'true' : 'false');
				favoriteButton.title = interactions.user_favorited ? 'Remove Favorite' : 'Favorite';
				favoriteButton.setAttribute('aria-label', interactions.user_favorited ? 'Remove Favorite' : 'Favorite');
			}

			if (reportButton)
				reportButton.hidden = !interactions.can_report_media;

			if (reportPanel)
				reportPanel.hidden = true;

			commentsPanel.hidden = !interactions.comments_enabled;
			if (interactions.comments_enabled)
				renderComments(data.comments || [], !!interactions.can_comment);

		} catch (err) {
			setMessage(err.message || 'Unable to load media.', true);
		}
	};

	const updateNav = () => {
		prevButton.disabled = currentIndex <= 0;
		nextButton.disabled = currentIndex < 0 || currentIndex >= openers.length - 1;
	};

	const openIndex = async (index) => {
		if (index < 0 || index >= openers.length)
			return;

		currentIndex = index;
		updateNav();

		const opener = openers[index];
		const mediaId = Number(opener.dataset.mediaId) || 0;
		if (!mediaId)
			return;

		stage.textContent = '';
		title.textContent = '';
		description.textContent = '';
		commentsList.textContent = '';
		setMessage('Loading…');

		if (box.hidden) {
			lastFocus = document.activeElement;
			box.hidden = false;
			document.body.classList.add('bg_lightbox_open');
		}

		await loadInfo(mediaId, true);
		box.querySelector('.bg_lightbox_close')?.focus();
	};

	document.addEventListener('pointerdown', (event) => {
		const pop = document.querySelector('.bg_br_who_floating');
		if (pop && !pop.contains(event.target) && !event.target.closest('.bg_br_count'))
			dismissFloatingWho();
	}, true);
	window.addEventListener('scroll', dismissFloatingWho, true);

	window.addEventListener('resize', () => {
		dismissFloatingWho();
		if (box.hidden)
			return;
		if (mediaReactionTray && mediaReactionTray.classList.contains('is-open'))
			positionFloatingMediaTray();
		const img = stage.querySelector('img');
		if (img && currentMediaId)
			updateFullSizeVisibility({
				media_type: 'image',
				width: img.naturalWidth,
				height: img.naturalHeight,
				file_url: img.src
			}, img);
	});

	const close = () => {
		window.clearTimeout(mediaTrayCloseTimer);
		if (mediaReactionTray)
			mediaReactionTray.classList.remove('is-open');
		const video = stage.querySelector('video');
		if (video)
			video.pause();

		box.hidden = true;
		document.body.classList.remove('bg_lightbox_open');
		stage.textContent = '';
		setMessage('');
		if (lastFocus && typeof lastFocus.focus === 'function')
			lastFocus.focus();
	};

	openers.forEach((opener, index) => {
		opener.addEventListener('click', (event) => {
			event.preventDefault();
			openIndex(index);
		});
	});

	// dev2zdda: deep-link directly to one media item from shared tag results.
	// SMF commonly uses semicolon-delimited query parameters, so parse both
	// standard '&media=' and ';media=' forms from the current URL.
	const mediaParamMatch = window.location.search.match(/[?;&]media=(\d+)/i);
	if (mediaParamMatch) {
		const requestedMediaId = Number(mediaParamMatch[1]) || 0;
		const requestedIndex = openers.findIndex((opener) =>
			(Number(opener.dataset.mediaId) || 0) === requestedMediaId
		);

		if (requestedIndex >= 0)
			window.setTimeout(() => openIndex(requestedIndex), 0);
	}

	box.querySelectorAll('[data-bg-close]').forEach((el) => el.addEventListener('click', close));

	// dev2j: fetch the protected media endpoint directly from currentMediaId,
	// convert it to a Blob, and hand that Blob to the browser as a download.
	// This removes the last dependency on a pre-populated anchor/download_url.
	if (downloadLink) {
		downloadLink.addEventListener('click', async (event) => {
			event.preventDefault();

			if (!currentMediaId) {
				setMessage('Better Gallery could not identify the media item to download.', true);
				return;
			}

			const href = `${endpoint('file', currentMediaId)};download=1`;

			event.stopPropagation();

			const wasDisabled = downloadLink.getAttribute('aria-disabled') === 'true';
			if (wasDisabled)
				return;

			downloadLink.setAttribute('aria-disabled', 'true');
			downloadLink.classList.add('is-busy');
			setMessage('');

			try {
				const response = await fetch(href, {
					method: 'GET',
					credentials: 'same-origin',
					cache: 'no-store'
				});

				if (!response.ok)
					throw new Error(`Download failed with HTTP ${response.status}.`);

				const blob = await response.blob();

				if (!blob || !blob.size)
					throw new Error('The server returned an empty download.');

				let filename = '';
				const disposition = response.headers.get('Content-Disposition') || '';
				const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i);
				const plainMatch = disposition.match(/filename="([^"]+)"/i);

				if (utf8Match && utf8Match[1]) {
					try {
						filename = decodeURIComponent(utf8Match[1]);
					} catch (ignore) {
						filename = utf8Match[1];
					}
				} else if (plainMatch && plainMatch[1]) {
					filename = plainMatch[1];
				}

				if (!filename)
					filename = `gallery-media-${currentMediaId || 'download'}`;

				const objectUrl = URL.createObjectURL(blob);
				const trigger = document.createElement('a');
				trigger.href = objectUrl;
				trigger.download = filename;
				trigger.style.display = 'none';
				document.body.appendChild(trigger);
				trigger.click();
				trigger.remove();

				window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
			} catch (err) {
				setMessage(err && err.message ? err.message : 'Unable to download this media.', true);
			} finally {
				downloadLink.removeAttribute('aria-disabled');
				downloadLink.classList.remove('is-busy');
			}
		});
	}

	prevButton.addEventListener('click', () => openIndex(currentIndex - 1));
	nextButton.addEventListener('click', () => openIndex(currentIndex + 1));

	document.addEventListener('keydown', (event) => {
		if (box.hidden)
			return;
		if (event.key === 'Escape') {
			event.preventDefault();
			close();
		} else if (event.key === 'ArrowLeft') {
			event.preventDefault();
			if (currentIndex > 0)
				openIndex(currentIndex - 1);
		} else if (event.key === 'ArrowRight') {
			event.preventDefault();
			if (currentIndex < openers.length - 1)
				openIndex(currentIndex + 1);
		}
	});

	if (favoriteButton) {
		favoriteButton.addEventListener('click', async () => {
			if (!currentMediaId || favoriteButton.disabled)
				return;
			try {
				favoriteButton.disabled = true;
				const data = await post('mediafavorite', {media: currentMediaId});
				if (!data.success)
					throw new Error(data.message || 'Unable to update favorite.');
				favoriteButton.classList.toggle('is-favorited', !!data.favorited);
				favoriteButton.setAttribute('aria-pressed', data.favorited ? 'true' : 'false');
				favoriteButton.title = data.favorited ? 'Remove Favorite' : 'Favorite';
				favoriteButton.setAttribute('aria-label', data.favorited ? 'Remove Favorite' : 'Favorite');
			} catch (err) {
				setMessage(err.message, true);
			} finally {
				favoriteButton.disabled = false;
			}
		});
	}

	if (reportButton) {
		reportButton.addEventListener('click', () => {
			reportTargetType.value = 'media';
			reportCommentId.value = '0';
			reportPanel.hidden = false;
			reportReason.focus();
		});
	}

	if (reportCancel) {
		reportCancel.addEventListener('click', () => {
			reportPanel.hidden = true;
			reportNote.value = '';
		});
	}

	if (reportForm) {
		reportForm.addEventListener('submit', async (event) => {
			event.preventDefault();
			if (!currentMediaId)
				return;
			const submit = reportForm.querySelector('button[type="submit"]');
			try {
				if (submit) submit.disabled = true;
				const data = await post('mediareport', {
					media: currentMediaId,
					target_type: reportTargetType.value || 'media',
					comment: reportCommentId.value || '0',
					reason: reportReason.value || 'other',
					note: reportNote.value.trim()
				});
				if (!data.success)
					throw new Error(data.message || 'Unable to submit report.');
				reportPanel.hidden = true;
				reportNote.value = '';
				setMessage(data.message || 'Report submitted.');
			} catch (err) {
				setMessage(err.message, true);
			} finally {
				if (submit) submit.disabled = false;
			}
		});
	}

	likeButton.addEventListener('click', async () => {
		if (!currentMediaId || likeButton.disabled) return;
		try {
			likeButton.disabled = true;
			const data = betterReactions ? await post('mediareaction',{media:currentMediaId,reaction:'like'}) : await post('medialike',{media:currentMediaId});
			if (!data.success) throw new Error(data.message || 'Unable to update like.');
			if (betterReactions && data.better_reactions) updateMediaReactionUI(data.better_reactions);
			else { likeButton.classList.toggle('is-liked',!!data.liked); likeButton.setAttribute('aria-pressed',data.liked?'true':'false'); if(likeCount) likeCount.textContent=String(data.like_count||0); }
		} catch (err) { setMessage(err.message,true); } finally { likeButton.disabled=false; }
	});

	commentForm.addEventListener('submit', async (event) => {
		event.preventDefault();
		if (!currentMediaId)
			return;
		const body = commentBody.value.trim();
		if (!body)
			return;

		try {
			const submit = commentForm.querySelector('button[type="submit"]');
			if (submit)
				submit.disabled = true;
			const data = await post('mediacomment', {media: currentMediaId, body});
			if (!data.success)
				throw new Error(data.message || 'Unable to post comment.');
			commentBody.value = '';
			await loadInfo(currentMediaId, false);
		} catch (err) {
			setMessage(err.message, true);
		} finally {
			const submit = commentForm.querySelector('button[type="submit"]');
			if (submit)
				submit.disabled = false;
		}
	});
})();


/* dev44: keep Better Gallery custom switch state text synchronized live.
 * The visual thumb already follows :checked in CSS; this keeps the adjacent
 * state label equally authoritative without requiring a save/reload. */
(() => {
	'use strict';

	const switches = document.querySelectorAll('.bg_switch input[type="checkbox"]');

	if (!switches.length)
		return;

	const syncSwitchState = (input) => {
		const wrapper = input.closest('.bg_switch');
		const state = wrapper ? wrapper.querySelector('.bg_switch_state') : null;

		if (!state)
			return;

		const onLabel = state.dataset.onLabel || 'On';
		const offLabel = state.dataset.offLabel || 'Off';
		state.textContent = input.checked ? onLabel : offLabel;
		state.dataset.state = input.checked ? 'on' : 'off';
	};

	switches.forEach((input) => {
		syncSwitchState(input);
		input.addEventListener('change', () => syncSwitchState(input));
	});
})();

/* Better Gallery dev51h: first-class album reactions. */
(() => {
	'use strict';

	const dataNode = document.getElementById('bg_album_reactions_data');
	const wrap = document.getElementById('bg_album_reaction_wrap');
	const trigger = document.getElementById('bg_album_reaction_trigger');
	const tray = document.getElementById('bg_album_reaction_tray');
	const summaryHost = document.getElementById('bg_album_reaction_summary');

	if (!dataNode || !wrap || !trigger || !tray || !summaryHost || !window.fetch)
		return;

	let config;
	try {
		config = JSON.parse(dataNode.textContent || '{}');
	} catch (err) {
		return;
	}

	const albumId = Number(config.id_album) || 0;
	const sessionVar = config.session_var || '';
	const sessionId = config.session_id || '';
	let payload = config.payload || {};
	let closeTimer = 0;

	if (!albumId || !payload.enabled)
		return;

	const endpoint = (sa) => `${window.location.pathname}?action=gallery;sa=${encodeURIComponent(sa)}`;
	const typeFor = (key) => Array.isArray(payload.types) ? payload.types.find((type) => type.key === key) : null;

	const iconFor = (type) => {
		if (!type)
			return document.createTextNode('👍');
		if (type.image) {
			const img = document.createElement('img');
			img.src = type.image;
			img.alt = '';
			img.className = 'bg_br_icon_image';
			return img;
		}
		const span = document.createElement('span');
		span.className = 'bg_br_icon_glyph';
		span.textContent = type.glyph || '👍';
		return span;
	};

	const post = async (sa, values) => {
		const body = new FormData();
		Object.entries(values || {}).forEach(([key, value]) => body.append(key, value));
		if (sessionVar)
			body.append(sessionVar, sessionId);
		const response = await fetch(endpoint(sa), {
			method: 'POST',
			body,
			credentials: 'same-origin',
			headers: {'X-Requested-With': 'XMLHttpRequest'}
		});
		return response.json();
	};

	const positionFloating = (node, anchor, gap = 7) => {
		const rect = anchor.getBoundingClientRect();
		const width = node.offsetWidth || 240;
		const height = node.offsetHeight || 44;
		let left = rect.left;
		let top = rect.top - height - gap;
		if (left + width > window.innerWidth - 8)
			left = Math.max(8, window.innerWidth - width - 8);
		if (top < 8)
			top = Math.min(window.innerHeight - height - 8, rect.bottom + gap);
		node.style.left = `${Math.max(8, left) + window.scrollX}px`;
		node.style.top = `${Math.max(8, top) + window.scrollY}px`;
	};

	const dismissAlbumWho = () => {
		document.querySelectorAll('.bg_album_reactors_popover').forEach((node) => node.remove());
	};

	const showReactors = async (reaction, anchor) => {
		if (!payload.display || !payload.display.showWho)
			return;
		document.querySelectorAll('.bg_album_reactors_popover').forEach((node) => node.remove());
		try {
			const response = await fetch(`${endpoint('albumreactors')}&album=${albumId}&reaction=${encodeURIComponent(reaction)}`, {
				credentials: 'same-origin',
				headers: {'X-Requested-With': 'XMLHttpRequest'}
			});
			const data = await response.json();
			if (!data.success)
				throw new Error(data.message || 'Unable to load reactions.');

			const pop = document.createElement('div');
			pop.className = 'bg_br_who bg_album_reactors_popover';
			const type = typeFor(reaction);
			const title = document.createElement('div');
			title.className = 'bg_br_who_title';
			if (type) {
				title.appendChild(iconFor(type));
				const label = document.createElement('span');
				label.textContent = type.label || reaction;
				title.appendChild(label);
			} else
				title.textContent = reaction;
			pop.appendChild(title);

			const list = document.createElement('div');
			list.className = 'bg_br_who_list';
			const people = data.people || [];
			if (!people.length) {
				const row = document.createElement('div');
				row.className = 'bg_br_who_name';
				row.textContent = 'No reactions yet';
				list.appendChild(row);
			} else people.forEach((person) => {
				const row = document.createElement('div');
				row.className = 'bg_br_who_name';
				if (person.url) {
					const link = document.createElement('a');
					link.className = 'bg_br_who_profile';
					link.href = person.url;
					link.textContent = person.name || 'Member';
					row.appendChild(link);
				} else
					row.textContent = person.name || 'Member';
				list.appendChild(row);
			});
			pop.appendChild(list);

			if ((Number(data.total) || 0) > people.length) {
				const more = document.createElement('div');
				more.className = 'bg_br_who_more';
				more.textContent = `and ${Number(data.total) - people.length} more`;
				pop.appendChild(more);
			}

			document.body.appendChild(pop);
			pop.style.visibility = 'hidden';
			requestAnimationFrame(() => {
				positionFloating(pop, anchor, 6);
				pop.style.visibility = '';
			});
		} catch (err) {
			console.error(err);
		}
	};

	document.addEventListener('pointerdown', (event) => {
		const pop = document.querySelector('.bg_album_reactors_popover');
		if (pop && !pop.contains(event.target) && !event.target.closest('.bg_br_count'))
			dismissAlbumWho();
	}, true);
	window.addEventListener('scroll', dismissAlbumWho, true);
	window.addEventListener('resize', dismissAlbumWho);

	const renderSummary = () => {
		summaryHost.textContent = '';
		if (!payload.display || payload.display.showCounts === false) {
			summaryHost.hidden = true;
			return;
		}
		const summary = payload.summary || {counts: {}, mine: ''};
		Object.entries(summary.counts || {}).forEach(([key, rawCount]) => {
			const count = Number(rawCount) || 0;
			const type = typeFor(key);
			if (!count || !type)
				return;
			const chip = document.createElement('button');
			chip.type = 'button';
			chip.className = 'bg_br_count';
			if (summary.mine === key)
				chip.classList.add('is-mine');
			chip.append(iconFor(type), document.createTextNode(` ${count}`));
			chip.title = `${type.label || key}: ${count}`;
			chip.addEventListener('click', () => showReactors(key, chip));
			summaryHost.appendChild(chip);
		});
		summaryHost.hidden = summaryHost.childElementCount === 0;
	};

	const buildTray = () => {
		tray.textContent = '';
		const summary = payload.summary || {counts: {}, mine: ''};
		(payload.types || []).forEach((type) => {
			const choice = document.createElement('button');
			choice.type = 'button';
			choice.className = 'bg_br_choice';
			if (summary.mine === type.key)
				choice.classList.add('is-selected');
			choice.title = type.label || type.key;
			choice.setAttribute('aria-label', type.label || type.key);
			choice.appendChild(iconFor(type));
			choice.addEventListener('click', async (event) => {
				event.preventDefault();
				event.stopPropagation();
				try {
					choice.disabled = true;
					const data = await post('albumreaction', {album: albumId, reaction: type.key});
					if (!data.success)
						throw new Error(data.message || 'Unable to react.');
					payload = data.better_reactions || payload;
					render();
				} catch (err) {
					console.error(err);
				} finally {
					choice.disabled = false;
				}
			});
			tray.appendChild(choice);
		});
	};

	const positionTray = () => {
		if (!tray.classList.contains('is-open'))
			return;
		positionFloating(tray, trigger, 6);
	};

	const openTray = () => {
		window.clearTimeout(closeTimer);
		tray.classList.add('is-open');
		trigger.setAttribute('aria-expanded', 'true');
		requestAnimationFrame(positionTray);
	};

	const closeTray = () => {
		window.clearTimeout(closeTimer);
		closeTimer = window.setTimeout(() => {
			tray.classList.remove('is-open');
			trigger.setAttribute('aria-expanded', 'false');
		}, 140);
	};

	const render = () => {
		const summary = payload.summary || {counts: {}, mine: ''};
		const likeType = typeFor('like');
		trigger.innerHTML = '';
		trigger.appendChild(iconFor(likeType));
		const text = document.createElement('span');
		text.textContent = summary.mine === 'like' ? 'Unlike' : 'Like';
		trigger.appendChild(text);
		trigger.setAttribute('aria-pressed', summary.mine === 'like' ? 'true' : 'false');
		trigger.title = text.textContent;
		buildTray();
		renderSummary();
	};

	// Portal the tray to the page root so album toolbars/containers can never clip it.
	document.body.appendChild(tray);
	tray.classList.add('bg_album_reaction_tray_floating');

	trigger.addEventListener('mouseenter', openTray);
	trigger.addEventListener('focusin', openTray);
	trigger.addEventListener('mouseleave', closeTray);
	trigger.addEventListener('focusout', closeTray);
	trigger.addEventListener('click', async () => {
		try {
			trigger.disabled = true;
			const data = await post('albumreaction', {album: albumId, reaction: 'like'});
			if (!data.success)
				throw new Error(data.message || 'Unable to update Like.');
			payload = data.better_reactions || payload;
			render();
		} catch (err) {
			console.error(err);
		} finally {
			trigger.disabled = false;
		}
	});
	tray.addEventListener('mouseenter', () => window.clearTimeout(closeTimer));
	tray.addEventListener('mouseleave', closeTray);
	window.addEventListener('resize', positionTray);

	render();
})();
