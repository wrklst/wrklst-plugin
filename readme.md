# WrkLst Plugin

**Contributors:** Tobias Vielmetter-Diekmann  
**Tags:** wrklst, art, inventory, image, media, gallery  
**Requires at least:** 4.8.1  
**Tested up to:** 6.5.3  
**Stable tag:** 3.0  
**License:** GPLv2  
**License URI:** [http://www.gnu.org/licenses/gpl-2.0.html](http://www.gnu.org/licenses/gpl-2.0.html)  

## Description

[WrkLst](https://wrklst.art) is a professional artwork inventory management system. This WordPress plugin seamlessly integrates your WrkLst account with your WordPress website, enabling you to import artwork images and metadata directly into your WordPress Media Library.

## Features

- **Media Library Integration:** Import artwork directly through the WordPress Media Library interface
- **Bulk Import:** Transfer multiple artworks at once with batch processing
- **Metadata Preservation:** Automatically import titles, captions, descriptions, and inventory numbers
- **Multi-Image Support:** Handle artworks with multiple views/images
- **Search & Filter:** Advanced search capabilities with inventory filtering
- **Availability Status:** Filter artworks by availability status
- **Responsive Grid Layout:** Beautiful flexbox-based image grid display
- **Real-time Search:** Instant search results as you type
- **Infinite Scroll:** Automatic loading of more results as you scroll

## Installation

1. **Download the Plugin:**
   - Download the latest version from the [GitHub repository](https://github.com/wrklst/wrklst-plugin)

2. **Upload the Plugin:**
   - Upload the plugin folder to `/wp-content/plugins/wrklst-plugin/`
   - Or install directly through the WordPress admin interface

3. **Activate the Plugin:**
   - Navigate to 'Plugins' in your WordPress admin
   - Find 'WrkLst Plugin' and click 'Activate'

4. **Configure Settings:**
   - Go to WrkLst → Settings in your WordPress admin
   - Enter your WrkLst API credentials
   - Save the settings

## Usage

### Importing Artwork via Media Library

1. Click 'Add New' in the Media Library
2. Select the 'Import WrkLst Work' tab
3. Search for artworks using title, artist, or inventory number
4. Filter by inventory or availability status
5. Click on any artwork to import it to your Media Library

### Managing Imported Artwork

- Imported artworks appear in your Media Library with all metadata intact
- Use standard WordPress features to insert artworks into posts and pages
- Artworks are marked to show import status (fully/partially imported)

### Works Overview Page

- Access via WrkLst → Works in your WordPress admin
- View all available artworks in a grid layout
- Search and filter functionality
- Click to import individual artworks

## Requirements

- WordPress 4.8.1 or higher
- PHP 7.0 or higher
- Active WrkLst account with API access
- Valid SSL certificate (for secure API communication)

## Development

The `vendor/` directory is committed to the repository so the repo can be dropped into `wp-content/plugins/` and used directly — no `composer install` step required.

When updating dependencies, run `composer update` (or `composer require ...`) and commit the resulting changes to `vendor/` and `composer.lock` together. Bundled tests, fixtures, and dev metadata inside vendor packages are excluded via `.gitignore` to keep the tree small.

## Getting API Access

To use this plugin, WrkLst users need to obtain API credentials. Please contact **support@wrklst.art** to request the creation of an API key for authenticating the plugin with your WrkLst account.

## Support

For support and documentation, please visit [WrkLst Support](https://wrklst.art/support)

## Changelog

### 3.8
- New "Download first image of each artwork" bulk button on the Exhibitions detail view (admin subpage + media uploader tab). For single-image artworks it imports the only image; for multi-image artworks it imports the first sub-image only. Already-imported artworks are skipped entirely (no second pick is taken from a multi-image artwork whose first image is already in the library)

### 3.7
- Bulk-import buttons on the exhibition detail view: "Download all installation views", "Download all artworks", and "Download all" — each shows the remaining count and skips items already in the WP Media Library
- New `assets/img/wrklst-icon.svg` — a stroke-only monogram tuned for 20px legibility in the admin menu, replacing the full-glyph logo for that role (the full logo stays at `assets/img/wrklst-logo.svg`)
- More descriptive auto-generated alt text for installation images (built server-side from the exhibition data: title, solo/group framing, artists, venue, dates, artwork count) so imported install views land with accessible alt text out of the box

### 3.6
- Restored the full WrkLst monogram (filled W + L glyphs inside the rounded outline) for the admin menu icon, kept on `currentColor` so it inherits the active WordPress admin color scheme

### 3.5
- Press releases on the Exhibitions detail view: when an exhibition has one or more press releases, a row of buttons appears at the top of the detail header showing each release's title; clicking a button copies the cleaned Quill HTML to the clipboard, ready to paste into a WordPress post body. Wired up on both the Exhibitions admin subpage and the Media Library "Import from Exhibition" tab
- Picker cards now show a press-release count alongside the install/artwork counts so it's visible at a glance which exhibitions ship a release

### 3.4
- Replaced the admin menu icon with the WrkLst monogram SVG, served as a `data:` URI so it picks up WordPress's admin color scheme automatically. Removed the orphan PNG

### 3.3
- Self-hosted plugin updates from GitHub: tagged releases at `wrklst/wrklst-plugin` now surface in WordPress's Dashboard → Updates flow with one-click install, replacing the need to manually re-upload the plugin zip on each release. Powered by `yahnis-elsts/plugin-update-checker` against GitHub releases (release-asset zip preferred, falls back to source archive of the tag)
- One-time manual upgrade required to reach 3.3 (3.2 does not yet contain the update checker); from 3.3 onward updates land automatically

### 3.2
- Added an "Exhibitions" tab/subpage with a two-step picker → detail UX: select an exhibition, then browse its installation images and artworks side-by-side
- Added the same Exhibitions tab inside the WordPress Media Library uploader so install images and exhibition artworks can be pulled directly into Media at insert time
- Backend API: new `/ext/api/wordpress/exhibitions` and `/ext/api/wordpress/exhibitions/{id}` endpoints that shape installation images and artworks with the same fields as the existing inventory hits, so the renderer and import path are reused unchanged
- Namespaced installation-image source IDs (`exh-{id}-{mediaId}`) so dedup against the WP media library does not collide with inventory IDs

### 3.1
- Added "Image Format" setting (JPEG/WebP) on the WrkLst settings page; chosen format is applied to imgproxy URLs for thumbnails and uploaded copies (default: JPEG)
- Switched media library thumbnails to imgproxy `rs:fit:500:0` (matches the WrkLst overview cache) and uploaded copies to `rs:fit:2500:0`
- Accepted `images.wrklst.com` alongside `img.wrklst.com` on the upload endpoint
- Slimmed bundled `vendor/` (excluded tests/fixtures/dev metadata) and documented the commit-vendor policy

### 3.0
- Complete refactor using WordPress Backbone.js framework
- Improved multi-image artwork support
- Enhanced search and filtering capabilities
- Better error handling and user feedback
- Performance optimizations

### 2.0
- Added Media Library integration
- Implemented batch import functionality
- Improved UI/UX

### 1.0
- Initial release
- Basic import functionality

---

For more information about WrkLst, visit [wrklst.art](https://wrklst.art)