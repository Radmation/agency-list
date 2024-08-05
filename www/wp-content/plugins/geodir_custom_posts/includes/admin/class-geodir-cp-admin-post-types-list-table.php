<?php
/**
 * List Table API: GeoDir_CP_Admin_Post_Types_List_Table class.
 *
 * @author   AyeCode
 * @category Admin
 * @package  GeoDir_Custom_Posts/Classes
 * @version  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class GeoDir_CP_Admin_Post_Types_List_Table extends WP_List_Table {

	var $gd_post_types;
    var $custom_types;

    function __construct() {
        parent::__construct( array(
            'singular'  => 'gd-cpt',
            'plural'    => 'gd-cpts',
            'ajax'      => true
        ) );

		$this->gd_post_types = geodir_get_posttypes( 'object' );
        $this->custom_types = $this->setup_post_types();
    }

	public function setup_post_types() {
		$post_types = $this->gd_post_types;

		$custom_types = array();
		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $post_type => $info ) {
				$custom_types[ $post_type ] = array(
					'cpt' 				=> $post_type,
					'name' 				=> $info->labels->name,
					'slug'        		=> $info->has_archive,
					'taxonomies'  		=> '',
					'link_post_types'	=> GeoDir_CP_Link_Posts::linked_to_post_types( $post_type ),
					'menu_icon'  		=> ( ! empty( $info->menu_icon ) ? $info->menu_icon : '' ),
					'image'  	  		=> ( ! empty( $info->default_image ) ? $info->default_image : '' ),
					'order'  	  		=> ( ! empty( $info->listing_order ) ? $info->listing_order : '' ),
				);
			}
		}
		return $custom_types;
	}

    function get_columns() {
        $columns = array(
            'cb'          		=> '',
			'cpt'   	  		=> __( 'Post Type', 'geodir_custom_posts' ),
            'name'        		=> __( 'Name', 'geodir_custom_posts' ),
			'slug'        		=> __( 'Slug', 'geodir_custom_posts' ),
            'taxonomies'  		=> __( 'Taxonomies', 'geodir_custom_posts' ),
			'link_post_types'  	=> __( 'Linked CPTs', 'geodir_custom_posts' ),
			'image'  	 	 	=> __( 'Image', 'geodir_custom_posts' ),
			'order'  	  		=> __( 'Order', 'geodir_custom_posts' )
        );
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
			'cpt'       		=> array( 'cpt', true ),
            'name'       		=> array( 'name', true ),
			'slug'       		=> array( 'slug', true ),
            'order' 			=> array( 'order', false )
        );
        return $sortable_columns;
    }

    function column_default( $item, $column_name ) {
        switch ( $column_name ) {
			default:
                return isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
				break;
        }
    }

    function column_cb( $item ) {
        $cb = '<input type="hidden" class="gd-has-id" data-delete-nonce="' . esc_attr( wp_create_nonce( 'geodir-delete-post-type-' . $item['cpt'] ) ) . '" value="' . $item['cpt'] . '" />';
		if ( ! empty( $item['menu_icon'] ) ) {
			$icon = GeoDir_Post_types::sanitize_menu_icon( $item['menu_icon'] );

			if ( $icon ) {
				$cb .= '<div class="dashicons dashicons-before ' . $icon . '"></div>';
			}
		}

		return $cb;
    }
	
	function column_cpt( $item ) {
		$edit_link = esc_url(
            add_query_arg(
                array(
                    'page' => $item['cpt'] . '-settings',
					'tab' => 'cpt',
					'post_type' => $item['cpt'],
                ),
                admin_url( 'edit.php' )
            )
        );

		$actions = array();
        $actions[ 'edit' ] = sprintf( '<a href="%s">%s</a>', $edit_link, __( 'Edit', 'geodir_custom_posts' ) );
		if ( GeoDir_CP_Admin::allow_delete_cpt( $item['cpt'] ) ) {
			$actions[ 'delete' ] = sprintf( '<a href="javascript:void(0)" class="geodir-delete-post-type geodir-act-delete">%s</a>', __( 'Delete', 'geodir_custom_posts' ) );
		}
		$actions[ 'view' ] = sprintf( '<a href="%s" target="_blank" aria-label="' . wp_sprintf( esc_attr__( 'View "%s"', 'geodir_custom_posts' ), $item['name'] ) . '">%s</a>', get_post_type_archive_link( $item['cpt'] ), __( 'View', 'geodir_custom_posts' ) );
		
		return sprintf(
            '<strong><a href="%s" class="row-cpt">%s</strong>%s',
            $edit_link,
            $item['cpt'],
            $this->row_actions( $actions )
        );
	}

    function column_name( $item ) {
		return $item['name'];
    }

    function column_slug( $item ) {
        return $item['slug'];
    }

    function column_taxonomies( $item ) {
        $taxonomies = $this->gd_post_types->{$item['cpt']}->taxonomies;

		$data = array();
		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy ) {
				$taxonomy_obj = get_taxonomy( $taxonomy );

				if ( ! empty( $taxonomy_obj ) ) {
					$data[] = $taxonomy_obj->labels->name . ' ( ' . $taxonomy . ' )';
				}
			}
		}
		$value = ! empty( $data ) ? implode( '<br>', $data ) : '';

		return $value;
    }

	function column_link_post_types( $item ) {
		$post_types = array();
		if ( ! empty( $item['link_post_types'] ) && is_array( $item['link_post_types'] ) ) {
			foreach ( $item['link_post_types'] as $post_type ) {
				$post_types[] = geodir_post_type_name( $post_type, true ) . ' ( ' . $post_type . ' )';
			}
		}
        $value = ! empty( $post_types ) ? implode( '<br>', $post_types ) : '';

		return $value;
    }

    function column_image( $item ) {
		$value = '';
		if ( ! empty( $item['image'] ) ) {
			$value = wp_get_attachment_image( $item['image'], 'thumbnail', true );
		}

		return $value;
    }

    function prepare_items() {
        $columns = $this->get_columns();
		$hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

		$data = array();
		$data = $this->custom_types;

		usort( $data, array( 'GeoDir_CP_Post_Types', 'reorder_post_types' ) );

        $this->items = $data;
    }

    public function single_row( $item ) {
        static $row_class = '';
        $row_class = ( $row_class == '' ? 'alternate' : '' );

        printf('<tr class="%s cpt-%s">', $row_class, $item['cpt']);
        $this->single_row_columns( $item );
        echo '</tr>';
    }

	public function no_items() {
        _e( 'No post types founds.', 'geodir_custom_posts' );
		return;
    }

}
