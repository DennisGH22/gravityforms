<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use GFForms;

/**
 * Config items for the Settings Fields
 *
 * @since 2.6.2
 */
class GF_Settings_Config_Admin extends GF_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';

	/**
	 * Only enqueue in the admin.
	 *
	 * @since 2.6.2
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		return GFForms::is_gravity_page();
	}

	/**
	 * Config data.
	 *
	 * @since 2.6.2
	 *
	 * @return array[]
	 */
	public function data() {
		$post_types = apply_filters( 'gform_post_select_post_types', array( 'post', 'page' ) );
		$count      = apply_filters( 'gform_post_select_initial_count', 5 );

		$data = array(
			'components' => array(
				'post_select' => array(),
				'user_select' => array(
					'endpoints' => array(
						'get' => rest_url( '/wp/v2/users' ),
					),
					'data'      => $this->get_first_users(),
				),
				'file_upload' => array(
					'data' => array(
						'allowed_file_types' => array(
							'SVG',
							'PNG',
							'JPG',
							'GIF',
						),
						'max_width'          => 800,
						'max_height'         => 400,
					),
				),
			),
		);

		foreach ( $post_types as $post_type ) {
			$post_type_obj = get_post_type_object( $post_type );
			if ( ! $post_type_obj ) {
				continue;
			}

			$data['components']['post_select'][ $post_type ] = array(
				'endpoints' => array(
					'get' => rest_url( $this->get_post_type_rest_route( $post_type_obj ) ),
				),
				'data'      => $this->get_first_posts_for_type( $post_type, $count ),
			);
		}

		return $data;
	}

	/**
	 * Returns the REST route for the given post type.
	 *
	 * @since 2.6.4
	 *
	 * @param \WP_Post_Type $post_type A post type object.
	 *
	 * @return string
	 */
	private function get_post_type_rest_route( $post_type ) {
		if ( function_exists( 'rest_get_route_for_post_type_items' ) ) {
			return rest_get_route_for_post_type_items( $post_type->name );
		}

		if ( ! $post_type->show_in_rest ) {
			return '';
		}

		return sprintf(
			'/%s/%s',
			! empty( $post_type->rest_namespace ) ? $post_type->rest_namespace : 'wp/v2',
			! empty( $post_type->rest_base ) ? $post_type->rest_base : $post_type->name
		);
	}

	/**
	 * Get the first posts to populate the dropdown.
	 *
	 * @since 2.6.2
	 *
	 * @param string $post_type Post type slug
	 * @param int    $count     Number of posts
	 *
	 * @return array|false
	 */
	public function get_first_posts_for_type( $post_type, $count ) {
		$first_posts = array();
		$posts       = get_posts( array( 'post_type' => $post_type, 'numberposts' => $count ) );

		if ( ! is_array( $posts ) || empty( $posts ) ) {
			return false;
		}

		foreach ( $posts as $post ) {
			$first_posts[] = array(
				'label' => esc_html( $post->post_title ),
				'value' => esc_attr( $post->ID ),
			);
		}

		return $first_posts;
	}

	/**
	 * Get the first users to populate the dropdown.
	 *
	 * @since 2.9.5
	 *
	 * @return array|false
	 */
	public function get_first_users() {
		$first_users = array(
			array(
				'label' => esc_html__( 'Logged In User', 'gravityforms' ),
				'value' => 'logged-in-user',
			),
		);

		/*
		 * Filter the parameters for the query for the initial list of users in the user select dropdown.
		 *
		 * @since 2.9.5
		 *
		 * @param array $user_query_params Array of parameters for the query for the initial list of users in the user select dropdown.
		 */
		$user_query_params = apply_filters( 'gform_user_select_query_params', array( 'number' => 10 ) );
		$users = get_users( $user_query_params );

		if ( ! is_array( $users ) || empty( $users ) ) {
			return false;
		}

		foreach ( $users as $user ) {
			$first_users[] = array(
				'label' => esc_html( $user->display_name ),
				'value' => esc_attr( $user->ID ),
			);
		}

		return $first_users;
	}
}
