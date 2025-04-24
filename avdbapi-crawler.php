<?php

/*
* @wordpress-plugin
* Plugin Name: AVDBAPI Crawler
* Plugin URI: https://avdbapi.com/
* Description: Collect movies from AVDBAPI - Compatibility Theme wp-script
* Version: 1.3.0
* Requires PHP: 7.0^
* Author: Avdbapi
* Author URI: https://github.com/vantayden
*/

// Protect plugins from direct access. If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die('The action has not been authenticated!');
}

/**
 * Currently plugin version.
 * Start at version 1.2.0
 */
define( 'PLUGIN_NAME_VERSION', '1.2.0' );

/**
 * The unique identifier of this plugin.
 */
set_time_limit(0);
if ( defined( 'PLUGIN_NAME_VERSION' ) ) {
    $version = PLUGIN_NAME_VERSION;
} else {
    $version = '1.2.0';
}

define('PLUGIN_NAME', 'avdbapi-crawler');
define('VERSION', $version);

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_plugin_name() {
    // Code
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_plugin_name() {
    // Code
}

register_activation_hook( __FILE__, 'activate_plugin_name' );
register_deactivation_hook( __FILE__, 'deactivate_plugin_name' );

/**
 * Provide a public-facing view for the plugin
 */
function avdbapi_crawler_add_menu() {
    add_menu_page(
        __('AVDBAPI Crawler Tools', 'textdomain'),
        'AVDBAPI Crawler',
        'manage_options',
        'avdbapi-crawler-tools',
        'avdbapi_crawler_page_menu',
        'dashicons-buddicons-replies',
        2
    );
}

/**
 * Include the following files that make up the plugin
 */
function avdbapi_crawler_page_menu() {
    require_once plugin_dir_path(__FILE__) . 'public/partials/avdbapi_crawler_view.php';
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 * 
 */
require_once plugin_dir_path( __FILE__ ) . 'public/public-crawler.php';
function run_plugin_name() {
    add_action('admin_menu', 'avdbapi_crawler_add_menu');

    $plugin_admin = new Nguon_avdbapi_crawler( PLUGIN_NAME, VERSION );
    add_action('in_admin_header', array($plugin_admin, 'enqueue_scripts'));
    add_action('in_admin_header', array($plugin_admin, 'enqueue_styles'));

    add_action('wp_ajax_avdbapi_crawler_api', array($plugin_admin, 'avdbapi_crawler_api'));
    add_action('wp_ajax_avdbapi_get_movies_page', array($plugin_admin, 'avdbapi_get_movies_page'));
    add_action('wp_ajax_avdbapi_crawl_by_id', array($plugin_admin, 'avdbapi_crawl_by_id'));
}
run_plugin_name();