<?php

/*function that run on basic of corn jobs*/



$root = dirname(dirname(dirname(dirname(__FILE__))));
// WP 2.6
require_once($root.'/wp-load.php');
function cpm_importer_run_files()
{

	cpm_import_data_files();
	cpm_wc_importer_webgroups_files();
	cpm_wc_importer_pim_data_description_files();
	
}
//  add_action('admin_head', 'cpm_importer_run_files');
//  add_action( 'cpm_wc_importer_cronjob', 'cpm_importer_run_files' );


/*Define importer file location */
if( ! function_exists( 'wp_get_upload_dir' ) ) {
		function wp_get_upload_dir(){
			return wp_upload_dir( null, false );
		}
	}

// if (!function_exists('cpm_wc_importer_get_upload_dir_var')) {
	function cpm_wc_importer_get_upload_dir_var($param, $subfolder = '')
	{
		$upload_dir = wp_get_upload_dir();
		$url = $upload_dir[$param];

		if ($param === 'baseurl' && is_ssl()) {
			$url = str_replace('http://', 'https://', $url);
		}

		return $url . $subfolder;
	}
// }


/*import main product files inlcudes simple and varitions product with its attribute, categories, product image, etc*/

// if (!function_exists('cpm_import_data_files')) {
	function cpm_import_data_files()
	{

		$files = cpm_wc_importer_get_upload_dir_var('baseurl', '/products');
		$data_files = $files . "/" . "data.csv";


		if (($open = fopen($data_files, "r")) !== FALSE) {
			// var_dump('file-open');
			// Skip the first line
			$first_row = true;
			$final_ata = array();
			$headers = array();

			while (($data = fgetcsv($open, 1000, ";")) !== FALSE) {

				if ($first_row) {
					$headers = $data;
					$first_row = false;
				} else {
					$final_ata[] = array_combine($headers, array_values($data));
				}
			}

			fclose($open);
			// echo "<pre>";
			// var_dump($importer_data_files);
			// echo "</pre>";
		}



		// var_dump($final_ata);die;
		foreach ($final_ata as $final_atas) {


			$V_PRIME_ID	= $final_atas['V_PRIME_ID'];
			$V_PRODUCTS_MODEL  = $final_atas['V_PRODUCTS_MODEL'];
			$product_sku = $V_PRIME_ID . '-' . $V_PRODUCTS_MODEL;
			$V_PRODUCTS_NAME_4_product_name	= $final_atas['V_PRODUCTS_NAME_4'];
			$product_price_with_zero = $final_atas['V_PRODUCTS_PRICE'];
			$product_base_price_with_zero = $final_atas['V_PRODUCTS_BASE_PRICE'];
			$product_weight = $final_atas['V_PRODUCTS_WEIGHT'];
			$v_product_quantity = $final_atas['V_PRODUCTS_QUANTITY'];
			$manufacture_name = $final_atas['V_MANUFACTURERS_NAME'];
			$product_category_id = $final_atas['V_PRIME_CATEGORY_ID'];
			$date_added = $final_atas['V_DATE_ADDED'];
			$tax_class_title = $final_atas['V_TAX_CLASS_TITLE'];
			$status = $final_atas['V_STATUS'];
			$master_product_model = $final_atas['V_MASTER_PRODUCT_MODEL'];
			$is_master_product = $final_atas['V_IS_MASTER_PRODUCT'];

			$image_path_product = $final_atas['V_PRODUCTS_IMAGE'];
			$get_image_files = cpm_wc_importer_get_upload_dir_var('baseurl', '/pics');
			$imagePath = esc_url($get_image_files . $image_path_product);


			$final_product_size =  cpm_wc_importer_pim_data_files_for_size($product_sku);
			if (strpos($final_product_size, ',')) {

					$final_product_size = str_replace(',', '.', $final_product_size);
			}

			$remove_zero = 10000;

			$convert_product_stock =  str_replace(',', '', $v_product_quantity);

			$product_stock = (int)($convert_product_stock) / $remove_zero;


			$convert_product_price =  str_replace(',', '', $product_price_with_zero);
			$product_price = $convert_product_price / $remove_zero;

			$convert_product_base_price =  str_replace(',', '', $product_base_price_with_zero);
			$product_base_price = $convert_product_base_price / $remove_zero;

			$product_status = 'publish';

			/*check post status */

			// if ($status == 'Active') {

			// 	$product_status = 'publish';
			// } else {
			// 	$product_status = 'draft';
			// }


			/*check if product stock status is in out of stock(0) and in stock (1)*/
			if ($product_stock == '0') {

				$product_stock_status = 'outofstock';
			} else {

				$product_stock_status = 'instock';
			}



			if ((!empty($V_PRODUCTS_MODEL)) && (!empty($master_product_model))) {

				global $wpdb;

				$results = $wpdb->get_results("select post_id,meta_value from $wpdb->postmeta where meta_value = '" . $master_product_model . "'", ARRAY_A);
				if (!empty($results)) {

					$post_id_parent = $results[0]['post_id'];
					$post_id_parent_modal_no  = $results[0]['meta_value'];

					if ($master_product_model == $post_id_parent_modal_no) {

						//  echo $master_product_model . 'P_SKU';
						$attachment_id = get_post_thumbnail_id($post_id_parent);

						$varition_p_size = $final_product_size;

						cpm_set_att($final_product_size, 'pa_size');
						if (!empty($varition_p_size)) {
							$variation_data =  array(
								'attributes' => array(
									'size'  => $varition_p_size,

								),
								'sku'           => $product_sku,
								'regular_price' => $product_base_price,
								'sale_price'    => $product_price,
								'stock_qty'		=>  $product_stock,
								'stock_status'  => $product_stock_status,
								'image_id'      => $attachment_id,

							);

							// var_dump($variation_data);

							$post_v_id = wc_get_product_id_by_sku($product_sku);

							if (!$post_v_id) {
								if (!empty($post_id_parent)) {

									cpm_wc_create_product_variation($post_id_parent, $variation_data);
								}
							}
						}
					}
				}
			}


			if ($is_master_product == 1) {

				# code...
				//echo $product_sku.'master';
				$get_parent_product_name = $V_PRODUCTS_NAME_4_product_name;
				$taxonomy   = 'pa_size'; // The taxonomy
				if (isset(get_term_by('name', $final_product_size, $taxonomy)->name)) {
					$term_id_att    = get_term_by('name', $final_product_size, $taxonomy)->name;
					// echo $term_id_att . 'cpm-attr';
				}


				$post_id = wc_get_product_id_by_sku($product_sku);

				if (!$post_id) {

					$product =  new WC_Product();
					$product->set_name($get_parent_product_name);
					$product->set_sku($product_sku);
					$product->set_status($product_status);
					$get_cat_name_v_product = cpm_wc_importer_get_product_category_name($product_category_id);
					$get_cat_id_v_p = cpm_wc_importer_get_category_id($get_cat_name_v_product);
					$att_id_product = cpm_wc_product_img_gallery_insert_attachment_from_url($imagePath, 0);
					$product->set_category_ids([$get_cat_id_v_p]);

					// Your product attribute settings
					$taxonomy   = 'pa_size'; // The taxonomy

					$attributes = (array) $product->get_attributes();

					// 1) If The product attribute is set for the product
					if (array_key_exists($taxonomy, $attributes)) {
						foreach ($attributes as $key => $attribute) {
							if ($key == $taxonomy) {
								$attribute->set_options(array($term_id_att));
								$attributes[$key] = $attribute;
								break;
							}
						}
						$product->set_attributes($attributes);
					}
					// 2. The product attribute is not set for the product
					$taxonomy   = 'pa_size'; // The taxonomy
					if (isset(get_term_by('name', $final_product_size, $taxonomy)->name)) {
						$term_id_att    = get_term_by('name', $final_product_size, $taxonomy)->name;
					} else {
						$attribute = new WC_Product_Attribute();

						$attribute->set_id(sizeof($attributes) + 1);
						$attribute->set_name($taxonomy);
						// $attribute->set_options(array($term_id_att));
						$attribute->set_position(sizeof($attributes) + 1);
						$attribute->set_visible(false);
						$attribute->set_variation(true);

						$attributes[] = $attribute;

						$product->set_attributes($attributes);
					}


					if (!empty($product_price)) {
						# code...

						$product->save();
					}

					wp_set_object_terms($product->get_id(), 'variable', 'product_type');
					$parent_id = $product->get_id(); // Or get the variable product id dynamically
					$parent_product_sku = $product->get_sku();
					update_post_meta($parent_id, '_product_modal_number', $V_PRODUCTS_MODEL);
					update_post_meta($parent_id, 'cpm_wc_importer_manufacturers_name_field', $manufacture_name);
					// And finally assign featured image to post
					
					set_post_thumbnail($parent_id, $att_id_product);
					/* functions add product gallery images if product has gallery image */
					cpm_wc_importer_pics($parent_product_sku);
					/* function add product tag  */
					cpm_wc_importer_pim_data_files($parent_id, $parent_product_sku);

					/*to update product data*/
				} else {

					$publish_date = get_the_date('Y-m-d H:i:s', $post_id);

					$status = strtotime($publish_date) > strtotime('today') ? 'future' : 'publish';
					wp_update_post(
						array(
							'ID' => $post_id,
							'post_title' => $get_parent_product_name,
							'edit_date' => true,
							'post_date' => $publish_date,
							'post_status' => $product_status,
							'post_date_gmt' => gmdate('Y-m-d H:i:s', strtotime($publish_date))
						)
					);
					$get_cat_name_v_product = cpm_wc_importer_get_product_category_name($product_category_id);
					$att_id_product = cpm_wc_product_img_gallery_insert_attachment_from_url($imagePath, 0);
					set_post_thumbnail($post_id, $att_id_product);
					$get_cat_id_v_p = cpm_wc_importer_get_category_id($get_cat_name_v_product);
					wp_set_post_terms($post_id, $get_cat_id_v_p, 'product_cat');
					$sku = get_post_meta( $post_id, '_sku', true );
					cpm_wc_importer_pics($sku);
				}
			} elseif ($master_product_model == '' && $is_master_product == 0) {

				$post_id = wc_get_product_id_by_sku($product_sku);

				if (!$post_id) {

					$product =  new WC_Product();
					$product->set_name($V_PRODUCTS_NAME_4_product_name);
					$product->set_sku($product_sku);
					// Prices
					if (empty($product_price)) {
						$product->set_price($product_base_price);
					} else {
						$product->set_price($product_price);
						$product->set_sale_price($product_price);
					}
					$product->set_regular_price($product_base_price);

					$product->set_status($product_status);
					$get_cat_name_s_p = cpm_wc_importer_get_product_category_name($product_category_id);
					$get_cat_id_s_p = cpm_wc_importer_get_category_id($get_cat_name_s_p);
					$product->set_category_ids([$get_cat_id_s_p]);
					$product->set_stock_status($product_stock_status);
					$product_sku = $product->get_sku();

					if (!empty($product_price)) {
						# code...

						$product->save();
					}


					// And finally assign featured image to post
					$att_id_product = cpm_wc_product_img_gallery_insert_attachment_from_url($imagePath, 0);
					set_post_thumbnail($product->get_id(), $att_id_product);
					/* function add product tag  */
					cpm_wc_importer_pim_data_files($product->get_id(), $product_sku);
					/* functions add product gallery images if product has gallery image */
					cpm_wc_importer_pics($product_sku);
					update_post_meta($product->get_id(), '_regular_price', $product_base_price);
					update_post_meta($product->get_id(), '_sale_price', $product_price);
					update_post_meta($product->get_id(), "_stock_status", $product_stock_status);
					update_post_meta($product->get_id(), 'cpm_wc_importer_manufacturers_name_field', $manufacture_name);
				} else {

					$publish_date = get_the_date('Y-m-d H:i:s', $post_id);

					$status = strtotime($publish_date) > strtotime('today') ? 'future' : 'publish';
					wp_update_post(
						array(
							'ID' => $post_id,
							'post_title' => $V_PRODUCTS_NAME_4_product_name,
							'edit_date' => true,
							'post_date' => $publish_date,
							'post_status' => $product_status,
							'post_date_gmt' => gmdate('Y-m-d H:i:s', strtotime($publish_date))
						)
					);

					// update_post_meta($post_id, '_price', $product_price);
					update_post_meta($post_id, '_regular_price', $product_base_price);
					update_post_meta($post_id, '_sale_price', $product_price);
					update_post_meta($post_id, "_stock_status", $product_stock_status);
					$get_cat_name_v_product = cpm_wc_importer_get_product_category_name($product_category_id);
					$get_cat_id_s_p = cpm_wc_importer_get_category_id($get_cat_name_v_product);
					wp_set_post_terms($post_id, $get_cat_id_s_p, 'product_cat');
					$att_id_product = cpm_wc_product_img_gallery_insert_attachment_from_url($imagePath, 0);
					set_post_thumbnail($post_id, $att_id_product);
					$sku = get_post_meta( $post_id, '_sku', true );
					cpm_wc_importer_pics($sku);

				}
			}
		} /*end of foreach*/
	}
