<?php
/**
 * Plugin Name:     EDD FES Submissions Manager
 * Plugin URI:      https://wordpress.org/plugins/edd-fes-submissions-manager/
 * Description:     EDD Frontend submissions reviews made easy
 * Version:         1.0.0
 * Author:          rubengc
 * Author URI:      http://rubengc.com
 * Text Domain:     edd-fes-submissions-manager
 *
 * @package         EDD\FES\Submissions_Manager
 * @author          rubengc
 * @copyright       Copyright (c) rubengc
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_FES_Submissions_Manager' ) ) {

    /**
     * Main EDD_FES_Submissions_Manager class
     *
     * @since       1.0.0
     */
    class EDD_FES_Submissions_Manager {

        /**
         * @var         EDD_FES_Submissions_Manager $instance The one true EDD_FES_Submissions_Manager
         * @since       1.0.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true EDD_FES_Submissions_Manager
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new EDD_FES_Submissions_Manager();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'EDD_FES_SUBMISSIONS_MANAGER_VER', '1.0.0' );

            // Plugin path
            define( 'EDD_FES_SUBMISSIONS_MANAGER_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'EDD_FES_SUBMISSIONS_MANAGER_URL', plugin_dir_url( __FILE__ ) );
        }


        /**
         * Include necessary files
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function includes() {
            // Include scripts
            require_once EDD_FES_SUBMISSIONS_MANAGER_DIR . 'includes/scripts.php';
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {
            // Adds edd fes submissions manager menu item
            add_action( 'admin_menu', array( $this, 'edd_fes_submissions_manager_menu' ) );

            // Custom redirect on downloads approval
            add_action( 'fes_approve_download_admin', array( $this, 'edd_fes_submissions_manager_approve_download' ) );
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = EDD_FES_SUBMISSIONS_MANAGER_DIR . '/languages/';
            $lang_dir = apply_filters( 'edd_fes_submissions_manager_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'edd-fes-submissions-manager' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'edd-fes-submissions-manager', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-fes-submissions-manager/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-fes-submissions-manager/ folder
                load_textdomain( 'edd-fes-submissions-manager', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-fes-submissions-manager/languages/ folder
                load_textdomain( 'edd-fes-submissions-manager', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-fes-submissions-manager', false, $lang_dir );
            }
        }

        public function edd_fes_submissions_manager_menu() {
            add_submenu_page( 'fes-about', 'EDD FES Submissions Manager', 'Submissions Manager', 'manage_shop_settings', 'fes-submissions-manager', array( $this, 'edd_fes_submissions_manager_page' ));
        }

        public function edd_fes_submissions_manager_page() {
            if ( !current_user_can( 'manage_shop_settings' ) )  {
                wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
            }

            $pending_downloads = get_posts(array(
                'post_type' => 'download',
                'post_status' => 'pending',
                'numberposts' => -1
            ));
            ?>
            <div class="wrap">

                <h1>EDD FES Submissions Manager</h1>

                <?php self::approved_notice(); ?>

                <div class="edd-fes-submissions-manager-wrapper">
                        <?php
                        if( count($pending_downloads) > 0 ) {
                            foreach($pending_downloads as $download) {
                                $vendor = new FES_Vendor( $download->post_author, true );
                                ?>
                                    <div class="edd-fes-submissions-manager-column">
                                        <div class="edd-fes-submissions-manager-box">
                                            <div class="edd-fes-submissions-manager-title">
                                                <div class="title">
                                                    <a href="<?php echo  get_permalink( $download->ID ); ?>"><?php echo '#' . $download->ID . ' - ' . $download->post_title; ?></a>
                                                </div>
                                                <div class="vendor">
                                                    <?php echo sprintf( '%1$s: <a href="%2$s">%3$s (%4$s)</a>',
                                                        EDD_FES()->helper->get_vendor_constant_name( $plural = false, $uppercase = true ),
                                                        admin_url( 'admin.php?page=fes-vendors&view=overview&id=' . $vendor->id ),
                                                        $vendor->name,
                                                        $vendor->username
                                                    ); ?>
                                                </div>
                                            </div>
                                            <div class="edd-fes-submissions-manager-content">
                                                    <?php
                                                        $form_id = EDD_FES()->helper->get_option( 'fes-submission-form', false );

                                                        do_action('edd_fes_submissions_manager_before_render_form', $download, $vendor);

                                                        // Make the FES Form
                                                        $form = EDD_FES()->helper->get_form_by_id( $form_id, $download->ID );

                                                        foreach($form->fields as $field) {
                                                        ?>
                                                            <label class="field-label"><?php echo $field->get_label(); ?></label>
                                                            <div class="field-value">
                                                                <?php echo apply_filters('edd_fes_submissions_manager_' . $field->characteristics['template'] . '_output',  $field->formatted_data() ); ?>
                                                            </div>
                                                        <?php
                                                        }

                                                        do_action('edd_fes_submissions_manager_after_render_form', $download, $vendor);
                                                    ?>
                                            </div>
                                            <div class="edd-fes-submissions-manager-footer">
                                                <?php
                                                $actions['view']   = array(
                                                    'action' => 'view',
                                                    'name' => __( 'View', 'edd_fes' ),
                                                    'url' => get_permalink( $download->ID )
                                                );
                                                $actions['edit']   = array(
                                                    'action' => 'edit',
                                                    'name' => __( 'Edit', 'edd_fes' ),
                                                    'url' => get_edit_post_link( $download->ID )
                                                );
                                                $actions['revoke'] = array(
                                                    'action' => 'revoked',
                                                    'name' => __( 'Revoke', 'edd_fes' ),
                                                    'url' => get_delete_post_link( $download->ID )
                                                );
                                                $actions['approve'] = array(
                                                    'action' => 'approved',
                                                    'name' => __( 'Approve', 'edd_fes' ),
                                                    'url' => wp_nonce_url( add_query_arg( 'approve_download', $download->ID ), 'approve_download' )
                                                );

                                                $output = '';

                                                foreach ( $actions as $action ) {
                                                    $image = isset( $action['image_url'] ) ? $action['image_url'] : fes_plugin_url . 'assets/img/icons/' . $action['action'] . '.png';
                                                    $output .= sprintf( '<a class="button tips" href="%s" data-tip="%s"><img src="%s" alt="%s" width="16" /></a>', esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $image ), esc_attr( $action['name'] ) );
                                                }

                                                echo $output;
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                            }
                        } else {
                            ?>
                                <p><?php  _e('No submissions awaiting approval', 'edd-fes-submissions-approval'); ?></p>
                            <?php
                        }
                        ?>
                </div>
            </div>
            <?php
        }

        public function edd_fes_submissions_manager_approve_download( $download_id ) {
            // Checks if is single approve download action
            if ( !empty( $_GET['approve_download'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'approve_download' ) && current_user_can( 'edit_post', $_GET['approve_download'] ) ) {
                if( isset( $_GET['page'] ) && $_GET['page'] == 'fes-submissions-manager' ) {
                    // If request comes from fes submissions manager page, then redirects to this page
                    wp_redirect(remove_query_arg('approve_download', add_query_arg('approved_downloads', $download_id, admin_url('admin.php?page=fes-submissions-manager'))));
                    exit;
                }
            }
        }

        public function approved_notice() {
            if ( !empty( $_REQUEST['approved_downloads'] ) ) {
                $approved_downloads = $_REQUEST['approved_downloads'];
                if ( is_array( $approved_downloads ) ) {
                    $approved_downloads = array_map( 'absint', $approved_downloads );
                    $titles             = array();

                    if ( empty( $approved_downloads ) ){
                        return;
                    }

                    foreach ( $approved_downloads as $download_id ){
                        $titles[] = get_the_title( $download_id );
                    }
                    echo '<div class="updated"><p>' . sprintf( _x( '%s approved', 'Titles of downloads approved', 'edd_fes' ), '&quot;' . implode( '&quot;, &quot;', $titles ) . '&quot;' ) . '</p></div>';
                } else {
                    echo '<div class="updated"><p>' . sprintf( _x( '%s approved', 'Title of download apporved', 'edd_fes' ), '&quot;' . get_the_title( $approved_downloads ) . '&quot;' ) . '</p></div>';
                }
            }
        }
    }
} // End if class_exists check


/**
 * The main function responsible for returning the one true EDD_FES_Submissions_Manager
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \EDD_FES_Submissions_Manager The one true EDD_FES_Submissions_Manager
 */
function edd_fes_submissions_manager() {
    return EDD_FES_Submissions_Manager::instance();
}
add_action( 'plugins_loaded', 'edd_fes_submissions_manager' );
