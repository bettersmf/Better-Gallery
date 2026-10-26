<?php

/**
 * dev37 shared Better Gallery action toolbar.
 *
 * SMF template layers let one toolbar render across every public Better Gallery
 * subpage without duplicating markup or relying on each page template.
 */
function template_bg_media_actions_above()
{
	global $context, $txt, $scripturl;

	$sa = !empty($_REQUEST['sa']) ? (string) $_REQUEST['sa'] : 'home';

	echo '
	<div class="windowbg bg_media_action_bar">
		<div class="bg_media_action_list">';

	echo '<a class="button', in_array($sa, array('home'), true) ? ' active' : '', '" href="', $scripturl, '?action=gallery">', htmlspecialchars($context['bg_identity_label'] ?? 'Media', ENT_QUOTES, 'UTF-8'), '</a>';

	if (!empty($context['user']['is_logged']))
	{
		echo '<a class="button', $sa === 'mygallery' ? ' active' : '', '" href="', $scripturl, '?action=gallery;sa=mygallery">', $txt['bg_my_gallery'], '</a>';
		echo '<a class="button', $sa === 'myalbums' ? ' active' : '', '" href="', $scripturl, '?action=gallery;sa=myalbums">', $txt['bg_my_albums'], '</a>';
		echo '<a class="button', $sa === 'favorites' ? ' active' : '', '" href="', $scripturl, '?action=gallery;sa=favorites">', $txt['bg_my_favorites'], '</a>';
	}

	if (!empty($context['bg_can_moderate']))
		echo '<a class="button', $sa === 'reports' ? ' active' : '', '" href="', $scripturl, '?action=gallery;sa=reports">', $txt['bg_report_queue'], '</a>';

	if (!empty($context['bg_can_create']))
		echo '<a class="button bg_media_create_album', $sa === 'create' ? ' active' : '', '" href="', $scripturl, '?action=gallery;sa=create">', $txt['bg_create_album'], '</a>';

	echo '
		</div>
	</div>';
}

function template_bg_media_actions_below()
{
	// The shared action bar has no closing layer markup.
}



function template_bg_my_gallery_dashboard()
{
	global $context, $txt, $scripturl;

	$s = $context['bg_dashboard_stats'];

	echo '
	<div class="cat_bar"><h3 class="catbg">', $txt['bg_my_gallery'], '</h3></div>
	<div class="windowbg bg_gallery_toolbar">
		<div>
			<strong>', $txt['bg_my_gallery_dashboard'], '</strong>
			<div class="smalltext">', $txt['bg_my_gallery_help'], '</div>
		</div>
	</div><br>

	<div class="bg_member_stats_grid">
		<div class="windowbg bg_member_stat"><strong>', (int) $s['albums'], '</strong><span>', $txt['bg_stat_albums'], '</span></div>
		<div class="windowbg bg_member_stat"><strong>', (int) $s['media'], '</strong><span>', $txt['bg_stat_media'], '</span></div>
		<div class="windowbg bg_member_stat"><strong>', (int) $s['album_views'], '</strong><span>', $txt['bg_stat_album_views'], '</span></div>
		<div class="windowbg bg_member_stat"><strong>', (int) $s['media_views'], '</strong><span>', $txt['bg_stat_media_views'], '</span></div>
		<div class="windowbg bg_member_stat"><strong>', (int) $s['likes_received'], '</strong><span>', $txt['bg_stat_likes_received'], '</span></div>
		<div class="windowbg bg_member_stat"><strong>', (int) $s['comments_received'], '</strong><span>', $txt['bg_stat_comments_received'], '</span></div>
		<div class="windowbg bg_member_stat"><strong>', (int) $s['favorites_received'], '</strong><span>', $txt['bg_stat_favorites_received'], '</span></div>
		<div class="windowbg bg_member_stat"><strong>', htmlspecialchars($s['storage_formatted'], ENT_QUOTES, 'UTF-8'), '</strong><span>', $txt['bg_stat_storage_used'], '</span></div>
	</div><br>';

	$q = $context['bg_dashboard_storage_quota'];
	$meter_percent = !empty($q['unlimited']) ? 0 : min(100, max(0, (float) $q['percent']));
	$meter_class = !empty($q['at_limit']) || (!$q['unlimited'] && $q['percent'] >= 95)
		? ' is-critical'
		: ((!$q['unlimited'] && $q['percent'] >= 80) ? ' is-warning' : '');

	echo '
	<div class="cat_bar"><h3 class="catbg">', $txt['bg_storage_quota_heading'], '</h3></div>
	<div class="windowbg bg_storage_quota_panel">
		<div class="bg_storage_quota_summary">
			<div>
				<strong>', htmlspecialchars($q['used_formatted'], ENT_QUOTES, 'UTF-8'), '</strong>
				<span>', $txt['bg_storage_used_of'], ' ', htmlspecialchars($q['quota_formatted'], ENT_QUOTES, 'UTF-8'), '</span>
			</div>
			<div class="smalltext">';

	if (!empty($q['unlimited']))
		echo $txt['bg_storage_quota_unlimited_help'];
	else
		echo htmlspecialchars($q['remaining_formatted'], ENT_QUOTES, 'UTF-8'), ' ', $txt['bg_storage_remaining'];

	echo '</div>
		</div>
		<div class="bg_storage_meter', $meter_class, '" role="progressbar"
			aria-valuemin="0"
			aria-valuemax="100"
			aria-valuenow="', htmlspecialchars((string) $meter_percent, ENT_QUOTES, 'UTF-8'), '">
			<div class="bg_storage_meter_fill" style="width:', htmlspecialchars((string) $meter_percent, ENT_QUOTES, 'UTF-8'), '%"></div>
		</div>';

	if (empty($q['unlimited']))
		echo '<div class="smalltext bg_storage_percent">', htmlspecialchars((string) $q['percent'], ENT_QUOTES, 'UTF-8'), '% ', $txt['bg_storage_used'], '</div>';

	echo '
	</div><br>

	<div class="bg_dashboard_columns">
		<section>
			<div class="cat_bar"><h3 class="catbg">', $txt['bg_recent_uploads'], '</h3></div>
			<div class="windowbg">';

	if (empty($context['bg_dashboard_recent_media']))
		echo '<p class="bg_dashboard_empty_state">', $txt['bg_no_recent_uploads'], '</p>';
	else
	{
		echo '<div class="bg_dashboard_media_grid">';
		foreach ($context['bg_dashboard_recent_media'] as $media)
		{
			$thumb = (!empty($media['thumb_key']) || !empty($media['poster_key']))
				? $scripturl . '?action=gallery;sa=file;media=' . (int) $media['id_media'] . ';thumb=1'
				: '';

			echo '<a class="bg_dashboard_media_card" href="', $scripturl, '?action=gallery;sa=album;album=', (int) $media['id_album'], '">';
			if ($thumb !== '')
				echo '<img src="', $thumb, '" loading="lazy" alt="">';
			else
				echo '<span class="bg_dashboard_media_placeholder">', strtoupper(htmlspecialchars($media['media_type'], ENT_QUOTES, 'UTF-8')), '</span>';
			echo '<span><strong>', htmlspecialchars($media['title'], ENT_QUOTES, 'UTF-8'), '</strong><small>', htmlspecialchars($media['album_title'], ENT_QUOTES, 'UTF-8'), '</small></span></a>';
		}
		echo '</div>';
	}

	echo '
			</div>
		</section>

		<section>
			<div class="cat_bar"><h3 class="catbg">', $txt['bg_recent_engagement'], '</h3></div>
			<div class="windowbg">';

	if (empty($context['bg_dashboard_recent_activity']))
		echo '<p class="bg_dashboard_empty_state">', $txt['bg_no_recent_engagement'], '</p>';
	else
	{
		echo '<div class="bg_dashboard_activity">';
		foreach ($context['bg_dashboard_recent_activity'] as $activity)
		{
			$actor = $activity['actor_name'] ?: $txt['bg_unknown_member'];
			$text = $activity['activity_type'] === 'comment'
				? sprintf($txt['bg_activity_commented'], $actor, $activity['media_title'])
				: sprintf($txt['bg_activity_liked'], $actor, $activity['media_title']);

			echo '<div class="bg_dashboard_activity_item">', htmlspecialchars($text, ENT_QUOTES, 'UTF-8'), '</div>';
		}
		echo '</div>';
	}

	echo '
			</div>
		</section>
	</div><br>

	<div class="bg_dashboard_columns">
		<section>
			<div class="cat_bar"><h3 class="catbg">', $txt['bg_most_viewed'], '</h3></div>
			<div class="windowbg bg_dashboard_ranked">';

	if (empty($context['bg_dashboard_most_viewed']))
		echo '<p class="bg_dashboard_empty_state">', $txt['bg_no_ranked_media'], '</p>';
	else
		foreach ($context['bg_dashboard_most_viewed'] as $media)
			echo '<a href="', $scripturl, '?action=gallery;sa=album;album=', (int) $media['id_album'], '"><span>', htmlspecialchars($media['title'], ENT_QUOTES, 'UTF-8'), '</span><strong>', (int) $media['num_views'], '</strong></a>';

	echo '
			</div>
		</section>

		<section>
			<div class="cat_bar"><h3 class="catbg">', $txt['bg_most_liked'], '</h3></div>
			<div class="windowbg bg_dashboard_ranked">';

	if (empty($context['bg_dashboard_most_liked']))
		echo '<p class="bg_dashboard_empty_state">', $txt['bg_no_ranked_media'], '</p>';
	else
		foreach ($context['bg_dashboard_most_liked'] as $media)
			echo '<a href="', $scripturl, '?action=gallery;sa=album;album=', (int) $media['id_album'], '"><span>', htmlspecialchars($media['title'], ENT_QUOTES, 'UTF-8'), '</span><strong>', (int) $media['like_count'], '</strong></a>';

	echo '
			</div>
		</section>
	</div>';
}

