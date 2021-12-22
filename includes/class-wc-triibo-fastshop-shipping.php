<?php
/**
 * Fastshop
 *
 * @package Triibo_Fastshop_Shipping/Classes
 * @since   1.0.0
 * @version 1.0.0
 */

if ( ! defined( "ABSPATH" ) ) exit;

/**
 * Plugins main class.
 */
class WC_Triibo_Fastshop_Shipping
{
	/**
	 * Initialize the plugin public actions.
	 */
	public static function init()
	{
		// add_action( "init", array( __CLASS__, "load_plugin_textdomain" ), -1 );

        self::includes();

        // if ( is_admin() )
        // {
        //     self::admin_includes();
        // }

        add_filter( "woocommerce_shipping_methods", array( __CLASS__, "include_methods" ) );
    }

	// /**
	//  * Load the plugin text domain for translation.
	//  */
	// public static function load_plugin_textdomain() {
	// 	load_plugin_textdomain( "triibo-fastshop-shipping", false, dirname( plugin_basename( WC_FASTSHOP_FRETE_PLUGIN_FILE ) ) . "/languages/" );
	// }

	/**
	 * Includes.
	 */
	private static function includes()
    {
        include_once dirname( __FILE__ ) . "/shipping/class-wc-fastshop-shipping.php";
		include_once dirname( __FILE__ ) . "/class-wc-triibo-fastshop-cart.php";
		include_once dirname( __FILE__ ) . "/class-wc-triibo-webservice.php";
    }

	/**
	 * Admin includes.
	 */
	private static function admin_includes()
    {
		// include_once dirname( __FILE__ ) . "/admin/class-wc-fastshop-admin-orders.php";
	}

	/**
	 * Include Fastshop shipping to WooCommerce.
	 *
	 * @param  array $methods Default shipping methods.
	 *
	 * @return array
	 */
	public static function include_methods( $methods )
    {
        $methods["fastshop-shipping"] = "WC_Fastshop_Shipping";

        return $methods;
    }

	/**
	 * Get main file.
	 *
	 * @return string
	 */
	public static function get_main_file()
	{
		return WC_TRIIBO_FASTSHOP_PLUGIN_FILE;
	}

	/**
	 * Get plugin path.
	 *
	 * @return string
	 */
	public static function get_plugin_path()
	{
		return plugin_dir_path( WC_TRIIBO_FASTSHOP_PLUGIN_FILE );
	}

	// /**
	//  * Get templates path.
	//  *
	//  * @return string
	//  */
	// public static function get_templates_path()
	// {
	// 	return self::get_plugin_path() . "templates/";
	// }

}
