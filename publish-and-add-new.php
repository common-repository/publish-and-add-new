<?php
/**
 * Plugin Name: Publish & Add New
 * Plugin URI: https://wordpress.org/plugins/publish-and-add-new/
 * Description: Add a convenient `Publish & Add New` button to the editor.
 * Version: 1.0.0
 * Author: Ramon Ahnert
 * Author URI: https://profiles.wordpress.org/rahmohn/
 * Text Domain: publish-and-add-new
 * Requires at least: 5.9
 * Requires PHP: 7.2
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package PublishAndAddNew
 */

namespace Publish_And_Add_New;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'post_submitbox_start', with_namespace( 'output_publish_and_add_new_button' ) );
add_action( 'admin_print_styles', with_namespace( 'output_style' ), 15 );
add_action( 'admin_print_footer_scripts', with_namespace( 'output_script' ), 15 );

add_filter( 'redirect_post_location', with_namespace( 'update_post_redirect_destination_url' ), 10, 2 );
add_filter( 'wp_insert_post_data', with_namespace( 'update_post_status_to_publish' ), 10, 2 );

/**
 * Add namespace to function name.
 *
 * @param string $function_name The function name.
 *
 * @return string
 */
function with_namespace( $function_name ) {
	return __NAMESPACE__ . '\\' . $function_name;
}

/**
 * Output the Publish & Add New button.
 *
 * @param WP_Post|null $post WP_Post object for the current post on Edit Post screen, null on Edit Link screen.
 */
function output_publish_and_add_new_button( $post ) {
	if ( 'auto-draft' !== $post->post_status && 'draft' !== $post->post_status ) {
		return;
	}

	/**
	 * Filter the Publish & Add New button label.
	 *
	 * @since 1.0.0
	 * @hook publish_and_add_new_button_label
	 * @param  string $button_label The button label. Default: Publish & Add New.
	 * @return string New value
	 */
	$button_label = apply_filters( 'publish_and_add_new_button_label', __( 'Publish & Add New', 'publish-and-add-new' ) );

	?>
		<div id="publishing-and-add-new-action" style="display: none; float: left">
			<?php submit_button( $button_label, 'large', 'publish_and_add_new', false ); ?>
		</div>
	<?php
}

/**
 * Check if it's an add new screen.
 *
 * @return bool
 */
function should_show_publish_and_add_new_button() {
	$screen = get_current_screen();

	/**
	 * Filter whether the Publish & Add New button should be shown.
	 *
	 * @since 1.0.0
	 * @hook publish_and_add_new_should_show_the_button
	 * @param  bool           $should_show True if `'add' === $screen->action`.
	 * @param  WP_Screen|null $screen      Current screen object or null when screen not defined.
	 * @return bool New value
	 */
	return apply_filters( 'publish_and_add_new_should_show_the_button', 'add' === $screen->action, $screen );
}

/**
 * Output the CSS.
 */
function output_style() {
	if ( ! should_show_publish_and_add_new_button() ) {
		return;
	}
	?>
	<style>
		.submitbox.is-submitting #minor-publishing-actions .spinner {
			display: none
		}

		#duplicate-action:has(+ #delete-action .submitdelete:not([style="display: inline;"])) {
			margin-bottom: 10px;
		}

		#publishing-and-add-new-action {
			clear: both;
		}
	</style>
	<?php
}

/**
 * Output the JS.
 */
function output_script() {
	if ( ! should_show_publish_and_add_new_button() ) {
		return;
	}

	?>
	<script>
		jQuery( document ).ready( function( $ ) {
			$("#publishing-and-add-new-action")
				.insertBefore("#publishing-action")
				.fadeIn();

			$("#publishing-and-add-new-action")
				.on( "click", function(e) {
					$( this ).parents( '#major-publishing-actions' ).find( '.spinner' ).addClass( 'is-active' );

					$( this ).parents( '#submitpost' ).addClass('is-submitting');
				})
		})
	</script>
	<?php
}

/**
 * Update the post redirect destination URL to the new post screen.
 *
 * @param string $location The destination URL.
 * @param int    $post_id  The post ID.
 *
 * @return string
 */
function update_post_redirect_destination_url( $location, $post_id ) {
	check_admin_referer( 'update-post_' . $post_id );

	if ( empty( $_POST['publish_and_add_new'] ) ) {
		return $location;
	}

	if ( empty( $_POST['post_type'] ) ) {
		return $location;
	}

	$post_type = sanitize_text_field( wp_unslash( $_POST['post_type'] ) );

	return admin_url( 'post-new.php?post_type=' . $post_type );
}

/**
 * Update the post status to publish.
 *
 * @param array $data    An array of slashed, sanitized, and processed post data.
 * @param array $postarr An array of sanitized (and slashed) but otherwise unmodified post data.
 *
 * @return array
 */
function update_post_status_to_publish( $data, $postarr ) {
	$post_id = $postarr['ID'] ?? false;

	if ( ! $post_id ) {
		return $data;
	}

	if ( empty( $_POST['_wpnonce'] ) ) {
		return $data;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-post_' . $post_id ) ) {
		return $data;
	}

	if ( empty( $_POST['publish_and_add_new'] ) ) {
		return $data;
	}

	$data['post_status'] = 'publish';

	return $data;
}