//}

/*function to check if product attributes exist if not then create new ones*/

if (!function_exists('cpm_set_att')) {
	function cpm_set_att($term, $tax)
	{

		if (!empty($term) && (!empty($tax))) {
			if (!term_exists($term, $tax)) {
				wp_insert_term($term, $tax);
			}
		}
	}
}

if (!function_exists('cpm_wc_importer_pim_data_files_for_size')) {
	function cpm_wc_importer_pim_data_files_for_size($sku)
	{

		$files = cpm_wc_importer_get_upload_dir_var('baseurl', '/products');
		$pim_data_files_size = $files . "/" . "pim_data.csv";



		if (($open_pim_data_desc_size = fopen($pim_data_files_size, "r")) !== FALSE) {
			// var_dump('file-open');
			// Skip the first line
			fgetcsv($open_pim_data_desc_size);

			while (($data = fgetcsv($open_pim_data_desc_size, 1000, ";")) !== FALSE) {
				$importer_pim_data_files_size[] = $data;
			}

			fclose($open_pim_data_desc_size);
			// echo "<pre>";
			// var_dump($importer_pim_data_files_size);
			// echo "</pre>";
		}

		$count = count($importer_pim_data_files_size);

		for ($x = 0; $x <= $count; $x++) {
			if (!empty($importer_pim_data_files_size[$x][0])) {
				$pim_product_id   = $importer_pim_data_files_size[$x][0];
			}
			if (!empty($importer_pim_data_files_size[$x][1])) {
				$pim_product_modal   = $importer_pim_data_files_size[$x][1];
			}
			if (!empty($importer_pim_data_files_size[$x][3])) {
				$pim_product_size   = $importer_pim_data_files_size[$x][3];
				if (!empty($pim_product_size)) {
					$product_sku = $pim_product_id . '-' . $pim_product_modal;
					if ($product_sku == $sku) {
						return $pim_product_size;
					}
				}
			}
		}
	}
}

