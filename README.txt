Better Gallery 1.0.2 — Stable Release

- Maintenance release following a full stable-package audit.
- Adds the missing bg_album language fallback used after bulk media moves.
- Adds a direct upgrade path from Better Gallery 1.0.1.
- Normalizes Better Gallery asset cache identifiers to 1.0.2.
- No database schema changes.
- Preserves existing download protection, Better Tags, Better Reactions, Better Ads, Better Storage/S3, and Portal integrations.

- Promotes the extensively tested 1.0.0-dev2zddb build to the next stable release.
- Includes Better Tags album/media integration hooks and media lightbox deep links.
- Includes Theme Lab album header/button polish and Curve2 compatibility fixes.
- Includes protected media downloads, Better Ads slots, S3/storage pipeline work, and Better SMF branding.
- Supports direct upgrade from 1.0.0-dev2zddb and earlier listed builds.

Better Gallery 1.0.0-dev2zddb — Theme Lab Album Header Polish

- Cleans the album header into a left summary area and right action area.
- Gives owner/category/tags/description consistent spacing.
- Prevents Theme Lab visited-link colors from recoloring Gallery toolbar/action buttons.
- Active Gallery navigation retains the normal active/primary button text color.
- Keeps Curve 2 readable via dark fallback colors when Theme Lab variables are absent.

Better Gallery 1.0.0-dev2zdda — Media Lightbox Deep Links

- Adds native album deep-link support for `;media=<id>`.
- When an album page loads with a matching media ID, BetterGalleryViewer opens that exact item in the existing lightbox.
- Normal album links without `media=` behave exactly as before.
- No duplicate viewer or alternate media page is introduced.

Better Gallery 1.0.0-dev2zdd — Stage 5C Individual Media Integration Hooks
- Adds optional generic media form/save/info/delete integration points.
- Adds a generic lightbox metadata slot populated by mediainfo JSON.
- Supports single and bulk delete cleanup callbacks.
- Better Gallery remains fully independent when Better Tags is absent.
- Does not force one tag set onto multi-file uploads; individual tags are assigned in Edit Media.

Better Gallery 1.0.0-dev2zdc — CLEAN Stage 5B Album Tags Bridge Rebuild

- Rebuilt from the clean pre-Stage-5 dev2zc baseline.
- Removes the accidental Album Tags form code that had been inserted into bg_gallery_home().
- Album Tags form bridge exists only inside bg_album_edit(), after $is_edit and bg_album_form exist.
- Save bridge exists only inside bg_album_edit() after successful save.
- View bridge exists only inside bg_album_view().
- Delete bridge exists only inside bg_album_delete().
- Normal SMF hook is tried first; optional direct Better Tags bridge runs only if needed.
- Gallery still works normally when Better Tags is absent.

Better Gallery 1.0.0-dev2zc — Native Toolbar Button Styling
- Removes Better Gallery's custom color/text-fill authority from the shared Gallery toolbar.
- Inactive toolbar links now use the active theme's normal `.button` appearance.
- The selected toolbar link uses the active theme's normal `.button.active` appearance.
- Bumps the hard-injected BetterGallery.css cache seed to dev2zc so the browser loads the corrected CSS.
- No toolbar sizing, spacing, or layout changes.

Better Gallery 1.0.0-dev2zb — Real Curve 2 Toolbar Foreground Fix
- Removes the late hard-coded white foreground on .bg_media_action_list buttons.
- Curve 2 now receives dark #444 toolbar labels.
- Theme Lab keeps its configured --tl-button-secondary-text color.
- Removes the ineffective dev2za selector patch.

Better Gallery 1.0.0-dev2z — SMF 2.1.7 / 2.1.8 Compatibility
COMPATIBILITY UPDATE
--------------------
- Package Manager install, upgrade, and uninstall declarations now support SMF 2.1.7 through 2.1.8.
- No Gallery feature or database schema changes are required for SMF 2.1.8.
- Existing dev2q behavior is unchanged.

