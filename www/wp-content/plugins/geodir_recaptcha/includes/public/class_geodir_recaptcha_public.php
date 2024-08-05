<?php
// Check GD_Recaptcha_Public class exists or not.
if( !class_exists( 'GD_Recaptcha_Public' ) ) {
    
    /**
     * The public-specific functionality of the plugin.
     *
     * @since 2.0.0
     * @package    GD_Recaptcha
     * @subpackage GD_Recaptcha/public
     *
     * Class GD_Recaptcha_Public
     */
    class GD_Recaptcha_Public {
        
        /**
        * Constructor.
        *
        * @since 2.0.0
        *
        * GD_Recaptcha_Public constructor.
        */
        public function __construct() {
            
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );
            add_action( 'init', array( $this, 'recaptcha_init' ), 0 );

        }
        
        /**
        * Register and enqueue duplicate alert styles and scripts.
        *
        * @since 2.0.0
        */
        public function enqueue_styles_and_scripts() {
            if ( ! geodir_design_style() ) {
                wp_register_style( 'recaptcha-public-style', GD_RECAPTCHA_PLUGIN_URL . 'assets/css/geodir_recaptcha_public.css', array(), GD_RECAPTCHA_VERSION );
                wp_enqueue_style( 'recaptcha-public-style' );
            }
        }

        /**
         * Init GD ReCaptcha plugin.
         *
         * @since 2.0.0
         *
         * @param bool $admin Is this admin page?.
         * @param bool $admin_ajax Is this a admin ajax request?
         */
        public function recaptcha_init( $admin=false, $admin_ajax = false ) {

            global $pagenow;
            $admin = is_admin();
            $admin_ajax = ( defined( 'DOING_AJAX' ) && DOING_AJAX );

            $general_obj = new GD_Recaptcha_General();

            if ( $general_obj->recaptcha_check_role() ) { // disable captcha as per user role settings
                return;
            }

            $load_css = false;

            // WP registration form
            if ( ! $admin && geodir_get_option( 'rc_wp_registration' ) && ! defined( 'UWP_RECAPTCHA_VERSION' ) ) {
                // Make sure jQuery is available
                if ( $pagenow == 'wp-login.php' || $pagenow == 'wp-signup.php') {
                    wp_enqueue_script('jquery');
                }

                if ( !is_multisite() ) {
                    add_action( 'register_form', array( $this, 'registration_form') );
                } else {
                    add_action( 'signup_extra_fields', array( $this, 'registration_form') );
                }

                add_action( 'register_post', array( $this, 'registration_check'), 0, 3 );

                $load_css = true;

            }

            // WP registration form
            if ( ! $admin && geodir_get_option( 'rc_wp_login' ) && ! defined( 'UWP_RECAPTCHA_VERSION' ) ) {
                add_action( 'login_form', array( $this, 'wp_login_form') );
                add_filter( 'login_form_middle', array( $this, 'wp_login_form_return') );
                add_filter( 'wp_authenticate_user', array( $this, 'wp_login_check'),20,2 );

            }

            // add listing form
            if ( geodir_get_option( 'rc_add_listing' ) ) {

                $post_info = NULL;
                if ( isset( $_REQUEST['pid'] ) && $_REQUEST['pid'] != '' ) {
                    $post_id = $_REQUEST['pid'];
                    $post_info = get_post( $post_id );
                }

                if ( empty( $post_info ) ) {
                    add_action( 'geodir_after_main_form_fields', array( $this ,'add_listing_form' ), 0 );
                    $load_css = true;
                }

                // validation
                add_filter('geodir_validate_ajax_save_post_data',array( $this, 'add_listing_check'),5,3);
            }

            // comments form
            if ( !$admin && geodir_get_option( 'rc_comments' ) ) {

                add_action( 'comment_form', array( $this,'comments_form'), 100 );
                add_action( 'pre_comment_on_post', array( $this, 'comments_check' ), 0, 1 );
                add_action( 'comment_on_trash', array( $this,'comments_check' ), 0, 1 );
                add_action( 'comment_on_draft', array( $this, 'comments_check' ), 0, 1 );
                add_action( 'comment_on_password_protected', array( $this, 'comments_check' ), 0, 1 );

                $load_css = true;
            }

            // buddypress registration form
            if ( !$admin && geodir_get_option( 'rc_buddypress' ) ) {

                add_action( 'bp_before_registration_submit_buttons', array( $this, 'bp_registration_form' ) );
                add_action( 'bp_signup_validate', array( $this, 'bp_registration_check' ) );

                $load_css = true;
            }

            // claim listing form
            if (defined( 'GEODIR_CLAIM_VERSION' ) && geodir_get_option( 'rc_claim_listing' ) ) {

                add_action( 'geodir_claim_post_form_after_fields', array( $this, 'claim_listing_form' ) );

                add_filter('geodir_validate_ajax_claim_listing_data',array( $this, 'claim_listing_form_check' ), 10, 2 );

                $load_css = true;
            }




            /**
             * Functions added to this hook will be executed after init.
             *
             * @since 1.0.0
             * @package GeoDirectory_ReCaptcha
             */
            do_action( 'recaptcha_init' );

        }

        /**
         * Check captcha for add listing form
         *
         * @since 2.0.0
         *
         * @param $valid
         * @param $post_data
         * @param bool $update
         *
         * @return string|void
         */
        public function claim_listing_form_check($valid, $post_data ) {

            if( !is_wp_error($valid) ) { // no point checking if its already invalid
                    $general_obj = new GD_Recaptcha_General();
                    $error = new WP_Error();
                    $captcha = $general_obj->recaptcha_check( 'claim_listing' ,$error  );
                    if($captcha) {
                        $valid = $captcha;
                    }
            }

            return $valid;

        }

        /**
         * Gd ReCaptcha for add listing form.
         *
         * @since 2.0.0
         */
        public function claim_listing_form() {

            $general_obj = new GD_Recaptcha_General();

            echo $general_obj->recaptcha_display( 'claim_listing', 'geodir_form_row clearfix' );

        }

        /**
         * Gd ReCaptcha for registration form.
         *
         * @since 2.0.0
         */
        public function registration_form() {

            $general_obj = new GD_Recaptcha_General();

            echo $general_obj->recaptcha_display( 'registration' );

        }

        /**
         * Gd Recaptcha for login form.
         *
         * @since 2.0.0
         */
        public function wp_login_form() {

            $general_obj = new GD_Recaptcha_General();

            echo $general_obj->recaptcha_display( 'login' );

        }

        /**
         * Gd Recaptcha for login form.
         *
         * @since 2.0.0
         */
        public function wp_login_form_return($fields) {

            $general_obj = new GD_Recaptcha_General();

            $output = $general_obj->recaptcha_display( 'login' );


//            echo '###'.$output;exit;

            $fields .= $output;

            return $fields;
        }

        /**
         * Check captcha for login form
         *
         * @since 2.0.0
         *
         * @param string $user Get login username.
         * @param string $password Get login password.
         * @return string $return_value
         */
        public function wp_login_check( $user, $password ) {

           $return_value = $user;

           if( !empty( $_POST['log'] )) {

               if( !empty( $user ) && !empty($password ) ) {

                   $general_obj = new GD_Recaptcha_General();
                   $error = new WP_Error();
                   $captcha = $general_obj->recaptcha_check( 'login' ,$error  );
                   if($captcha) {
                       $return_value = $captcha;
                   }

               }

           }

           return $return_value;

        }

        /**
         * Check captcha for registration.
         *
         * @since 2.0.0
         *
         * @param string $user_login Username.
         * @param string $user_email User email.
         * @param string $errors Registration errors.
         */
        public function registration_check( $user_login='', $user_email='', $errors='' ) {

            $general_obj = new GD_Recaptcha_General();

            $general_obj->recaptcha_check( 'registration', $errors );

        }

        /**
         * Check captcha for add listing form
         *
         * @since 2.0.0
         *
         * @param $valid
         * @param $post_data
         * @param bool $update
         *
         * @return string|void
         */
        public function add_listing_check($valid, $post_data, $update = false ) {

            if( $valid ) { // no point checking if its already invalid
                if( isset($post_data['post_title']) && isset($post_data['post_type']) && !$update ){

                    $general_obj = new GD_Recaptcha_General();
                    $error = new WP_Error();
                    $captcha = $general_obj->recaptcha_check( 'add_listing' ,$error  );
                    if($captcha) {
                        $valid = $captcha;
                    }
                }
            }

            return $valid;

        }

        /**
         * Gd ReCaptcha for add listing form.
         *
         * @since 2.0.0
         */
        public function add_listing_form() {

            $general_obj = new GD_Recaptcha_General();

            echo $general_obj->recaptcha_display( 'add_listing', 'geodir_form_row clearfix' );

        }

        /**
         * Gd ReCaptcha for comments form.
         *
         * @since 2.0.0
         */
        public function comments_form() {

            $general_obj = new GD_Recaptcha_General();

            echo $general_obj->recaptcha_display( 'comments' );

            if ( geodir_get_option( 'rc_client_version' ) != 'invisible' ) {
                ?>
                <script type="text/javascript">
                    jQuery(document).ready(function () {
                        var parentForm = jQuery('#gdcaptcha_comments').closest('form');
                        var findEle = jQuery(parentForm).find('.gd-captcha-comments');
                        jQuery(parentForm).find('.gd-captcha-comments').remove();
                        jQuery(parentForm).find(':submit').before(findEle);
                    });
                </script>
                <?php
            }
        }

        /**
         * check Gd ReCaptcha for comments.
         *
         * @since 2.0.0
         */
        public function comments_check() {

            if ( isset( $_POST['comment'] ) && trim( $_POST['comment'] ) != '' ) {

                $general_obj = new GD_Recaptcha_General();
                $general_obj->recaptcha_check( 'comments' );

            }

        }

        /**
         * Gd ReCaptcha BuddyPress registration form.
         *
         * @since 2.0.0
         *
         */
        public function bp_registration_form() {

            $general_obj = new GD_Recaptcha_General();

            echo $general_obj->recaptcha_display( 'bp_registration' );

        }

        /**
         * check captcha for BuddyPress registration.
         *
         * @since 2.0.0
         *
         * @param string $user_login Username.
         * @param string $user_email User email.
         * @param string $errors Registration errors.
         */
        public function bp_registration_check( $user_login='', $user_email='', $errors='' ) {

            $general_obj = new GD_Recaptcha_General();

            $general_obj->recaptcha_check( 'bp_registration', $errors );
        }

    }
    
}