/* function to add product gallery image */
if (!function_exists('cpm_wc_importer_pics')) {
	function cpm_wc_importer_pics($p_sku)
	{

		$files = cpm_wc_importer_get_upload_dir_var('baseurl', '/products');
		$imp_pics = $files . "/" . "pics.csv";


		if (($open_pics_file = fopen($imp_pics, "r")) !== FALSE) {
			// var_dump('file-open');
			// Skip the first line
			fgetcsv($open_pics_file);

			while (($data = fgetcsv($open_pics_file, 1000, ";")) !== FALSE) {
				$pics_files[] = $data;
			}

			fclose($open_pics_file);
			// echo "<pre>";
			// var_dump($pics_files);
			// echo "</pre>";
		}
		$count = count($pics_files);


		if (!empty($count)) {

			for ($x = 0; $x <= $count; $x++) {
				if (!empty($pics_files[$x][0])) {
					$V_PRIME_ID = $pics_files[$x][0];
				}
				if (!empty($pics_files[$x][1])) {
					$V_PRODUCT_MODEL = $pics_files[$x][1];
				}
				// if (!empty($pics_files[$x][2])) {
				// 	$V_EXTRA_FILE = $pics_files[$x][2];
				// }
				if (!empty($pics_files[$x][2])) {
					$V_EXTRA_FILE = $pics_files[$x][2];
					$get_image_files = cpm_wc_importer_get_upload_dir_var('baseurl', '/pics');
					$imagePath_p_gallery = esc_url($get_image_files . $V_EXTRA_FILE);

					$product_sku = $V_PRIME_ID . '-' . $V_PRODUCT_MODEL;
					if ($product_sku == $p_sku) {
						$att_id = cpm_wc_product_img_gallery_insert_attachment_from_url($imagePath_p_gallery, 0);
						$at_id[] = $att_id;
						$new_a_id = implode(',', $at_id);

						$post_id = wc_get_product_id_by_sku($p_sku);
						update_post_meta($post_id, '_product_image_gallery', $new_a_id);
					}
				}
			}
		}
	}
}