Better Gallery 1.0.0-dev2u — Main Menu Visibility Setting
DEV2Q CHANGES
-------------
- Adds “Show Gallery in the main menu” to Gallery Settings.
- The setting is available whether or not Better Portal is installed.
- Enabled by default so upgrades preserve the existing Gallery header button.
- When disabled, Better Gallery remains fully accessible by direct URL, Portal integrations, and other custom navigation; only the SMF main-menu button is hidden.
- Keeps the dev2p menu alignment and icon sizing unchanged.


Better Gallery 1.0.0-dev2p — Main Menu Icon Size Match
DEV2P CHANGES
-------------
- Keeps the Gallery main-menu item in SMF's native .main_icons slot from dev2o.
- Enlarges only the Gallery artwork to a 20x20 rendered box so its visible icon
  matches the visual size/weight of Home, Search, Admin, Members and Downloads.
- Does not alter the menu item baseline, line-height, text positioning, or admin icon.
- Cache-bust version updated to 1.0.0-dev2p.


Adds a second presentation for repeat integrations inside an album grid.

Supported repeat presentations:
- Full-width row (existing/default behavior)
- One gallery-grid card slot

The integration remains optional. Better Gallery still does not manage ads and
does not require Better Ads.

No database changes.
No lightbox advertising in this pass.

Better Gallery 1.0.0-dev2l — Better Ads Integration Slots

Adds optional integration slots without creating a hard dependency on Better Ads.

Slots:
- gallery_index_top
- gallery_index_bottom
- gallery_album_top
- gallery_album_bottom
- gallery_album_repeat

Better Gallery calls `integrate_bettergallery_ad_slot`; when no integration is
installed, nothing is rendered and Gallery behaves exactly as before.

Repeat slots span the full album media grid. Restricted albums do not render
album ad slots.

No database changes.
No lightbox advertising in this pass.

Better Gallery 1.0.0-dev2k — Download Visibility Enforcement Fix

- When an album has “Allow downloads” turned off, the Download action is no longer rendered in the lightbox at all.
- Hides the per-media download counter/action surface on album cards when downloads are disabled.
- Keeps the album-level Download Album button suppressed when downloads are disabled.
- The protected PHP download endpoint still returns HTTP 403 for direct/manual download requests, so the server-side permission remains enforced.
- Bumps Gallery CSS/JS/icon cache keys to dev2k so stale dev2j viewer assets are not reused.

Better Gallery 1.0.0-dev2j — Authoritative Media Download Endpoint Fix

- Fixes the dev2i lightbox error: “This media does not have a valid download URL.”
- Stops depending on the mediainfo JSON download_url field for the Download button.
- Builds the protected gallery file endpoint directly from the active media ID.
- Keeps the dev2i Blob download flow, busy state, visible HTTP/empty-file errors, and open lightbox.
- Bumps Better Gallery asset cache keys to dev2j so browsers receive the repaired viewer immediately.
- No database/schema changes.

Better Gallery 1.0.0-dev2i — Blob Download + Error Surface Fix

- Replaces link/iframe download triggering with an explicit same-origin fetch.
- Converts the returned media file to a Blob and hands it to the browser as a real download.
- Keeps the lightbox open during download.
- Shows a visible lightbox error if the media endpoint returns HTTP failure or an empty file.
- Adds a small busy state while the download request is running.

Better Gallery 1.0.0-dev2f - Image Download Trigger Fix

- Makes the lightbox Download control explicitly start the attachment request in a hidden frame.
- Keeps the gallery lightbox open while the browser handles the download.
- Avoids silent/no-op download clicks caused by page/theme navigation interception.
- Bumps Better Gallery asset cache keys so the repaired viewer JavaScript loads immediately.

1.0.0-dev2e — Better SMF Branded Admin Icon

- Uses the branded Better SMF icon in the Configuration hover menu.
- Uses the same branded icon on Admin Home → Configuration.
- Installs native SMF small (16px) and large (32px) admin icon assets.

Better Gallery 1.0.0-dev2d — Configuration Dropdown Gallery Icon

- Reuses the glossy Better Gallery main-menu icon for the Configuration dropdown entry.
- Replaces the generic fallback question-mark look with the branded gallery icon.
- Preserves all existing Better Gallery behavior and admin rebrand work.

