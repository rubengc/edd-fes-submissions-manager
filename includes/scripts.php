<?php
/**
 * Scripts
 *
 * @package     EDD\FES\Submissions_Manager\Scripts
 * @since       1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * Load admin scripts
 *
 * @since       1.0.0
 * @global      array $edd_settings_page The slug for the EDD settings page
 * @global      string $post_type The type of post that we are editing
 * @return      void
 */
function edd_fes_submissions_manager_admin_scripts( $hook ) {
    // Use minified libraries if SCRIPT_DEBUG is turned off
    $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

    wp_enqueue_style( 'edd_fes_submissions_manager_admin_css', EDD_FES_SUBMISSIONS_MANAGER_URL . '/assets/css/edd-fes-submissions-manager' . $suffix . '.css' );
}
add_action( 'admin_enqueue_scripts', 'edd_fes_submissions_manager_admin_scripts', 100 );