function cpm_wc_product_img_gallery_insert_attachment_from_url($url, $parent_post_id = null)
{

	$imagePath_gallery = $url;
	/*check if image has valid size */
	if (getimagesize($imagePath_gallery)) {

		$imagePath_cpm_gallery = $imagePath_gallery;

		# code...
	} else {
		$imagePath_cpm_gallery =  WC()->plugin_url() . '/assets/images/placeholder.png';
	}
	$image_titlecpm_gallery = basename($imagePath_cpm_gallery);

	if (strpos($image_titlecpm_gallery, '%20')) {

		$image_titlecpm_gallery = str_replace('%20', ' ', $image_titlecpm_gallery);
	}


	if (!empty($image_titlecpm_gallery)) {
		# code...
		$product_image_title_gallery =  $image_titlecpm_gallery;


		$upload_dir       = wp_get_upload_dir(); // Set upload folder
		$image_data       = file_get_contents($imagePath_cpm_gallery); // Get image data


		// Check folder permission and define file location
		if (wp_mkdir_p($upload_dir['path'])) {
			$file = $upload_dir['path'] . '/' . $product_image_title_gallery;
		} else {
			$file = $upload_dir['basedir'] . '/' . $product_image_title_gallery;
		}

		// Create the image  file on the server
		file_put_contents($file, $image_data);

		$wp_filetype = wp_check_filetype($product_image_title_gallery, null);



		/*Set attachment data*/
		$attachment_data_cpm = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => ($image_titlecpm_gallery),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);




		//if image exist update else create it
		if (post_exists($image_titlecpm_gallery)) {
			$page = get_page_by_title($image_titlecpm_gallery, OBJECT, 'attachment');
			$attach_id = $page->ID;

			$attach_data = wp_generate_attachment_metadata($attach_id, $file); // Generate attachment data, filesize, height, width etc.

			wp_update_attachment_metadata($attach_id, $attach_data); // Add the above meta data


		} else {
			if (empty($parent_post_id)) {
				$parent_post_id = 0;
			}

			$attach_id = wp_insert_attachment($attachment_data_cpm, $file, $parent_post_id);

			$attach_data = wp_generate_attachment_metadata($attach_id, $file);

			wp_update_attachment_metadata($attach_id, $attach_data);
		}
	}

	return $attach_id;
}

