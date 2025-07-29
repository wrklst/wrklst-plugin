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

## Getting API Access

To use this plugin, WrkLst users need to obtain API credentials. Please contact **support@wrklst.art** to request the creation of an API key for authenticating the plugin with your WrkLst account.

## Support

For support and documentation, please visit [WrkLst Support](https://wrklst.art/support)

## Changelog

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