function template_bg_home()
{
	global $context, $txt, $scripturl;

	$filters = $context['bg_search_filters'];

	echo '
	', !empty($context['bg_is_my_albums']) && !empty($_REQUEST['albumdeleted']) ? '<div class="successbox">' . htmlspecialchars($txt['bg_album_deleted_success'], ENT_QUOTES, 'UTF-8') . '</div>' : '', '
	<div class="cat_bar"><h3 class="catbg">',
		!empty($context['bg_is_my_albums'])
			? $txt['bg_my_albums']
			: (!empty($context['bg_portfolio']['enabled']) ? htmlspecialchars($context['bg_portfolio']['title'], ENT_QUOTES, 'UTF-8') : htmlspecialchars($context['bg_identity_label'], ENT_QUOTES, 'UTF-8')),
	'</h3></div>
	<div class="windowbg bg_gallery_toolbar">
		<div>
			<strong>',
				!empty($context['bg_is_my_albums'])
					? $txt['bg_my_albums']
					: (!empty($context['bg_portfolio']['enabled']) ? $txt['bg_portfolio_albums'] : $txt['bg_browse_albums']),
			'</strong>
			<div class="smalltext">',
				!empty($context['bg_is_my_albums'])
					? $txt['bg_my_albums_help']
					: (!empty($context['bg_portfolio']['enabled'])
						? (!empty($context['bg_portfolio']['intro'])
							? nl2br(htmlspecialchars($context['bg_portfolio']['intro'], ENT_QUOTES, 'UTF-8'))
							: sprintf($txt['bg_portfolio_featuring'], htmlspecialchars($context['bg_portfolio']['owner_name'], ENT_QUOTES, 'UTF-8')))
						: $txt['bg_discovery_note']),
			'</div>
		</div>
</div><br>';

	if (empty($context['bg_is_my_albums']))
	{
		$bg_ad = bg_integration_slot('gallery_index_top');
		if ($bg_ad !== '')
			echo '<div class="bg_gallery_ad_slot bg_gallery_ad_index_top">', $bg_ad, '</div>';
	}

	echo '
	<div class="cat_bar"><h3 class="catbg">', $txt['bg_search_albums'], '</h3></div>
	<div class="windowbg">
		<form action="', $scripturl, '" method="get" class="bg_gallery_search_form">
			<input type="hidden" name="action" value="gallery">';

	if (!empty($context['bg_is_my_albums']))
		echo '<input type="hidden" name="sa" value="myalbums">';

	echo '
			<label><strong>', $txt['bg_search_keywords'], '</strong>
				<input type="search" name="q" value="', htmlspecialchars($filters['q'], ENT_QUOTES, 'UTF-8'), '" placeholder="', htmlspecialchars($txt['bg_search_placeholder'], ENT_QUOTES, 'UTF-8'), '">
			</label>
			<label><strong>', $txt['bg_category'], '</strong>
				<select name="category"><option value="0">', $txt['bg_all_categories'], '</option>';

	foreach ($context['bg_categories'] as $category)
		echo '<option value="', (int) $category['id_category'], '"', (int) $filters['category'] === (int) $category['id_category'] ? ' selected' : '', '>', htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'), '</option>';

	echo '</select></label>
			<label><strong>', $txt['bg_sort_media'], '</strong>
				<select name="sort">
					<option value="featured"', $filters['sort'] === 'featured' ? ' selected' : '', '>', $txt['bg_sort_featured_updated'], '</option>
					<option value="newest"', $filters['sort'] === 'newest' ? ' selected' : '', '>', $txt['bg_sort_newest'], '</option>
					<option value="oldest"', $filters['sort'] === 'oldest' ? ' selected' : '', '>', $txt['bg_sort_oldest'], '</option>
					<option value="title_asc"', $filters['sort'] === 'title_asc' ? ' selected' : '', '>', $txt['bg_sort_title_asc'], '</option>
					<option value="title_desc"', $filters['sort'] === 'title_desc' ? ' selected' : '', '>', $txt['bg_sort_title_desc'], '</option>
				</select>
			</label>
			<label class="bg_gallery_search_check">
				<input type="checkbox" name="accessible_only" value="1"', !empty($filters['accessible_only']) ? ' checked' : '', '>
				<span>', $txt['bg_only_accessible'], '</span>
			</label>
			<div class="bg_gallery_search_actions">
				<button class="button" type="submit">', $txt['search'], '</button>
				<a class="button" href="', $scripturl, '?action=gallery', !empty($context['bg_is_my_albums']) ? ';sa=myalbums' : '', '">', $txt['bg_clear_search'], '</a>
			</div>
		</form>
	</div><br>
	<div class="bg_gallery_result_summary"><strong>', (int) ($context['bg_album_total'] ?? count($context['bg_albums'])), '</strong> ', (int) ($context['bg_album_total'] ?? count($context['bg_albums'])) === 1 ? $txt['bg_album_result'] : $txt['bg_album_results'], '</div>';

	if (!empty($context['bg_album_page_index']))
		echo '<div class="pagesection bg_album_pages_top"><div class="pagelinks">', $context['bg_album_page_index'], '</div></div>';

	if (empty($context['bg_albums']))
	{
		echo '<div class="windowbg"><p class="information">', !empty($context['bg_is_my_albums']) ? $txt['bg_no_my_albums'] : $txt['bg_no_matching_albums'], '</p></div>';
		return;
	}

	echo '<div class="bg_album_grid">';

	foreach ($context['bg_albums'] as $album)
	{
		$cover_html = '';

		if (!empty($album['locked']))
		{
			$cover_html = '<div class="bg_album_cover bg_album_cover_placeholder bg_album_cover_locked">'
				. '<span class="bg_album_placeholder_icon" aria-hidden="true">&#128274;</span>'
				. '<span class="bg_album_placeholder_label">' . htmlspecialchars($txt['bg_restricted_album'], ENT_QUOTES, 'UTF-8') . '</span>'
				. '</div>';
		}
		elseif (!empty($album['id_cover_media']))
		{
			$cover_html = '<div class="bg_album_cover"><img src="'
				. $scripturl . '?action=gallery;sa=file;media=' . (int) $album['id_cover_media']
				. ';thumb=1" alt="" loading="lazy"></div>';
		}
		else
		{
			$icon = '&#128444;'; // framed picture / empty gallery
			$label = $txt['bg_no_cover_media'];

			$mime = strtolower((string) ($album['first_media_mime'] ?? ''));
			$type = strtolower((string) ($album['first_media_type'] ?? ''));

			if ($type === 'video' || strpos($mime, 'video/') === 0)
			{
				$icon = '&#127916;';
				$label = $txt['bg_media_type_video'];
			}
			elseif (strpos($mime, 'audio/') === 0)
			{
				$icon = '&#127925;';
				$label = $txt['bg_media_type_audio'];
			}
			elseif (
				strpos($mime, 'zip') !== false
				|| strpos($mime, 'compressed') !== false
				|| strpos($mime, 'archive') !== false
			)
			{
				$icon = '&#128230;';
				$label = $txt['bg_media_type_archive'];
			}
			elseif ($mime !== '' && strpos($mime, 'image/') !== 0)
			{
				$icon = '&#128196;';
				$label = $txt['bg_media_type_file'];
			}

			$cover_html = '<div class="bg_album_cover bg_album_cover_placeholder">'
				. '<span class="bg_album_placeholder_icon" aria-hidden="true">' . $icon . '</span>'
				. '<span class="bg_album_placeholder_label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>'
				. '</div>';
		}

		echo '
		<a class="bg_album_card', !empty($album['locked']) ? ' bg_album_card_locked' : '', '" href="', $scripturl, '?action=gallery;sa=album;album=', (int) $album['id_album'], '">
			', $cover_html, '
			<div class="bg_album_card_body">
				<h3>', htmlspecialchars($album['title'], ENT_QUOTES, 'UTF-8'), '</h3>
				<div class="smalltext">', htmlspecialchars($album['owner_name'] ?: $txt['bg_unknown_member'], ENT_QUOTES, 'UTF-8');

		if (!empty($album['category_name']))
			echo ' · ', htmlspecialchars($album['category_name'], ENT_QUOTES, 'UTF-8');

		echo '</div>
				<p>', nl2br(htmlspecialchars($album['description'], ENT_QUOTES, 'UTF-8')), '</p>
				<div class="smalltext">', (int) $album['num_items'], ' ', $txt['bg_items'], ' · ', (int) $album['num_views'], ' ', $txt['bg_views'], '</div>';

		if (!empty($album['locked']))
			echo '<div class="bg_locked_note"><span aria-hidden="true">&#128274;</span> ', $txt['bg_discoverable_locked'], '</div>';

		echo '</div></a>';
	}
 
	echo '</div>';

	if (!empty($context['bg_album_page_index']))
		echo '<div class="pagesection bg_album_pages_bottom"><div class="pagelinks">', $context['bg_album_page_index'], '</div></div>';

	if (empty($context['bg_is_my_albums']))
	{
		$bg_ad = bg_integration_slot('gallery_index_bottom');
		if ($bg_ad !== '')
			echo '<div class="bg_gallery_ad_slot bg_gallery_ad_index_bottom">', $bg_ad, '</div>';
	}
}

function template_bg_my_favorites()
{
	global $context, $txt, $scripturl;

	$type = $context['bg_favorite_type'];

	echo '
	<div class="cat_bar"><h3 class="catbg">', $txt['bg_my_favorites'], '</h3></div>
	<div class="windowbg bg_gallery_toolbar">
		<div>
			<strong>', $txt['bg_my_favorites'], '</strong>
			<div class="smalltext">', $txt['bg_my_favorites_help'], '</div>
		</div>
		<div class="buttonlist">
			<a class="button" href="', $scripturl, '?action=gallery">', htmlspecialchars($context['bg_identity_label'] ?? 'Media', ENT_QUOTES, 'UTF-8'), '</a>
			<a class="button" href="', $scripturl, '?action=gallery;sa=mygallery">', $txt['bg_my_gallery'], '</a>
			<a class="button" href="', $scripturl, '?action=gallery;sa=myalbums">', $txt['bg_my_albums'], '</a>';
	if (!empty($context['bg_can_moderate']))
		echo '<a class="button" href="', $scripturl, '?action=gallery;sa=reports">', $txt['bg_report_queue'], '</a>';
	echo '
		</div>
	</div><br>

	<div class="buttonlist bg_favorites_tabs">
		<a class="button', $type === 'albums' ? ' active' : '', '" href="', $scripturl, '?action=gallery;sa=favorites;type=albums">', $txt['bg_favorite_albums'], '</a>
		<a class="button', $type === 'media' ? ' active' : '', '" href="', $scripturl, '?action=gallery;sa=favorites;type=media">', $txt['bg_favorite_media'], '</a>
	</div>
	<div class="clear"></div><br>';

	if ($type === 'albums')
	{
		if (empty($context['bg_favorite_albums']))
		{
			echo '<div class="windowbg"><p class="information">', $txt['bg_no_favorite_albums'], '</p></div>';
			return;
		}

		echo '<div class="bg_favorite_grid">';

		foreach ($context['bg_favorite_albums'] as $album)
		{
			$thumb = !empty($album['id_cover_media'])
				? $scripturl . '?action=gallery;sa=file;media=' . (int) $album['id_cover_media'] . ';thumb=1'
				: '';

			echo '
			<div class="bg_favorite_card">
				<a class="bg_favorite_preview" href="', $scripturl, '?action=gallery;sa=album;album=', (int) $album['id_album'], '">';

			if ($thumb !== '')
				echo '<img src="', $thumb, '" loading="lazy" alt="', htmlspecialchars($album['title'], ENT_QUOTES, 'UTF-8'), '">';
			else
				echo '<span class="bg_favorite_placeholder">ALBUM</span>';

			echo '
				</a>
				<div class="bg_favorite_body">
					<strong>', htmlspecialchars($album['title'], ENT_QUOTES, 'UTF-8'), '</strong>
					<div class="smalltext">', htmlspecialchars($album['owner_name'] ?: $txt['bg_unknown_member'], ENT_QUOTES, 'UTF-8'), '</div>
					<div class="smalltext">', (int) $album['num_items'], ' ', $txt['bg_items'], ' · ', (int) $album['num_views'], ' ', $txt['bg_views'], '</div>
					<a class="button" href="', $scripturl, '?action=gallery;sa=album;album=', (int) $album['id_album'], '">', $txt['bg_view_album'], '</a>
				</div>
			</div>';
		}

		echo '</div>';
		return;
	}

	if (empty($context['bg_favorites']))
	{
		echo '<div class="windowbg"><p class="information">', $txt['bg_no_favorites'], '</p></div>';
		return;
	}

	echo '<div class="bg_favorite_grid">';

	foreach ($context['bg_favorites'] as $media)
	{
		$thumb = !empty($media['thumb_key']) || !empty($media['poster_key'])
			? $scripturl . '?action=gallery;sa=file;media=' . (int) $media['id_media'] . ';thumb=1'
			: '';

		echo '
		<div class="bg_favorite_card">
			<a class="bg_favorite_preview" href="', $scripturl, '?action=gallery;sa=album;album=', (int) $media['id_album'], '">';

		if ($thumb !== '')
			echo '<img src="', $thumb, '" loading="lazy" alt="', htmlspecialchars($media['title'], ENT_QUOTES, 'UTF-8'), '">';
		else
			echo '<span class="bg_favorite_placeholder">', strtoupper(htmlspecialchars($media['media_type'], ENT_QUOTES, 'UTF-8')), '</span>';

		echo '
			</a>
			<div class="bg_favorite_body">
				<strong>', htmlspecialchars($media['title'], ENT_QUOTES, 'UTF-8'), '</strong>
				<div class="smalltext">', htmlspecialchars($media['album_title'], ENT_QUOTES, 'UTF-8'), '</div>
				<div class="smalltext bg_card_stats">
					<span class="bg_card_stat" title="', htmlspecialchars($txt['bg_views'], ENT_QUOTES, 'UTF-8'), '" aria-label="', htmlspecialchars($txt['bg_views'], ENT_QUOTES, 'UTF-8'), '">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6S2.5 12 2.5 12Z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="2"/></svg>
						<span>', (int) $media['num_views'], '</span>
					</span>
					<span class="bg_card_stat" title="', htmlspecialchars($txt['bg_downloads'], ENT_QUOTES, 'UTF-8'), '" aria-label="', htmlspecialchars($txt['bg_downloads'], ENT_QUOTES, 'UTF-8'), '">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						<span>', (int) $media['num_downloads'], '</span>
					</span>
				</div>
				<a class="button" href="', $scripturl, '?action=gallery;sa=album;album=', (int) $media['id_album'], '">', $txt['bg_view_in_album'], '</a>
			</div>
		</div>';
	}

	echo '</div>';
}

function template_bg_album_delete_confirm()
{
	global $context, $txt, $scripturl;

	$a = $context['bg_delete_album'];

	echo '
	<div class="cat_bar"><h3 class="catbg">', $txt['bg_delete_album'], '</h3></div>
	<div class="windowbg bg_album_delete_confirm">
		<div class="errorbox bg_permanent_delete_warning">
			<strong>', $txt['bg_delete_album_warning_title'], '</strong>
			<p>', $txt['bg_delete_album_warning'], '</p>
		</div>';

	if (!empty($_REQUEST['confirmerror']))
		echo '<div class="errorbox">', $txt['bg_delete_album_confirm_error'], '</div>';

	echo '
		<div class="bg_delete_album_summary">
			<strong>', htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8'), '</strong>
			<div class="smalltext">', sprintf($txt['bg_delete_album_media_count'], (int) $a['actual_media_count']), '</div>
		</div>

		<form action="', $scripturl, '?action=gallery;sa=albumdelete" method="post" class="bg_delete_album_form">
			<input type="hidden" name="album" value="', (int) $a['id_album'], '">
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">

			<label class="bg_delete_confirm_check">
				<input type="checkbox" name="confirm_permanent" value="1" required>
				<span>', $txt['bg_delete_album_checkbox'], '</span>
			</label>

			<label>
				<strong>', $txt['bg_delete_album_type_title'], '</strong>
				<input type="text" name="confirm_title" autocomplete="off" required>
				<span class="smalltext">', sprintf($txt['bg_delete_album_type_help'], htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8')), '</span>
			</label>

			<div class="buttonlist">
				<a class="button" href="', $scripturl, '?action=gallery;sa=album;album=', (int) $a['id_album'], '">', $txt['bg_cancel'], '</a>
				<button class="button bg_delete_album_danger" type="submit">', $txt['bg_delete_album_permanently'], '</button>
			</div>
		</form>
	</div>';
}

