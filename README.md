# Better Gallery

**A polished media gallery for SMF 2.1.x with albums, protected media, lightbox viewing, downloads, tagging, reactions, and Better SMF integrations.**

Better Gallery turns SMF into a clean photo/video gallery experience while keeping the familiar SMF structure and administration workflow.

> **Keep SMF familiar. Make it better.**

## Features

- Photo and video albums
- Public, member, approved, password, invite, and other album access controls
- Image/video uploads
- Lightbox media viewer
- Protected media download endpoint
- Album-level download controls
- Media download visibility enforcement
- Multiple upload and bulk-management tools
- Gallery categories
- Comments and likes/reactions support
- Better Tags integration for albums and individual media
- Better Ads integration slots
- Better Storage / S3 media pipeline support
- Better Portal integration
- Theme Lab and Curve2 compatibility polish
- Branded Better Gallery menu and administration icons

## Current Version

**1.0.2**

Better Gallery 1.0.2 is the current audited stable release. It includes the complete 1.0.1 feature set plus the missing `bg_album` language fallback used by the bulk media move workflow, a direct 1.0.1 upgrade path, and normalized stable asset/version identifiers.

## Compatibility

- Simple Machines Forum **2.1.7–2.1.8**
- PHP 8.x friendly
- Designed for Curve2 and compatible Better SMF themes
- Optional integrations remain inactive when the corresponding Better SMF MOD is not installed

## Installation

1. Download the latest Better Gallery package from the GitHub **Releases** section.
2. In SMF, open **Administration Center → Main → Package Manager → Download Packages**.
3. Upload the Better Gallery ZIP.
4. Install the package normally.
5. Open **Administration Center → Configuration → Better Gallery** to configure the gallery.

## Better SMF Integrations

### Better Tags

Albums and individual media can participate in the shared Better Tags system. Media links can deep-link directly to the matching item in the existing Gallery lightbox.

### Better Ads

Better Gallery exposes optional ad integration slots for gallery index and album views without creating a hard dependency.

### Better Storage

Gallery media can participate in the Better Storage pipeline, including S3-backed media storage where configured.

### Better Portal

Gallery content can be surfaced through Better Portal blocks and integrations.

## Downloads

Album owners can control whether media downloads are available. When downloads are disabled, Better Gallery removes the download controls from the interface while the protected PHP endpoint continues to enforce the restriction server-side.

## Project Structure

The install package contains:

- Gallery source/bootstrap/storage classes
- Main Gallery template
- English language file
- Gallery stylesheet
- Uploader, viewer, and bulk-management JavaScript
- Gallery and administration icons
- Install/uninstall handlers
- `package-info.xml`

## Support & More Better SMF MODs

Better Gallery is part of the **Better SMF** project.

Visit **https://bettersmf.com/** for support, discussion, downloads, and the rest of the Better SMF MOD ecosystem.

## Development Philosophy

Better SMF MODs are built around a simple idea:

**Keep SMF familiar. Make it better.**

Better Gallery follows that philosophy by adding a capable media system without turning SMF into a separate, unfamiliar application.

## License

A project license will be published separately. Until then, all rights are reserved unless otherwise stated.
