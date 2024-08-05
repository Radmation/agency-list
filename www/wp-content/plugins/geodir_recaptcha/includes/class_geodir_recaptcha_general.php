<?php
// Check GD_Recaptcha_General class exists or not.
if( !class_exists( 'GD_Recaptcha_General' ) ) {
    
    /**
     * GD_Recaptcha_General Class for general actions.
     *
     * @since 2.0.0
     *
     * Class GD_Recaptcha_General
     */
    class GD_Recaptcha_General {
        
        /**
         * Constructor.
         *
         * @since 2.0.0
         *
         * GD_Recaptcha_General constructor.
         */
        public function __construct() {

        }

        /**
         * Get recaptcha site and secret key in selected version.
         *
         * @since 2.0.0
         *
         * @return array $recaptcha_keys
         */
        public function get_recatcha_site_and_secret_key() {

            $recaptcha_keys = array();
            $recaptcha_version = geodir_get_option('rc_client_version');

            if( 'invisible' === $recaptcha_version ) {
                $recaptcha_keys['catcha_site_key'] = geodir_get_option('rc_invisible_site_key');
                $recaptcha_keys['catcha_secret_key'] = geodir_get_option('rc_invisible_secret_key');
            } elseif ( 'v2' === $recaptcha_version ) {
                $recaptcha_keys['catcha_site_key'] = geodir_get_option('rc_v2_site_key');
                $recaptcha_keys['catcha_secret_key'] = geodir_get_option('rc_v2_secret_key');
            } elseif ( 'v3' === $recaptcha_version ) {
                $recaptcha_keys['catcha_site_key'] = geodir_get_option('rc_v3_site_key');
                $recaptcha_keys['catcha_secret_key'] = geodir_get_option('rc_v3_secret_key');
            }

            return $recaptcha_keys;

        }
        /**
         * Check current logged_in user role.
         *
         * @since 2.0.0
         *
         * @return bool
         */
        public function recaptcha_check_role() {

            if ( !is_user_logged_in() ) { // visitors
                return false;
            }

            global $current_user;

            $role = !empty( $current_user ) && isset( $current_user->roles[0] ) ? $current_user->roles[0] : '';

            if ( is_multisite() && is_super_admin( $current_user->ID ) ) {
                $role = 'administrator';
            }

            if ( $role != '' && (int)geodir_get_option( 'geodir_recaptcha_role_' . $role ) == 1 ) { // disable captcha
                return true;
            } else {
                return false;
            }

        }

        /**
         * Get gd recaptcha language.
         *
         * @since 2.0.0
         *
         * @param string $default get recaptcha language.
         *
         * @return string $language
         */
        public function recaptcha_language( $default = 'en' ) {
            $current_lang = get_locale();

            $current_lang = $current_lang != '' ? $current_lang : $default;

            $special_lang = array( 'zh-HK', 'zh-CN', 'zh-TW', 'en-GB', 'fr-CA', 'de-AT', 'de-CH', 'pt-BR', 'pt-PT', 'es-419' );

            if ( !in_array( $current_lang, $special_lang ) ) {
                $current_lang = substr( $current_lang, 0, 2 );
            }

            /**
             * Filters the recaptcha api language.
             *
             * @since 1.0.0
             * @package GeoDirectory_ReCaptcha
             */
            $language = apply_filters( 'geodir_recaptcha_api_language', $current_lang );

            return $language;
        }

        /**
         * Get gd recaptcha selected theme.
         *
         * @since 2.0.0
         *
         * @return string $theme
         */
        public function recaptcha_theme() {

            $theme = geodir_get_option( 'rc_theme', 'light' );

            /**
             * Filters the recaptcha theme.
             *
             * @since 1.0.0
             * @package GeoDirectory_ReCaptcha
             */
            $theme = apply_filters( 'geodir_recaptcha_captcha_theme', $theme );

            return $theme;
        }

        /**
         * Displays ReCaptcha form code..
         *
         * @since 2.0.0
         *
         * global array|null $geodir_recaptcha_loaded Check and store recaptcha content.
         *
         * @param string $form Get display form.
         * @param string $extra_class Get extra class for recaptcha.
         */
        public function recaptcha_display( $form, $extra_class='' ) {
            global $aui_bs5, $geodir_recaptcha_loaded;

            // Recaptcha already loaded.
            if ( ! empty( $geodir_recaptcha_loaded ) && ! empty( $geodir_recaptcha_loaded[ $form ] ) ) {
                return;
            }

            $site_keys_and_secret = $this->get_recatcha_site_and_secret_key();
            $site_key = !empty( $site_keys_and_secret['catcha_site_key'] ) ? $site_keys_and_secret['catcha_site_key'] :'';
            $secret_key = !empty( $site_keys_and_secret['catcha_secret_key'] ) ? $site_keys_and_secret['catcha_secret_key'] :'';
            $captcha_version = geodir_get_option( 'rc_client_version' );

			$design_style = geodir_design_style();
			$inline_css = '';
			if ( ( $form == 'login' || $form == 'registration' ) && $captcha_version != 'v3' ) {
				$inline_css = '#gd_recaptch_row{margin-bottom:1rem}';
			}

			$style_class = '';
			$label_class = '';
			$field_class = '';

			if ( $design_style ) {
				$style_class = $aui_bs5 ? ' mb-3' : ' form-group';
				if ( $form == 'add_listing' ) {
					$style_class .= ' row';
					$label_class = ' col-sm-2 col-form-label';
					$field_class = ' col-sm-10';
				}
			} else {
				$style_class = '';
				$label_class = '';
				$field_class = '';
			}

            ob_start();

            if ( strlen( $site_key ) > 10 && strlen( $secret_key ) > 10 ) {
                $captcha_title = geodir_get_option( 'rc_title' );
                $language = $this->recaptcha_language();
                $captcha_theme = $this->recaptcha_theme();
                /**
                 * Filters the recaptcha title.
                 *
                 * @since 1.0.0
                 * @package GeoDirectory_ReCaptcha
                 */
                $captcha_title = apply_filters( 'geodir_recaptcha_captcha_title', $captcha_title );
                $div_id = 'gdcaptcha_' . $form;
                if ( $captcha_title ) {
                    $captcha_title = __( $captcha_title, 'geodirectory' ) . ' <span class="text-danger">*</span>';
                }
                ?>
                <div id="gd_recaptch_row" class="required_field gd-fieldset-details gd-captcha gd-captcha-<?php echo $form;?> <?php echo $extra_class . $style_class; ?>">
                    <input type="hidden" field_type="recaptcha" name="gd_recaptcha_version" class="gd-recaptcha-hidden-response" id="gd_recaptcha_version" value="<?php echo $captcha_version; ?>">
                    <?php if( !empty( $captcha_version ) && 'v3' != $captcha_version ) { ?>
                        <label class="gd-captcha-title<?php echo $label_class; ?>" for="gd_recaptcha_invisible_value"><?php echo $captcha_title; ?></label>
                        <div id="<?php echo $div_id;?>" class="gd-captcha-render<?php echo $field_class; ?>"></div>
                        <?php /* ?><div class="g-recaptcha" data-sitekey="<?php echo $site_key; ?>"></div> <?php */ ?>
                        <?php if( !empty( $captcha_version ) && 'invisible' === $captcha_version ) { ?>
                        <input type="hidden" id="gd_recaptcha_invisible_value" name="gd_recaptcha_invisible_value" value="">
                        <?php } ?>
                        <span class="geodir_message_error gdcaptcha-err form-text text-danger col-sm-12 d-block"></span>
                        <script type="text/javascript" src="https://www.google.com/recaptcha/api.js?onload=<?php echo $div_id;?>&hl=<?php echo $language;?>&render=explicit" async defer></script>
                        <script type="text/javascript">
                            <?php if( !empty( $captcha_version ) && 'invisible' != $captcha_version ) { ?>
                                var gdCaptchaSize = (jQuery( document ).width() < 1200) ? 'compact' : 'normal';
                            <?php } ?>
                            try {
                                var <?php echo $div_id;?> = function() {
                                    <?php if( !empty($captcha_version) && 'invisible' === $captcha_version ) { ?>
                                        for ( var i = 0; i < document.forms.length; ++i ) {
                                            var form = document.forms[i];
                                            var holder = form.querySelector('.gd-captcha-render');
                                            if (null === holder) {continue;}
                                            (function(frm) {
                                                if ( !jQuery(holder).html() ) {
                                                    var holderId = grecaptcha.render(holder, {
                                                        'sitekey': '<?php echo $site_key;?>',
                                                        'size': 'invisible',
                                                        'badge': 'inline',
                                                        'theme' : '<?php echo $captcha_theme;?>',
                                                        'callback': function (recaptchaToken) {
                                                         <?php if ( $form == 'add_listing') { ?>
                                                            jQuery('#geodirectory-add-post').trigger('submit');
                                                        <?php } else if ( $form == 'claim_listing' ) { ?>
                                                            if(!jQuery('.geodir-post-claim-form').hasClass('geodir-recaptcha-submit')){jQuery('.geodir-post-claim-form').addClass('geodir-recaptcha-submit');jQuery('.geodir-post-claim-form').trigger('submit');}
                                                        <?php } else { ?>
                                                            HTMLFormElement.prototype.submit.call(frm);
                                                            <?php }?>
                                                        }
                                                    });
                                                    frm.onsubmit = function (evt) {
                                                        console.log("NO AJAX else: <?php echo $div_id;?>  / holder ID: " + holderId);
                                                        evt.preventDefault();
                                                        <?php if ($form == 'add_listing'){ ?>
                                                        if(jQuery('#geodirectory-add-post #g-recaptcha-response').val()==''){
                                                            grecaptcha.execute(holderId);
                                                        }
                                                        <?php } else { ?>
                                                        grecaptcha.execute(holderId);
                                                        <?php } ?>
                                                    };
                                                }
                                            })(form);
                                        }
                                    <?php } else { ?>
                                        if ( ( typeof jQuery != 'undefined' && !jQuery('#<?php echo $div_id;?>').html() ) || '<?php echo $form; ?>'=='registration' ) {
                                            grecaptcha.render('<?php echo $div_id;?>', { 'sitekey' : '<?php echo $site_key;?>', 'theme' : '<?php echo $captcha_theme;?>', 'size' : gdCaptchaSize });
                                        }
                                    <?php } ?>
                                }
                            } catch(err) {
                                console.log(err);
                            }

                            if ( typeof grecaptcha != 'undefined' && grecaptcha ) {
                                setTimeout(function(){
                                    try {
                                        <?php echo $div_id;?>();
                                    } catch(err) {
                                        console.log(err.message);
                                    }
                                },1000);
                            }
                        </script>
                    <?php } else { ?>
                        <input type="hidden" id="gd_recaptcha_v3_value" name="g-recaptcha-response" class="g-recaptcha-response" value="">
                        <script type="text/javascript">
                            jQuery(function($) {
                                if (typeof grecaptcha == 'undefined') {
                                    jQuery.getScript('https://www.google.com/recaptcha/api.js?hl=<?php echo $language;?>&render=<?php echo $site_key;?>').done(function(script, textStatus) {
                                        grecaptcha.ready(function() {
                                            grecaptcha.execute('<?php echo $site_key;?>', {action: '<?php echo esc_attr($form);?>'}).then(function(token) { jQuery('.g-recaptcha-response').attr('value',token);});
                                            setInterval(function(){ grecaptcha.execute('<?php echo $site_key;?>', {action: '<?php echo esc_attr($form);?>'}).then(function(token) { jQuery('.g-recaptcha-response').attr('value',token);}); }, 110000);
                                        });
                                    }).fail(function (jqxhr, settings, exception) {
                                        console.log(exception);
                                    });
                                } else {
                                    grecaptcha.ready(function() {
                                        grecaptcha.execute('<?php echo $site_key;?>', {action: '<?php echo esc_attr($form);?>'}).then(function(token) { jQuery('.g-recaptcha-response').attr('value',token);});
                                        setInterval(function(){ grecaptcha.execute('<?php echo $site_key;?>', {action: '<?php echo esc_attr($form);?>'}).then(function(token) { jQuery('.g-recaptcha-response').attr('value',token);}); }, 110000);
                                    });
                                }
                            });
                        </script>
                    <?php } ?>
                </div>
                <?php
				if ( $inline_css ) {
					echo '<style>' . $inline_css . '</style>';
				}
            } else {
                $plugin_settings_link = admin_url( '/admin.php?page=gd-settings&tab=gd-recaptcha' );
                ?>
                <div class="gd-captcha gd-captcha-<?php echo $form; ?><?php echo $style_class; ?>">
                    <div class="gd-captcha-err"><?php echo sprintf( __( 'To use reCAPTCHA you must get an API key from  <a target="_blank" href="https://www.google.com/recaptcha/admin">here</a> and enter keys in the plugin settings page at <a target="_blank" href="%s">here</a>','geodir-recaptcha' ), $plugin_settings_link ); ?></div>
                </div>
                <?php
            }

            $content = ob_get_clean();

            $geodir_recaptcha_loaded[ $form ] = $content;

            return $content;
        }

        /**
         * Verify gd ReCaptcha.
         *
         * @since 2.0.0
         *
         * @param string $form Get the form name.
         *
         * @param string $errors Get the form errors.
         */
        public function recaptcha_check( $form = '', $errors='' ) {

            global $pagenow;

            $site_keys_and_secret = $this->get_recatcha_site_and_secret_key();

            $site_key = !empty( $site_keys_and_secret['catcha_site_key'] ) ? $site_keys_and_secret['catcha_site_key'] :'';

            $secret_key = !empty( $site_keys_and_secret['catcha_secret_key'] ) ? $site_keys_and_secret['catcha_secret_key'] :'';

            // Don't check captcha on WP registration if option is unchecked
            if ( $pagenow == 'wp-login.php' && $form == 'registration' && ( ! geodir_get_option( 'rc_wp_registration' ) || defined( 'UWP_RECAPTCHA_VERSION' ) ) ) {
                return;
            }

            // Don't check captcha on WP Login if option is unchecked
            if ( $pagenow != 'wp-login.php' && $form == 'login' && ( ! geodir_get_option( 'rc_wp_login' ) || defined( 'UWP_RECAPTCHA_VERSION' ) ) ) {
                return;
            }

            if ( !( strlen( $site_key ) > 10 && strlen( $secret_key ) > 10 ) ) {
                return;
            }

            if ( !class_exists( 'ReCaptcha' ) ) {
                require_once( GD_RECAPTCHA_PLUGIN_DIR_PATH . '/lib/recaptchalib.php' );
            }

            $reCaptcha = new ReCaptchalib\ReCaptcha\ReCaptcha( $secret_key );

            $recaptcha_value = isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : '';
            $response = $reCaptcha->verifyResponse( $_SERVER['REMOTE_ADDR'], $recaptcha_value );
            $valid_captcha = $response->isSuccess();
            $force_fail = geodir_get_option('rc_force_fail',false);

            if (!$force_fail && $valid_captcha ) {
                return;
            } else {
                if ( $form == 'bp_registration' ) {
                    global $bp;
                    $bp->signup->errors['gd_recaptcha_field'] = __( 'You have entered an incorrect CAPTCHA value.', 'geodir-recaptcha' );
                    return;
                } else {
                    if ( !empty( $errors ) && is_object( $errors ) ) {
                        $errors->add( 'invalid_captcha', __( '<p><strong>ERROR</strong>: You have entered an incorrect CAPTCHA value.</p>', 'geodir-recaptcha' ) );
                    } else {
                        wp_die( __( '<p><strong>ERROR</strong>: You have entered an incorrect CAPTCHA value. Click the BACK button in your browser and try again.</p>', 'geodir-recaptcha' ) );
                    }
                }
            }

            return $errors;
        }
    }
}