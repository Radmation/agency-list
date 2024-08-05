<?php
/**
 * Pricing Manager Email class.
 *
 * @since 2.5.0
 * @package GeoDir_Pricing_Manager
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDir_Pricing_Email class.
 */
class GeoDir_Pricing_Email {

	public static function init() {
		if ( is_admin() ) {
			add_filter( 'geodir_email_settings', array( __CLASS__, 'filter_email_settings' ), 10, 1 );
			add_filter( 'geodir_admin_email_settings', array( __CLASS__, 'filter_admin_email_settings' ), 10, 1 );
			add_filter( 'geodir_user_email_settings', array( __CLASS__, 'filter_user_email_settings' ), 10, 1 );
		}

		add_filter( 'geodir_email_subject', array( __CLASS__, 'get_subject' ), 10, 3 );
		add_filter( 'geodir_email_content', array( __CLASS__, 'get_content' ), 10, 3 );
		add_filter( 'geodir_email_wild_cards', array( __CLASS__, 'set_wild_cards' ), 10, 4 );
		add_action( 'geodir_pricing_post_user_pre_expiry_reminder_email', array( __CLASS__, 'pre_expiry_reminder_sent' ), 10, 1 );
		add_action( 'post_updated', array( __CLASS__, 'on_post_updated' ), 100, 3 );
		add_action( 'geodir_pricing_post_downgraded', array( __CLASS__, 'on_post_downgraded' ), 100, 3 );
	}

	public static function filter_email_settings( $settings ) {
		if ( $merge_settings = self::bcc_email_settings() ) {
			$position = count( $settings ) - 1;
			$settings = array_merge( array_slice( $settings, 0, $position ), $merge_settings, array_slice( $settings, $position ) );
		}

		return $settings;
	}

	public static function filter_admin_email_settings( $settings ) {
		if ( $merge_settings = self::admin_email_settings() ) {
			$position = count( $settings );
			$settings = array_merge( array_slice( $settings, 0, $position ), $merge_settings, array_slice( $settings, $position ) );
		}

		return $settings;
	}

	public static function filter_user_email_settings( $settings ) {
		if ( $merge_settings = self::user_email_settings() ) {
			$position = count( $settings );
			$settings = array_merge( array_slice( $settings, 0, $position ), $merge_settings, array_slice( $settings, $position ) );
		}

		return $settings;
	}

	public static function bcc_email_settings() {
		$settings = array(
			array(
				'type' => 'checkbox',
				'id' => 'email_bcc_user_pre_expiry_reminder',
				'name' => __( 'Pre expiry reminders', 'geodir_pricing' ),
				'desc' => __( 'This will send a BCC email to the site admin on listing pre expiry reminders sent.', 'geodir_pricing' ),
				'default' => 0,
				'advanced' => false
			),
			array(
				'type' => 'checkbox',
				'id' => 'email_bcc_user_renew_success',
				'name' => __( 'Listing renewed', 'geodir_pricing' ),
				'desc' => __( 'This will send a BCC email to the site admin on listing renewed.', 'geodir_pricing' ),
				'default' => 0,
				'advanced' => false
			),
			array(
				'type' => 'checkbox',
				'id' => 'email_bcc_user_upgrade_success',
				'name' => __( 'Listing upgraded', 'geodir_pricing' ),
				'desc' => __( 'This will send a BCC email to the site admin on listing upgraded.', 'geodir_pricing' ),
				'default' => 0,
				'advanced' => false
			),
			array(
				'type' => 'checkbox',
				'id' => 'email_bcc_user_post_downgrade',
				'name' => __( 'Listing package downgraded', 'geodir_pricing' ),
				'desc' => __( 'This will send a BCC email to the site admin on listing package downgraded.', 'geodir_pricing' ),
				'default' => 0,
				'advanced' => false
			),
			array(
				'type' => 'checkbox',
				'id' => 'email_bcc_user_post_expire',
				'name' => __( 'Listing expired', 'geodir_pricing' ),
				'desc' => __( 'This will send a BCC email to the site admin on listing expired.', 'geodir_pricing' ),
				'default' => 0,
				'advanced' => false
			)
		);

		return apply_filters( 'geodir_pricing_bcc_email_settings', $settings );
	}