if (!function_exists('cpm_wc_importer_pim_data_files')) {
	function cpm_wc_importer_pim_data_files($product_id, $p_sku)
	{

		$files = cpm_wc_importer_get_upload_dir_var('baseurl', '/products');
		$pim_data__files = $files . "/" . "pim_data.csv";



		if (($open_pim_data_desc = fopen($pim_data__files, "r")) !== FALSE) {
			// var_dump('file-open');
			// Skip the first line
			fgetcsv($open_pim_data_desc);

			while (($data = fgetcsv($open_pim_data_desc, 1000, ";")) !== FALSE) {
				$importer_pim_data_files[] = $data;
			}

			fclose($open_pim_data_desc);
			// echo "<pre>";
			// var_dump($importer_pim_data_files);
			// echo "</pre>";
		}

		$count = count($importer_pim_data_files);

		for ($x = 0; $x <= $count; $x++) {
			if (!empty($importer_pim_data_files[$x][0])) {
				$pim_product_id   = $importer_pim_data_files[$x][0];
			}
			if (!empty($importer_pim_data_files[$x][1])) {
				$pim_product_modal   = $importer_pim_data_files[$x][1];
			}
			if (!empty($importer_pim_data_files[$x][4])) {
				$product_tags   = utf8_encode($importer_pim_data_files[$x][4]);



				$final_tag = explode(',', $product_tags);
				$product_sku = $pim_product_id . '-' . $pim_product_modal;
				if ($product_sku == $p_sku) {
					wp_set_object_terms($product_id, $final_tag, 'product_tag');
				}
			}
		}
	}
}


