# CI vs RM Display Plugin

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](./LICENSE)
[![Version](https://img.shields.io/badge/Version-2.2.0-orange.svg)](./CHANGELOG.md)

A comprehensive WordPress plugin that displays Capital Improvement vs Repair and Maintenance items with advanced search and filtering functionality.

## Plugin Information

- **Plugin Name:** CI vs RM Display Plugin
- **Version:** 2.2.0
- **Author:** Sterling Digital
- **Author URI:** https://sterlingdigital.com
- **License:** GPL v2 or later

## Description

This plugin provides both shortcode-based and page template display systems for CIVSRM (Capital Improvement vs Repair and Maintenance) items. It's designed to work with existing custom post types and taxonomies, focusing on display, search, and filtering capabilities.

## Features

### Core Functionality
- **Shortcode Display**: Use `[civsrm_display]` to embed the interface anywhere
- **Page Template**: Automatic template handling for CIVSRM pages
- **Advanced Search**: Smart search with phrase matching, word scoring, and text highlighting
- **Category Filtering**: Optional category-based filtering system
- **Responsive Design**: Mobile-friendly grid layout with adaptive columns
- **Performance Optimized**: Efficient database queries and caching system

### Search & Filter Features
- **Intelligent Search Scoring**: 
  - Exact phrase matches (highest priority)
  - All words present (medium priority)
  - Individual word matches (lower priority)
- **Real-time Text Highlighting**: Search terms highlighted using Mark.js
- **Category Filters**: Toggle-able category filtering with checkboxes
- **Empty Container Handling**: Automatically hide empty categories and columns
- **Responsive Controls**: Mobile-optimized search and filter interface

### Technical Features
- **Caching System**: Transient-based caching for improved performance
- **Clean Architecture**: Object-oriented design with proper separation of concerns
- **Template System**: Proper WordPress template hierarchy support
- **Asset Management**: Conditional loading of CSS and JavaScript
- **Translation Ready**: Full internationalization support

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Existing plugin that provides:
  - Custom Post Type: `civsrm-item`
  - Custom Taxonomy: `civsrm-category`
  - Custom Meta Field: `civsrm_classification`

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Use the shortcode `[civsrm_display]` on any page or post, or create a page with slug 'civsrm'

## Usage

### Basic Shortcode
```
[civsrm_display]
```

### Shortcode with Custom Options
```
[civsrm_display title="Custom Title" subtitle="Custom Subtitle" show_filters="true"]
```

### Shortcode Attributes

- `title` - Main heading (default: "New York Sales and Use Tax Handbook For Contractors")
- `subtitle` - Subheading (default: "Capital Improvement vs Repair and Maintenance")
- `use_cache` - Enable/disable caching (default: "true")
- `show_filters` - Show category filters (default: "false")

### Page Template Usage

The plugin provides multiple ways to use the page template:

1. **Custom Template Selection**: 
   - Create or edit any page in WordPress
   - In the Page Attributes section, select "CIVSRM Items Display" from the Template dropdown
   - This template will appear in the dropdown automatically when the plugin is active

2. **Automatic Handling**: 
   - Pages with slug `civsrm` are automatically handled
   - Pages with template `page-civsrm.php` are automatically processed

Category filters are enabled by default in page template mode.

**Note**: The template is registered programmatically by the plugin, so it will appear in the WordPress admin template dropdown without needing to copy files to your theme.

## File Structure

```
ci-vs-rm/
├── ci-vs-rm.php                    # Main plugin file
├── includes/
│   ├── class-civsrm-plugin.php     # Main plugin class
│   ├── class-civsrm-shortcode.php  # Shortcode handler
│   └── class-civsrm-template.php   # Template functionality
├── templates/
│   └── page-civsrm.php             # Page template
├── assets/
│   ├── css/
│   │   └── civsrm-styles.css       # Plugin styles
│   └── js/
│       ├── civsrm-search.js        # Search & filter functionality
│       └── mark.min.js             # Text highlighting library
├── languages/                      # Translation files (future)
└── README.md                       # This file
```

## Styling

The plugin includes comprehensive CSS for:
- Responsive grid layout (2-column on desktop, 1-column on mobile)
- Search interface styling with modern design
- Sticky headers for better navigation
- Category filter panel with checkbox styling
- Mobile-friendly responsive design
- Elementor theme compatibility

### CSS Classes

Key CSS classes for customization:
- `.civsrm-container` - Main container
- `.civsrm-search-box` - Search interface
- `.civsrm-category-group` - Category sections
- `.civsrm-items` - Items grid container
- `.ci-items` - Capital Improvement column
- `.rm-items` - Repair & Maintenance column
- `.filter-panel` - Category filter panel

## JavaScript Dependencies

- **Mark.js v8.11.1**: Text highlighting functionality (included)
- **Vanilla JavaScript**: No jQuery dependency for better performance

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile responsive design
- Progressive enhancement approach
- IE11+ support (with graceful degradation)

## Performance Features

### Database Optimization
- Single optimized query for all categories
- Efficient item retrieval with joins
- Reduced N+1 query problems

### Caching System
- Transient-based caching (1-hour default)
- Automatic cache invalidation on content updates
- Separate caches for shortcode and template data

### Asset Loading
- Conditional asset loading (only when needed)
- Minified JavaScript library
- Optimized CSS with mobile-first approach

## Customization

### CSS Customization
Override styles by adding custom CSS to your theme:

```css
.civsrm-container {
    max-width: 1400px; /* Custom max width */
}

.civsrm-items {
    gap: 3rem; /* Custom grid gap */
}
```

### PHP Hooks
The plugin provides several action hooks for customization:
- `save_post` - Automatic cache clearing
- `edited_civsrm-category` - Category update handling
- `created_civsrm-category` - New category handling
- `deleted_civsrm-category` - Category deletion handling

### Template Override
Themes can override the page template by creating:
`your-theme/page-civsrm.php`

## Troubleshooting

### Common Issues

1. **No items displayed**: 
   - Ensure the required CPT and taxonomy exist
   - Check that items have the correct classification meta field

2. **Search not working**: 
   - Verify Mark.js is loading properly
   - Check browser console for JavaScript errors

3. **Styling issues**: 
   - Confirm CSS file is being loaded
   - Check for theme conflicts

4. **Filter panel not showing**:
   - Ensure `show_filters="true"` in shortcode
   - Verify categories exist and have items

### Debug Steps

1. Check if shortcode is properly placed
2. Verify custom post type and taxonomy names match expected values
3. Ensure meta field `civsrm_classification` contains expected values ("Capital Improvement" or other)
4. Check browser console for JavaScript errors
5. Verify plugin assets are loading correctly

## Development

### Local Development
1. Clone/download the plugin to your WordPress plugins directory
2. Ensure you have the required CPT and taxonomy setup
3. Activate the plugin and test functionality

### Contributing
Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute to this project.

## Issues and Feature Requests
Please use the GitHub issue tracker to report bugs or request features.

## Security
If you discover any security related issues, please email security@sterlingdigital.com instead of using the issue tracker.

## Changelog

### Version 2.2.0
- Production-ready release
- Cleaned up development files
- Standardized version numbering
- Optimized for GitHub distribution

### Version 2.0.2
- Complete plugin reorganization and cleanup
- Enhanced search functionality with intelligent scoring
- Added category filtering system
- Improved responsive design
- Optimized database queries and caching
- Updated branding to Sterling Digital
- Added programmatic template registration
- Fixed template loading and asset enqueuing
- Added comprehensive documentation

### Version 1.0.0
- Initial release with basic shortcode functionality
- Basic search with highlighting
- Simple responsive design

## License

GPL v2 or later

## Support

For support and feature requests, please contact Sterling Digital at https://sterlingdigital.com

---

**Sterling Digital** - Professional WordPress Development