function template_bg_album()
{
	global $context, $txt, $scripturl;

	$a = $context['bg_album'];

	echo '
	<div class="cat_bar"><h3 class="catbg">', htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8'), '</h3></div>
	<div class="windowbg bg_album_header_panel">
		<div class="bg_gallery_toolbar bg_album_header_toolbar">
			<div class="bg_album_summary">
				<div class="bg_album_owner"><strong>', htmlspecialchars($a['owner_name'] ?: $txt['bg_unknown_member'], ENT_QUOTES, 'UTF-8'), '</strong></div>
				<div class="smalltext bg_album_category">', !empty($a['category_name']) ? htmlspecialchars($a['category_name'], ENT_QUOTES, 'UTF-8') : $txt['bg_uncategorized'], '</div>
			</div>
			<div class="bg_album_actions">';

	if (!empty($a['can_edit']))
	{
		echo '<a class="button" href="', $scripturl, '?action=gallery;sa=edit;album=', (int) $a['id_album'], '">', $txt['bg_edit_album'], '</a> ';
		echo '<a class="button" href="', $scripturl, '?action=gallery;sa=requests;album=', (int) $a['id_album'], '">', $txt['bg_access_requests'], '</a> ';
	}

	if (!empty($a['album_download_available']))
		echo '<a class="button" href="', $scripturl, '?action=gallery;sa=albumdownload;album=', (int) $a['id_album'], '">', $txt['bg_download_album'], '</a> ';

	if (!empty($a['can_like_album']))
	{
		if (!empty($a['better_reactions']['enabled']))
			echo '
		<div class="bg_album_reaction_wrap" id="bg_album_reaction_wrap">
			<button class="button bg_album_reaction_trigger" id="bg_album_reaction_trigger" type="button" aria-haspopup="true" aria-expanded="false"></button>
			<div class="bg_br_tray bg_album_reaction_tray" id="bg_album_reaction_tray" role="menu"></div>
		</div>';
		else
			echo '
		<form class="bg_album_like_form" action="', $scripturl, '?action=gallery;sa=albumlike" method="post">
			<input type="hidden" name="album" value="', (int) $a['id_album'], '">
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
			<button class="button bg_album_like_button', !empty($a['album_liked']) ? ' is-liked' : '', '"
				type="submit"
				title="', htmlspecialchars(!empty($a['album_liked']) ? $txt['bg_unlike_album'] : $txt['bg_like_album'], ENT_QUOTES, 'UTF-8'), '"
				aria-label="', htmlspecialchars(!empty($a['album_liked']) ? $txt['bg_unlike_album'] : $txt['bg_like_album'], ENT_QUOTES, 'UTF-8'), '">
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="M12 20.5 10.55 19.18C5.4 14.5 2 11.42 2 7.65 2 4.57 4.42 2.15 7.5 2.15c1.74 0 3.41.81 4.5 2.09a6.02 6.02 0 0 1 4.5-2.09c3.08 0 5.5 2.42 5.5 5.5 0 3.77-3.4 6.85-8.55 11.54L12 20.5Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
				</svg>
				<span class="bg_album_like_count">', (int) $a['album_like_count'], '</span>
			</button>
		</form>';
	}

	if (!empty($a['can_favorite_album']))
		echo '
		<form class="bg_album_favorite_form" action="', $scripturl, '?action=gallery;sa=albumfavorite" method="post">
			<input type="hidden" name="album" value="', (int) $a['id_album'], '">
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
			<button class="button bg_album_favorite_button', !empty($a['album_favorited']) ? ' is-favorited' : '', '"
				type="submit"
				title="', htmlspecialchars(!empty($a['album_favorited']) ? $txt['bg_remove_favorite_album'] : $txt['bg_favorite_album'], ENT_QUOTES, 'UTF-8'), '"
				aria-label="', htmlspecialchars(!empty($a['album_favorited']) ? $txt['bg_remove_favorite_album'] : $txt['bg_favorite_album'], ENT_QUOTES, 'UTF-8'), '">
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="M6 3h12v18l-6-4-6 4V3Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
				</svg>
			</button>
		</form>';

	if (!empty($a['can_delete_album']))
		echo '
		<a class="button bg_album_delete_button"
			href="', $scripturl, '?action=gallery;sa=albumdeleteconfirm;album=', (int) $a['id_album'], '"
			title="', htmlspecialchars($txt['bg_delete_album'], ENT_QUOTES, 'UTF-8'), '"
			aria-label="', htmlspecialchars($txt['bg_delete_album'], ENT_QUOTES, 'UTF-8'), '">
			<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<path d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</a>';

	if (!empty($a['better_reactions']['enabled']))
		echo '
		<div class="bg_album_reaction_summary bg_br_summary" id="bg_album_reaction_summary"></div>';

	echo '</div>
		</div>';

	if (!empty($a['better_reactions']['enabled']))
	{
		$album_reaction_json = json_encode(array(
			'id_album' => (int) $a['id_album'],
			'session_var' => (string) $context['session_var'],
			'session_id' => (string) $context['session_id'],
			'payload' => $a['better_reactions'],
		), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);

		echo '
		<script type="application/json" id="bg_album_reactions_data">', $album_reaction_json, '</script>';
	}

	echo '<div class="bg_album_metadata">';

	if (!empty($context['bg_album_integrations_html']))
		echo $context['bg_album_integrations_html'];

	if (trim((string) $a['description']) !== '')
		echo '<p class="bg_album_description">', nl2br(htmlspecialchars($a['description'], ENT_QUOTES, 'UTF-8')), '</p>';

	echo '</div>
	</div><br>';

	if (!empty($_REQUEST['bulkdeleted']) && !empty($a['can_manage_media']))
		echo '<div class="successbox">', sprintf($txt['bg_bulk_delete_success'], (int) $_REQUEST['bulkdeleted']), '</div>';

	if (!empty($_REQUEST['bulkmoved']) && !empty($a['can_manage_media']))
	{
		$destination_title = '';

		foreach ($context['bg_move_destinations'] as $destination)
			if ((int) $destination['id_album'] === (int) ($_REQUEST['movedto'] ?? 0))
			{
				$destination_title = $destination['title'];
				break;
			}

		echo '<div class="successbox">',
			sprintf(
				$txt['bg_bulk_move_success'],
				(int) $_REQUEST['bulkmoved'],
				htmlspecialchars($destination_title !== '' ? $destination_title : $txt['bg_album'], ENT_QUOTES, 'UTF-8')
			),
		'</div>';
	}

	if (!empty($_REQUEST['moveerror']) && !empty($a['can_manage_media']))
		echo '<div class="errorbox">', $txt['bg_bulk_move_destination_required'], '</div>';

	if (empty($a['accessible']))
	{
		echo '<div class="cat_bar"><h3 class="catbg">', $txt['bg_restricted_album'], '</h3></div>
		<div class="windowbg">
			<p class="information">', $txt['bg_restricted_explain'], '</p>';

		if (in_array((int) $a['access_mode'], array(3, 4), true))
			echo '
			<form action="', $scripturl, '?action=gallery;sa=unlock;album=', (int) $a['id_album'], '" method="post" class="bg_inline_form">
				<label><strong>', $txt['bg_album_password'], '</strong>
					<input type="password" name="album_password" autocomplete="current-password">
				</label>
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
				<button class="button" type="submit" name="bg_unlock_album" value="1">', $txt['bg_unlock_album'], '</button>
			</form>';

		if (!empty($a['can_request']))
		{
			if ($a['request_status'] === 'pending')
				echo '<p class="successbox">', $txt['bg_request_pending'], '</p>';
			else
				echo '
				<form action="', $scripturl, '?action=gallery;sa=request;album=', (int) $a['id_album'], '" method="post">
					<label><strong>', $txt['bg_request_message'], '</strong>
						<textarea name="request_message" rows="3"></textarea>
					</label>
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
					<button class="button" type="submit">', $txt['bg_request_access'], '</button>
				</form>';
		}

		echo '</div>';
		return;
	}

	$bg_ad = bg_integration_slot('gallery_album_top', array(
		'id_album' => (int) $a['id_album'],
	));
	if ($bg_ad !== '')
		echo '<div class="bg_gallery_ad_slot bg_gallery_ad_album_top">', $bg_ad, '</div>';

	if (!empty($a['can_upload']))
	{
		$u = $context['bg_upload_config'];

		echo '
		<div class="cat_bar"><h3 class="catbg">', $txt['bg_upload_media'], '</h3></div>
		<div class="windowbg">
			<form
				id="bg_media_uploader"
				class="bg_uploader"
				action="', $scripturl, '?action=gallery;sa=upload;album=', (int) $a['id_album'], '"
				method="post"
				enctype="multipart/form-data"
				data-max-files="', (int) $u['max_files'], '"
				data-progress-mode="', htmlspecialchars($u['progress_mode'], ENT_QUOTES, 'UTF-8'), '"
				data-max-image-bytes="', (int) $u['max_image_bytes'], '"
				data-max-video-bytes="', (int) $u['max_video_bytes'], '"
				data-max-file-bytes="', (int) $u['max_file_bytes'], '"
				data-allowed-extensions="', htmlspecialchars($u['allowed_extensions_csv'], ENT_QUOTES, 'UTF-8'), '"
				data-php-file-ceiling="', (int) $u['php_file_ceiling'], '"
				data-storage-quota-bytes="', (int) $u['storage_quota_bytes'], '"
				data-storage-used-bytes="', (int) $u['storage_used_bytes'], '"
				data-storage-remaining-bytes="', (int) $u['storage_remaining_bytes'], '"
				data-storage-unlimited="', !empty($u['storage_unlimited']) ? '1' : '0', '">

				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">

				<div id="bg_upload_dropzone" class="bg_upload_dropzone" tabindex="0" role="button" aria-controls="bg_media_files">
					<svg class="bg_upload_icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path d="M12 16V5m0 0L8 9m4-4 4 4M5 15v4h14v-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<strong>', $txt['bg_drop_media_here'], '</strong>
					<span class="smalltext">', $txt['bg_drop_media_or_browse'], '</span>
					<span class="button bg_upload_browse_button">', $txt['bg_browse_files'], '</span>
				</div>

				<input
					id="bg_media_files"
					class="bg_upload_native_input"
					type="file"
					name="bg_media_files[]"
					accept=".', implode(',.', array_map(function ($ext) { return htmlspecialchars($ext, ENT_QUOTES, 'UTF-8'); }, $u['allowed_extensions'])), '"
					multiple>

				<div class="smalltext bg_upload_limits">
					', sprintf(
						$txt['bg_upload_limits_summary'],
						(int) $u['max_files'],
						round($u['max_image_bytes'] / 1048576, 1),
						round($u['max_video_bytes'] / 1048576, 1)
					), '<br>
					', !empty($u['storage_unlimited'])
						? $txt['bg_upload_storage_unlimited']
						: sprintf(
							$txt['bg_upload_storage_remaining'],
							htmlspecialchars(bg_format_bytes($u['storage_remaining_bytes']), ENT_QUOTES, 'UTF-8'),
							htmlspecialchars(bg_format_bytes($u['storage_quota_bytes']), ENT_QUOTES, 'UTF-8')
						), '
				</div>

				<div id="bg_upload_notice" class="bg_upload_notice" role="status" aria-live="polite"></div>

				<div id="bg_upload_top_actions" class="bg_upload_actions bg_upload_actions_top" hidden>
					<button id="bg_upload_start_top" class="button bg_upload_primary_button" type="button">', $txt['bg_upload_queued'], '</button>
				</div>

				<div id="bg_upload_overall" class="bg_upload_overall" hidden>
					<div class="bg_upload_overall_head">
						<strong>', $txt['bg_upload_overall_progress'], '</strong>
						<span id="bg_upload_overall_count" class="smalltext"></span>
					</div>
					<div id="bg_upload_overall_progress" class="bg_upload_overall_track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
						<div id="bg_upload_overall_bar" class="bg_upload_overall_bar"></div>
					</div>
					<div id="bg_upload_overall_status" class="smalltext bg_upload_overall_status">', $txt['bg_upload_waiting'], '</div>
				</div>

				<div id="bg_upload_queue" class="bg_upload_queue" aria-live="polite"></div>

				<div id="bg_upload_bottom_actions" class="bg_upload_actions bg_upload_actions_bottom" hidden>
					<button id="bg_upload_start" class="button bg_upload_primary_button" type="submit">', $txt['bg_upload_queued'], '</button>
					<button id="bg_upload_clear" class="button bg_upload_clear_button" type="button">', $txt['bg_clear_queue'], '</button>
				</div>
			</form>
		</div><br>';
	}

	echo '
	<div class="cat_bar"><h3 class="catbg">', $txt['bg_media'], '</h3></div>
	<div class="windowbg">
		<div class="bg_media_toolbar">
			<div class="smalltext">', sprintf(
				$txt['bg_media_showing_summary'],
				empty($context['bg_media_total']) ? 0 : (int) $context['bg_media_start'] + 1,
				min((int) $context['bg_media_start'] + count($context['bg_media']), (int) $context['bg_media_total']),
				(int) $context['bg_media_total']
			), '</div>
			<form action="', $scripturl, '" method="get" class="bg_media_sort_form">
				<input type="hidden" name="action" value="gallery">
				<input type="hidden" name="sa" value="album">
				<input type="hidden" name="album" value="', (int) $a['id_album'], '">
				<label>
					<span class="smalltext">', $txt['bg_sort_media'], '</span>
					<select name="sort" onchange="this.form.submit()">
						<option value="newest"', $context['bg_media_sort'] === 'newest' ? ' selected' : '', '>', $txt['bg_sort_newest'], '</option>
						<option value="oldest"', $context['bg_media_sort'] === 'oldest' ? ' selected' : '', '>', $txt['bg_sort_oldest'], '</option>
						<option value="title_asc"', $context['bg_media_sort'] === 'title_asc' ? ' selected' : '', '>', $txt['bg_sort_title_asc'], '</option>
						<option value="title_desc"', $context['bg_media_sort'] === 'title_desc' ? ' selected' : '', '>', $txt['bg_sort_title_desc'], '</option>
					</select>
				</label>
				<noscript><button class="button" type="submit">', $txt['go'], '</button></noscript>
			</form>
		</div>';

	if (empty($context['bg_media']))
	{
		echo '<p class="information">', $txt['bg_no_media'], '</p></div>';
		return;
	}

	if (!empty($context['page_index']))
		echo '<div class="pagesection bg_media_pages_top"><div class="pagelinks">', $context['page_index'], '</div></div>';

	if (!empty($a['can_manage_media']))
		echo '
		<form id="bg_bulk_manage_form"
			class="bg_bulk_manage_form"
			action="', $scripturl, '?action=gallery;sa=mediabulkdelete"
			method="post"
			data-confirm-template="', htmlspecialchars($txt['bg_bulk_delete_confirm'], ENT_QUOTES, 'UTF-8'), '"
			data-move-confirm-template="', htmlspecialchars($txt['bg_bulk_move_confirm'], ENT_QUOTES, 'UTF-8'), '">
			<input type="hidden" name="album" value="', (int) $a['id_album'], '">
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
			<div class="bg_bulk_toolbar">
				<div class="bg_bulk_toolbar_left">
					<strong>', $txt['bg_bulk_manage'], '</strong>
					<span id="bg_bulk_count" class="smalltext">', $txt['bg_bulk_none_selected'], '</span>
				</div>
				<div class="bg_bulk_toolbar_actions">
					<button id="bg_bulk_select_all" class="button" type="button">', $txt['bg_select_all'], '</button>
					<button id="bg_bulk_clear" class="button" type="button">', $txt['bg_clear_selection'], '</button>';

	if (!empty($context['bg_move_destinations']))
	{
		echo '
					<label class="bg_bulk_move_picker">
						<span class="smalltext">', $txt['bg_move_to_album'], '</span>
						<select id="bg_bulk_destination" name="destination_album">
							<option value="">', $txt['bg_choose_album'], '</option>';

		foreach ($context['bg_move_destinations'] as $destination)
			echo '<option value="', (int) $destination['id_album'], '">',
				htmlspecialchars($destination['title'], ENT_QUOTES, 'UTF-8'),
			'</option>';

		echo '
						</select>
					</label>
					<button id="bg_bulk_move"
						class="button bg_bulk_move_button"
						type="submit"
						formaction="', $scripturl, '?action=gallery;sa=mediabulkmove"
						disabled>
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<path d="M4 7h8l2 2h6v10H4V7Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
							<path d="M9 13h6m0 0-2-2m2 2-2 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
						<span>', $txt['bg_move_selected'], '</span>
					</button>';
	}

	echo '
					<button id="bg_bulk_delete" class="button bg_bulk_delete_button" type="submit" disabled>
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<path d="M7 7h10l-.7 12H7.7L7 7Zm2-3h6l1 2H8l1-2Zm-3 2h12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M10 10v6M14 10v6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
						</svg>
						<span>', $txt['bg_delete_selected'], '</span>
					</button>
				</div>
			</div>
		</form>';

	echo '<div class="bg_media_grid">';

	$bg_ad_item_index = 0;

	foreach ($context['bg_media'] as $media)
	{
		echo '<div class="bg_media_card', !empty($a['can_manage_media']) ? ' bg_media_card_selectable' : '', '">';

		if (!empty($a['can_manage_media']))
			echo '
			<label class="bg_media_bulk_select" title="', htmlspecialchars($txt['bg_select_media'], ENT_QUOTES, 'UTF-8'), '">
				<input class="bg_bulk_media_checkbox"
					type="checkbox"
					name="media[]"
					value="', (int) $media['id_media'], '"
					form="bg_bulk_manage_form"
					aria-label="', htmlspecialchars(sprintf($txt['bg_select_media_named'], $media['title']), ENT_QUOTES, 'UTF-8'), '">
				<span aria-hidden="true"></span>
			</label>';

		if ($media['media_type'] === 'video')
			echo '
			<a class="bg_media_open bg_media_preview bg_media_video_preview"
				href="', $scripturl, '?action=gallery;sa=file;media=', (int) $media['id_media'], '"
				data-media-id="', (int) $media['id_media'], '">
				', !empty($media['poster_key'])
					? '<img src="' . $scripturl . '?action=gallery;sa=file;media=' . (int) $media['id_media'] . ';thumb=1" alt="' . htmlspecialchars($media['title'], ENT_QUOTES, 'UTF-8') . '" loading="lazy">'
					: '<span class="bg_media_video_fallback"></span>', '
				<span class="bg_media_play" aria-hidden="true">
					<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z" fill="currentColor"/></svg>
				</span>
			</a>';
		elseif ($media['media_type'] === 'image')
			echo '
			<a class="bg_media_open bg_media_preview"
				href="', $scripturl, '?action=gallery;sa=file;media=', (int) $media['id_media'], '"
				data-media-id="', (int) $media['id_media'], '">
				<img src="', $scripturl, '?action=gallery;sa=file;media=', (int) $media['id_media'], ';thumb=1" alt="', htmlspecialchars($media['title'], ENT_QUOTES, 'UTF-8'), '" loading="lazy">
			</a>';
		else
		{
			$type_label = isset($txt['bg_media_type_' . $media['media_type']])
				? $txt['bg_media_type_' . $media['media_type']]
				: $txt['bg_media_type_file'];
			$downloadable = !empty($a['downloads_enabled']);
			echo $downloadable
				? '<a class="bg_media_preview bg_media_file_preview" href="' . $scripturl . '?action=gallery;sa=file;media=' . (int) $media['id_media'] . ';download=1">'
				: '<div class="bg_media_preview bg_media_file_preview bg_media_file_preview_disabled">';
			echo '
				<span class="bg_media_file_icon" aria-hidden="true">
					<svg viewBox="0 0 24 24"><path d="M6 3h9l3 3v15H6V3Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M15 3v4h4M9 12h6M9 16h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
				</span>
				<span class="bg_media_file_type">', htmlspecialchars($type_label, ENT_QUOTES, 'UTF-8'), '</span>';
			echo $downloadable ? '</a>' : '</div>';
		}

		echo '
			<div class="bg_media_card_body">
				<strong>', htmlspecialchars($media['title'], ENT_QUOTES, 'UTF-8'), '</strong>
				<div class="bg_media_meta_primary smalltext">';

		$primary_meta = array();

		if ($media['media_type'] === 'video' && !empty($media['duration_ms']))
		{
			$seconds = (int) round($media['duration_ms'] / 1000);
			$primary_meta[] = floor($seconds / 60) . ':' . str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT);
		}

		if (!empty($media['width']) && !empty($media['height']))
			$primary_meta[] = (int) $media['width'] . ' × ' . (int) $media['height'];

		$primary_meta[] = $media['file_size'] >= 1048576
			? round(((int) $media['file_size']) / 1048576, 1) . ' MB'
			: round(((int) $media['file_size']) / 1024, 1) . ' KB';

		echo implode(' · ', $primary_meta), '</div>';

		echo '
			<div class="bg_media_stats_row smalltext">
				<span class="bg_card_stat" title="', htmlspecialchars($txt['bg_views'], ENT_QUOTES, 'UTF-8'), '" aria-label="', htmlspecialchars($txt['bg_views'], ENT_QUOTES, 'UTF-8'), '">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6S2.5 12 2.5 12Z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="2"/></svg>
					<span>', (int) $media['num_views'], '</span>
				</span>';

		if (!empty($a['downloads_enabled']))
			echo '
				<span class="bg_card_stat" title="', htmlspecialchars($txt['bg_downloads'], ENT_QUOTES, 'UTF-8'), '" aria-label="', htmlspecialchars($txt['bg_downloads'], ENT_QUOTES, 'UTF-8'), '">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					<span>', (int) $media['num_downloads'], '</span>
				</span>';

		if (!empty($a['likes_enabled']))
			echo '
				<span class="bg_card_stat" title="', htmlspecialchars($txt['bg_like'], ENT_QUOTES, 'UTF-8'), '" aria-label="', htmlspecialchars($txt['bg_like'], ENT_QUOTES, 'UTF-8'), '">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20.5 4.7 13.7A5 5 0 0 1 12 6.9a5 5 0 0 1 7.3 6.8L12 20.5Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
					<span>', (int) $media['like_count'], '</span>
				</span>';

		if (!empty($a['comments_enabled']))
			echo '
				<span class="bg_card_stat" title="', htmlspecialchars($txt['bg_comments'], ENT_QUOTES, 'UTF-8'), '" aria-label="', htmlspecialchars($txt['bg_comments'], ENT_QUOTES, 'UTF-8'), '">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 5h16v11H9l-5 4V5Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
					<span>', (int) $media['comment_count'], '</span>
				</span>';

		echo '</div>';

		if (!empty($media['description']))
			echo '<p class="bg_media_description">', nl2br(htmlspecialchars($media['description'], ENT_QUOTES, 'UTF-8')), '</p>';

		if (!empty($a['can_manage_media']))
		{
			echo '
				<div class="bg_media_manage">
					<a class="bg_media_action bg_media_action_edit"
						href="', $scripturl, '?action=gallery;sa=mediaedit;media=', (int) $media['id_media'], '"
						title="', htmlspecialchars($txt['bg_edit_media'], ENT_QUOTES, 'UTF-8'), '"
						aria-label="', htmlspecialchars($txt['bg_edit_media'], ENT_QUOTES, 'UTF-8'), '">
						<svg class="bg_media_action_icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
<path d="M4 20h4.25L19.5 8.75a2.12 2.12 0 0 0 0-3L18.25 4.5a2.12 2.12 0 0 0-3 0L4 15.75V20Zm2-3.42L14.54 8.04l1.42 1.42L7.42 18H6v-1.42Zm9.96-9.96 1.41-1.41a.62.62 0 0 1 .88 0l1.25 1.25a.62.62 0 0 1 0 .88l-1.41 1.41-2.13-2.13Z" fill="currentColor"/>
</svg>
					</a>';

			if (!empty($media['thumb_key']))
			{
				if (!empty($media['is_cover']))
					echo '
					<span class="bg_media_action bg_media_action_cover bg_media_action_cover_active"
						title="', htmlspecialchars($txt['bg_album_cover'], ENT_QUOTES, 'UTF-8'), '"
						aria-label="', htmlspecialchars($txt['bg_album_cover'], ENT_QUOTES, 'UTF-8'), '">
						<svg class="bg_media_action_icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
<rect x="4" y="4" width="16" height="16" rx="2" ry="2" fill="currentColor"/>
<path d="m8 12 2.5 2.5L16 9" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
					</span>';
				else
					echo '
					<form action="', $scripturl, '?action=gallery;sa=setcover" method="post">
						<input type="hidden" name="media" value="', (int) $media['id_media'], '">
						<input type="hidden" name="start" value="', (int) ($context['bg_media_start'] ?? 0), '">
						<input type="hidden" name="sort" value="', htmlspecialchars($context['bg_media_sort'] ?? 'newest', ENT_QUOTES, 'UTF-8'), '">
						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
						<button class="bg_media_action bg_media_action_cover"
							type="submit"
							title="', htmlspecialchars($txt['bg_set_album_cover'], ENT_QUOTES, 'UTF-8'), '"
							aria-label="', htmlspecialchars($txt['bg_set_album_cover'], ENT_QUOTES, 'UTF-8'), '">
							<svg class="bg_media_action_icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
<rect x="4" y="4" width="16" height="16" rx="2" ry="2" fill="none" stroke="currentColor" stroke-width="2"/>
<path d="m8 12 2.5 2.5L16 9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
						</button>
					</form>';
			}

			echo '
					<form action="', $scripturl, '?action=gallery;sa=mediadelete" method="post">
						<input type="hidden" name="media" value="', (int) $media['id_media'], '">
						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
						<button class="bg_media_action bg_media_action_delete you_sure"
							type="submit"
							data-confirm="', htmlspecialchars($txt['bg_delete_media_confirm'], ENT_QUOTES, 'UTF-8'), '"
							title="', htmlspecialchars($txt['bg_delete_media'], ENT_QUOTES, 'UTF-8'), '"
							aria-label="', htmlspecialchars($txt['bg_delete_media'], ENT_QUOTES, 'UTF-8'), '">
							<svg class="bg_media_action_icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
<path d="M7 7h10l-.7 12H7.7L7 7Zm2-3h6l1 2H8l1-2Zm-3 2h12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M10 10v6M14 10v6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
						</button>
					</form>
				</div>';
		}

		echo '
			</div>
		</div>';

		$bg_ad_item_index++;
		$bg_ad = bg_integration_slot('gallery_album_repeat', array(
			'id_album' => (int) $a['id_album'],
			'item_index' => $bg_ad_item_index,
			'id_media' => (int) $media['id_media'],
		));
		if ($bg_ad !== '')
		{
			// Integrations may opt into one normal gallery-grid slot by returning
			// this generic presentation marker. Otherwise the existing full row
			// remains the safe/default presentation.
			$bg_ad_is_card = strpos($bg_ad, 'data-bg-slot-presentation="card"') !== false;
			$bg_ad_class = $bg_ad_is_card
				? 'bg_gallery_ad_slot bg_gallery_ad_repeat bg_gallery_ad_repeat_card'
				: 'bg_gallery_ad_slot bg_gallery_ad_repeat bg_gallery_ad_repeat_row';

			echo '<div class="', $bg_ad_class, '" data-bg-ad-after-item="', $bg_ad_item_index, '">', $bg_ad, '</div>';
		}
	}

	echo '</div>';

	if (!empty($context['page_index']))
		echo '<div class="pagesection bg_media_pages_bottom"><div class="pagelinks">', $context['page_index'], '</div></div>';

	$bg_ad = bg_integration_slot('gallery_album_bottom', array(
		'id_album' => (int) $a['id_album'],
	));
	if ($bg_ad !== '')
		echo '<div class="bg_gallery_ad_slot bg_gallery_ad_album_bottom">', $bg_ad, '</div>';

	echo '</div>

	<div id="bg_lightbox"
		class="bg_lightbox"
		hidden
		data-session-var="', htmlspecialchars($context['session_var'], ENT_QUOTES, 'UTF-8'), '"
		data-session-id="', htmlspecialchars($context['session_id'], ENT_QUOTES, 'UTF-8'), '">
		<div class="bg_lightbox_backdrop" data-bg-close></div>
		<div class="bg_lightbox_dialog" role="dialog" aria-modal="true" aria-labelledby="bg_lightbox_title">
			<button class="bg_lightbox_close" type="button" data-bg-close title="', $txt['bg_close_viewer'], '" aria-label="', $txt['bg_close_viewer'], '">
				<svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
			</button>
			<button class="bg_lightbox_nav bg_lightbox_prev" type="button" data-bg-prev title="', $txt['bg_previous_media'], '" aria-label="', $txt['bg_previous_media'], '">
				<svg viewBox="0 0 24 24"><path d="m15 5-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<div class="bg_lightbox_stage" id="bg_lightbox_stage"></div>
			<button class="bg_lightbox_nav bg_lightbox_next" type="button" data-bg-next title="', $txt['bg_next_media'], '" aria-label="', $txt['bg_next_media'], '">
				<svg viewBox="0 0 24 24"><path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<aside class="bg_lightbox_info">
				<h3 id="bg_lightbox_title"></h3>
				<p id="bg_lightbox_description"></p>
				<div id="bg_lightbox_integrations" class="bg_lightbox_integrations" hidden></div>
				<div class="bg_lightbox_toolbar">
				<div class="bg_lightbox_actions">
					<a id="bg_full_size_link" class="button bg_lightbox_action bg_icon_button"
						href="#" target="_blank" rel="noopener" hidden
						title="', htmlspecialchars($txt['bg_view_full_size'], ENT_QUOTES, 'UTF-8'), '"
						aria-label="', htmlspecialchars($txt['bg_view_full_size'], ENT_QUOTES, 'UTF-8'), '">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</a>
