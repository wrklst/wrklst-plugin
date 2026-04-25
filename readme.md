# WrkLst Plugin

**Contributors:** Tobias Vielmetter-Diekmann  
**Tags:** wrklst, art, inventory, image, media, gallery  
**Requires at least:** 4.8.1  
**Tested up to:** 6.5.3  
**Stable tag:** 3.16  
**License:** GPLv2  
**License URI:** [http://www.gnu.org/licenses/gpl-2.0.html](http://www.gnu.org/licenses/gpl-2.0.html)  

## Description

[WrkLst](https://wrklst.art) is a professional artwork inventory management system. This WordPress plugin connects your WrkLst account to your WordPress site so you can pull artworks, exhibitions, installation views, and press releases directly into the Media Library and into posts and pages.

## Features

### Inventory

- **Inventory tab in the Media Library** ("WrkLst Inventory") and a dedicated **WrkLst → Inventory** admin subpage for browsing the full catalogue
- **Real-time search** by title, artist, or inventory number, with infinite scroll
- **Filter by inventory and availability status**, with selections persisted across sessions
- **Multi-image artworks** are supported — each view can be imported independently
- **Metadata preserved** on import: titles, captions, descriptions, inventory numbers
- **Import-status badges** on previously imported works (fully / partially imported)

### Exhibitions

- **Exhibitions tab in the Media Library** ("WrkLst Exhibition") and a dedicated **WrkLst → Exhibitions** admin subpage with a two-step picker → detail flow
- **Installation views and exhibited artworks** shown side-by-side on the detail view, with deduplication against the Media Library
- **Bulk-import buttons** on the exhibition detail view:
  - "Download all installation views"
  - "Download all artworks"
  - "Download all"
  - "Download first image of each artwork" (one image per multi-image artwork)
  Each button shows the remaining count and skips items already in the Media Library
- **Auto-generated alt text** for installation images, built server-side from the exhibition data (title, solo/group framing, artists, venue, dates, artwork count)
- **Press release HTML copy buttons** at the top of the detail header — one click copies cleaned Quill HTML to the clipboard, ready to paste into a post body

### Image delivery & settings

- **Image format setting** (JPEG / WebP) applied to imgproxy URLs for both Media Library thumbnails and uploaded copies
- **imgproxy-backed sizes**: thumbnails at `rs:fit:500:0`, uploaded copies at `rs:fit:2500:0`
- **Optional inventory number** in WordPress captions, controlled from the settings page
- **Biography webhook** support (with configurable bio/news formats and a webhook auth token)

### Updates

- **Self-hosted updates from GitHub releases** — tagged releases of `wrklst/wrklst-plugin` surface in WordPress's Dashboard → Updates flow with one-click install

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

### Importing artworks via the Media Library

1. Open the Media Library and click 'Add New' (or insert media from the post editor)
2. Select the **WrkLst Inventory** tab
3. Search by title, artist, or inventory number; filter by inventory and/or availability
4. Click an artwork to import it. Multi-image artworks expand so each view can be picked individually

### Importing from an exhibition

1. From the Media Library uploader open the **WrkLst Exhibition** tab, or go to **WrkLst → Exhibitions** in the admin
2. Pick an exhibition from the picker
3. On the detail view, either click individual installation views / artworks, or use the bulk-import buttons ("Download all installation views", "Download all artworks", "Download all", "Download first image of each artwork"). Already-imported items are skipped automatically
4. If the exhibition has press releases, click the title button at the top to copy the cleaned HTML to your clipboard and paste it into a WordPress post

### Inventory overview page

- Open via **WrkLst → Inventory** in the admin
- Browse the full catalogue in a grid with the same search/filter controls as the Media Library tab
- Click an artwork to import it directly into the Media Library

### Managing imported media

- Imported artworks land in the Media Library with all metadata intact and can be inserted into posts and pages with the normal WordPress flow
- Items are flagged to show their import status (fully / partially imported) so the next pass can resume where it left off

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

### 3.16
- Confirmed artworks in an exhibition are now visually marked with a green "confirmed" badge in the picker grid (admin subpage and Media Library tab)
- New "Confirmed only" toggle on the exhibition detail header. Defaults to on whenever the exhibition has at least one confirmed artwork (the unconfirmed roster is usually still being curated). When on, unconfirmed artworks are hidden from the grid, the bulk-download counts only count confirmed items, and the bulk-download buttons skip unconfirmed inventory. Installation views are unaffected by the toggle since they have no confirmation state
- Picker cards show "(N confirmed)" alongside the artwork count whenever the count differs from the total
- Backend (wrklst-app): the WordPress integration endpoint now emits `confirmed: bool` on each inventory hit (from `exhibitionables.confirmed`) and `artwork_count_confirmed` on the exhibition summary

### 3.15
- Hide artworks with no uploaded image from the Exhibitions detail view (admin subpage and Media Library tab). Placeholder inventory rows ("Title TBC", "IMAGE IN PROGRESS") were rendering `<img src="">` which the browser then resolved against the page URL → 404. Affected items are now filtered out of the rendered grid and the bulk-download counts
- Defensive: when a preview URL is empty, render a 1×1 transparent GIF instead of an empty `src` to prevent any future no-image hit from re-introducing the same 404

### 3.14
- Picker preview thumbnails now request the exact same imgproxy URL that wrklst-app uses for its overview cache (`rs:fit:500:0` + `@webp`), so the preview hits the existing cache instead of forcing imgproxy to render an extra variant. The Image Format setting still applies to the uploaded copy

### 3.13
- Admin menu icon switched to `assets/img/wrklst-logo.png` (rendered via a plain `<img>` tag by WordPress). Removed the SVG and orphan Sketch source

### 3.9
- Fix: the "WrkLst Exhibition" tab inside the WordPress Media Library uploader now populates immediately on open. Previously the initial fetch fired before the submit handler was bound (because the API-credentials callback ran synchronously off the localized nonce), so the picker stayed empty until the user typed a character to re-trigger the fetch

### 3.8
- New "Download first image of each artwork" bulk button on the Exhibitions detail view (admin subpage + media uploader tab). For single-image artworks it imports the only image; for multi-image artworks it imports the first sub-image only. Already-imported artworks are skipped entirely (no second pick is taken from a multi-image artwork whose first image is already in the library)

### 3.7
- Bulk-import buttons on the exhibition detail view: "Download all installation views", "Download all artworks", and "Download all" — each shows the remaining count and skips items already in the WP Media Library
- More descriptive auto-generated alt text for installation images (built server-side from the exhibition data: title, solo/group framing, artists, venue, dates, artwork count) so imported install views land with accessible alt text out of the box

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