/*functions to create categories from importer files*/
if (!function_exists('cpm_wc_importer_webgroups_files')) {
	function cpm_wc_importer_webgroups_files()
	{

		$files = cpm_wc_importer_get_upload_dir_var('baseurl', '/products');
		$webgroups_files = $files . "/" . "webgroups.csv";



		if (($open_web_group = fopen($webgroups_files, "r")) !== FALSE) {
			// var_dump('file-open');
			// Skip the first line
			fgetcsv($open_web_group);

			while (($data = fgetcsv($open_web_group, 1000, ";")) !== FALSE) {
				$importer_web_group_files[] = $data;
			}

			//Define the category


			fclose($open_web_group);
			// echo "<pre>";
			// var_dump($importer_web_group_files);
			// echo "</pre>";
		}
		$count = count($importer_web_group_files);



		for ($x = 0; $x <= $count; $x++) {

			if (!empty($importer_web_group_files[$x][0])) {
				$V_PRIME_CATEGORY_ID = $importer_web_group_files[$x][0];
			}
			// $V_PRIME_CATEGORY_PARENT_ID = $importer_web_group_files[$x][1];
			if (!empty($importer_web_group_files[$x][3])) {
				$V_CATEGORY_NAME_4 = utf8_encode($importer_web_group_files[$x][3]);
			}

			wp_insert_term($V_CATEGORY_NAME_4, 'product_cat');
			if (isset(get_term_by('name', $V_CATEGORY_NAME_4, 'product_cat')->term_id)) {
				$get_cat_id = get_term_by('name', $V_CATEGORY_NAME_4, 'product_cat')->term_id;
				update_term_meta($get_cat_id, '_cpm_wc_product_cat_id', $V_PRIME_CATEGORY_ID);
			}
		}
	}
}


/*functions to create product attributes. For now We only assign Attribute: SIZE*/