';
				if (!empty($a['downloads_enabled']))
					echo '
					<a id="bg_download_link" class="button bg_lightbox_action bg_icon_button"
						href="#" download hidden
						title="', htmlspecialchars($txt['bg_download_media'], ENT_QUOTES, 'UTF-8'), '"
						aria-label="', htmlspecialchars($txt['bg_download_media'], ENT_QUOTES, 'UTF-8'), '">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</a>';

			echo '
				</div>

				<div class="bg_lightbox_member_actions">
					<button id="bg_lightbox_favorite"
						class="button bg_favorite_button bg_icon_button"
						type="button"
						aria-pressed="false"
						title="', htmlspecialchars($txt['bg_favorite'], ENT_QUOTES, 'UTF-8'), '"
						aria-label="', htmlspecialchars($txt['bg_favorite'], ENT_QUOTES, 'UTF-8'), '"
						hidden>
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.2 6.4 20.2 7.5 14 3 9.6l6.2-.9L12 3Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
					</button>
					<button id="bg_lightbox_report"
						class="button bg_report_button bg_icon_button"
						type="button"
						title="', htmlspecialchars($txt['bg_report'], ENT_QUOTES, 'UTF-8'), '"
						aria-label="', htmlspecialchars($txt['bg_report'], ENT_QUOTES, 'UTF-8'), '"
						hidden>
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 21V4m0 1h11l-1.5 4L16 13H5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
				</div>


				<div id="bg_lightbox_like_wrap" class="bg_lightbox_like_wrap" hidden>
					<button id="bg_lightbox_like"
						class="button bg_like_button bg_icon_button bg_icon_button_with_badge"
						type="button"
						aria-pressed="false"
						title="', htmlspecialchars($txt['bg_like'], ENT_QUOTES, 'UTF-8'), '"
						aria-label="', htmlspecialchars($txt['bg_like'], ENT_QUOTES, 'UTF-8'), '">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20.5 4.7 13.7A5 5 0 0 1 12 6.9a5 5 0 0 1 7.3 6.8L12 20.5Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
						<strong id="bg_like_count" class="bg_icon_badge">0</strong>
					</button>
				</div>
				</div><!-- .bg_lightbox_toolbar -->
				<div id="bg_lightbox_reaction_summary" class="bg_lightbox_reaction_summary" hidden></div>

				<div id="bg_report_panel" class="bg_report_panel" hidden>
					<h4>', $txt['bg_report_content'], '</h4>
					<form id="bg_report_form">
						<input id="bg_report_target_type" type="hidden" name="target_type" value="media">
						<input id="bg_report_comment_id" type="hidden" name="comment" value="0">
						<label>
							<span class="smalltext">', $txt['bg_report_reason'], '</span>
							<select id="bg_report_reason" name="reason">
								<option value="spam">', $txt['bg_report_reason_spam'], '</option>
								<option value="abuse">', $txt['bg_report_reason_abuse'], '</option>
								<option value="copyright">', $txt['bg_report_reason_copyright'], '</option>
								<option value="inappropriate">', $txt['bg_report_reason_inappropriate'], '</option>
								<option value="other">', $txt['bg_report_reason_other'], '</option>
							</select>
						</label>
						<label>
							<span class="smalltext">', $txt['bg_report_note'], '</span>
							<textarea id="bg_report_note" name="note" rows="3" maxlength="2000"></textarea>
						</label>
						<div class="bg_report_actions">
							<button class="button" type="submit">', $txt['bg_submit_report'], '</button>
							<button id="bg_report_cancel" class="button" type="button">', $txt['bg_cancel'], '</button>
						</div>
					</form>
				</div>

				<div id="bg_comments_panel" class="bg_comments_panel" hidden>
					<h4>', $txt['bg_comments'], '</h4>
					<div id="bg_comments_list" class="bg_comments_list"></div>
					<form id="bg_comment_form" class="bg_comment_form">
						<textarea id="bg_comment_body" name="body" rows="3" maxlength="2000" placeholder="', htmlspecialchars($txt['bg_write_comment'], ENT_QUOTES, 'UTF-8'), '"></textarea>
						<button class="button" type="submit">', $txt['bg_post_comment'], '</button>
					</form>
				</div>
				<div id="bg_lightbox_message" class="bg_lightbox_message" role="status" aria-live="polite"></div>
			</aside>
		</div>
	</div>';
}

