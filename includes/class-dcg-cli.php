<?php

if (!defined('ABSPATH')) {
  exit;
}

// Ensure WP-CLI is available
if (!defined('WP_CLI')) {
  return;
}

/**
 * Implements WP-CLI commands for Dummy Content Generator
 */
class DCG_CLI {
  /**
   * Generates dummy content posts with Gutenberg blocks
   *
   * ## OPTIONS
   *
   * [--count=<number>]
   * : Number of posts to generate
   * ---
   * default: 25
   * ---
   *
   * [--post-type=<type>]
   * : Post type to generate
   * ---
   * default: post
   * ---
   *
   * [--image-count=<number>]
   * : Number of images to include in content (0-10). Images are imported to Media Library.
   * ---
   * default: 3
   * ---
   *
   * [--featured-image=<bool>]
   * : Whether to add featured images
   * ---
   * default: true
   * options:
   *   - true
   *   - false
   * ---
   *
   * [--categories=<bool>]
   * : Whether to generate and assign categories (only for post types that support categories)
   * ---
   * default: true
   * options:
   *   - true
   *   - false
   * ---
   *
   * ## EXAMPLES
   *
   *     # Generate 25 posts with 3 content images each (default)
   *     $ wp dcg generate
   *
   *     # Generate 5 pages with 5 images each
   *     $ wp dcg generate --count=5 --post-type=page --image-count=5
   *
   *     # Generate posts without any images or categories
   *     $ wp dcg generate --image-count=0 --featured-image=false --categories=false
   *
   * @param array $args
   * @param array $assoc_args
   */
  public function generate($args, $assoc_args) {
    // Parse arguments with defaults
    $count = (int) ($assoc_args['count'] ?? 25);
    $post_type = $assoc_args['post-type'] ?? 'post';
    $image_count = min(10, max(0, (int) ($assoc_args['image-count'] ?? 3)));
    $use_featured = $this->parse_bool($assoc_args['featured-image'] ?? 'true');
    $use_categories = $this->parse_bool($assoc_args['categories'] ?? 'true');

    // Validate post type
    if (!post_type_exists($post_type)) {
      \WP_CLI::error(sprintf('Post type "%s" does not exist.', $post_type));
      return;
    }

    // Create admin instance for content generation
    $admin = new DCG_Admin();
    $generated = 0;
    $failed = 0;

    // Pre-generate categories if requested and post type supports them
    $category_ids = array();
    if ($use_categories && is_object_in_taxonomy($post_type, 'category')) {
      \WP_CLI::log('Creating categories...');
      $category_ids = $admin->get_or_create_categories();
      \WP_CLI::log(sprintf('Using %d categories.', count($category_ids)));
    }

    \WP_CLI::log(sprintf('Generating %d %s(s) with %d content image(s) each...', $count, $post_type, $image_count));
    $progress = \WP_CLI\Utils\make_progress_bar('Generating content', $count);

    for ($i = 0; $i < $count; $i++) {
      // Create post first to get ID for image attachment
      $post_data = array(
        'post_title' => $admin->generate_title(),
        'post_content' => '',
        'post_status' => 'publish',
        'post_type' => $post_type,
        'post_author' => get_current_user_id()
      );

      $post_id = wp_insert_post($post_data);

      if ($post_id && !is_wp_error($post_id)) {
        update_post_meta($post_id, '_dcg_generated', true);

        // Generate content with post_id so images can be attached
        $content = $admin->generate_content_text(true, $post_id, $image_count);
        wp_update_post(array(
          'ID' => $post_id,
          'post_content' => $content
        ));

        if ($use_featured) {
          $admin->add_random_image($post_id);
        }

        // Assign random categories
        if (!empty($category_ids)) {
          $num_cats = rand(1, min(3, count($category_ids)));
          $random_keys = array_rand($category_ids, $num_cats);
          if (!is_array($random_keys)) {
            $random_keys = array($random_keys);
          }
          $selected = array();
          foreach ($random_keys as $key) {
            $selected[] = $category_ids[$key];
          }
          wp_set_post_categories($post_id, $selected);
        }

        $generated++;
      } else {
        $failed++;
      }

      $progress->tick();
    }

    $progress->finish();

    if ($failed > 0) {
      \WP_CLI::warning(sprintf('%d posts failed to generate.', $failed));
    }

    \WP_CLI::success(sprintf('Generated %d %s(s).', $generated, $post_type));
  }

  /**
   * Deletes all generated dummy content, images, and categories
   *
   * ## EXAMPLES
   *
   *     # Delete all generated content
   *     $ wp dcg delete
   *
   */
  public function delete() {
    $args = array(
      'post_type' => 'any',
      'posts_per_page' => -1,
      'meta_key' => '_dcg_generated',
      'meta_value' => true,
    );

    $query = new WP_Query($args);
    $deleted = 0;
    $attachments_deleted = 0;

    if ($query->have_posts()) {
      \WP_CLI::log(sprintf('Found %d posts to delete...', $query->found_posts));
      $progress = \WP_CLI\Utils\make_progress_bar('Deleting content', $query->found_posts);

      while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();

        // Delete featured image
        $featured_image_id = get_post_thumbnail_id($post_id);
        if ($featured_image_id) {
          wp_delete_attachment($featured_image_id, true);
          $attachments_deleted++;
        }

        // Delete all attached content images (marked with our meta)
        $attached_images = get_posts(array(
          'post_type' => 'attachment',
          'posts_per_page' => -1,
          'post_parent' => $post_id,
          'meta_key' => '_dcg_generated_attachment',
          'meta_value' => true,
        ));

        foreach ($attached_images as $image) {
          wp_delete_attachment($image->ID, true);
          $attachments_deleted++;
        }

        // Delete the post
        wp_delete_post($post_id, true);
        $deleted++;
        $progress->tick();
      }

      $progress->finish();
    }

    wp_reset_postdata();

    // Delete generated categories
    $admin = new DCG_Admin();
    $categories_deleted = $admin->delete_generated_categories();

    if ($deleted === 0 && $categories_deleted === 0) {
      \WP_CLI::log('No generated content found to delete.');
    } else {
      \WP_CLI::success(sprintf('Deleted %d posts, %d attachments, and %d categories.', $deleted, $attachments_deleted, $categories_deleted));
    }
  }

  /**
   * Helper function to parse boolean CLI arguments
   */
  private function parse_bool($value) {
    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
  }
}
