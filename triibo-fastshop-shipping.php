<?php
/**
 * Plugin Name:          #Triibo_Fastshop_Shipping
 * Description:          Triibo Fast Shop Frete - WooCommerce e WooCommerce-Correios
 * Author:               Mateus Costa
 * Author URI:           https://costamateus.com.br/
 * Version:              2.0.0
 * Text Domain:          triibo-fastshop-shipping
 * WC requires at least: 3.6
 * WC tested up to:      5.8
 *
 * @package Triibo_Fastshop_Shipping
 */

if ( ! defined( "ABSPATH" ) ) exit;

if ( ! is_plugin_active( "woocommerce/woocommerce.php" ) ) exit;

if ( ! is_plugin_active( "woocommerce-correios/woocommerce-correios.php" ) ) exit;

define( "WC_TRIIBO_FASTSHOP_PLUGIN_FILE", __FILE__ );

if ( ! class_exists( "WC_Triibo_Fastshop_Shipping" ) )
{
	include_once dirname( __FILE__ ) . "/includes/class-wc-triibo-fastshop-shipping.php";

	// init plugin
	add_action( "plugins_loaded", array( "WC_Triibo_Fastshop_Shipping", "init" ) );

	// update stock when added to cart
	add_action( "woocommerce_add_to_cart", "TSF_WC_update_stock" , 10, 6 );
    function TSF_WC_update_stock( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data  )
    {
        $product = wc_get_product( $product_id );
        $sku     = $product->get_sku();
        $split   = explode( "FS-", $sku );

        if ( count( $split ) > 1 )
        {
            $api = new WC_Triibo_Webservice();
            $api->update_stock( $sku );
        }

        return;
    }
}