Better Gallery 1.0.0-dev2c — Custom Glossy Gallery Menu Icon

- Replaces the monochrome inline SVG top-menu icon with the selected glossy blue stacked-photo Better Gallery icon.
- Installs the optimized transparent PNG into the default theme image folder and renders it at 18x18 in the SMF main menu.
- Keeps the existing Better Gallery label, navigation behavior, and admin polish unchanged.
- No database/schema changes.

Better Gallery 1.0.0-dev2c — Admin Control Polish + Gallery Menu Icon

Better Gallery 1.0.0-dev2c — Admin Rebrand Cleanup

This pass finishes the Better Media -> Better Gallery admin identity reset.
It also protects the admin UI from stale custom-theme language copies by
forcing current canonical Better Gallery strings for admin-only labels.

Highlights:
- Better Gallery name now owns the Admin breadcrumb, title bar and description.
- Gallery Identity replaces the retired Better Media Identity heading.
- Maintenance wording uses gallery items / original gallery files throughout.
- Settings use current gallery-first wording even if Theme Lab contains an older
  BetterGallery language snapshot.
- Database names, setting keys, hooks and compatibility internals remain unchanged.

============================================================
Better Gallery 1.0.0-dev2c — Complete Admin Rebrand
For Simple Machines Forum 2.1.7
============================================================

Better Media is now Better Gallery. This development pass retires the old
multi-purpose product modes and establishes one clear purpose: a polished
SMF photo/video gallery with albums, permissions, social interaction and
protected media storage.


DEV2 COMPLETE ADMIN REBRAND
---------------------------
- Rebranded the entire SMF admin section around Better Gallery terminology.
- Admin navigation now reads Gallery Dashboard, Gallery Categories,
  Gallery Settings and Gallery Maintenance.
- Rewrote the admin description, identity, upload/display, server-limit and
  maintenance wording so the old multi-purpose Media identity no longer leaks
  into the control panel.
- Gallery comments, likes, item counts and storage help now use gallery-first
  language focused on images and video.
- Replaced developer-facing raw database names on the dashboard with friendly
  Gallery Data labels while preserving the existing bg_* schema underneath.
- Environment checks now describe Gallery data components instead of exposing
  raw table names as the primary labels.
- Removed retired Content Profile and Portfolio Mode admin language entries.
- No schema/settings rename is performed; existing bg_* internals are retained
  deliberately for safe upgrades and compatibility.

DEV1 IDENTITY RESET
-------------------
- Renamed visible product branding from Better Media to Better Gallery.
- Retired Content Profile choices: Downloads, Resources, Media Hub and Custom.
- New uploads are now permanently gallery-first: JPG/JPEG, PNG, GIF, WebP,
  MP4, WebM, MOV and MKV.
- Retired Portfolio Mode and its single-owner public filtering.
- Preserved album access modes (public, members, approved, password, invite,
  etc.) because these are privacy/access controls, not product modes.
- Default public navigation label is Better Gallery.
- Gallery icon is now the canonical Better Gallery navigation icon.
- Existing albums/media and legacy settings are preserved on upgrade.
- Existing legacy non-gallery rows are not deleted; the reset only prevents
  new document/archive/audio uploads through Better Gallery.
- Better Downloads is now the companion product for packages, themes, MODs,
  documents, archives and downloadable resources.

CORE GALLERY FEATURES RETAINED
------------------------------
- Album categories, covers, search, filtering, sorting and pagination.
- Public, members-only, approved, password, approved-or-password and invite
  album access controls.
- Request Access workflow with owner/moderator approval.
- Protected storage and permission-aware media delivery.
- Image thumbnails and JPEG EXIF orientation normalization.
- MP4/WebM support plus optional FFmpeg/ffprobe MOV/MKV normalization and
  video poster generation.
- Upload limits, member storage quotas and media-per-page controls.
- Favorites, album/media likes, comments, reporting and moderation.
- Optional Better Reactions integration.
- Album ZIP downloads remain available as a convenience for album owners/users
  where permitted; Better Downloads remains the product for standalone files.
