<?php
/**
 * @package  clatoolkit-wp
 * @version 0.1
 */
/*
Plugin Name: CLA Toolkit WP
Plugin URI: https://github.com/uts-cic/clatoolkit-wp
Description: Plugin to connect WordPress with the CLA Toolkit
Author: Tommaso Armstrong
Version: 0.1
Author URI: https://tomma.so/
*/

add_action( 'rest_api_init', function () {
	register_rest_route( 'clatoolkit-wp/v1', 'posts', array(
		'methods' => 'GET',
		'callback' => 'cla_get_recent_posts',
		'permission_callback' => function () {
			return current_user_can( 'edit_others_posts' );
		}
	) );
	register_rest_route( 'clatoolkit-wp/v1', 'friendships', array(
		'methods' => 'GET',
		'callback' => 'cla_get_friendships',
		'permission_callback' => function () {
			return current_user_can( 'edit_others_posts' );
		}
	) );
} );

const POSTS_PER_BLOG = 10;

function cla_get_recent_posts(WP_REST_Request $request) {

	// Get all blogs in the network
	$sites = get_sites();

	$posts = [];

	// If the page query exists, use it, otherwise default $page to 0
	$page = isset($request['page']) ? $request['page'] : 0;
	$offset = POSTS_PER_BLOG * $page;

	// Flag to indicate whether there are more pages
	$more_pages = false;

	// For each blog in the network
	foreach( $sites as $site ){
		// Switch to the blog
		switch_to_blog( $site->blog_id );

		// Get posts
		$blog_posts = get_posts(array( "posts_per_page" => POSTS_PER_BLOG, "offset" => $offset, 'post_status' => 'publish'));
		$num_posts = wp_count_posts()->publish;

		if ($num_posts > $offset + POSTS_PER_BLOG) $more_pages = true;

		// Get post tags, comments and author
		for ($i = 0; $i < count($blog_posts); $i++) {
			$postID = $blog_posts[$i]->ID;

			$tags = wp_get_post_tags($postID);

			$blog_posts[$i]->tags = [];


			foreach ($tags as $tag) {
				$blog_posts[$i]->tags[] = [
					"name" => $tag->name,
					"slug" => $tag->slug
				];
			}

			$comments = get_comments(array("post_id" => $postID));

			if (count($comments) > 0) {
				for ($j = 0; $j < count($comments); $j++) {
					$comments[$j]->comment_guid = get_comment_guid($comments[$j]->comment_ID);
				}

				$blog_posts[$i]->comments = $comments;
			}

			$author = get_userdata($blog_posts[$i]->post_author);

			$blog_posts[$i]->author = [];
			$blog_posts[$i]->author["email"] = $author->user_email;
			$blog_posts[$i]->author["nicename"] = $author->user_nicename;
			$blog_posts[$i]->author["login"] = $author->user_login;

		}

		// Add the blog's posts to the posts object to return (if there are any)
		if (count($blog_posts) > 0) {
			$posts[] = ["blog_id" => $site->blog_id, "posts" => $blog_posts];
		}

		// Switch back to the original blog
		restore_current_blog();
	}

	// If there are more pages return the uri for the next page
	if ($more_pages) {
		$next_page = "//".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."?page=".($page+1);

		// Return all the posts to the user
		return ["posts" => $posts, "next_page" => $next_page];
	}

	return ["posts" => $posts];
}

function cla_get_friendships (WP_REST_Request $request) {
	// If BuddyPress is installed
	if (function_exists('bp_is_active')) {
		$users = get_users();
		$result = [
			"users" => [],
			"friendships" => [],
			"entities" => []
		];
		for ($i = 0; $i < count($users); $i++) {
			$id = (string)$users[$i]->ID;

			$friends = array_map("strval", friends_get_friend_user_ids($id));
			$result["users"][] = $id;
			$result["friendships"][$id] = $friends;
			$result["entities"][$id] = [
				"email" => $users[$i]->user_email,
				"nicename" => $users[$i]->user_nicename,
				"login" => $users[$i]->user_login
			];
		}

		return $result;
	}
	else {
		return new WP_REST_Response("ERROR: BuddyPress is not installed", 400);
	}
}