	public static function admin_email_settings() {
		$settings = array(
			// Pre expiry post reminder to admin.
			array(
				'type' => 'title',
				'id' => 'email_admin_renew_success_settings',
				'name' => __( 'Post renew success email to admin', 'geodir_pricing' ),
				'desc' => '',
			),
			array(
				'type' => 'checkbox',
				'id' => 'email_admin_renew_success',
				'name' => __( 'Enable email', 'geodir_pricing' ),
				'desc' => __( 'Send an email to admin after successfull post renewal.', 'geodir_pricing' ),
				'default' => 1,
			),
			array(
				'type' => 'text',
				'id' => 'email_admin_renew_success_subject',
				'name' => __( 'Subject', 'geodir_pricing' ),
				'desc' => __( 'The email subject.', 'geodir_pricing' ),
				'class' => 'active-placeholder',
				'desc_tip' => true,
				'placeholder' => GeoDir_Pricing_Email::email_admin_renew_success_subject(),
				'advanced' => true
			),
			array(
				'type' => 'textarea',
				'id' => 'email_admin_renew_success_body',
				'name' => __( 'Body', 'geodir_pricing' ),
				'desc' => __( 'The email body, this can be text or HTML.', 'geodir_pricing' ),
				'class' => 'code gd-email-body',
				'desc_tip' => true,
				'advanced' => true,
				'placeholder' => GeoDir_Pricing_Email::email_admin_renew_success_body(),
				'custom_desc' => __( 'Available template tags:', 'geodir_pricing' ) . ' ' . GeoDir_Pricing_Email::admin_renew_success_email_tags()
			),
			array(
				'type' => 'sectionend',
				'id' => 'email_admin_renew_success_settings'
			),

			// Post upgrade success email to admin.
			array(
				'type' => 'title',
				'id' => 'email_admin_upgrade_success_settings',
				'name' => __( 'Post upgrade success email to admin', 'geodir_pricing' ),
				'desc' => '',
			),
			array(
				'type' => 'checkbox',
				'id' => 'email_admin_upgrade_success',
				'name' => __( 'Enable email', 'geodir_pricing' ),
				'desc' => __( 'Send an email to admin after successfull post upgrade.', 'geodir_pricing' ),
				'default' => 1,
			),
			array(
				'type' => 'text',
				'id' => 'email_admin_upgrade_success_subject',
				'name' => __( 'Subject', 'geodir_pricing' ),
				'desc' => __( 'The email subject.', 'geodir_pricing' ),
				'class' => 'active-placeholder',
				'desc_tip' => true,
				'placeholder' => GeoDir_Pricing_Email::email_admin_upgrade_success_subject(),
				'advanced' => true
			),
			array(
				'type' => 'textarea',
				'id' => 'email_admin_upgrade_success_body',
				'name' => __( 'Body', 'geodir_pricing' ),
				'desc' => __( 'The email body, this can be text or HTML.', 'geodir_pricing' ),
				'class' => 'code gd-email-body',
				'desc_tip' => true,
				'advanced' => true,
				'placeholder' => GeoDir_Pricing_Email::email_admin_upgrade_success_body(),
				'custom_desc' => __( 'Available template tags:', 'geodir_pricing' ) . ' ' . GeoDir_Pricing_Email::admin_upgrade_success_email_tags()
			),
			array(
				'type' => 'sectionend',
				'id' => 'email_admin_upgrade_success_settings'
			)
		);

		return apply_filters( 'geodir_pricing_admin_email_settings', $settings );
	}

