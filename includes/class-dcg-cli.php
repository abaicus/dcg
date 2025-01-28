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
   * Generates dummy content posts
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
   * [--images=<bool>]
   * : Whether to include images in content
   * ---
   * default: true
   * options:
   *   - true
   *   - false
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
   * ## EXAMPLES
   * 
   *     # Generate 25 posts with images (default)
   *     $ wp dcg generate
   * 
   *     # Generate 5 pages without images
   *     $ wp dcg generate --count=5 --post-type=page --images=false --featured-image=false
   * 
   * @param array $args
   * @param array $assoc_args
   */
  public function generate($args, $assoc_args) {
    // Parse arguments with defaults
    $count = (int) ($assoc_args['count'] ?? 25);
    $post_type = $assoc_args['post-type'] ?? 'post';
    $use_images = $this->parse_bool($assoc_args['images'] ?? 'true');
    $use_featured = $this->parse_bool($assoc_args['featured-image'] ?? 'true');

    // Validate post type
    if (!post_type_exists($post_type)) {
      \WP_CLI::error(sprintf('Post type "%s" does not exist.', $post_type));
      return;
    }

    // Create admin instance for content generation
    $admin = new DCG_Admin();
    $generated = 0;
    $failed = 0;

    \WP_CLI::log(sprintf('Generating %d %s(s)...', $count, $post_type));
    $progress = \WP_CLI\Utils\make_progress_bar('Generating content', $count);

    for ($i = 0; $i < $count; $i++) {
      $post_data = array(
        'post_title' => $admin->generate_title(),
        'post_content' => $admin->generate_content_text(true, $use_images),
        'post_status' => 'publish',
        'post_type' => $post_type,
        'post_author' => get_current_user_id()
      );

      $post_id = wp_insert_post($post_data);

      if ($post_id && !is_wp_error($post_id)) {
        update_post_meta($post_id, '_dcg_generated', true);

        if ($use_featured) {
          $admin->add_random_image($post_id);
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
   * Deletes all generated dummy content
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
        }

        // Delete the post
        wp_delete_post($post_id, true);
        $deleted++;
        $progress->tick();
      }

      $progress->finish();
    }

    wp_reset_postdata();

    if ($deleted === 0) {
      \WP_CLI::log('No generated content found to delete.');
    } else {
      \WP_CLI::success(sprintf('Deleted %d generated posts.', $deleted));
    }
  }

  /**
   * Helper function to parse boolean CLI arguments
   */
  private function parse_bool($value) {
    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
  }
}
