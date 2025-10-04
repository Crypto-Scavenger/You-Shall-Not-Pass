# You Shall Not Pass

A powerful content restriction plugin for WordPress that displays only a customizable login form page to non-logged-in users. Features a futuristic cyberpunk-themed design with full customization options.

## Description

**You Shall Not Pass** completely restricts access to your WordPress site for non-logged-in visitors. Instead of allowing access to any content, pages, or posts, it displays a beautifully designed login form with a cyberpunk aesthetic. Perfect for membership sites, private communities, development environments, or any WordPress installation that requires user authentication before access.

### Key Features

- **Complete Content Restriction** - Non-logged-in users see only the login form
- **Fully Customizable Design** - Control every aspect of the restriction page
- **Cyberpunk Theme** - Futuristic design with glowing effects and animations
- **Color Customization** - 11 different color settings to match your brand
- **Custom Text Content** - Customize all labels, headings, and messages
- **Font Awesome Icons** - Built-in icon support for enhanced visuals
- **Mobile Responsive** - Looks great on all devices
- **Custom CSS Support** - Add your own styles for advanced customization
- **Clean Admin Interface** - Simple, organized settings under Users menu
- **No External Dependencies** - All functionality built-in, no external services
- **GDPR Compliant** - No tracking, cookies, or external data transmission
- **Optional Cleanup** - Remove all data on uninstall

## Installation

### Manual Installation

1. Download the plugin files
2. Upload the `you-shall-not-pass` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to **Users → Content Restriction** to configure settings

### Configuration

After activation:

1. Navigate to **Users → Content Restriction** in your WordPress admin
2. Configure your preferred settings across four tabs:
   - **General** - Enable/disable restriction and cleanup options
   - **Page Content** - Customize headings, messages, and logo text
   - **Login Form** - Configure form labels and elements
   - **Design** - Customize all colors and add custom CSS
3. Click "Save Changes"
4. Log out to preview the restriction page

## Usage Guide

### General Settings

- **Enable Content Restriction** - Toggle the content restriction on/off
- **Cleanup on Uninstall** - Choose whether to remove all data when uninstalling

### Page Content Settings

- **Page Title** - Browser tab title (for SEO)
- **Show Logo Section** - Display/hide the top logo area
- **Logo Text** - Text displayed in the logo section (default: "RESTRICTED AREA")
- **Main Heading** - Large heading on the restriction page (default: "You Shall Not Pass")
- **Page Message** - Message displayed below the heading

### Login Form Settings

- **Username Label** - Label for username field
- **Password Label** - Label for password field
- **Submit Button Text** - Text on the login button
- **Show "Remember Me"** - Display/hide the remember me checkbox
- **Remember Me Label** - Label for the remember me option
- **Show "Lost Password"** - Display/hide the lost password link
- **Lost Password Link Text** - Text for the lost password link

### Design Settings

Customize 11 different color aspects:

1. **Background Color** - Page background
2. **Text Color** - Main text color
3. **Accent Color** - Accent elements (icons, borders, glows)
4. **Form Background Color** - Login form background (supports rgba)
5. **Form Border Color** - Border around the form
6. **Input Background Color** - Input field backgrounds (supports rgba)
7. **Input Text Color** - Text inside input fields
8. **Input Border Color** - Input field borders (supports rgba)
9. **Button Background Color** - Submit button background
10. **Button Text Color** - Submit button text
11. **Button Hover Color** - Button color on hover

**Custom CSS** - Add your own CSS for advanced styling

### Default Color Scheme

The plugin uses a cyberpunk theme by default:
- Background: `#262626` (dark gray)
- Text: `#ffffff` (white)
- Accent: `#d11c1c` (red)
- Form Background: `rgba(38, 38, 38, 0.9)` (semi-transparent dark)
- Form Border: `#d11c1c` (red)

## File Structure

```
you-shall-not-pass/
├── you-shall-not-pass.php     # Main plugin file, initialization
├── README.md                  # This file
├── uninstall.php              # Cleanup on plugin deletion
├── index.php                  # Security stub
├── includes/
│   ├── class-database.php     # Database operations, settings storage
│   ├── class-core.php         # Core functionality, content restriction
│   ├── class-admin.php        # Admin interface, settings page
│   └── index.php              # Security stub
└── assets/
    ├── public.css             # Front-end cyberpunk styling
    ├── admin.css              # Admin page styling
    ├── admin.js               # Admin page JavaScript (tabs, color picker)
    └── index.php              # Security stub
```

### File Descriptions

**you-shall-not-pass.php**
- Plugin header information
- Constant definitions
- Class includes and initialization
- Activation hook

**includes/class-database.php**
- Custom database table creation
- Settings CRUD operations
- Settings caching for performance
- Lazy loading implementation
- Default settings insertion

