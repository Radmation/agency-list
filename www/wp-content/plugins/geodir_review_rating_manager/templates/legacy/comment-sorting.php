<?php
/**
 * Comment Sorting.
 *
 * @ver 1.0.0
 *
 * @var array  $comment_sorting_form_field_val The array of sort options.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<form name="comment_sorting_form" id="comment_sorting_form" method="get" action="<?php comments_link(); ?>">
	<?php
	$query_variables = $_GET;

	$hidden_vars = '';
	if (!empty($query_variables)) {
		foreach ($query_variables as $key => $val) {
			$key = sanitize_text_field($key);
			$val = sanitize_text_field($val);
			if ( $key != 'comment_sorting')
				$hidden_vars .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($val) . '" />';
		}
	}

	echo $hidden_vars;
	?>
	<select name="comment_sorting" class="comment_sorting" onchange="jQuery(this).closest('#comment_sorting_form').trigger('submit')">
		<?php
		$page_comments = get_option('page_comments');
		$default_shorting = 'latest';

		if ( $page_comments ) {
			$default_comments_page = get_option('default_comments_page');

			if ( $default_comments_page == 'newest' ) {
				$default_shorting = 'latest';
			} else if ( $default_comments_page == 'oldest' ) {
				$default_shorting = 'oldest';
			}
		}
		/**
		 * Filter the default comments sorting.
		 *
		 * @since 1.1.7
		 * @package GeoDirectory_Review_Rating_Manager
		 *
		 * @param string $comment_sorting Sorting name to sort comments.
		 */
		$comment_sorting = apply_filters( 'geodir_reviewrating_comments_shorting_default', $default_shorting );
		$comment_sorting = isset( $_REQUEST['comment_sorting'] ) && !empty( $_REQUEST['comment_sorting'] ) && isset( $comment_sorting_form_field_val[$_REQUEST['comment_sorting']] ) ? $_REQUEST['comment_sorting'] : $comment_sorting;

		if (isset($comment_sorting_form_field_val) && !empty($comment_sorting_form_field_val)) {
			foreach($comment_sorting_form_field_val as $key => $value) {
				?>
				<option <?php if($comment_sorting ==  $key){echo 'selected="selected"';} ?> value="<?php echo $key; ?>"><?php _e($value, 'geodir_reviewratings');?></option>
				<?php
			}
		}
		?>
	</select>
</form>