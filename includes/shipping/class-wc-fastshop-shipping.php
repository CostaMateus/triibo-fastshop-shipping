<?php
/**
 * Triibo Fastshop Shipping.
 *
 * @package Triibo_Fastshop_Shipping/Classes/Shipping
 * @since   1.0.0
 * @version 1.0.0
 */

if ( ! defined( "ABSPATH" ) ) exit;

/**
 * Fastshop shipping method class.
 */
class WC_Fastshop_Shipping extends WC_Shipping_Method
{
    /**
     * Initialize the Correios shipping method.
     *
     * @param int $instance_id Shipping zone instance ID.
     */
    public function __construct( $instance_id = 0 )
    {
        $this->instance_id        = absint( $instance_id );

        $this->id                 = "fastshop-shipping";                  // Id for your shipping method. Should be uunique.
        $this->method_title       = __( "Fast Shop" );                    // Title shown in admin
        $this->method_description = __( "Entrega feita pela Fast Shop" ); // Description shown in admin

        $this->enabled            = "yes";                                // This can be added as an setting but for this example its forced enabled
        $this->title              = "Fast Shop";                          // This can be added as an setting but for this example its forced.

        $this->supports           = array(
            "shipping-zones",
            "instance-settings",
        );

        $this->init();
    }

    /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    function init()
    {
        // Load the settings API
        // This is part of the settings API.
        // Override the method to add your own settings
        $this->init_form_fields();

        // Define user set variables.
        $this->enabled            = $this->get_option( "enabled" );
        $this->title              = $this->get_option( "title" );
        $this->shipping_class_id  = ( int ) $this->get_option( "shipping_class_id", "0" );
        $this->show_delivery_time = $this->get_option( "show_delivery_time" );
        $this->additional_time    = $this->get_option( "additional_time" );
        // $this->fee                = $this->get_option( "fee" );

        // Save settings in admin if you have any defined
        add_action( "woocommerce_update_options_shipping_" . $this->id, array( $this, "process_admin_options" ) );
    }

    /**
     * Admin options fields.
     */
    public function init_form_fields()
    {
        $this->instance_form_fields = array(
            "enabled"            => array(
                "title"       => __( "Habilitar/Desabilitar" ),
                "type"        => "checkbox",
                "label"       => __( "Ativar este método de envio" ),
                "default"     => "yes",
            ),
            "title"              => array(
                "title"       => __( "Título" ),
                "type"        => "text",
                "description" => __( "Isto controla o título que o usuário vê durante o checkout." ),
                "desc_tip"    => true,
                "default"     => $this->method_title,
            ),
            "behavior_options"   => array(
                "title"       => __( "Opções de comportamento" ),
                "type"        => "title",
                "default"     => "",
            ),
            "shipping_class_id"  => array(
                "title"       => __( "Classe de entrega" ),
                "type"        => "select",
                "description" => __( "Se necessário, selecione uma classe de envio para aplicar neste método." ),
                "desc_tip"    => true,
                "default"     => "",
                "class"       => "wc-enhanced-select",
                "options"     => $this->get_shipping_classes_options(),
            ),
            "show_delivery_time" => array(
                "title"       => __( "Tempo de entrega" ),
                "type"        => "checkbox",
                "label"       => __( "Exibir estimativa de entrega" ),
                "description" => __( "Exibir o tempo estimado de entrega em dias úteis." ),
                "desc_tip"    => true,
                "default"     => "no",
            ),
            "additional_time"    => array(
                "title"       => __( "Dias adicionais" ),
                "type"        => "text",
                "description" => __( "Dias úteis adicionais para a estimativa de entrega." ),
                "desc_tip"    => true,
                "default"     => "0",
                "placeholder" => "0",
            ),
            // "fee"                => array(
            //     "title"       => __( "Taxa extra de envio" ),
            //     "type"        => "price",
            //     "description" => __( "Digite um valor, por exemplo: 2.50, ou uma porcentagem, por exemplo, 5%. Deixe em branco para desabilitar." ),
            //     "desc_tip"    => true,
            //     "placeholder" => "0.00",
            //     "default"     => "",
            // ),
        );
    }

    /**
     * Get shipping classes options.
     *
     * @return array
     */
    protected function get_shipping_classes_options()
    {
        $shipping_classes = WC()->shipping->get_shipping_classes();
        $options          = array(
            "-1" => __( "Qualquer classe" ),
            "0"  => __( "Nenhuma classe" ),
        );

        if ( !empty( $shipping_classes ) )
        {
            $options += wp_list_pluck( $shipping_classes, "name", "term_id" );
        }

        return $options;
    }

    /**
     * Check if package uses only the selected shipping class.
     *
     * @param  array $package Cart package.
     * @return bool
     */
    protected function has_only_selected_shipping_class( $package )
    {
        $only_selected = true;

        if ( -1 === $this->shipping_class_id )
        {
            return $only_selected;
        }

        foreach ( $package["contents"] as $item_id => $values )
        {
            $product = $values["data"];
            $qty     = $values["quantity"];

            if ( $qty > 0 && $product->needs_shipping() )
            {
                if ( $this->shipping_class_id !== $product->get_shipping_class_id() )
                {
                    $only_selected = false;
                    break;
                }
            }
        }

        return $only_selected;
    }

    /**
     * calculate_shipping function.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping( $package = array() )
    {
        // Check if valid to be calculeted
        if ( "" === $package["destination"]["postcode"] || "BR" !== $package["destination"]["country"] )
        {
            return;
        }

        // Check if complete postcode
        $postcode = strlen($package["destination"]["postcode"]);
        if ( $postcode < 8 || $postcode > 9 )
        {
            return;
        }

        // Check for shipping classes.
        if ( ! $this->has_only_selected_shipping_class( $package ) )
        {
            return;
        }

        // executa a consulta a api
        $shipping = $this->get_rate( $package );

        if ($shipping["errorCode"])
        {
            $nl = PHP_EOL . "==========================" . PHP_EOL . "Return calculate_shipping" . PHP_EOL . date("Y-m-d H:i:s") . PHP_EOL;
            file_put_contents( plugin_dir_path( WC_TRIIBO_FASTSHOP_PLUGIN_FILE ) . "shipping.log", $nl . json_encode($shipping), FILE_APPEND );
            return;
        }

        foreach ($shipping["data"] as $s)
        {
            $cost = $s["deliveryPrice"];

            // // Exit if don't have price
            // if ( 0 === intval( $cost ) ) return;
            //
            // // Apply fees.
            // $aux_cost = ( intval( $cost ) < 10 ) ? 10 : $cost;
            // $fee      = $this->get_fee( $this->fee, $aux_cost );
            // $cost     = (float) $cost + (float) $fee;

            // Display delivery
            $meta_delivery = [];
            if ( "yes" === $this->show_delivery_time )
            {
                $meta_delivery = [
                    "_delivery_forecast" => intval( $s["deliveryTime"] ) + intval( $this->additional_time ),
                ];
            }

            // Create the rate
            $rate = [
                "id"             => $this->id . $this->instance_id,
                "label"          => $this->title,
                "cost"           => (float) $cost,
                "calc_tax"       => "per_item",
                "meta_data"      => $meta_delivery,
            ];

            // Register the rate
            $this->add_rate( $rate );
        }
    }


	/**
	 * Get shipping rate.
	 *
	 * @param  array $package Cart package.
	 *
	 * @return SimpleXMLElement|null
	 */
	protected function get_rate( $package )
    {
		$api      = new WC_Triibo_Webservice( $package );
		$shipping = $api->get_shipping();

		return $shipping;
	}
}
