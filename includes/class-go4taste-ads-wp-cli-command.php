<?php
/**
 * WP-CLI command for Go4Taste Product Ads plugin.
 *
 * This class adds a custom WP-CLI command to update post meta fields with 
 * product IDs extracted from shortcodes within post content.
 *
 * @package    Go4Taste_Ads
 * @subpackage Go4Taste_Ads/includes
 * @since      1.0.0
 */

if ( defined( 'WP_CLI' ) && WP_CLI ) {

    /**
     * Class Go4Taste_WP_CLI_Command
     *
     * Provides WP-CLI commands to interact with the Go4Taste Product Ads plugin.
     *
     * @since    1.0.0
     */
    class Go4Taste_WP_CLI_Command {

        /**
         * Update post meta with product ID from the first occurrence of the shortcode.
         *
         * This method scans all posts for a specified shortcode, extracts the product ID,
         * and updates the specified post meta field. The command supports options like 
         * dry-run and force-overwrite for added flexibility.
         *
         * @since 1.0.0
         *
         * @param array $args       The positional arguments.
         * @param array $assoc_args The associative arguments.
         */
        public function update_product_id_from_shortcode( $args, $assoc_args ) {
            // Define query parameters to fetch all published posts.
            $query_args = array(
                'post_type'   => 'post',
                'post_status' => 'publish',
                'numberposts' => -1, // Get all posts
            );

            // Fetch the posts based on query arguments.
            $posts = get_posts( $query_args );

            // Retrieve options from the command.
            $dry_run = isset( $assoc_args['dry-run'] );
            $force_overwrite = isset( $assoc_args['force-overwrite'] );
            $ad_type = isset( $assoc_args['ad_type'] ) ? $assoc_args['ad_type'] : 'sidebar-regular-widget';
            $source = isset( $assoc_args['source'] ) ? $assoc_args['source'] : 'shortcode';
            $shortcode_name = isset( $assoc_args['shortcode_name'] ) ? $assoc_args['shortcode_name'] : 'prestashop_data';

            // Map the ad type to the specific meta key.
            $meta_key = $this->get_meta_key_by_ad_type( $ad_type );

            // If the ad type is unknown, return an error.
            if ( ! $meta_key ) {
                WP_CLI::error( "Unknown ad type: {$ad_type}" );
                return;
            }

            // Iterate through each post.
            foreach ( $posts as $post ) {
                $content = $post->post_content;

                // If the source is a shortcode, search for the product ID in the shortcode.
                if ( $source === 'shortcode' ) {
                    // Construct the regex pattern dynamically for the specified shortcode.
                    $shortcode_regex = '/\[' . $shortcode_name . '\s[^]]*product="(\d+)"[^]]*\]/';

                    // Match the first occurrence of the shortcode.
                    if ( preg_match( $shortcode_regex, $content, $matches ) ) {
                        $product_id = $matches[1];
                        $existing_product_id = get_post_meta( $post->ID, $meta_key, true );

                        // Skip updating if the product ID already exists and force-overwrite is not set.
                        if ( $existing_product_id && ! $force_overwrite ) {
                            WP_CLI::warning( "Post ID {$post->ID} already has a product ID ({$existing_product_id}) in {$meta_key}. Skipping..." );
                            continue;
                        }

                        // Log actions in dry-run mode without making changes.
                        if ( $dry_run ) {
                            WP_CLI::log( "Dry run: Post ID {$post->ID} would be updated with product ID {$product_id} in {$meta_key}." );
                        } else {
                            // Update the post meta with the extracted product ID.
                            update_post_meta( $post->ID, $meta_key, $product_id );
                            WP_CLI::success( "Updated post ID {$post->ID} with product ID {$product_id} in {$meta_key}." );
                        }
                    } else {
                        WP_CLI::warning( "No shortcode found in post ID {$post->ID} using shortcode [{$shortcode_name}]." );
                    }
                } else {
                    WP_CLI::error( "Unsupported source: {$source}" );
                }
            }
        }

        /**
         * Map ad_type to the corresponding meta key.
         *
         * This method maps the ad type specified in the WP-CLI command to its corresponding
         * post meta field key. This allows the command to update the correct meta field.
         *
         * @since 1.0.0
         *
         * @param string $ad_type The ad type specified in the command.
         * @return string|null The corresponding meta key or null if not found.
         */
        private function get_meta_key_by_ad_type( $ad_type ) {
            // Define the mapping between ad types and their meta keys.
            $meta_keys = array(
                'sidebar-regular-widget' => '_g4t_a_sidebar_regular_widget_ps_product_id',
                // Add other ad types and their corresponding meta keys here in the future.
            );

            return isset( $meta_keys[ $ad_type ] ) ? $meta_keys[ $ad_type ] : null;
        }
    }

    // Register the WP-CLI command with WP-CLI.
    WP_CLI::add_command( 'go4taste update_product_ids', [ 'Go4Taste_WP_CLI_Command', 'update_product_id_from_shortcode' ] );
}
