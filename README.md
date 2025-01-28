# Dummy Content Generator

A WordPress plugin that helps you generate dummy content for your website. Perfect for testing and development purposes.

## Features

- Generate multiple posts at once
- Support for all public post types
- Option to include HTML formatting in content
- Add featured images from Lorem Picsum
- Include up to 3 random images in post content
- Simple and user-friendly interface
- WP-CLI support for automation

## Installation

1. Download the plugin files
2. Upload the plugin folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Tools > Dummy Content to start generating content

## Usage

### Admin Interface

1. Navigate to Tools > Dummy Content in your WordPress admin panel
2. Select the desired post type from the dropdown menu
3. Enter the number of posts you want to generate (1-50)
4. Choose your content options:
   - Include HTML formatting
   - Add featured images
   - Include images in content
5. Click "Generate Content" and wait for the process to complete

### WP-CLI Commands

Generate content:

```bash
# Generate 10 posts with images (default)
wp dcg generate

# Generate 5 pages without images
wp dcg generate --count=5 --post-type=page --images=false --featured-image=false
```

Delete all generated content:

```bash
wp dcg delete
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher

## Notes

- The plugin uses Lorem Picsum for generating images
- Generated content includes various HTML elements (headings, lists, blockquotes)
- All generated posts are published immediately
- Only users with 'manage_options' capability can generate content
- Generated content is tracked and can be bulk deleted

## License

This project is licensed under the GLWTS(Good Luck With That Shit) Public License - see the [LICENSE](LICENSE) file for details.

Basically, you can do whatever you want with this code, but you're on your own. Good luck!