	public static function user_email_settings() {
		$settings = array(
			// Pre expiry post reminder to user.
			array(
				'type' => 'title',
				'id' => 'email_user_pre_expiry_reminder_settings',
				'name' => __( 'Pre expiry reminders to user', 'geodir_pricing' ),
				'desc' => '',
			),
			array(
				'type' => 'checkbox',
				'id' => 'email_user_pre_expiry_reminder',
				'name' => __( 'Enable email', 'geodir_pricing' ),
				'desc' => __( 'Send pre expiry reminders email to user.', 'geodir_pricing' ),
				'default' => '0',
			),
			array(
				'type' => 'multicheckbox',
				'id' => 'email_user_pre_expiry_reminder_days',
				'name' => __( 'When to Send', 'geodir_pricing' ),
				'desc' => __( 'Check when you would like pre expiry reminders sent out.', 'geodir_pricing' ),
				'default' => '',
				'options' => geodir_pricing_pre_expiry_day_options(),
				'desc_tip' => true,
				'advanced' => true
			),
			array(
				'type' => 'text',
				'id' => 'email_user_pre_expiry_reminder_subject',
				'name' => __( 'Subject', 'geodir_pricing' ),
				'desc' => __( 'The email subject.', 'geodir_pricing' ),
				'class' => 'active-placeholder',
				'desc_tip' => true,
				'placeholder' => GeoDir_Pricing_Email::email_user_pre_expiry_reminder_subject(),
				'advanced' => true
			),
			array(
				'type' => 'textarea',
				'id' => 'email_user_pre_expiry_reminder_body',
				'name' => __( 'Body', 'geodir_pricing' ),
				'desc' => __( 'The email body, this can be text or HTML.', 'geodir_pricing' ),
				'class' => 'code gd-email-body',
				'desc_tip' => true,
				'advanced' => true,
				'placeholder' => GeoDir_Pricing_Email::email_user_pre_expiry_reminder_body(),
				'custom_desc' => __( 'Available template tags:', 'geodir_pricing' ) . ' ' . GeoDir_Pricing_Email::user_pre_expiry_reminder_email_tags( true )
			),
			array(
				'type' => 'sectionend',
				'id' => 'email_user_pre_expiry_reminder_settings'
			),

			// Post renew success email to user.
			array(
				'type' => 'title',
				'id' => 'email_user_renew_success_settings',
				'name' => __( 'Post renew success email to user', 'geodir_pricing' ),
				'desc' => '',
			),
			array(
				'type' => 'checkbox',
				'id' => 'email_user_renew_success',
				'name' => __( 'Enable email', 'geodir_pricing' ),
				'desc' => __( 'Send an email to user after successfull post renewal.', 'geodir_pricing' ),
				'default' => 1,
			),
			array(
				'type' => 'text',
				'id' => 'email_user_renew_success_subject',
				'name' => __( 'Subject', 'geodir_pricing' ),
				'desc' => __( 'The email subject.', 'geodir_pricing' ),
				'class' => 'active-placeholder',
				'desc_tip' => true,
				'placeholder' => GeoDir_Pricing_Email::email_user_renew_success_subject(),
				'advanced' => true
			),
			array(
				'type' => 'textarea',
				'id' => 'email_user_renew_success_body',
				'name' => __( 'Body', 'geodir_pricing' ),
				'desc' => __( 'The email body, this can be text or HTML.', 'geodir_pricing' ),
				'class' => 'code gd-email-body',
				'desc_tip' => true,
				'advanced' => true,
				'placeholder' => GeoDir_Pricing_Email::email_user_renew_success_body(),
				'custom_desc' => __( 'Available template tags:', 'geodir_pricing' ) . ' ' . GeoDir_Pricing_Email::user_renew_success_email_tags()
			),
			array(
				'type' => 'sectionend',
				'id' => 'email_user_renew_success_settings'
			),

			// Post upgrade success email to user.
			array(
				'type' => 'title',
				'id' => 'email_user_upgrade_success_settings',
				'name' => __( 'Post upgrade success email to user', 'geodir_pricing' ),
				'desc' => '',
			),
			array(
				'type' => 'checkbox',
				'id' => 'email_user_upgrade_success',
				'name' => __( 'Enable email', 'geodir_pricing' ),
				'desc' => __( 'Send an email to user after successfull post upgrade.', 'geodir_pricing' ),
				'default' => 1,
			),
			array(
				'type' => 'text',
				'id' => 'email_user_upgrade_success_subject',
				'name' => __( 'Subject', 'geodir_pricing' ),
				'desc' => __( 'The email subject.', 'geodir_pricing' ),
				'class' => 'active-placeholder',
				'desc_tip' => true,
				'placeholder' => GeoDir_Pricing_Email::email_user_upgrade_success_subject(),
				'advanced' => true
			),
			array(
				'type' => 'textarea',
				'id' => 'email_user_upgrade_success_body',
				'name' => __( 'Body', 'geodir_pricing' ),
				'desc' => __( 'The email body, this can be text or HTML.', 'geodir_pricing' ),
				'class' => 'code gd-email-body',
				'desc_tip' => true,
				'advanced' => true,
				'placeholder' => GeoDir_Pricing_Email::email_user_upgrade_success_body(),
				'custom_desc' => __( 'Available template tags:', 'geodir_pricing' ) . ' ' . GeoDir_Pricing_Email::user_upgrade_success_email_tags()
			),
			array(
				'type' => 'sectionend',
				'id' => 'email_user_upgrade_success_settings'
			),

			// Post package downgrade email to user.
			array(
				'type' => 'title',
				'id' => 'email_user_post_downgrade_settings',
				'name' => __( 'Post package downgraded email to user', 'geodir_pricing' ),
				'desc' => '',
			),
			array(
				'type' => 'checkbox',
				'id' => 'email_user_post_downgrade',
				'name' => __( 'Enable email', 'geodir_pricing' ),
				'desc' => __( 'Send an email to user after post package downgrade to another package.', 'geodir_pricing' ),
				'default' => 1,
			),
			array(
				'type' => 'text',
				'id' => 'email_user_post_downgrade_subject',
				'name' => __( 'Subject', 'geodir_pricing' ),
				'desc' => __( 'The email subject.', 'geodir_pricing' ),
				'class' => 'active-placeholder',
				'desc_tip' => true,
				'placeholder' => GeoDir_Pricing_Email::email_user_post_downgrade_subject(),
				'advanced' => true
			),
			array(
				'type' => 'textarea',
				'id' => 'email_user_post_downgrade_body',
				'name' => __( 'Body', 'geodir_pricing' ),
				'desc' => __( 'The email body, this can be text or HTML.', 'geodir_pricing' ),
				'class' => 'code gd-email-body',
				'desc_tip' => true,
				'advanced' => true,
				'placeholder' => GeoDir_Pricing_Email::email_user_post_downgrade_body(),
				'custom_desc' => __( 'Available template tags:', 'geodir_pricing' ) . ' ' . GeoDir_Pricing_Email::user_post_downgrade_email_tags()
			),
			array(
				'type' => 'sectionend',
				'id' => 'email_user_post_downgrade_settings'
			),

			// Post expired email to user.
			array(
				'type' => 'title',
				'id' => 'email_user_post_expire_settings',
				'name' => __( 'Post expired email to user', 'geodir_pricing' ),
				'desc' => '',
			),
			array(
				'type' => 'checkbox',
				'id' => 'email_user_post_expire',
				'name' => __( 'Enable email', 'geodir_pricing' ),
				'desc' => __( 'Send an email to user after post has been marked as expired.', 'geodir_pricing' ),
				'default' => 1,
			),
			array(
				'type' => 'text',
				'id' => 'email_user_post_expire_subject',
				'name' => __( 'Subject', 'geodir_pricing' ),
				'desc' => __( 'The email subject.', 'geodir_pricing' ),
				'class' => 'active-placeholder',
				'desc_tip' => true,
				'placeholder' => GeoDir_Pricing_Email::email_user_post_expire_subject(),
				'advanced' => true
			),
			array(
				'type' => 'textarea',
				'id' => 'email_user_post_expire_body',
				'name' => __( 'Body', 'geodir_pricing' ),
				'desc' => __( 'The email body, this can be text or HTML.', 'geodir_pricing' ),
				'class' => 'code gd-email-body',
				'desc_tip' => true,
				'advanced' => true,
				'placeholder' => GeoDir_Pricing_Email::email_user_post_expire_body(),
				'custom_desc' => __( 'Available template tags:', 'geodir_pricing' ) . ' ' . GeoDir_Pricing_Email::user_post_expire_email_tags()
			),
			array(
				'type' => 'sectionend',
				'id' => 'email_user_post_expire_settings'
			)
		);

		return apply_filters( 'geodir_pricing_user_email_settings', $settings );
	}

	public static function on_post_updated( $post_ID, $post_after, $post_before ) {
		if ( ! empty( $post_after->post_type ) && geodir_is_gd_post_type( $post_after->post_type ) ) {
			// Send post expire email
			if ( $post_after->post_status == 'gd-expired' && $post_before->post_status == 'publish' ) {
				$gd_post = geodir_get_post_info( $post_ID );

				if ( ! empty( $gd_post ) ) {
					self::send_user_post_expire_email( $gd_post );
				}
			}
		}
	}

	public static function on_post_downgraded( $gd_post, $downgrade_to, $package ) {
		if ( ! empty( $gd_post ) && ! empty( $downgrade_to ) && ! empty( $package ) ) {
			self::send_user_post_downgrade_email( $gd_post, array( 'package' => $downgrade_to, 'previous_package' => $package ) );
		}
	}

