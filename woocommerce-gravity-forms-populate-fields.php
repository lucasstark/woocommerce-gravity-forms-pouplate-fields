<?php
/*
 *  Plugin Name:  WooCommerce Gravity Forms Populate Fields
 *  Description:  Allows Gravity Forms Fields to be populated via product attributes.  Designed to work with WooCommerce Gravity Forms Product Addons only.
 *  Version: 1.0
 *  Author: Element Stark
 *  Author URI:  https://www.elementstark.com
 *  Tested up to: 5.3
 *
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

		foreach ( $form['fields'] as &$field ) {

			if ( $field->type != 'select' || strpos( $field->inputName, 'wc_attribute_' ) === false ) {
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