function template_bg_album_download()
{
	global $context, $txt, $scripturl;

	$d = $context['bg_album_download'];
	$a = $d['album'];

	echo '
	<div class="cat_bar"><h3 class="catbg">', $txt['bg_download_album'], ': ', htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8'), '</h3></div>
	<div class="windowbg">
		<p>', $txt['bg_album_download_explain'], '</p>
		<div class="bg_album_download_summary">
			<span><strong>', (int) $d['total_files'], '</strong> ', $txt['bg_files'], '</span>
			<span><strong>', bg_template_format_bytes((int) $d['total_size']), '</strong> ', $txt['bg_total_size'], '</span>
			<span><strong>', (int) $d['chunk_mb'], ' MB</strong> ', $txt['bg_target_part_size'], '</span>
		</div>
	</div><br>';

	if (empty($d['chunks']))
	{
		echo '<div class="windowbg"><p class="information">', $txt['bg_album_download_empty'], '</p></div>';
		return;
	}

	echo '
	<div class="cat_bar"><h3 class="catbg">', $txt['bg_download_parts'], '</h3></div>
	<div class="windowbg">
		<div class="bg_album_download_parts">';

	$total_parts = count($d['chunks']);

	foreach ($d['chunks'] as $chunk)
		echo '
			<div class="bg_album_download_part">
				<div>
					<strong>', sprintf($txt['bg_download_part_name'], (int) $chunk['part'], $total_parts), '</strong>
					<div class="smalltext">', (int) $chunk['files'], ' ', $txt['bg_files'], ' · ', bg_template_format_bytes((int) $chunk['size']), '</div>
				</div>
				<a class="button" href="', $scripturl, '?action=gallery;sa=albumdownload;album=', (int) $a['id_album'], ';part=', (int) $chunk['part'], '">', $txt['bg_download_part'], '</a>
			</div>';

	echo '
		</div>
		<p class="smalltext">', $txt['bg_album_download_cleanup_note'], '</p>
	</div>';
}

function bg_template_format_bytes($bytes)
{
	$bytes = max(0, (int) $bytes);
	$units = array('B', 'KB', 'MB', 'GB', 'TB');
	$value = (float) $bytes;
	$unit = 0;

	while ($value >= 1024 && $unit < count($units) - 1)
	{
		$value /= 1024;
		$unit++;
	}

	$decimals = $unit === 0 || $value >= 10 ? 0 : 1;

	return number_format($value, $decimals) . ' ' . $units[$unit];
}

function template_bg_media_edit()
{
	global $context, $txt, $scripturl;

	$m = $context['bg_media_edit'];
	$storage_label = !empty($m['storage_backend']) && $m['storage_backend'] === 's3' ? 'Amazon S3' : 'Local server';

	echo '
	<div class="cat_bar"><h3 class="catbg">', $txt['bg_edit_media'], '</h3></div>
	<div class="windowbg">
		<form action="', $scripturl, '?action=gallery;sa=mediaedit;media=', (int) $m['id_media'], '" method="post">
			<dl class="settings">
				<dt>
					<strong>', $txt['bg_media_title'], '</strong><br>
					<span class="smalltext">', $txt['bg_media_title_help'], '</span>
				</dt>
				<dd>
					<input type="text" name="title" value="', htmlspecialchars($m['title'], ENT_QUOTES, 'UTF-8'), '" maxlength="200" size="40">
				</dd>
				<dt>
					<strong>', $txt['bg_media_description'], '</strong><br>
					<span class="smalltext">', $txt['bg_media_description_help'], '</span>
				</dt>
				<dd>
					<textarea name="description" rows="6" cols="40">', htmlspecialchars($m['description'], ENT_QUOTES, 'UTF-8'), '</textarea>
				</dd>
			</dl>';

	if (!empty($context['bg_media_form_integrations_html']))
		echo $context['bg_media_form_integrations_html'];

	echo '
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
			<a class="button" href="', $scripturl, '?action=gallery;sa=album;album=', (int) $m['id_album'], '">', $txt['bg_cancel'], '</a>
			<button class="button" type="submit" name="bg_save_media" value="1">', $txt['save'], '</button>
		</form>
	</div>';
}