	public static function get_subject( $subject, $email_name = '', $email_vars = array() ) {
		if ( ! empty( $subject ) ) {
			return $subject;
		}

		$method = 'email_' . $email_name . '_subject';
		if (  method_exists( __CLASS__, $method ) ) {
			$subject = self::$method();
		}

		if ( $subject ) {
			$subject = GeoDir_Email::replace_variables( __( $subject, 'geodirectory' ), $email_name, $email_vars );
		}

		return $subject;
	}

	public static function get_content( $content, $email_name = '', $email_vars = array() ) {
		if ( ! empty( $content ) ) {
			return $content;
		}

		$method = 'email_' . $email_name . '_body';
		if (  method_exists( __CLASS__, $method ) ) {
			$content = self::$method();
		}

		if ( $content ) {
			$content = GeoDir_Email::replace_variables( __( $content, 'geodirectory' ), $email_name, $email_vars );
		}

		return $content;
	}

	public static function set_wild_cards( $wild_cards, $content, $email_name, $email_vars = array() ) {
		switch ( $email_name ) {
			case 'admin_renew_success':
			case 'user_renew_success':
			case 'admin_upgrade_success':
			case 'user_upgrade_success':
			case 'user_pre_expiry_reminder':
			case 'user_post_expire':
			case 'user_post_downgrade':
				$params = array();

				if ( ! empty( $email_vars['post'] ) ) {
					$gd_post = $email_vars['post'];

					$author_data = get_userdata( $gd_post->post_author );
					$package_id = geodir_get_post_meta( $gd_post->ID, 'package_id', true );
					$expire_date = geodir_get_post_meta( $gd_post->ID, 'expire_date', true );
					$alive_days = (int) geodir_pricing_package_alive_days( $package_id );
					$has_renew = geodir_pricing_post_renew_link( $gd_post->ID );
					$has_upgrade = geodir_pricing_post_upgrade_link( $gd_post->ID );

					$params['username'] = $author_data->user_login;
					$params['user_email'] = $author_data->user_email;
					$params['post_package_name'] = geodir_pricing_package_name( $package_id );
					$params['package_lifetime_days'] = $alive_days > 0 ? $alive_days : __( 'Unlimited', 'geodir_pricing' );
					$params['number_of_days'] = $params['package_lifetime_days'];
					$params['renew_url'] = $has_renew ? geodir_pricing_post_renew_url( $gd_post->ID ) : '';
					$params['upgrade_url'] = $has_upgrade ? geodir_pricing_post_upgrade_url( $gd_post->ID ) : '';
					$params['renew_link'] = $params['renew_url'] ? '<a href="' . esc_url( $params['renew_url'] ) . '">' . __( 'Renew', 'geodir_pricing' ) . '</a>' : '';
					$params['upgrade_link'] = $params['upgrade_url'] ? '<a href="' . esc_url( $params['upgrade_url'] ) . '">' . __( 'Upgrade', 'geodir_pricing' ) . '</a>' : '';

					if ( $expire_date ) {
						$params['expire_date'] = $expire_date;

						if ( geodir_pricing_date_never_expire( $expire_date ) ) {
							$params['display_expire_date'] = __( 'Never', 'geodir_pricing' );
							$params['number_of_grace_days'] = __( 'Unlimited', 'geodir_pricing' );
						} else {
							$current_date = date_i18n( 'Y-m-d' );

							if ( strtotime( $expire_date ) < strtotime( $current_date ) ) {
								$params['number_of_grace_days'] = 0;
							} else {
								$params['number_of_grace_days'] = round( abs( strtotime( $expire_date ) - strtotime( $current_date ) ) / 86400 );
							}
							$params['display_expire_date'] = date_i18n( geodir_date_format(), strtotime( $expire_date ) );
						}
					} else {
						$params['expire_date'] = 'never';
						$params['display_expire_date'] = __( 'Never', 'geodir_pricing' );
						$params['number_of_grace_days'] = __( 'Unlimited', 'geodir_pricing' );
					}
				}

				if ( ! empty( $email_vars['package'] ) ) {
					$params['package_name'] = geodir_pricing_package_name( $email_vars['package'] );
				}

				if ( ! empty( $email_vars['previous_package'] ) ) {
					$params['previous_post_package_name'] = geodir_pricing_package_name( $email_vars['previous_package'] );
				}

				$defaults = array(
					'expire_date' => '',
					'display_expire_date' => '',
					'package_lifetime_days' => '',
					'number_of_grace_days' => '',
					'transaction_details' => '',
					'username' => '',
					'user_email' => '',
					'package_name' => '',
					'post_package_name' => '',
					'previous_post_package_name' => '',
					'number_of_days' => '',
					'renew_url' => '',
					'renew_link' => '',
					'upgrade_url' => '',
					'upgrade_link' => '',
				);
				$params = wp_parse_args( $params, $defaults );

				foreach ( $params as $key => $value ) {
					if ( ! isset( $email_vars[ '[#' . $key . '#]' ] ) ) {
						$wild_cards[ '[#' . $key . '#]' ] = $value;
					}
				}
			break;
		}
		return $wild_cards;
	}

	/**
	 * Pricing email tags.
	 *
	 * @since  2.5.0.0
	 *
	 * @param bool $inline Optional. Email tag inline value. Default true.
	 * @return array|string $tags.
	 */
	public static function pricing_email_tags( $inline = true ) { 
		$tags = array();
		
		$tags = apply_filters( 'geodir_pricing_email_tags', $tags );

		if ( $inline ) {
			$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		}
		
		return $tags;
	}

