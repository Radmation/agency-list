<?php
/**
 * Claim Listings Ninja Forms Class.
 * 
 * This class is used to allow claims to be paid if the Pricing Manager addon is installed.
 *
 * @since 2.0.0
 * @package Geodir_Claim_Listing
 * @author AyeCode Ltd
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class GeoDir_Claim_Ninja_Forms_Packages_field extends NF_Abstracts_List
{
	protected $_name = 'geodir_packages';
	//protected $_name = 'listselect';

	protected $_type = 'listselect';

	protected $_nicename = 'GD Claim Package';

	protected $_section = 'misc';

	protected $_icon = 'chevron-down';

//	protected $_settings_exclude = array( 'default', 'required', 'placeholder', 'input_limit_set', 'disable_input' );

	protected $_templates = 'listselect';

	protected $_old_classname = 'list-select';

	public function __construct()
	{
		parent::__construct();

		$this->_nicename = __( 'GD Claim Package', 'geodir-claim' );
		//print_r($this->_settings);exit;
//		print_r($this->_settings);


		// Rename Label setting to Question
		$this->_settings[ 'label' ][ 'value' ] = __( 'Select Package', 'geodir-claim' );

		$this->_settings[ 'options' ][ 'value' ] = array();
		$this->_settings[ 'options' ][ 'group' ] = '';


		add_filter( 'ninja_forms_merge_tag_calc_value_' . $this->_type, array( $this, 'get_calc_value' ), 10, 2 );

		// add the price pacakge options
		add_filter( 'ninja_forms_render_options',array( $this, 'package_values' ),10,2);

		add_filter( 'ninja_forms_display_fields', array( $this, 'maybe_show_field' ));
	}

	public function maybe_show_field($fields){

		$post_id = !empty($_REQUEST['p']) ? absint($_REQUEST['p']) : '';
		$post_type = get_post_type($post_id);

		if($post_type && $post_id && !empty($fields)){
			$package_id = geodir_get_post_meta($post_id,'package_id',true);

			$packages = GeoDir_Claim_Payment::get_upgrade_price_packages($post_type,$package_id);
			if(empty($packages)){
				foreach($fields as $key=>$field){
					if($field['type']=='geodir_packages'){
						// hide field
						$fields[$key]['options'] = array();
						$fields[$key]['value'] = '';
						$fields[$key]['type'] = 'hidden';
						$fields[$key]['element_templates'] = array('hidden');
					}
				}
			}

		}
		return $fields;
	}

	public function get_calc_value( $value, $field )
	{
		if( isset( $field[ 'options' ] ) ) {
			foreach ($field['options'] as $option ) {
				if( ! isset( $option[ 'value' ] ) || $value != $option[ 'value' ] || ! isset( $option[ 'calc' ] ) ) continue;
				return $option[ 'calc' ];
			}
		}
		return $value;
	}

	/**
	 * Add the price package options.
	 *
	 * @param $options
	 * @param $settings
	 *
	 * @return array
	 */
	public function package_values($options, $settings){

		if($settings['type']=='geodir_packages'){
			$options = array();

			$post_id = !empty($_REQUEST['p']) ? absint($_REQUEST['p']) : '';

			$post_type = get_post_type($post_id);
			
			$package_id = geodir_get_post_meta($post_id,'package_id',true);

			$packages = GeoDir_Claim_Payment::get_upgrade_price_packages($post_type,$package_id);

			if(!empty($packages)){
				$options = array();
				foreach($packages as $id => $name){
					$options[] = [
						'label' => $name,
						'value' => $id,
					];
				}
			}

			//print_r($packages);exit;
		}
		//$options = array();

		//print_r($settings);exit;
		return $options;
	}

//	protected $_type = 'listselect';
//
//	protected $_nicename = 'GD Claim Packages';
//
//	protected $_section = 'pricing';
//
//	protected $_icon = 'chevron-down';
//
//	//protected $_templates = 'Sample Field';
//	protected $_templates = 'listselect';
//
//
//	protected $_test_value = '123 Main Street';
//
//	protected $_settings = array(  );
//
//	public function __construct()
//	{
//		parent::__construct();
//
//		//print_r($this);exit;
//
//		//$this->_nicename = __( 'Sample Field', 'ninja-forms' );
//
//		// Manually set Field Key and stop tracking.
//		$this->_settings[ 'key' ][ 'value' ] = 'xxxxx';
//		$this->_settings[ 'manual_key' ][ 'value' ] = TRUE;
//	}

}