- Maintenance and cleanup tools.
- Native SMF Package Manager uninstall-data checkbox behavior.

UPGRADE NOTES
-------------
This package supports direct upgrade from Better Media 1.0.0 and the prior
0.1.0 development series listed in package-info.xml. No gallery data is
deleted by this identity reset.


dev2b changes:
- Widens admin numeric inputs so three-digit values remain fully visible.
- Adds spacing between Modify/Delete category actions.
- Introduces a clearer stacked-gallery inline SVG for the main SMF menu.
- Keeps the icon theme-aware via currentColor and avoids a native SMF icon-slot collision.

DEV2R - Better Storage / Amazon S3 Foundation
- Adds reusable Sources/BetterStorage.php shared storage helper.
- Adds Local / Amazon S3 backend configuration to Gallery settings.
- Adds AWS region, bucket, key prefix, access key, secret key, private-object and optional endpoint settings.
- Adds a signed Amazon S3 connection test using AWS Signature Version 4.
- This pass deliberately does not move or upload Gallery media to S3 yet; local storage remains active while connectivity is validated.


DEV2T - Backend-Aware Storage Test
- Test button now tests Local storage when Local is selected.
- S3 test now falls back to saved values for any blank field.
- Missing S3 settings are named individually instead of returning a generic credentials error.


Better Gallery 1.0.0-dev2u - S3 Live Media Pipeline
---------------------------------------------------
- Saves Local/Amazon S3 storage configuration in SMF settings.
- Keeps the saved S3 secret when the password field is left blank.
- Adds per-media storage_backend tracking so local and S3 media can coexist.
- New image, video and file uploads can be stored in Amazon S3.
- S3 originals, thumbnails and video posters are uploaded after local processing.
- Successful S3 uploads remove the processed local object copy.
- Permission checks remain in Better Gallery; S3 media is delivered with short-lived signed URLs.
- Deleting S3-backed media removes its S3 objects.
- Album ZIP downloads can temporarily fetch S3-backed originals for archive creation.
- Existing local media is not automatically migrated.
- Storage connection button now reads Test Storage Connection for both backends.


Better Gallery 1.0.0-dev2v - S3 Test/Save State Fix
-----------------------------------------------------
- Test Storage Connection now preserves unsaved backend, region, bucket, prefix, access key, endpoint, and private-object settings across its redirect.
- The tested secret key is retained only in transient admin session state so clicking Save immediately after a successful test persists the tested secret without redisplaying it.
- Saving clears the transient test state.
- Fixes the confusing behavior where a successful S3 test returned to Local server defaults and could cause those defaults to be saved accidentally.


Better Gallery 1.0.0-dev2w - S3 Pipeline Polish
- Added clear Active backend/bucket/prefix/region status summary.
- Added local vs S3 media counts on the settings page.
- Clarified that connection testing does not save settings.
- Added per-media storage-backend indicator on media edit.
- Added S3 delete failure logging while preserving media deletion flow.
- Retains mixed local/S3 media, signed private delivery, and S3 album ZIP support.


Better Gallery 1.0.0-dev2z - Storage Label Scope Fix
------------------------------------------------------
- Removes an accidental storage-status line from the Gallery search block.
- Fixes Undefined variable $storage_label and the related undefined media variable on the Gallery index.
- Leaves the working S3 upload, signed delivery, delete cleanup, and album ZIP pipeline unchanged.


1.0.0-dev2z
- Adds optional Better Storage integration.
- Automatically uses Better Storage when the standalone MOD is installed and enabled.
- Falls back to Better Gallery's built-in storage when Better Storage is absent.
- Stops owning/removing the shared BetterStorage.php file; legacy fallback helper is now BetterGalleryStorage.php.
- Adopts existing Gallery local/S3 objects into Better Storage's central registry and registers future uploads/deletes.
- Gallery settings show central Better Storage status instead of duplicate AWS credentials when integrated.


1.0.0-dev2z
- Fixes Better Storage detection and removes duplicate AWS/S3 configuration from Better Gallery.
- New uploads fall back to local storage when Better Storage is absent.
