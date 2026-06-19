# Luminous Timeline

A lightweight WordPress plugin for creating an interactive process timeline with paired image transitions. The plugin lets site admins manage timeline steps, descriptions, images, layout settings, colors, and autoplay behavior from the WordPress dashboard, then render the timeline anywhere using a shortcode.

## Overview

**Luminous Timeline** is designed for service/process sections where each step needs supporting text and visual storytelling. It displays the active step with a large image and the next step with a smaller preview image. Visitors can click steps, use keyboard navigation, or click the smaller image to move through the timeline.

The plugin is fully self-contained in one PHP file and outputs its frontend CSS and JavaScript only once per page.

## Features

- WordPress admin settings page for managing timeline content
- Add, remove, and reorder timeline steps
- Media Library image selector for each step
- Shortcode-based output using `[luminous_timeline]`
- Large active image with smaller next-image preview
- Slide transition with seamless loop support
- Fade/crossfade transition option
- Optional auto-rotation
- Adjustable auto-advance speed
- Editable section title and intro paragraph
- Customizable active image width, inactive image width, gap, and image height
- Color controls for accent, heading, and background colors
- Opacity support for color values
- Responsive layout for mobile screens
- Keyboard navigation support for timeline steps
- Pause on hover and keyboard focus
- Reduced-motion support for users who prefer less animation
- Basic security handling with capability checks, nonce verification, sanitization, and escaping

## Requirements

- WordPress 5.5 or higher
- PHP 7.2 or higher
- Administrator access to configure plugin settings

## Installation

1. Download or clone this repository.
2. Make sure the plugin file is named:

   ```text
   luminous-timeline.php
   ```

3. Place the plugin folder inside your WordPress plugins directory:

   ```text
   wp-content/plugins/luminous-timeline/
   ```

4. The final structure should look like this:

   ```text
   wp-content/plugins/luminous-timeline/
   ├── luminous-timeline.php
   └── README.md
   ```

5. Go to **WordPress Admin > Plugins**.
6. Activate **Luminous Timeline**.
7. After activation, open **Luminous Timeline** from the WordPress admin menu.

## Usage

After activating the plugin, configure your timeline from:

```text
WordPress Admin > Luminous Timeline
```

Then place this shortcode anywhere on your site:

```text
[luminous_timeline]
```

You can use the shortcode inside:

- Gutenberg shortcode block
- Gutenberg paragraph block
- Classic Editor
- Elementor shortcode widget
- Avada Code Block
- Avada Text element
- WordPress widgets
- Theme template files using `do_shortcode()`

Example for template usage:

```php
<?php echo do_shortcode('[luminous_timeline]'); ?>
```

## Admin Settings

The plugin includes a dashboard settings page where you can manage:

### Heading

- Section title
- Intro paragraph

### Steps

Each timeline step includes:

- Step heading
- Step description
- Step image

You can also:

- Add new steps
- Remove existing steps
- Move steps up or down
- Select images from the WordPress Media Library
- Clear selected images

### Layout and Style

Available controls include:

- Active image width
- Inactive image width
- Gap between images
- Image height
- Transition type: `Slide` or `Fade`
- Auto-rotate on/off
- Auto-advance speed in seconds
- Accent color
- Heading color
- Background color
- Opacity for supported colors

## Default Timeline Content

On first activation, the plugin creates default content for a five-step lighting process:

1. Discovery & Consultation
2. Custom Lighting Design
3. Precision Installation
4. Night time Fine-Tuning
5. Continued Care

These default steps can be edited or replaced from the plugin settings page.

## Frontend Behavior

The rendered timeline includes:

- A title and intro paragraph
- A vertical step list
- Active step highlighting
- Progress indicator for autoplay
- Large active image
- Smaller next-image preview
- Click interaction
- Keyboard interaction
- Responsive layout on smaller screens

When autoplay is enabled, the progress line fills over the configured dwell time and then advances to the next step. Hovering over the timeline or focusing inside it pauses the animation.

## Accessibility Notes

The plugin includes several accessibility-focused behaviors:

- Timeline steps are rendered as tab-style buttons
- Arrow key navigation is supported
- `Home` and `End` keys move to the first and last steps
- Focus states are visible
- Autoplay pauses during keyboard focus
- Reduced-motion preferences are respected

## Styling Notes

The plugin outputs its CSS inline through the shortcode and uses CSS custom properties for key layout and color settings.

Default fonts loaded from Google Fonts:

- Poppins for headings/display text
- Inter for body text

The assets are printed only once per page, even if the shortcode appears multiple times.

## Security Notes

The plugin uses standard WordPress security practices, including:

- Direct file access protection with `ABSPATH`
- Admin capability check using `manage_options`
- Nonce verification before saving settings
- Sanitization for submitted text, textarea, URL, color, and numeric fields
- Escaping before outputting admin and frontend content

## Repository Structure

```text
luminous-timeline/
├── luminous-timeline.php
└── README.md
```

## Main Shortcode

```text
[luminous_timeline]
```

The shortcode does not currently accept custom attributes. It renders the timeline using the saved plugin settings.

## Version

Current plugin version:

```text
1.4.0
```

## License

This plugin is licensed under the GPL-2.0-or-later license.

License URI:

```text
https://www.gnu.org/licenses/gpl-2.0.html
```

## Author

Luminous

## Notes for Developers

This plugin is currently built as a single PHP file. The admin styles, frontend styles, admin scripts, and frontend scripts are included inline inside the plugin file.

For a larger production version, you may later split the code into separate files, such as:

```text
assets/css/frontend.css
assets/js/frontend.js
assets/css/admin.css
assets/js/admin.js
includes/admin-page.php
includes/shortcode.php
```

This is optional. The current plugin works as a compact single-file WordPress plugin.