if (!function_exists('cpm_wc_importer_pim_data_description_files')) {
	function cpm_wc_importer_pim_data_description_files()
	{

		$files = cpm_wc_importer_get_upload_dir_var('baseurl', '/products');
		$pim_data_description_files = $files . "/" . "pim_data_description.csv";



		if (($open_pim_data_desc = fopen($pim_data_description_files, "r")) !== FALSE) {
			// var_dump('file-open');
			// Skip the first line
			fgetcsv($open_pim_data_desc);

			while (($data = fgetcsv($open_pim_data_desc, 1000, ",")) !== FALSE) {
				$importer_pim_data_desc_files[] = $data;
			}

			fclose($open_pim_data_desc);
			// echo "<pre>";
			// var_dump($importer_pim_data_desc_files);
			// echo "</pre>";
		}

		$count = count($importer_pim_data_desc_files);

		for ($x = 0; $x <= $count; $x++) {
			if (!empty($importer_pim_data_desc_files[$x][0])) {
				$FIELD_NAME   = $importer_pim_data_desc_files[$x][0];
			}

			if ($FIELD_NAME == "P_SIZE") {


				$filed_namee = "SIZE";

				$args = array(

					'name'   => __($filed_namee, 'cpm-wc-importer'),
					'type'    => 'select',
					'orderby' => 'menu_order',
					'has_archives'  => false,
				);

				$result = wc_create_attribute($args);
			}
		}
	}
}




// Metabox HTML
if (!function_exists('cpm_wc_cimporter_manufacturers_name_field_callback')) {
	function cpm_wc_cimporter_manufacturers_name_field_callback($post)
	{

		wp_nonce_field('cpm_wc_importer_manufacture_nonce', 'cpm_wc_importer_manufactures_name_fields_nonce');

		$cpm_wc_importer_manufacturers_name_field = get_post_meta($post->ID, 'cpm_wc_importer_manufacturers_name_field', true);

?>
		<div id="cpm_wc_importer_section" class="cpm_wc_importer_section-wrap">
			<div class="form-group">
				<input type="text" name="cpm_wc_importer_manufacturers_name_field" class="cpm_wc_importer_manufacutrers_name" value="<?php echo ($cpm_wc_importer_manufacturers_name_field); ?>"><?php _e('Manufacturers Name', 'cpm-wc-importer'); ?><br>
			</div>
		</div>
<?php
	}
}



/*Function to get categories Id and match the id on importer files webgroups*/
if (!function_exists('cpm_wc_importer_get_product_category_name')) {
	function cpm_wc_importer_get_product_category_name($cat_id)
	{
		$files = cpm_wc_importer_get_upload_dir_var('baseurl', '/products');
		$webgroups_files = $files . "/" . "webgroups.csv";



		//echo $cat_id."parameter cat id";

		if (($open_webf = fopen($webgroups_files, "r")) !== FALSE) {
			// var_dump('file-open');
			// Skip the first line
			fgetcsv($open_webf);

			while (($data = fgetcsv($open_webf, 1000, ";")) !== FALSE) {
				$importer_data_files_web[] = $data;
			}

			fclose($open_webf);
			// echo "<pre>";
			// var_dump($importer_data_files_web);
			// echo "</pre>";
		}
		if (!empty($cat_id)) {
			$get_parm_cat_id = (array($cat_id));
			if (!empty($get_parm_cat_id)) {
				# code...


				//var_dump($get_parm_cat_id);

				foreach ($get_parm_cat_id as $key1) {
					# code...

					foreach ($importer_data_files_web as $key => $imp_data_web) {
						$category_id = $imp_data_web[0];
						$cat_id_name = $imp_data_web[3];
						if ($key1 == $category_id) {
							$final_cat_file = $cat_id_name;
							return $final_cat_file;
						}
					}
				}
			}
		}
	}
}


if (!function_exists('cpm_wc_importer_get_category_id')) {
	function cpm_wc_importer_get_category_id($cat_name)
	{
		if (isset(get_term_by('name', $cat_name, 'product_cat')->term_id)) {
			$catData = get_term_by('name', $cat_name, 'product_cat')->term_id;
			return ($catData);
		}
	}
}





/**
 * Create a product variation for a defined variable product ID.
 *
 * @since 3.0.0
 * @param int   $product_id | Post ID of the product parent variable product.
 * @param array $variation_data | The data to insert in the product.
 */

