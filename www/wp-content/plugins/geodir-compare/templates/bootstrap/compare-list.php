<?php
/**
 * Compare listings list
 *
 * This template can be overridden by copying it to yourtheme/geodirectory/bootstrap/compare-list.php.
 *
 * HOWEVER, on occasion GeoDirectory will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.wpgeodirectory.com/article/346-customizing-templates/
 * @package    GeoDir_Compare
 * @version    2.1.0.0
 */

defined( 'ABSPATH' ) || exit;

global $aui_bs5, $post;

// Maybe abort early
if ( ! empty( $listings ) ) {
	// Save a reference to the global post object
	$_post = $post;
?>
<div class="geodir-compare-page-wrapper gd-ios-scrollbars overflow-auto">
<?php 
	$show_images = true;
	$show_title = true;
	$return = '';

	if( function_exists( 'geodir_show_hints' ) && geodir_show_hints() ){
		$return .= geodir_output_hint(
			array(
				__("Set what fields are compared in each CPT field settings under `Show in extra output location`","geodir-compare"),
				__("You can share urls comparing listings","geodir-compare"),
				__("You can embed a shortcode comparing specific listings on any page.","geodir-compare"),
			),
			"https://docs.wpgeodirectory.com/article/56-compare-listings", // documentation url
			"", // video documentation url
			"compare_listings" // feedback id
		);
	}

	/*
	 * ______________| Image Item 1  | Image Item 2 | Image Item 3   |
	 * Feature Name  | Item 1 value  | Item 2 Value | Item 3 Value   |
	 * Feature2 Name | Item 1 value  | Item 2 Value | Item 3 Value   |
	 * 
	 */
	$return .= '<table class="geodir-compare-page-table table table-hover table-bordered"><thead class="' . ( $aui_bs5 ? 'bg-light' : 'thead-light' ) . '"><tr><th scope="col" class="geodir-compare-listing-header-titles"></th>';

	//Print the items table headers, i.e image and title
	$has_ratings = ! geodir_cpt_has_rating_disabled( $post_type ); //Checking it here prevents checking it severally

	$width = count($listings)>0 ? 90/count($listings) : 90;

	foreach( $listings as $listing ) {
		// Css class
		$class = 'text-center geodir-compare-listing-header geodir-compare-post geodir-compare-' . $listing->post_id;

		// Remove button
		$remove_button = $allow_remove ? sprintf(
			'<span onclick="geodir_compare_remove_from_table(\'%s\', \'%s\')" class="geodir-compare-table-remove-listing c-pointer py-3 px-5 text-danger" data-' . ( $aui_bs5 ? 'bs-' : '' ) . 'toggle="tooltip" title="'.__('Remove', 'geodir-compare').'"><i title="' . esc_attr__( 'Remove', 'geodir-compare' ) . '" class="fas fa-times-circle" aria-hidden="true"></i></span>',
			$listing->post_id,
			$post_type
		) : '';

		$return .= "<th scope='col' class='$class' style='width: $width%;'>$remove_button</th>";
	}

	$return .= '</tr></thead><tbody>';

	if( ! empty($fields) ) {
		foreach ( $fields as $field => $field_info ) {
			if(isset($field_info['htmlvar_name'])){
				if($field_info['htmlvar_name']=='post_title'){
					$show_title = false;
				}
				elseif($field_info['htmlvar_name']=='post_images'){
					$show_images = false;
				}
			}

		}
	}

	// title
	if($show_title){
		$return .= "<tr class='geodir-compare-field-title'><th scope='row'></th>";
		foreach( $listings as $listing ) {
			//Switch to this as the global post object
			$post    = get_post( $listing->post_id );
			setup_postdata( $post );
			//Link to the post
			$link    = esc_url( get_the_permalink( $listing->post_id ) );
			$return .= "<td class='geodir-compare-images geodir-compare-{$listing->post_id}'><a href='$link'><h5>".esc_attr($listing->post_title)."</h5></a></td>";
		}
		$return .= '</tr>';
	}

	// images
	if($show_images){
		$return .= "<tr class='geodir-compare-field-images'><th scope='row'>".__("Images","geodir-compare")."</th>";
		foreach( $listings as $listing ) {
			//Switch to this as the global post object
			$post    = get_post( $listing->post_id );
			setup_postdata( $post );
			$images   = do_shortcode("[gd_post_images show_title='1' slideshow='1' ajax_load='1 type='slider' cover='x' image_size='medium']");
			$return .= "<td class='geodir-compare-images geodir-compare-{$listing->post_id}'>$images</td>";
		}
		$return .= '</tr>';
	}

	// ratings
	if( $has_ratings ) {
		$return .= "<tr class='geodir-compare-field-ratings'><th scope='row'>".__("Ratings","geodir-compare")."</th>";
		foreach( $listings as $listing ) {
			//Switch to this as the global post object
			$post    = get_post( $listing->post_id );
			setup_postdata( $post );
			$rating  = do_shortcode("[gd_post_rating]");
			$return .= "<td class='geodir-compare-ratings geodir-compare-{$listing->post_id}'>$rating</td>";
		}
		$return .= '</tr>';
	}


	//Finally, print the table body
	if(!empty($fields)){
		foreach( $fields as $field => $field_info ) {

			$field_info = stripslashes_deep( $field_info );

			$class   = esc_attr( 'geodir-compare-field-' . $field_info['type'] );
			$return .= "<tr class='$class'><td>{$field_info['frontend_title']}</td>";

			$key = 0;
			while( $key < count( $listings ) ) {

				//Display the content of the CF
				$class   = 'geodir-compare-' . $listings[$key]->post_id;

				//Temporarily change the global post object
				$post    = get_post( $listings[$key]->post_id );
				setup_postdata( $post );

				//Output the row col content
				if($field_info['htmlvar_name']=='post_title'){
					$link    = esc_url( get_the_permalink( $listings[$key]->post_id ) );
					$output  = "<a href='$link'><h5>".apply_filters( "geodir_custom_field_output_{$field_info['type']}", '', 'compare', $field_info, $listings[$key]->post_id, 'value')."</h5></a>";
				}
				elseif( $field_info['type'] == 'images' ) {
					$output  = do_shortcode("[gd_post_images show_title='1' slideshow='1' ajax_load='1 type='slider'  link_to='lightbox']");
				} else {
					$output  = apply_filters( "geodir_custom_field_output_{$field_info['type']}", '', 'compare', $field_info, $listings[$key]->post_id, 'value');
				}

				$return .= "<td class='$class'>$output</td>";

				//Revert to the original global post object
				$post    = $_post;
				setup_postdata( $post );

				$key ++;
			}

			$return .= '</tr>';
		}
	}

	//Revert to the original global post object
	$post    = $_post;
	setup_postdata( $post );

	echo $return . '</tbody></table>';
 ?>
</div>
<?php } ?>