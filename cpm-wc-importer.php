<?php
/*
Plugin Name: CPM WC Importer
Plugin URI: https://codepixelzmedia.com/
Description: Woocommerce product importer
Version: 1.2.0
Author: Cpm
Author URI: https://codepixelzmedia.com/
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*Require some Wordpress core files for processing images*/
require_once(ABSPATH .'wp-load.php' );
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
/* require plugin loder file */
$init_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . "cpm-wc-importer" . DIRECTORY_SEPARATOR  ."cpm-wc-importer-loader.php";
require_once $init_file;

/*Create product custom fields to add Manufacture Name of Products*/

if (!function_exists('cpm_wc_importer_product_custom_field_valmistaja')) {
	function cpm_wc_importer_product_custom_field_valmistaja()
	{
		add_meta_box('cpm_wc_importer_custom_field', 'Manufacturers Name', 'cpm_wc_cimporter_manufacturers_name_field_callback', 'product', 'side', 'default');
	}
	add_action('add_meta_boxes', 'cpm_wc_importer_product_custom_field_valmistaja');
}

// save meta box function
if (!function_exists('cpm_wc_importer_manufactures_name_save_meta_box_data')) {
	function cpm_wc_importer_manufactures_name_save_meta_box_data($post_id)
	{

		if (!isset($_POST['cpm_wc_importer_manufactures_name_fields_nonce']) || !wp_verify_nonce($_POST['cpm_wc_importer_manufactures_name_fields_nonce'], 'cpm_wc_importer_manufacture_nonce'))
			return;

		if (!current_user_can('edit_post', $post_id))
			return;


		update_post_meta($post_id, 'cpm_wc_importer_manufacturers_name_field', sanitize_text_field($_POST['cpm_wc_importer_manufacturers_name_field']));
	}
}
add_action('save_post', 'cpm_wc_importer_manufactures_name_save_meta_box_data');


add_filter('wc_product_has_unique_sku', '__return_false');

if ( ! function_exists( 'post_exists' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/post.php' );
}
  