if (!function_exists('cpm_wc_create_product_variation')) {
	function cpm_wc_create_product_variation($product_id, $variation_data)
	{
		// Get the Variable product object (parent)
		$product = wc_get_product($product_id);
		$productname = $product->get_title;

		//$product_permalink = $product->get_permalink();

		$variation_post = array(
			'post_title'  => $productname,
			'post_name'   => $productname . '-' . $product_id . '-variation',
			'post_status' => 'publish',
			'post_parent' => $product_id,
			'post_type'   => 'product_variation',
			// 'guid'        => ''
		);


		// Creating the product variation
		$variation_id = wp_insert_post($variation_post);


		update_post_meta($variation_id, '_stock_status', $variation_data['stock_status']);
		add_post_meta($variation_id, '_thumbnail_id', $variation_data['image_id']);
		// Get an instance of the WC_Product_Variation object
		$variation = new WC_Product_Variation($variation_id);

		foreach ($variation as $var_p) {

			update_post_meta($var_p['variation_id'], '_thumbnail_id', $variation_data['image_id']);
			# code...
		}

		// $variation->set_gallery_image_ids($attachment_id);
		set_post_thumbnail($variation_id, $variation_data['image_id']);


		// Iterating through the variations attributes
		foreach ($variation_data['attributes'] as $attribute => $term_name) {
			$taxonomy = 'pa_' . $attribute;


			// If taxonomy doesn't exists we create it (Thanks to Carl F. Corneil)
			if (!taxonomy_exists($taxonomy)) {
				register_taxonomy(
					$taxonomy,
					'product_variation',
					array(
						'hierarchical' => false,
						'label' => ucfirst($taxonomy),
						'query_var' => true,
						'rewrite' => array('slug' => '$taxonomy') // The base slug
					)
				);
			}

			// Check if the Term name exist and if not we create it.
			if (!term_exists($term_name, $taxonomy))
				wp_insert_term($term_name, $taxonomy); // Create the term

			$term_slug = get_term_by('name', $term_name, $taxonomy)->slug; // Get the term slug

			// echo $term_slug."term name";

			// Get the post Terms names from the parent variable product.
			$post_term_names =  wp_get_post_terms($product_id, $taxonomy, array('fields' => 'names'));

			// Check if the post term exist and if not we set it in the parent variable product.
			if (!in_array($term_name, $post_term_names))
				wp_set_post_terms($product_id, $term_name, $taxonomy, true);

			// Set/save the attribute data in the product variation
			update_post_meta($variation_id, 'attribute_' . $taxonomy, $term_slug);
		}

		## Set/save all other data

		// SKU
		if (!empty($variation_data['sku']))
			$variation->set_sku($variation_data['sku']);

		/* image */
		if (!empty($variation_data['image_id']))
			$variation->set_image_id($variation_data['image_id']);

		// Prices
		if (empty($variation_data['sale_price'])) {
			$variation->set_price($variation_data['regular_price']);
		} else {
			$variation->set_price($variation_data['sale_price']);
			$variation->set_sale_price($variation_data['sale_price']);
		}
		$variation->set_regular_price($variation_data['regular_price']);

		// Stock
		if (!empty($variation_data['stock_qty'])) {
			// $variation->set_stock_quantity( );
			// $variation->set_manage_stock(true);
			$variation->set_stock_status($variation_data['stock_status']);
		} else {
			$variation->set_manage_stock(false);
		}

		$variation->save(); // Save the data
	}
}


/*cron job server script from command line args*/

global $argv;
error_reporting(E_ALL ^E_WARNING);
$date = date('Y/m/d H:i:s');
// var_dump($argv);
//If we have a command line argument
if (!empty($argv[1])) {
    switch ($argv[1]) {
        case "cpm_importer_run_files":
            cpm_importer_run_files(); //Call our function
            echo "Product Cron run sucessfully On ->".$date; //Print what we did to the console
            break;
    }
}