**includes/class-core.php**
- Content restriction logic
- Template redirect handling
- Restriction page rendering
- Asset enqueueing
- Login redirect management
- Dynamic CSS injection

**includes/class-admin.php**
- Admin menu registration (Users submenu)
- Tabbed settings page rendering
- Form handling and validation
- Nonce verification
- Settings save operations

**assets/public.css**
- Cyberpunk theme styling
- Responsive design
- CSS animations and effects
- Grid background pattern
- Glowing effects and shadows

**assets/admin.css**
- Clean, minimal styling for settings page
- WordPress admin theme consistency
- Tab content visibility management

**assets/admin.js**
- Tab switching functionality
- WordPress color picker initialization
- jQuery-based interactions

**uninstall.php**
- Checks cleanup preference
- Drops custom database table if enabled
- Cleans up on plugin deletion

## Technical Details

### Database

The plugin creates a custom database table: `wp_ysnp_settings`

**Table Structure:**
- `id` - Auto-incrementing primary key
- `setting_key` - Unique setting identifier
- `setting_value` - Setting value (longtext)

**Why Custom Tables?**
- Prevents wp_options table bloat
- Better performance for plugin-specific data
- Easier cleanup on uninstall
- Optimized for plugin's specific needs

### Caching

- Settings are cached using WordPress Object Cache
- Cache group: `ysnp_settings`
- Automatic cache invalidation on save
- Improved performance for repeated reads

### Security Features

- Nonce verification on all form submissions
- Capability checks (`manage_options`)
- Input sanitization using WordPress functions
- Output escaping at point of display
- SQL injection prevention with `$wpdb->prepare()`
- No direct database queries without preparation

### Performance

- Lazy loading of settings
- Object caching for database queries
- Minimal asset loading (only when needed)
- No external HTTP requests
- Optimized CSS and JavaScript

### Compatibility

- **WordPress Version:** 6.8+
- **PHP Version:** 7.4+
- **MySQL Version:** 5.0+
- **Browser Support:** All modern browsers
- **Mobile:** Fully responsive design

## Privacy & GDPR Compliance

This plugin is designed with privacy in mind:

- **No External Services** - All functionality is self-contained
- **No Data Transmission** - Nothing sent to external servers
- **No Cookies** - Plugin doesn't set any cookies
- **No Tracking** - No user tracking or analytics
- **No Personal Data Collection** - Only stores plugin settings
- **Local Storage Only** - All data stored in your WordPress database

## Features in Detail

### Cyberpunk Visual Design

The restriction page features:
- Animated glowing effects
- Pulsing shield icon
- Scanning grid background
- Animated scanline effects
- Glitch animation on heading
- Smooth hover transitions
- Mobile-optimized layouts

### Font Awesome Integration

The plugin utilizes Font Awesome icons for:
- Shield icon in logo
- User icon for username field
- Lock icon for password field
- Key icon for lost password link
- Arrow icon on submit button

**Note:** Your site must have Font Awesome loaded (via theme or another plugin).

### Responsive Design

Breakpoints:
- Desktop: Full experience
- Tablet (768px): Adjusted sizing
- Mobile (480px): Optimized for small screens

## Frequently Asked Questions

**Q: Will this lock me out of wp-admin?**  
A: No. The plugin only restricts the front-end. You can always access wp-admin and wp-login.php.

**Q: What happens to logged-in users?**  
A: Logged-in users see the normal site. The restriction only applies to non-logged-in visitors.

**Q: Can I customize the design?**  
A: Yes! The plugin offers 11 color settings plus a custom CSS field for complete control.

**Q: Does this work with caching plugins?**  
A: Yes, but logged-in users should be excluded from caching for best results.

**Q: Will this affect SEO?**  
A: Yes. Search engines won't be able to index your content. Use only for private sites.

**Q: Can I white-list specific pages?**  
A: Not in the current version. All content is restricted for non-logged-in users.

**Q: Does it work with membership plugins?**  
A: Yes, but those plugins may have their own restriction logic that could conflict.

**Q: How do I disable the restriction temporarily?**  
A: Uncheck "Enable Content Restriction" in the General settings tab.

## Changelog

### Version 1.0.0
- Initial release
- Complete content restriction for non-logged-in users
- Customizable cyberpunk-themed login page
- 11 color customization options
- Custom text for all page elements
- Tabbed admin interface under Users menu
- Custom database table for settings
- Object caching for performance
- Mobile responsive design
- Font Awesome icon integration
- Custom CSS support
- Optional cleanup on uninstall


## License

This plugin is licensed under GPL v2 or later.

---

**Plugin Version:** 1.0.0  
**Requires WordPress:** 6.8+  
**Requires PHP:** 7.4+  
**License:** GPL v2 or later
