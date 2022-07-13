<?php
/*
 * Plugin Name:  WooCommerce Gravity Forms Populate Fields
 * Description:  Allows Gravity Forms Fields to be populated via product attributes.  Designed to work with WooCommerce Gravity Forms Product Addons only.
 * Plugin URI:
 * Version: 1.0.1
 * Author: Element Stark
 * Author URI: https://www.elementstark.com/
 * Developer: Lucas Stark
 * Developer URI: http://www.elementstark.com/
 * Requires at least: 3.1
 * Tested up to: 6.0

 * Copyright: © 2009-2022 Element Stark.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html

 * WC requires at least: 3.0.0
 * WC tested up to: 6.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}


class WC_Gravity_Forms_Populate_Fields {

	private static $instance;

	public static function register() {

		if ( self::$instance == null ) {
			self::$instance = new WC_Gravity_Forms_Populate_Fields();
		}

	}


	protected function __construct() {
		//Set it up globally for all forms.
		add_filter( 'gform_pre_render', [ $this, 'populate_attributes' ] );
		add_filter( 'gform_pre_validation', [ $this, 'populate_attributes' ] );
		add_filter( 'gform_pre_submission_filter', [ $this, 'populate_attributes' ] );
	}

	public function populate_attributes( $form ) {
		global $product; //we are assuming this is on the single product page, not being used with the product_page shortcode.

		if ( empty( $product ) ) {
			return $form;
		}

		foreach ( $form['fields'] as &$field ) {

			if ( ( $field->type != 'select' && $field->type != 'option' ) || strpos( $field->inputName, 'wc_attribute_' ) === false ) {
				continue;
			}

			$attribute_name = str_replace( 'wc_attribute_', '', $field->inputName );
			$attributes     = $product->get_attributes();

			if ( isset( $attributes[ $attribute_name ] ) ) {
				$attribute_object = $attributes[ $attribute_name ];
			} elseif ( isset( $attributes[ 'pa_' . $attribute_name ] ) ) {
				$attribute_object = $attributes[ 'pa_' . $attribute_name ];
			} else {
				return $form;
			}

			$attribute_terms = [];
			if ( $attribute_object->is_taxonomy() ) {
				$attribute_terms = wc_get_product_terms( $product->get_id(), $attribute_object->get_name(), array( 'fields' => 'names' ) );
			} else {
				$attribute_terms = $attribute_object->get_options();
			}

			$choices = [];

			foreach ( $attribute_terms as $attribute ) {
				$choices[] = array( 'text' => $attribute, 'value' => $attribute );
			}

			$field->choices = $choices;

		}

		return $form;

	}

}

if ( is_woocommerce_active() ) {
	WC_Gravity_Forms_Populate_Fields::register();
}
