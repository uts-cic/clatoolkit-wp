<?php
/**
 * @package  clatoolkit-wp
 * @version 0.1
 */
/*
Plugin Name: CLA Toolkit WP
Plugin URI: https://github.com/uts-cic/wp-multisite-api
Description: Plugin to connect WordPress with the CLA Toolkit
Author: Tommaso Armstrong
Version: 0.1
Author URI: https://tomma.so/
*/

add_action( 'rest_api_init', function () {
	register_rest_route( 'clatoolkit-wp/v1', 'posts', array(
		'methods' => 'GET',
		'callback' => 'get_recent_posts',
		'permission_callback' => function () {
			return current_user_can( 'edit_others_posts' );
		}
	) );
} );

const POSTS_PER_PAGE = 10;

function get_recent_posts(WP_REST_Request $request) {

	// Get all blogs in the network
	$sites = get_sites();

	$posts = [];

	// If the page query exists, use it, otherwise default $page to 0
	$page = isset($request['page']) ? $request['page'] : 0;
	$offset = POSTS_PER_PAGE * $page;

	// For each blog in the network
	foreach( $sites as $site ){
		// Switch to the blog
		switch_to_blog( $site->blog_id );

		// Get posts
		$blog_posts = get_posts(array("posts_per_page" => POSTS_PER_PAGE, "offset" => $offset));

		// Get post comments
		for ($i = 0; $i < count($blog_posts); $i++) {
			$comments = get_comments(array("post_id" => $blog_posts[$i]->ID));

			$blog_posts[$i]->comments = $comments;
		}

		// Add the blog's posts to the posts object to return (if there are any)
		if (count($blog_posts) > 0) {
			$posts[] = ["blog_id" => $site->blog_id, "posts" => $blog_posts];
		}

		// Switch back to the original blog
		restore_current_blog();
	}

	// Return all the posts to the user
	return $posts;

}