	/**
	 * Global email tags.
	 *
	 * @since  2.0.0
	 *
	 * @param bool $inline Optional. Email tag inline value. Default true.
	 * @return array|string $tags.
	 */
	public static function global_email_tags( $inline = true ) { 
		$tags = array( '[#blogname#]', '[#site_name#]', '[#site_url#]', '[#site_name_url#]', '[#login_url#]', '[#login_link#]', '[#date#]', '[#time#]', '[#date_time#]', '[#current_date#]', '[#to_name#]', '[#to_email#]', '[#from_name#]', '[#from_email#]' );
		
		$tags = apply_filters( 'geodir_email_global_email_tags', $tags );

		if ( $inline ) {
			$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		}
		
		return $tags;
	}

	/**
	 * Email tags for pricing emails.
	 *
	 * @since  2.5.0.0
	 *
	 * @param bool $inline Optional. Email tag inline value. Default true.
	 * @return array|string $tags.
	 */
	public static function email_tags( $inline = true ) { 
		$email_tags = self::global_email_tags( false );
		$pricing_email_tags = self::pricing_email_tags( false );

		if ( ! empty( $pricing_email_tags ) ) {
			$email_tags = array_merge( $email_tags, $pricing_email_tags );
		}

		if ( $inline ) {
			$email_tags = '<code>' . implode( '</code> <code>', $email_tags ) . '</code>';
		}
		
		return $email_tags;
	}

	public static function user_renew_success_email_tags( $inline = true ) { 
		$email_tags = self::email_tags( false );

		$tags = array_merge( $email_tags, array( '[#transaction_details#]' ) );

		$tags = apply_filters( 'geodir_pricing_email_user_renew_success_email_tags', $tags );

		if ( $inline ) {
			$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		}

		return $tags;
	}

	public static function admin_renew_success_email_tags( $inline = true ) { 
		$email_tags = self::email_tags( false );

		$tags = array_merge( $email_tags, array( '[#transaction_details#]' ) );

		$tags = apply_filters( 'geodir_pricing_email_admin_renew_success_email_tags', $tags );

		if ( $inline ) {
			$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		}

		return $tags;
	}

	public static function user_upgrade_success_email_tags( $inline = true ) { 
		$email_tags = self::email_tags( false );

		$tags = array_merge( $email_tags, array( '[#transaction_details#]' ) );

		$tags = apply_filters( 'geodir_pricing_email_user_upgrade_success_email_tags', $tags );

		if ( $inline ) {
			$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		}

		return $tags;
	}

	public static function admin_upgrade_success_email_tags( $inline = true ) { 
		$email_tags = self::email_tags( false );

		$tags = array_merge( $email_tags, array( '[#transaction_details#]' ) );

		$tags = apply_filters( 'geodir_pricing_email_admin_upgrade_success_email_tags', $tags );

		if ( $inline ) {
			$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		}

		return $tags;
	}

	public static function user_pre_expiry_reminder_email_tags( $inline = true ) { 
		$email_tags = self::email_tags( false );

		$tags = array_merge( $email_tags, array( '[#package_lifetime_days#]', '[#number_of_grace_days#]', '[#expire_date#]', '[#display_expire_date#]', '[#renew_url#]', '[#renew_link#]', '[#upgrade_url#]', '[#upgrade_link#]' ) );

		$tags = apply_filters( 'geodir_pricing_email_user_pre_expiry_reminder_email_tags', $tags );

		if ( $inline ) {
			$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		}

		return $tags;
	}

	public static function email_user_renew_success_subject() {
		$subject = __( '[[#site_name#]] Your listing has been renewed', 'geodir_pricing' );

		return apply_filters( 'geodir_pricing_email_user_renew_success_subject', $subject );
	}

