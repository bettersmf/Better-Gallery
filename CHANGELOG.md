# Changelog

## 1.0.2

Maintenance and stable-release audit update.

### Fixed

- Added the missing `bg_album` language fallback used after bulk media moves.
- Prevented the related undefined-array-key warning in that fallback path.
- Added direct upgrade support from Better Gallery 1.0.1.
- Normalized Gallery asset cache and package version identifiers to 1.0.2.

### Verified

- Protected media and album-download permission enforcement
- Upload extension/MIME/size/image validation
- Better Reactions, Better Tags, Better Ads, Better Storage/S3, and Better Portal integrations
- Install/uninstall hook symmetry
- Data-preserving uninstall behavior
- PHP/JavaScript syntax and package/language coverage

## 1.0.1

Stable release promoted from the extensively tested **1.0.0-dev2zddb** development line.

### Highlights

- Better Tags integration for albums and individual media
- Media lightbox deep links
- Theme Lab album header and action-button polish
- Curve2 toolbar foreground and native button styling fixes
- Protected media download endpoint and download visibility enforcement
- Better Ads integration slots, including repeat-in-grid support
- Better Storage / S3 media pipeline work
- Main-menu visibility and icon polish
- Complete Better Media → Better Gallery identity rebrand
- SMF 2.1.7 / 2.1.8 package compatibility

### 1.0.0-dev2zddb

Final development build before the 1.0.1 stable release.

- Cleaned the album header into a left summary area and right action area
- Improved owner/category/tags/description spacing
- Prevented Theme Lab visited-link colors from recoloring Gallery toolbar/action buttons
- Preserved Curve2 readability when Theme Lab variables are absent

### Earlier dev2 series

The dev2 series also included the Better Tags bridge, media deep links, toolbar fixes, download pipeline fixes, Better Ads slots, S3/storage work, admin/menu branding, and the Better Gallery rebrand.
