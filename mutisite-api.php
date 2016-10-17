<?php
/**
 * @package  multisite-api
 * @version 0.1
 */
/*
Plugin Name: Multisite API
Plugin URI: https://github.com/uts-cic/wp-multisite-api
Description: Exposes an endpoint to read content from a multisite WordPress network
Author: Tommaso Armstrong
Version: 0.1
Author URI: https://tomma.so/
*/

add_action( 'rest_api_init', function () {
    register_rest_route( 'multisite-api/v1', 'sites', array(
        'methods' => 'GET',
        'callback' => 'get_all_sites',
    ) );
} );

// Get all blogs

function get_all_sites(WP_REST_Request $request) {
    $sites = get_sites();

    return $sites;
}