	public static function email_user_renew_success_body() {
		$body = "" . 
__( "Dear [#client_name#],

Your listing [#listing_link#] has been renewed.

NOTE: If your listing is not active yet your payment may be being checked by an admin and it will be activated shortly.

Thank You.", "geodir_pricing" );

		return apply_filters( 'geodir_pricing_email_user_renew_success_body', $body );
	}

	public static function email_user_upgrade_success_subject() {
		$subject = __( '[[#site_name#]] Your listing has been upgraded', 'geodir_pricing' );

		return apply_filters( 'geodir_pricing_email_user_upgrade_success_subject', $subject );
	}

	public static function email_user_upgrade_success_body() {
		$body = "" . 
__( "Dear [#client_name#],

Your listing [#listing_link#] has been upgraded.

NOTE: If your listing is not active yet your payment may be being checked by an admin and it will be activated shortly.

Thank You.", "geodir_pricing" );

		return apply_filters( 'geodir_pricing_email_user_upgrade_success_body', $body );
	}

	public static function email_admin_renew_success_subject() {
		$subject = __( '[[#site_name#]] The listing has been renewed', 'geodir_pricing' );

		return apply_filters( 'geodir_pricing_email_user_admin_success_subject', $subject );
	}

	public static function email_admin_renew_success_body() {
		$body = "" . 
__( "Dear Admin,

The listing [#listing_link#] has been renewed.

Please confirm payment and then update the listings published date to todays date.

NOTE: If payment was made by live gateway site then the published date should be updated automatically.

Thank You.", "geodir_pricing" );

		return apply_filters( 'geodir_pricing_email_admin_renew_success_body', $body );
	}

	public static function email_admin_upgrade_success_subject() {
		$subject = __( '[[#site_name#]] The listing has been upgraded', 'geodir_pricing' );

		return apply_filters( 'geodir_pricing_email_admin_upgrade_success_subject', $subject );
	}

	public static function email_admin_upgrade_success_body() {
		$body = "" . 
__( "Dear Admin,

The listing [#listing_link#] has been upgraded.

Please confirm payment and then update the listings published date to todays date.

NOTE: If payment was made by live gateway site then the published date should be updated automatically.

Thank You.", "geodir_pricing" );

		return apply_filters( 'geodir_pricing_email_admin_upgrade_success_body', $body );
	}

	public static function email_user_pre_expiry_reminder_subject() {
		$subject = __( '[[#site_name#]] Listing Expiration Reminder', 'geodir_pricing' );

		return apply_filters( 'geodir_pricing_email_user_pre_expiry_reminder_subject', $subject );
	}

	public static function email_user_pre_expiry_reminder_body() {
		$body = "" . 
__( "Dear [#client_name#],

Your listing [#listing_link#] is about to expire in [#number_of_grace_days#] days. After listing goes expire it will no longer appear on the site.

If you wish to renew your listing, simply login to your member area and renew it before it expire.

Your listing expires on: [#display_expire_date#].

Login link: [#login_url#]
Username: [#username#]
Email: [#user_email#]

Thank You.", "geodir_pricing" );

		return apply_filters( 'geodir_pricing_email_user_pre_expiry_reminder_body', $body );
	}

	public static function email_user_post_expire_subject() {
		$subject = __( '[[#site_name#]] Your listing has been expired', 'geodir_pricing' );

		return apply_filters( 'geodir_pricing_email_user_post_expire_subject', $subject );
	}

	public static function email_user_post_expire_body() {
		$body = "" . 
__( "Hi [#client_name#],

Your listing [#listing_link#] has been expired on [#display_expire_date#].

If you wish to renew your listing, simply login to your member area and renew it.

Thank You.", "geodir_pricing" );

		return apply_filters( 'geodir_pricing_email_user_post_expire_body', $body );
	}

	public static function user_post_expire_email_tags( $inline = true ) { 
		$email_tags = self::email_tags( false );

		$tags = array_merge( $email_tags, array( '[#expire_date#]', '[#display_expire_date#]', '[#post_package_name#]', '[#renew_url#]', '[#renew_link#]', '[#upgrade_url#]', '[#upgrade_link#]' ) );

		$tags = apply_filters( 'geodir_pricing_user_post_expire_email_tags', $tags );

		if ( $inline ) {
			$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		}

		return $tags;
	}

	public static function email_user_post_downgrade_subject() {
		$subject = __( '[[#site_name#]] Your listing has been downgraded to another package', 'geodir_pricing' );

		return apply_filters( 'geodir_pricing_email_user_post_downgrade_subject', $subject );
	}

	public static function email_user_post_downgrade_body() {
		$body = "" . 
__( "Hi [#client_name#],

Your listing [#listing_link#] package has been downgraded from \"[#previous_post_package_name#]\" to \"[#post_package_name#]\".

New expire date: [#display_expire_date#]

Thank You.", "geodir_pricing" );

		return apply_filters( 'geodir_pricing_email_user_post_downgrade_body', $body );
	}

	public static function user_post_downgrade_email_tags( $inline = true ) { 
		$email_tags = self::email_tags( false );

		$tags = array_merge( $email_tags, array( '[#expire_date#]', '[#display_expire_date#]', '[#post_package_name#]', '[#previous_post_package_name#]', '[#upgrade_url#]', '[#upgrade_link#]' ) );

		$tags = apply_filters( 'geodir_pricing_user_post_downgrade_email_tags', $tags );

		if ( $inline ) {
			$tags = '<code>' . implode( '</code> <code>', $tags ) . '</code>';
		}

		return $tags;
	}

	// Post renew success email to user
	public static function send_user_renew_success_email( $post, $data = array() ) {
		$email_name = 'user_renew_success';

		if ( ! GeoDir_Email::is_email_enabled( $email_name ) ) {
			return false;
		}

		$author_data = get_userdata( $post->post_author );
		if ( empty( $author_data ) ) {
			return false;
		}

		$recipient = ! empty( $author_data->user_email ) ? $author_data->user_email : '';

		if ( empty( $post ) || ! is_email( $recipient ) ) {
			return;
		}

		$email_vars             = $data;
		$email_vars['post']     = $post;
		$email_vars['to_name']  = geodir_get_client_name( $post->post_author );
		$email_vars['to_email'] = $recipient;

		do_action( 'geodir_pricing_pre_' . $email_name . '_email', $email_name, $email_vars );

		$subject      = GeoDir_Email::get_subject( $email_name, $email_vars );
		$message_body = GeoDir_Email::get_content( $email_name, $email_vars );
		$headers      = GeoDir_Email::get_headers( $email_name, $email_vars );
		$attachments  = GeoDir_Email::get_attachments( $email_name, $email_vars );

		$plain_text = GeoDir_Email::get_email_type() != 'html' ? true : false;
		$template   = $plain_text ? 'emails/plain/email-' . $email_name . '.php' : 'emails/email-' . $email_name . '.php';

		$content = geodir_get_template_html( $template, array(
			'email_name'    => $email_name,
			'email_vars'    => $email_vars,
			'email_heading'	=> '',
			'sent_to_admin' => true,
			'plain_text'    => $plain_text,
			'message_body'  => $message_body,
		) );

		$sent = GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments, $email_name, $email_vars );

		if ( GeoDir_Email::is_admin_bcc_active( $email_name ) ) {
			$recipient = GeoDir_Email::get_admin_email();
			$subject .= ' - ADMIN BCC COPY';
			GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments, $email_name, $email_vars );
		}

		do_action( 'geodir_pricing_post_' . $email_name . '_email', $email_vars );

		return $sent;
	}

	// Post renew success email to admin
	public static function send_admin_renew_success_email( $post, $data = array() ) {
		$email_name = 'admin_renew_success';

		if ( ! GeoDir_Email::is_email_enabled( $email_name ) ) {
			return false;
		}

		$recipient = GeoDir_Email::get_admin_email();

		if ( empty( $post ) || ! is_email( $recipient ) ) {
			return;
		}

		$email_vars             = $data;
		$email_vars['post']     = $post;
		$email_vars['to_email'] = $recipient;

		do_action( 'geodir_pricing_pre_' . $email_name . '_email', $email_name, $email_vars );

		$subject      = GeoDir_Email::get_subject( $email_name, $email_vars );
		$message_body = GeoDir_Email::get_content( $email_name, $email_vars );
		$headers      = GeoDir_Email::get_headers( $email_name, $email_vars );
		$attachments  = GeoDir_Email::get_attachments( $email_name, $email_vars );

		$plain_text = GeoDir_Email::get_email_type() != 'html' ? true : false;
		$template   = $plain_text ? 'emails/plain/email-' . $email_name . '.php' : 'emails/email-' . $email_name . '.php';

		$content = geodir_get_template_html( $template, array(
			'email_name'    => $email_name,
			'email_vars'    => $email_vars,
			'email_heading'	=> '',
			'sent_to_admin' => true,
			'plain_text'    => $plain_text,
			'message_body'  => $message_body,
		) );

		$sent = GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments, $email_name, $email_vars );

		do_action( 'geodir_pricing_post_' . $email_name . '_email', $email_vars );

		return $sent;
	}

	// Post upgrade success email to user
	public static function send_user_upgrade_success_email( $post, $data = array() ) {
		$email_name = 'user_upgrade_success';

		if ( ! GeoDir_Email::is_email_enabled( $email_name ) ) {
			return false;
		}

		$author_data = get_userdata( $post->post_author );
		if ( empty( $author_data ) ) {
			return false;
		}

		$recipient = ! empty( $author_data->user_email ) ? $author_data->user_email : '';

		if ( empty( $post ) || ! is_email( $recipient ) ) {
			return;
		}

		$email_vars             = $data;
		$email_vars['post']     = $post;
		$email_vars['to_name']  = geodir_get_client_name( $post->post_author );
		$email_vars['to_email'] = $recipient;

		do_action( 'geodir_pricing_pre_' . $email_name . '_email', $email_name, $email_vars );

		$subject      = GeoDir_Email::get_subject( $email_name, $email_vars );
		$message_body = GeoDir_Email::get_content( $email_name, $email_vars );
		$headers      = GeoDir_Email::get_headers( $email_name, $email_vars );
		$attachments  = GeoDir_Email::get_attachments( $email_name, $email_vars );

		$plain_text = GeoDir_Email::get_email_type() != 'html' ? true : false;
		$template   = $plain_text ? 'emails/plain/email-' . $email_name . '.php' : 'emails/email-' . $email_name . '.php';

		$content = geodir_get_template_html( $template, array(
			'email_name'    => $email_name,
			'email_vars'    => $email_vars,
			'email_heading'	=> '',
			'sent_to_admin' => true,
			'plain_text'    => $plain_text,
			'message_body'  => $message_body,
		) );

		$sent = GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments, $email_name, $email_vars );

		if ( GeoDir_Email::is_admin_bcc_active( $email_name ) ) {
			$recipient = GeoDir_Email::get_admin_email();
			$subject .= ' - ADMIN BCC COPY';
			GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments, $email_name, $email_vars );
		}

		do_action( 'geodir_pricing_post_' . $email_name . '_email', $email_vars );

		return $sent;
	}

	// Post upgrade success email to admin
	public static function send_admin_upgrade_success_email( $post, $data = array() ) {
		$email_name = 'admin_upgrade_success';

		if ( ! GeoDir_Email::is_email_enabled( $email_name ) ) {
			return false;
		}

		$recipient = GeoDir_Email::get_admin_email();

		if ( empty( $post ) || ! is_email( $recipient ) ) {
			return;
		}

		$email_vars             = $data;
		$email_vars['post']     = $post;
		$email_vars['to_email'] = $recipient;

		do_action( 'geodir_pricing_pre_' . $email_name . '_email', $email_name, $email_vars );

		$subject      = GeoDir_Email::get_subject( $email_name, $email_vars );
		$message_body = GeoDir_Email::get_content( $email_name, $email_vars );
		$headers      = GeoDir_Email::get_headers( $email_name, $email_vars );
		$attachments  = GeoDir_Email::get_attachments( $email_name, $email_vars );

		$plain_text = GeoDir_Email::get_email_type() != 'html' ? true : false;
		$template   = $plain_text ? 'emails/plain/email-' . $email_name . '.php' : 'emails/email-' . $email_name . '.php';

		$content = geodir_get_template_html( $template, array(
			'email_name'    => $email_name,
			'email_vars'    => $email_vars,
			'email_heading'	=> '',
			'sent_to_admin' => true,
			'plain_text'    => $plain_text,
			'message_body'  => $message_body,
		) );

		$sent = GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments, $email_name, $email_vars );

		do_action( 'geodir_pricing_post_' . $email_name . '_email', $email_vars );

		return $sent;
	}

	// Pre expiry reminder email to user
	public static function send_user_pre_expiry_reminder_email( $post, $data = array() ) {
		$email_name = 'user_pre_expiry_reminder';

		if ( ! GeoDir_Email::is_email_enabled( $email_name ) ) {
			return false;
		}

		$author_data = get_userdata( $post->post_author );
		if ( empty( $author_data ) ) {
			return false;
		}

		$recipient = ! empty( $author_data->user_email ) ? $author_data->user_email : '';

		if ( empty( $post ) || ! is_email( $recipient ) ) {
			return;
		}

		$email_vars             = $data;
		$email_vars['post']     = $post;
		$email_vars['to_name']  = geodir_get_client_name( $post->post_author );
		$email_vars['to_email'] = $recipient;

		do_action( 'geodir_pricing_pre_' . $email_name . '_email', $email_name, $email_vars );

		$subject      = GeoDir_Email::get_subject( $email_name, $email_vars );
		$message_body = GeoDir_Email::get_content( $email_name, $email_vars );
		$headers      = GeoDir_Email::get_headers( $email_name, $email_vars );
		$attachments  = GeoDir_Email::get_attachments( $email_name, $email_vars );

		$plain_text = GeoDir_Email::get_email_type() != 'html' ? true : false;
		$template   = $plain_text ? 'emails/plain/email-' . $email_name . '.php' : 'emails/email-' . $email_name . '.php';

		$content = geodir_get_template_html( $template, array(
			'email_name'    => $email_name,
			'email_vars'    => $email_vars,
			'email_heading'	=> '',
			'sent_to_admin' => true,
			'plain_text'    => $plain_text,
			'message_body'  => $message_body,
		) );

		$sent = GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments, $email_name, $email_vars );

		if ( GeoDir_Email::is_admin_bcc_active( $email_name ) ) {
			$recipient = GeoDir_Email::get_admin_email();
			$subject .= ' - ADMIN BCC COPY';
			GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments, $email_name, $email_vars );
		}

		do_action( 'geodir_pricing_post_' . $email_name . '_email', $email_vars );

		return $sent;
	}

	// Post expire email to user
	public static function send_user_post_expire_email( $post, $data = array() ) {
		$email_name = 'user_post_expire';

		if ( ! GeoDir_Email::is_email_enabled( $email_name ) ) {
			return false;
		}

		$author_data = get_userdata( $post->post_author );
		if ( empty( $author_data ) ) {
			return false;
		}

		$recipient = ! empty( $author_data->user_email ) ? $author_data->user_email : '';

		if ( empty( $post ) || ! is_email( $recipient ) ) {
			return;
		}

		$email_vars             = $data;
		$email_vars['post']     = $post;
		$email_vars['to_name']  = geodir_get_client_name( $post->post_author );
		$email_vars['to_email'] = $recipient;

		do_action( 'geodir_pricing_pre_' . $email_name . '_email', $email_name, $email_vars );

		$subject      = GeoDir_Email::get_subject( $email_name, $email_vars );
		$message_body = GeoDir_Email::get_content( $email_name, $email_vars );
		$headers      = GeoDir_Email::get_headers( $email_name, $email_vars );
		$attachments  = GeoDir_Email::get_attachments( $email_name, $email_vars );

		$plain_text = GeoDir_Email::get_email_type() != 'html' ? true : false;
		$template   = $plain_text ? 'emails/plain/email-' . $email_name . '.php' : 'emails/email-' . $email_name . '.php';

		$content = geodir_get_template_html( $template, array(
			'email_name'    => $email_name,
			'email_vars'    => $email_vars,
			'email_heading'	=> '',
			'sent_to_admin' => true,
			'plain_text'    => $plain_text,
			'message_body'  => $message_body,
		) );

		$sent = GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments, $email_name, $email_vars );

		if ( GeoDir_Email::is_admin_bcc_active( $email_name ) ) {
			$recipient = GeoDir_Email::get_admin_email();
			$subject .= ' - ADMIN BCC COPY';
			GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments, $email_name, $email_vars );
		}

		do_action( 'geodir_pricing_post_' . $email_name . '_email', $email_vars );

		return $sent;
	}

	// Post upgrade email to user
	public static function send_user_post_downgrade_email( $post, $data = array() ) {
		$email_name = 'user_post_downgrade';

		if ( ! GeoDir_Email::is_email_enabled( $email_name ) ) {
			return false;
		}

		$author_data = get_userdata( $post->post_author );
		if ( empty( $author_data ) ) {
			return false;
		}

		$recipient = ! empty( $author_data->user_email ) ? $author_data->user_email : '';

		if ( empty( $post ) || ! is_email( $recipient ) ) {
			return;
		}

		$email_vars             = $data;
		$email_vars['post']     = $post;
		$email_vars['to_name']  = geodir_get_client_name( $post->post_author );
		$email_vars['to_email'] = $recipient;

		do_action( 'geodir_pricing_pre_' . $email_name . '_email', $email_name, $email_vars );

		$subject      = GeoDir_Email::get_subject( $email_name, $email_vars );
		$message_body = GeoDir_Email::get_content( $email_name, $email_vars );
		$headers      = GeoDir_Email::get_headers( $email_name, $email_vars );
		$attachments  = GeoDir_Email::get_attachments( $email_name, $email_vars );

		$plain_text = GeoDir_Email::get_email_type() != 'html' ? true : false;
		$template   = $plain_text ? 'emails/plain/email-' . $email_name . '.php' : 'emails/email-' . $email_name . '.php';

		$content = geodir_get_template_html( $template, array(
			'email_name'    => $email_name,
			'email_vars'    => $email_vars,
			'email_heading'	=> '',
			'sent_to_admin' => true,
			'plain_text'    => $plain_text,
			'message_body'  => $message_body,
		) );

		$sent = GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments, $email_name, $email_vars );

		if ( GeoDir_Email::is_admin_bcc_active( $email_name ) ) {
			$recipient = GeoDir_Email::get_admin_email();
			$subject .= ' - ADMIN BCC COPY';
			GeoDir_Email::send( $recipient, $subject, $content, $headers, $attachments, $email_name, $email_vars );
		}

		do_action( 'geodir_pricing_post_' . $email_name . '_email', $email_vars );

		return $sent;
	}

	public static function pre_expiry_reminder_sent( $email_vars = array() ) {
		global $wpi_auto_reminder;

		if ( empty( $email_vars['post'] ) ) {
			return;
		}

		$value = (array) get_post_meta( $email_vars['post']->ID, '_geodir_reminder_sent', true );

		if ( empty( $value ) ) {
			$value = array();
		}
		$value[] = date_i18n( 'Y-m-d' );

		update_post_meta( $email_vars['post']->ID, '_geodir_reminder_sent', array_unique( array_filter( $value ) ) );
	}
}