function template_bg_album_edit()
{
	global $context, $txt, $scripturl;

	$a = $context['bg_album_form'];

	echo '
	<div class="cat_bar"><h3 class="catbg">', $context['page_title'], '</h3></div>
	<div class="windowbg">';

	if (!empty($context['bg_form_error']))
		echo '<div class="errorbox">', htmlspecialchars($context['bg_form_error'], ENT_QUOTES, 'UTF-8'), '</div>';

	echo '
	<form action="', $scripturl, '?action=gallery;sa=', !empty($a['id_album']) ? 'edit;album=' . (int) $a['id_album'] : 'create', '" method="post" class="bg_album_form">
		<label><strong>', $txt['bg_album_title'], '</strong>
			<input type="text" name="title" maxlength="180" value="', htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8'), '" required>
		</label>

		<label><strong>', $txt['bg_album_description'], '</strong>
			<textarea name="description" rows="6">', htmlspecialchars($a['description'], ENT_QUOTES, 'UTF-8'), '</textarea>
		</label>';

	if (!empty($context['bg_album_form_integrations_html']))
		echo $context['bg_album_form_integrations_html'];

	echo '
		<label><strong>', $txt['bg_category'], '</strong>
			<select name="id_category">
				<option value="0">', $txt['bg_uncategorized'], '</option>';

	foreach ($context['bg_categories'] as $category)
		echo '<option value="', (int) $category['id_category'], '"', (int) $a['id_category'] === (int) $category['id_category'] ? ' selected' : '', '>', htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'), '</option>';

	echo '
			</select>
		</label>

		<label><strong>', $txt['bg_discovery_visibility'], '</strong>
			<select name="discovery_visibility">
				<option value="2"', (int) $a['discovery_visibility'] === 2 ? ' selected' : '', '>', $txt['bg_visibility_public'], '</option>
				<option value="1"', (int) $a['discovery_visibility'] === 1 ? ' selected' : '', '>', $txt['bg_visibility_discoverable'], '</option>
				<option value="0"', (int) $a['discovery_visibility'] === 0 ? ' selected' : '', '>', $txt['bg_visibility_hidden'], '</option>
			</select>
			<span class="smalltext">', $txt['bg_discovery_help'], '</span>
		</label>

		<label><strong>', $txt['bg_content_access'], '</strong>
			<select name="access_mode">
				<option value="0"', (int) $a['access_mode'] === 0 ? ' selected' : '', '>', $txt['bg_access_public'], '</option>
				<option value="1"', (int) $a['access_mode'] === 1 ? ' selected' : '', '>', $txt['bg_access_members'], '</option>
				<option value="2"', (int) $a['access_mode'] === 2 ? ' selected' : '', '>', $txt['bg_access_approved'], '</option>
				<option value="3"', (int) $a['access_mode'] === 3 ? ' selected' : '', '>', $txt['bg_access_password'], '</option>
				<option value="4"', (int) $a['access_mode'] === 4 ? ' selected' : '', '>', $txt['bg_access_approved_or_password'], '</option>
				<option value="5"', (int) $a['access_mode'] === 5 ? ' selected' : '', '>', $txt['bg_access_invitation'], '</option>
			</select>
		</label>

		<label><strong>', $txt['bg_album_password'], '</strong>
			<input type="password" name="album_password" autocomplete="new-password">
			<span class="smalltext">', !empty($a['id_album']) ? $txt['bg_password_edit_help'] : $txt['bg_password_new_help'], '</span>
		</label>';

	if (!empty($context['bg_global_comments_enabled']))
		echo '
		<label class="bg_album_toggle">
			<span class="bg_album_toggle_copy">
				<strong>', $txt['bg_allow_comments'], '</strong>
				<span class="smalltext">', $txt['bg_allow_comments_help'], '</span>
			</span>
			<span class="bg_switch">
				<input type="checkbox" name="allow_comments" value="1"', !empty($a['allow_comments']) ? ' checked' : '', '>
				<span class="bg_switch_track" aria-hidden="true"><span class="bg_switch_thumb"></span></span>
				<span class="bg_switch_state" data-on-label="On" data-off-label="Off">', !empty($a['allow_comments']) ? 'On' : 'Off', '</span>
			</span>
		</label>';
	else
		echo '<div class="information">', $txt['bg_comments_disabled_by_admin'], '</div>';

	if (!empty($context['bg_global_likes_enabled']))
		echo '
		<label class="bg_album_toggle">
			<span class="bg_album_toggle_copy">
				<strong>', $txt['bg_allow_likes'], '</strong>
				<span class="smalltext">', $txt['bg_allow_likes_help'], '</span>
			</span>
			<span class="bg_switch">
				<input type="checkbox" name="allow_likes" value="1"', !empty($a['allow_ratings']) ? ' checked' : '', '>
				<span class="bg_switch_track" aria-hidden="true"><span class="bg_switch_thumb"></span></span>
				<span class="bg_switch_state" data-on-label="On" data-off-label="Off">', !empty($a['allow_ratings']) ? 'On' : 'Off', '</span>
			</span>
		</label>';
	else
		echo '<div class="information">', $txt['bg_likes_disabled_by_admin'], '</div>';

	echo '
		<label class="bg_album_toggle">
			<span class="bg_album_toggle_copy">
				<strong>', $txt['bg_allow_downloads'], '</strong>
				<span class="smalltext">', $txt['bg_allow_downloads_help'], '</span>
			</span>
			<span class="bg_switch">
				<input type="checkbox" name="allow_downloads" value="1"', !empty($a['allow_downloads']) ? ' checked' : '', '>
				<span class="bg_switch_track" aria-hidden="true"><span class="bg_switch_thumb"></span></span>
				<span class="bg_switch_state" data-on-label="On" data-off-label="Off">', !empty($a['allow_downloads']) ? 'On' : 'Off', '</span>
			</span>
		</label>';

	echo '
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
		<div><button class="button" type="submit" name="bg_save_album" value="1">', $txt['save'], '</button></div>
	</form>
	</div>';
}

function template_bg_album_requests()
{
	global $context, $txt, $scripturl;

	echo '
	<div class="cat_bar"><h3 class="catbg">', $context['page_title'], '</h3></div>
	<div class="windowbg">';

	if (empty($context['bg_requests']))
	{
		echo '<p class="information">', $txt['bg_no_access_requests'], '</p></div>';
		return;
	}

	echo '<table class="table_grid bg_health_table">
	<thead><tr class="title_bar"><th>', $txt['bg_member'], '</th><th>', $txt['bg_request_message'], '</th><th>', $txt['bg_status'], '</th><th>', $txt['bg_actions'], '</th></tr></thead><tbody>';

	foreach ($context['bg_requests'] as $r)
	{
		echo '<tr>
			<td>', htmlspecialchars($r['real_name'], ENT_QUOTES, 'UTF-8'), '</td>
			<td>', nl2br(htmlspecialchars($r['message'], ENT_QUOTES, 'UTF-8')), '</td>
			<td>', htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8'), '</td>
			<td>';

		if ($r['status'] === 'pending')
			echo '
			<form action="', $scripturl, '?action=gallery;sa=decide" method="post" class="bg_inline_form">
				<input type="hidden" name="request" value="', (int) $r['id_request'], '">
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
				<button class="button" type="submit" name="decision" value="approve">', $txt['bg_approve'], '</button>
				<button class="button" type="submit" name="decision" value="deny">', $txt['bg_deny'], '</button>
			</form>';

		echo '</td></tr>';
	}

	echo '</tbody></table></div>';
}

function template_bg_report_queue()
{
	global $context, $txt, $scripturl;

	echo '
	<div class="cat_bar"><h3 class="catbg">', $txt['bg_report_queue'], '</h3></div>
	<div class="windowbg">
		<div class="bg_report_queue_toolbar">
			<div class="buttonlist">
				<a class="button', $context['bg_report_status'] === 'open' ? ' active' : '', '" href="', $scripturl, '?action=gallery;sa=reports;status=open">', $txt['bg_report_open'], '</a>
				<a class="button', $context['bg_report_status'] === 'resolved' ? ' active' : '', '" href="', $scripturl, '?action=gallery;sa=reports;status=resolved">', $txt['bg_report_resolved'], '</a>
				<a class="button', $context['bg_report_status'] === 'dismissed' ? ' active' : '', '" href="', $scripturl, '?action=gallery;sa=reports;status=dismissed">', $txt['bg_report_dismissed'], '</a>
				<a class="button', $context['bg_report_status'] === 'all' ? ' active' : '', '" href="', $scripturl, '?action=gallery;sa=reports;status=all">', $txt['bg_report_all'], '</a>
			</div>
		</div>
	</div><br>';

	if (empty($context['bg_reports']))
	{
		echo '<div class="windowbg"><p class="information">', $txt['bg_no_reports'], '</p></div>';
		return;
	}

	echo '
	<div class="windowbg">
	<table class="table_grid bg_report_table">
		<thead><tr class="title_bar">
			<th>', $txt['bg_report_target'], '</th>
			<th>', $txt['bg_report_reason'], '</th>
			<th>', $txt['bg_reporter'], '</th>
			<th>', $txt['bg_report_status_label'], '</th>
			<th>', $txt['bg_actions'], '</th>
		</tr></thead><tbody>';

	foreach ($context['bg_reports'] as $report)
	{
		$target = $report['target_type'] === 'comment'
			? $txt['bg_comment'] . ': ' . mb_strimwidth((string) $report['comment_body'], 0, 90, '…')
			: $txt['bg_media'] . ': ' . (string) $report['media_title'];

		echo '
		<tr>
			<td>
				<strong>', htmlspecialchars($target, ENT_QUOTES, 'UTF-8'), '</strong>
				<div class="smalltext">', htmlspecialchars((string) $report['album_title'], ENT_QUOTES, 'UTF-8'), '</div>
				', !empty($report['note']) ? '<div class="bg_report_note">' . nl2br(htmlspecialchars($report['note'], ENT_QUOTES, 'UTF-8')) . '</div>' : '', '
			</td>
			<td>', htmlspecialchars($report['reason'], ENT_QUOTES, 'UTF-8'), '</td>
			<td>', htmlspecialchars($report['reporter_name'] ?: $txt['bg_unknown_member'], ENT_QUOTES, 'UTF-8'), '</td>
			<td>', htmlspecialchars($report['status'], ENT_QUOTES, 'UTF-8'), '</td>
			<td>';

		if ($report['status'] === 'open')
			echo '
				<form action="', $scripturl, '?action=gallery;sa=reportdecide" method="post" class="bg_report_decide_form">
					<input type="hidden" name="report" value="', (int) $report['id_report'], '">
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
					<button class="button" type="submit" name="decision" value="resolved">', $txt['bg_resolve'], '</button>
					<button class="button" type="submit" name="decision" value="dismissed">', $txt['bg_dismiss'], '</button>
				</form>';
		else
			echo '<span class="smalltext">', $txt['bg_report_closed'], '</span>';

		echo '</td>
		</tr>';
	}

	echo '</tbody></table></div>';
}

function template_bg_admin_dashboard()
{
	global $context, $txt, $scripturl;

	$passed = $warned = $failed = 0;

	foreach ($context['bg_environment'] as $check)
	{
		if ($check['status'] === 'pass') $passed++;
		elseif ($check['status'] === 'fail') $failed++;
		else $warned++;
	}

	echo '
	<div class="windowbg bg_admin_toolbar_surface">
		<div class="buttonlist bg_admin_toolbar_buttons">
			<a class="button active" href="', $scripturl, '?action=admin;area=bettergallery;sa=dashboard">', $txt['bg_admin_dashboard'], '</a>
			<a class="button" href="', $scripturl, '?action=admin;area=bettergallery;sa=categories">', $txt['bg_admin_categories'], '</a>
			<a class="button" href="', $scripturl, '?action=admin;area=bettergallery;sa=settings">', $txt['bg_admin_settings'], '</a>
			<a class="button" href="', $scripturl, '?action=admin;area=bettergallery;sa=maintenance">', $txt['bg_admin_maintenance'], '</a>
		</div>
	</div><br>

	<div class="cat_bar"><h3 class="catbg">', $txt['bg_admin_overview'], '</h3></div>
	<div class="windowbg">
		<div class="bg_health_summary">
			<div><strong>', $passed, '</strong><span>', $txt['bg_admin_passed'], '</span></div>
			<div><strong>', $warned, '</strong><span>', $txt['bg_admin_warnings'], '</span></div>
			<div><strong>', $failed, '</strong><span>', $txt['bg_admin_failed'], '</span></div>
		</div>
	</div><br>

	<div class="cat_bar"><h3 class="catbg">', $txt['bg_admin_health_heading'], '</h3></div>
	<div class="windowbg"><table class="table_grid bg_health_table">
	<thead><tr class="title_bar"><th>', $txt['bg_admin_status'], '</th><th>', $txt['bg_admin_check'], '</th><th>', $txt['bg_admin_details'], '</th></tr></thead><tbody>';

	foreach ($context['bg_environment'] as $check)
	{
		$label = $check['status'] === 'pass' ? 'PASS' : ($check['status'] === 'fail' ? 'FAIL' : 'WARN');
		echo '<tr>
			<td><span class="bg_status bg_status_', $check['status'], '">', $label, '</span></td>
			<td>', htmlspecialchars($check['label'], ENT_QUOTES, 'UTF-8'), '</td>
			<td>', htmlspecialchars($check['detail'], ENT_QUOTES, 'UTF-8'), '</td>
		</tr>';
	}

	echo '</tbody></table></div><br>
	<div class="cat_bar"><h3 class="catbg">', $txt['bg_admin_data_heading'], '</h3></div>
	<div class="windowbg"><div class="bg_foundation_grid">';

	foreach ($context['bg_schema_counts'] as $table => $count)
		echo '<div class="bg_foundation_card"><strong>', htmlspecialchars($table, ENT_QUOTES, 'UTF-8'), '</strong><p>', (int) $count, ' row', (int) $count === 1 ? '' : 's', '</p></div>';

	echo '</div></div>';
}

function template_bg_admin_settings()
{
	global $context, $txt, $scripturl;

	$s = $context['bg_upload_settings'];

	echo '
	<div class="windowbg bg_admin_toolbar_surface">
		<div class="buttonlist bg_admin_toolbar_buttons">
			<a class="button" href="', $scripturl, '?action=admin;area=bettergallery;sa=dashboard">', $txt['bg_admin_dashboard'], '</a>
			<a class="button" href="', $scripturl, '?action=admin;area=bettergallery;sa=categories">', $txt['bg_admin_categories'], '</a>
			<a class="button active" href="', $scripturl, '?action=admin;area=bettergallery;sa=settings">', $txt['bg_admin_settings'], '</a>
			<a class="button" href="', $scripturl, '?action=admin;area=bettergallery;sa=maintenance">', $txt['bg_admin_maintenance'], '</a>
		</div>
	</div><br>';

	if (!empty($_REQUEST['saved']))
		echo '
	<div class="windowbg bg_admin_notice_surface bg_admin_notice_success">', $txt['bg_settings_saved'], '</div><br>';


	echo '
	<form action="', $scripturl, '?action=admin;area=bettergallery;sa=settingssave" method="post" class="bg_gallery_settings_form">
		<div class="cat_bar"><h3 class="catbg">', $txt['bg_identity_settings_heading'], '</h3></div>
		<div class="windowbg">
			<dl class="settings">
				<dt>
					<strong>', $txt['bg_setting_nav_label'], '</strong><br>
					<span class="smalltext">', $txt['bg_setting_nav_label_help'], '</span>
				</dt>
				<dd>
					<input type="text" name="nav_label" maxlength="40" value="', htmlspecialchars($s['nav_label'], ENT_QUOTES, 'UTF-8'), '">
				</dd>

				<dt>
					<strong>', $txt['bg_setting_show_main_menu'], '</strong><br>
					<span class="smalltext">', $txt['bg_setting_show_main_menu_help'], '</span>
				</dt>
				<dd>
					<input type="checkbox" name="show_main_menu" value="1"', !empty($s['show_main_menu']) ? ' checked' : '', '>
				</dd>

				<dt>
					<strong>', $txt['bg_admin_gallery_content'], '</strong><br>
					<span class="smalltext">', $txt['bg_admin_gallery_content_help'], '</span>
				</dt>
				<dd>
					<strong>', $txt['bg_admin_gallery_content_value'], '</strong>
				</dd>
			</dl>
		</div><br>


		<div class="cat_bar"><h3 class="catbg">', $txt['bg_storage_settings_heading'], '</h3></div>
		<div class="windowbg">';
	if (!empty($s['betterstorage_integrated']))
	{
		$active_label = $s['storage_backend'] === 's3' ? 'Amazon S3' : 'Local server';
		echo '<div class="information"><strong>Managed by Better Storage.</strong><br>Better Gallery is connected to the central storage service and does not keep separate cloud credentials.<br><br><strong>Active backend:</strong> ', $active_label;
		if ($s['storage_backend'] === 's3')
			echo '<br><strong>Bucket:</strong> ', htmlspecialchars($s['s3_bucket'], ENT_QUOTES, 'UTF-8'), '<br><strong>Prefix:</strong> ', htmlspecialchars($s['s3_prefix'], ENT_QUOTES, 'UTF-8'), '/<br><strong>Region:</strong> ', htmlspecialchars($s['s3_region'], ENT_QUOTES, 'UTF-8');
		echo '<br><span class="smalltext">Gallery media records: ', (int) $context['bg_storage_counts']['s3'], ' on S3, ', (int) $context['bg_storage_counts']['local'], ' local.</span></div><br>
		<div class="buttonlist"><a class="button" href="', $scripturl, '?action=admin;area=betterstorage;sa=settings">Open Better Storage Settings</a></div>';
	}
	else
	{
		echo '<div class="information"><strong>Local storage active.</strong><br>Better Storage is not installed, so Better Gallery stores new uploads on the local server as usual.<br><span class="smalltext">Install Better Storage if you want centralized Amazon S3 or other shared storage support.</span></div>';
	}
	echo '</div><br>';

	echo '
		<div class="cat_bar"><h3 class="catbg">', $txt['bg_upload_settings_heading'], '</h3></div>
		<div class="windowbg">
			<dl class="settings">
				<dt>
					<strong>', $txt['bg_setting_max_files'], '</strong><br>
					<span class="smalltext">', $txt['bg_setting_max_files_help'], '</span>
				</dt>
				<dd>
					<input type="number" name="max_files" min="1" max="1000" value="', (int) $s['max_files'], '">
				</dd>

				<dt>
					<strong>', $txt['bg_setting_upload_progress_mode'], '</strong><br>
					<span class="smalltext">', $txt['bg_setting_upload_progress_mode_help'], '</span>
				</dt>
				<dd>
					<select name="upload_progress_mode">
						<option value="compact"', $s['upload_progress_mode'] === 'compact' ? ' selected' : '', '>', $txt['bg_upload_progress_compact'], '</option>
						<option value="per_file"', $s['upload_progress_mode'] === 'per_file' ? ' selected' : '', '>', $txt['bg_upload_progress_per_file'], '</option>
					</select>
				</dd>

				<dt>
					<strong>', $txt['bg_setting_member_storage_quota'], '</strong><br>
					<span class="smalltext">', $txt['bg_setting_member_storage_quota_help'], '</span>
				</dt>
				<dd>
					<input type="number" name="member_storage_quota_mb" min="0" max="1048576" value="', (int) $s['member_storage_quota_mb'], '"> MB
				</dd>

				<dt>
					<strong>', $txt['bg_setting_max_image_mb'], '</strong><br>
					<span class="smalltext">', $txt['bg_setting_max_image_mb_help'], '</span>
				</dt>
				<dd>
					<input type="number" name="max_image_mb" min="1" max="1024" step="0.5" value="', htmlspecialchars((string) $s['max_image_mb'], ENT_QUOTES, 'UTF-8'), '"> MB
				</dd>

				<dt>
					<strong>', $txt['bg_setting_max_video_mb'], '</strong><br>
					<span class="smalltext">', $txt['bg_setting_max_video_mb_help'], '</span>
				</dt>
				<dd>
					<input type="number" name="max_video_mb" min="1" max="10240" value="', (int) $s['max_video_mb'], '"> MB
				</dd>


				<dt>
					<strong>', $txt['bg_setting_thumb_width'], '</strong><br>
					<span class="smalltext">', $txt['bg_setting_thumb_width_help'], '</span>
				</dt>
				<dd>
					<input type="number" name="thumb_width" min="50" max="2400" value="', (int) $s['thumb_width'], '"> px
				</dd>

				<dt>
					<strong>', $txt['bg_setting_thumb_height'], '</strong><br>
					<span class="smalltext">', $txt['bg_setting_thumb_height_help'], '</span>
				</dt>
				<dd>
					<input type="number" name="thumb_height" min="50" max="2400" value="', (int) $s['thumb_height'], '"> px
				</dd>

				<dt>
					<strong>', $txt['bg_setting_comments_enabled'], '</strong><br>
					<span class="smalltext">', $txt['bg_setting_comments_enabled_help'], '</span>
				</dt>
				<dd>
					<input type="checkbox" name="comments_enabled" value="1"', !empty($s['comments_enabled']) ? ' checked' : '', '>
				</dd>

				<dt>
					<strong>', $txt['bg_setting_likes_enabled'], '</strong><br>
					<span class="smalltext">', $txt['bg_setting_likes_enabled_help'], '</span>
				</dt>
				<dd>
					<input type="checkbox" name="likes_enabled" value="1"', !empty($s['likes_enabled']) ? ' checked' : '', '>
				</dd>

				<dt>
					<strong>', $txt['bg_setting_album_chunk_mb'], '</strong><br>
					<span class="smalltext">', $txt['bg_setting_album_chunk_mb_help'], '</span>
				</dt>
				<dd>
					<input type="number" name="album_download_chunk_mb" min="50" max="4096" value="', (int) $s['album_download_chunk_mb'], '"> MB<br>
					<span class="smalltext">', !empty($s['zip_available']) ? $txt['bg_zip_available'] : $txt['bg_zip_unavailable'], '</span>
				</dd>

				<dt>
					<strong>', $txt['bg_setting_media_per_page'], '</strong><br>
					<span class="smalltext">', $txt['bg_setting_media_per_page_help'], '</span>
				</dt>
				<dd>
					<input type="number" name="media_per_page" min="6" max="100" value="', (int) $s['media_per_page'], '">
				</dd>
			</dl>

		</div><br>

			<div class="bg_admin_save_row">
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
				<button class="button" type="submit">', $txt['save'], '</button>
			</div>
	</form>

	<br>

	<div class="cat_bar"><h3 class="catbg">', $txt['bg_php_upload_limits'], '</h3></div>
	<div class="windowbg">
		<dl class="settings">
			<dt>
				<strong>upload_max_filesize</strong><br>
				<span class="smalltext">', $txt['bg_php_upload_max_help'], '</span>
			</dt>
			<dd>', htmlspecialchars($s['php_upload_max'], ENT_QUOTES, 'UTF-8'), '</dd>

			<dt>
				<strong>post_max_size</strong><br>
				<span class="smalltext">', $txt['bg_php_post_max_help'], '</span>
			</dt>
			<dd>', htmlspecialchars($s['php_post_max'], ENT_QUOTES, 'UTF-8'), '</dd>

			<dt>
				<strong>max_file_uploads</strong><br>
				<span class="smalltext">', $txt['bg_php_max_file_uploads_help'], '</span>
			</dt>
			<dd>', (int) $s['php_max_file_uploads'], '</dd>

			<dt>
				<strong>', $txt['bg_php_effective_limit'], '</strong><br>
				<span class="smalltext">', $txt['bg_php_effective_limit_help'], '</span>
			</dt>
			<dd>', $s['php_effective_mb'] > 0 ? htmlspecialchars((string) $s['php_effective_mb'], ENT_QUOTES, 'UTF-8') . ' MB' : $txt['bg_unknown'], '</dd>
		</dl>
	</div>';
}

function template_bg_admin_maintenance()
{
	global $context, $txt, $scripturl;

	$m = $context['bg_maintenance'];
	$s = $m['storage'];
	$d = $m['database'];

	echo '
	<div class="windowbg bg_admin_toolbar_surface">
		<div class="buttonlist bg_admin_toolbar_buttons">
			<a class="button" href="', $scripturl, '?action=admin;area=bettergallery;sa=dashboard">', $txt['bg_admin_dashboard'], '</a>
			<a class="button" href="', $scripturl, '?action=admin;area=bettergallery;sa=categories">', $txt['bg_admin_categories'], '</a>
			<a class="button" href="', $scripturl, '?action=admin;area=bettergallery;sa=settings">', $txt['bg_admin_settings'], '</a>
			<a class="button active" href="', $scripturl, '?action=admin;area=bettergallery;sa=maintenance">', $txt['bg_admin_maintenance'], '</a>
		</div>
	</div><br>';

	if (isset($_REQUEST['cleaned_files']) || isset($_REQUEST['cleaned_rows']))
		echo '<div class="windowbg bg_admin_notice_surface bg_admin_notice_success">',
			sprintf(
				$txt['bg_maintenance_cleanup_done'],
				(int) ($_REQUEST['cleaned_files'] ?? 0),
				bg_template_format_bytes((int) ($_REQUEST['cleaned_bytes'] ?? 0)),
				(int) ($_REQUEST['cleaned_rows'] ?? 0)
			),
		'</div><br>';

	if (isset($_REQUEST['recounted']))
		echo '<div class="windowbg bg_admin_notice_surface bg_admin_notice_success">',
			sprintf($txt['bg_maintenance_recount_done'], (int) $_REQUEST['recounted']),
		'</div><br>';

	if (isset($_REQUEST['zips_deleted']))
		echo '<div class="windowbg bg_admin_notice_surface bg_admin_notice_success">',
			sprintf(
				$txt['bg_maintenance_zips_done'],
				(int) $_REQUEST['zips_deleted'],
				bg_template_format_bytes((int) ($_REQUEST['zip_bytes'] ?? 0))
			),
		'</div><br>';

	echo '
	<div class="cat_bar"><h3 class="catbg">', $txt['bg_maintenance_heading'], '</h3></div>
	<div class="windowbg">
		<p>', $txt['bg_maintenance_intro'], '</p>
		<div class="bg_maintenance_summary">
			<div class="bg_maintenance_metric bg_maintenance_metric_safe">
				<strong>', (int) $m['safe_total'], '</strong>
				<span>', $txt['bg_maintenance_safe_cleanup'], '</span>
			</div>
			<div class="bg_maintenance_metric bg_maintenance_metric_warn">
				<strong>', (int) $m['warning_total'], '</strong>
				<span>', $txt['bg_maintenance_warnings'], '</span>
			</div>
			<div class="bg_maintenance_metric bg_maintenance_metric_attention">
				<strong>', (int) $m['attention_total'], '</strong>
				<span>', $txt['bg_maintenance_needs_attention'], '</span>
			</div>
		</div>
	</div><br>

	<div class="cat_bar"><h3 class="catbg">', $txt['bg_maintenance_storage_heading'], '</h3></div>
	<div class="windowbg">
		<div class="bg_maintenance_grid">
			<div class="bg_maintenance_card">
				<strong>', $txt['bg_maintenance_orphan_files'], '</strong>
				<span class="bg_maintenance_value">', count($s['orphan_files']), '</span>
				<p>', sprintf($txt['bg_maintenance_orphan_files_help'], bg_template_format_bytes((int) $s['orphan_bytes'])), '</p>
			</div>
			<div class="bg_maintenance_card">
				<strong>', $txt['bg_maintenance_stale_zips'], '</strong>
				<span class="bg_maintenance_value">', count($s['stale_zips']), '</span>
				<p>', sprintf($txt['bg_maintenance_stale_zips_help'], bg_template_format_bytes((int) $s['stale_zip_bytes'])), '</p>
			</div>
			<div class="bg_maintenance_card bg_maintenance_card_attention">
				<strong>', $txt['bg_maintenance_missing_originals'], '</strong>
				<span class="bg_maintenance_value">', count($s['missing_originals']), '</span>
				<p>', $txt['bg_maintenance_missing_originals_help'], '</p>
			</div>
			<div class="bg_maintenance_card">
				<strong>', $txt['bg_maintenance_missing_derivatives'], '</strong>
				<span class="bg_maintenance_value">', count($s['missing_thumbs']) + count($s['missing_posters']), '</span>
				<p>', sprintf($txt['bg_maintenance_missing_derivatives_help'], count($s['missing_thumbs']), count($s['missing_posters'])), '</p>
			</div>
		</div>';

	if (!empty($s['missing_originals']))
	{
		echo '
		<div class="bg_maintenance_attention_list">
			<strong>', $txt['bg_maintenance_missing_original_samples'], '</strong>
			<ul>';

		foreach (array_slice($s['missing_originals'], 0, 10) as $row)
			echo '<li>#', (int) $row['id_media'], ' — ',
				htmlspecialchars($row['filename'], ENT_QUOTES, 'UTF-8'),
				' <span class="smalltext">(', htmlspecialchars($row['key'], ENT_QUOTES, 'UTF-8'), ')</span></li>';

		echo '</ul></div>';
	}

	echo '
	</div><br>

	<div class="cat_bar"><h3 class="catbg">', $txt['bg_maintenance_database_heading'], '</h3></div>
	<div class="windowbg">
		<div class="overflow_auto">
			<table class="table_grid bg_health_table bg_maintenance_table">
				<thead><tr class="title_bar">
					<th>', $txt['bg_maintenance_check'], '</th>
					<th>', $txt['bg_maintenance_count'], '</th>
					<th>', $txt['bg_maintenance_classification'], '</th>
				</tr></thead>
				<tbody>
					<tr><td>', $txt['bg_maintenance_db_orphan_rows'], '</td><td>', (int) $d['safe_orphan_rows'], '</td><td><span class="bg_status bg_status_pass">', $txt['bg_maintenance_safe'], '</span></td></tr>
					<tr><td>', $txt['bg_maintenance_bad_covers'], '</td><td>', (int) $d['bad_covers'], '</td><td><span class="bg_status bg_status_pass">', $txt['bg_maintenance_safe'], '</span></td></tr>
					<tr><td>', $txt['bg_maintenance_bad_categories'], '</td><td>', (int) $d['bad_categories'], '</td><td><span class="bg_status bg_status_pass">', $txt['bg_maintenance_safe'], '</span></td></tr>
					<tr><td>', $txt['bg_maintenance_album_count_drift'], '</td><td>', (int) $d['album_count_drift'], '</td><td><span class="bg_status bg_status_warn">', $txt['bg_maintenance_repair'], '</span></td></tr>
					<tr><td>', $txt['bg_maintenance_media_missing_album'], '</td><td>', (int) $d['media_missing_album'], '</td><td><span class="bg_status bg_status_fail">', $txt['bg_maintenance_attention'], '</span></td></tr>
				</tbody>
			</table>
		</div>
	</div><br>

	<div class="cat_bar"><h3 class="catbg">', $txt['bg_maintenance_actions_heading'], '</h3></div>
	<div class="windowbg bg_maintenance_actions">
		<div class="bg_maintenance_action">
			<div>
				<strong>', $txt['bg_maintenance_run_safe_cleanup'], '</strong>
				<p>', $txt['bg_maintenance_run_safe_cleanup_help'], '</p>
			</div>
			<form action="', $scripturl, '?action=admin;area=bettergallery;sa=maintenanceclean" method="post">
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
				<button class="button" type="submit"', $m['safe_total'] === 0 ? ' disabled' : '', '>', $txt['bg_maintenance_clean_now'], '</button>
			</form>
		</div>

		<div class="bg_maintenance_action">
			<div>
				<strong>', $txt['bg_maintenance_recount_albums'], '</strong>
				<p>', $txt['bg_maintenance_recount_albums_help'], '</p>
			</div>
			<form action="', $scripturl, '?action=admin;area=bettergallery;sa=maintenancerecount" method="post">
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
				<button class="button" type="submit"', $d['album_count_drift'] === 0 ? ' disabled' : '', '>', $txt['bg_maintenance_recount_now'], '</button>
			</form>
		</div>

		<div class="bg_maintenance_action">
			<div>
				<strong>', $txt['bg_maintenance_delete_zips'], '</strong>
				<p>', $txt['bg_maintenance_delete_zips_help'], '</p>
			</div>
			<form action="', $scripturl, '?action=admin;area=bettergallery;sa=maintenancezips" method="post">
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
				<button class="button" type="submit"', empty($s['stale_zips']) ? ' disabled' : '', '>', $txt['bg_maintenance_delete_zips_now'], '</button>
			</form>
		</div>
	</div>';
}

function template_bg_admin_categories()
{
	global $context, $txt, $scripturl;

	$editing = !empty($context['bg_category_edit']);
	$category = $editing
		? $context['bg_category_edit']
		: array(
			'id_category' => 0,
			'id_parent' => 0,
			'name' => '',
			'description' => '',
			'sort_order' => 0,
			'enabled' => 1,
		);

	echo '
	<div class="windowbg bg_admin_toolbar_surface">
		<div class="buttonlist bg_admin_toolbar_buttons">
			<a class="button" href="', $scripturl, '?action=admin;area=bettergallery;sa=dashboard">', $txt['bg_admin_dashboard'], '</a>
			<a class="button active" href="', $scripturl, '?action=admin;area=bettergallery;sa=categories">', $txt['bg_admin_categories'], '</a>
			<a class="button" href="', $scripturl, '?action=admin;area=bettergallery;sa=settings">', $txt['bg_admin_settings'], '</a>
			<a class="button" href="', $scripturl, '?action=admin;area=bettergallery;sa=maintenance">', $txt['bg_admin_maintenance'], '</a>
		</div>
	</div><br>

	<form action="', $scripturl, '?action=admin;area=bettergallery;sa=categorysave" method="post" accept-charset="', $context['character_set'], '">
		<input type="hidden" name="id_category" value="', (int) $category['id_category'], '">

		<div class="cat_bar">
			<h3 class="catbg">', $editing ? $txt['bg_edit_category_heading'] : $txt['bg_create_category_heading'], '</h3>
		</div>

		<div class="windowbg">
			<dl class="settings">
				<dt>
					<strong>', $txt['bg_category_name'], '</strong><br>
					<span class="smalltext">', $txt['bg_category_name_help'], '</span>
				</dt>
				<dd>
					<input type="text" name="name" value="', htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'), '" size="40" maxlength="160">
				</dd>

				<dt>
					<strong>', $txt['bg_category_description'], '</strong><br>
					<span class="smalltext">', $txt['bg_category_description_help'], '</span>
				</dt>
				<dd>
					<textarea name="description" rows="4" cols="40">', htmlspecialchars($category['description'], ENT_QUOTES, 'UTF-8'), '</textarea>
				</dd>

				<dt>
					<strong>', $txt['bg_parent_category'], '</strong><br>
					<span class="smalltext">', $txt['bg_parent_category_help'], '</span>
				</dt>
				<dd>
					<select name="id_parent">
						<option value="0">', $txt['bg_none'], '</option>';

	foreach ($context['bg_categories'] as $cat)
		if (!$editing || (int) $cat['id_category'] !== (int) $category['id_category'])
			echo '
						<option value="', (int) $cat['id_category'], '"', (int) $category['id_parent'] === (int) $cat['id_category'] ? ' selected' : '', '>',
							htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'),
						'</option>';

	echo '
					</select>
				</dd>

				<dt>
					<strong>', $txt['bg_sort_order'], '</strong><br>
					<span class="smalltext">', $txt['bg_sort_order_help'], '</span>
				</dt>
				<dd>
					<input type="number" name="sort_order" min="0" value="', (int) $category['sort_order'], '" size="6">
				</dd>

				<dt>
					<strong>', $txt['bg_enabled'], '</strong><br>
					<span class="smalltext">', $txt['bg_enabled_help'], '</span>
				</dt>
				<dd>
					<input type="checkbox" name="enabled" value="1"', !empty($category['enabled']) ? ' checked' : '', '>
				</dd>
			</dl>';

	if ($editing)
		echo '
			<a class="button" href="', $scripturl, '?action=admin;area=bettergallery;sa=categories">', $txt['bg_cancel_edit'], '</a>
			<input type="submit" value="', $txt['save'], '" class="button" onclick="return !isEmptyText(this.form.name);">';
	else
		echo '
			<input type="submit" value="', $txt['bg_add_category'], '" class="button" onclick="return !isEmptyText(this.form.name);">';

	echo '
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
		</div>
	</form>

	<br>

	<div class="cat_bar">
		<h3 class="catbg">', $txt['bg_existing_categories'], ' (', count($context['bg_categories']), ')</h3>
	</div>

	<div class="windowbg">';

	if (empty($context['bg_categories']))
	{
		echo '
		<p class="information">', $txt['bg_no_categories'], '</p>
	</div>';
		return;
	}

	echo '
		<div class="overflow_auto">
			<table class="table_grid">
				<thead>
					<tr class="title_bar">
						<th>', $txt['bg_category_name'], '</th>
						<th>', $txt['bg_parent_category'], '</th>
						<th>', $txt['bg_sort_order'], '</th>
						<th>', $txt['bg_status'], '</th>
						<th>', $txt['bg_actions'], '</th>
					</tr>
				</thead>
				<tbody>';

	foreach ($context['bg_categories'] as $cat)
	{
		$parent_name = $txt['bg_none'];

		if (!empty($cat['id_parent']))
			foreach ($context['bg_categories'] as $parent)
				if ((int) $parent['id_category'] === (int) $cat['id_parent'])
				{
					$parent_name = $parent['name'];
					break;
				}

		echo '
					<tr class="windowbg">
						<td>
							<strong>', htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'), '</strong>
						</td>
						<td>', htmlspecialchars($parent_name, ENT_QUOTES, 'UTF-8'), '</td>
						<td>', (int) $cat['sort_order'], '</td>
						<td>', !empty($cat['enabled']) ? $txt['bg_enabled'] : $txt['bg_disabled_label'], '</td>
						<td>
							<div class="bg_admin_row_actions">
								<a class="button" href="', $scripturl, '?action=admin;area=bettergallery;sa=categories;edit=', (int) $cat['id_category'], '">', $txt['modify'], '</a>
								<form action="', $scripturl, '?action=admin;area=bettergallery;sa=categorydelete" method="post" accept-charset="', $context['character_set'], '">
									<input type="hidden" name="id_category" value="', (int) $cat['id_category'], '">
									<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
									<input type="submit" value="', $txt['delete'], '" class="button you_sure" data-confirm="', htmlspecialchars($txt['bg_delete_category_confirm'], ENT_QUOTES, 'UTF-8'), '">
								</form>
							</div>
						</td>
					</tr>';
	}

	echo '
				</tbody>
			</table>
		</div>
	</div>';
}

?>