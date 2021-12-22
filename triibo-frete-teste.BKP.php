<?php
/*
    Plugin Name: #Triibo Frete Teste
    Description: Triibo Frete Teste
    Version: 1.0.0
    Author: MateusCosta
*/

/**
 * Check if WooCommerce is active
 */
if ( in_array( "woocommerce/woocommerce.php", apply_filters( "active_plugins", get_option( "active_plugins" ) ) ) )
{

    add_action( "woocommerce_shipping_init", "your_shipping_method_init" );
    function your_shipping_method_init(  )
    {
        if ( !class_exists( "WC_Your_Shipping_Method" ) )
        {
            class WC_Your_Shipping_Method extends WC_Shipping_Method
            {
                /**
                 * Initialize the Correios shipping method.
                 *
                 * @param int $instance_id Shipping zone instance ID.
                 */
                public function __construct( $instance_id = 0 )
                {
                    $this->instance_id        = absint( $instance_id );

                    $this->id                 = "your_shipping_method";                      // Id for your shipping method. Should be uunique.
                    $this->method_title       = __( "Your Shipping Method" );                // Title shown in admin
                    $this->method_description = __( "Description of your shipping method" ); // Description shown in admin

                    $this->enabled            = "yes";                                       // This can be added as an setting but for this example its forced enabled
                    $this->title              = "My Shipping Method";                        // This can be added as an setting but for this example its forced.

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
                    $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
                    $this->init_settings();    // This is part of the settings API. Loads settings you previously init.

                    // Define user set variables.
                    $this->enabled            = $this->get_option( "enabled" );
                    $this->title              = $this->get_option( "title" );
                    $this->shipping_class_id  = ( int ) $this->get_option( "shipping_class_id", "0" );
                    $this->show_delivery_time = $this->get_option( "show_delivery_time" );
                    $this->additional_time    = $this->get_option( "additional_time" );
                    $this->fee                = $this->get_option( "fee" );
                    // $this->debug              = $this->get_option( "debug" );

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
                            "title"   => __( "Enable/Disable", "woocommerce-correios" ),
                            "type"    => "checkbox",
                            "label"   => __( "Enable this shipping method", "woocommerce-correios" ),
                            "default" => "yes",
                         ),
                        "title"              => array(
                            "title"       => __( "Title", "woocommerce-correios" ),
                            "type"        => "text",
                            "description" => __( "This controls the title which the user sees during checkout.", "woocommerce-correios" ),
                            "desc_tip"    => true,
                            "default"     => $this->method_title,
                         ),
                        "behavior_options"   => array(
                            "title"   => __( "Behavior Options", "woocommerce-correios" ),
                            "type"    => "title",
                            "default" => "",
                         ),
                        "shipping_class_id"  => array(
                            "title"       => __( "Shipping Class", "woocommerce-correios" ),
                            "type"        => "select",
                            "description" => __( "If necessary, select a shipping class to apply this method.", "woocommerce-correios" ),
                            "desc_tip"    => true,
                            "default"     => "",
                            "class"       => "wc-enhanced-select",
                            "options"     => $this->get_shipping_classes_options(  ),
                         ),
                        "show_delivery_time" => array(
                            "title"       => __( "Delivery Time", "woocommerce-correios" ),
                            "type"        => "checkbox",
                            "label"       => __( "Show estimated delivery time", "woocommerce-correios" ),
                            "description" => __( "Display the estimated delivery time in working days.", "woocommerce-correios" ),
                            "desc_tip"    => true,
                            "default"     => "no",
                         ),
                        "additional_time"    => array(
                            "title"       => __( "Additional Days", "woocommerce-correios" ),
                            "type"        => "text",
                            "description" => __( "Additional working days to the estimated delivery.", "woocommerce-correios" ),
                            "desc_tip"    => true,
                            "default"     => "0",
                            "placeholder" => "0",
                         ),
                        "fee"                => array(
                            "title"       => __( "Handling Fee", "woocommerce-correios" ),
                            "type"        => "price",
                            "description" => __( "Enter an amount, e.g. 2.50, or a percentage, e.g. 5%. Leave blank to disable.", "woocommerce-correios" ),
                            "desc_tip"    => true,
                            "placeholder" => "0.00",
                            "default"     => "",
                         ),

                        // "testing"            => array(
                        //     "title"   => __( "Testing", "woocommerce-correios" ),
                        //     "type"    => "title",
                        //     "default" => "",
                        // ),
                        // "debug"              => array(
                        //     "title"       => __( "Debug Log", "woocommerce-correios" ),
                        //     "type"        => "checkbox",
                        //     "label"       => __( "Enable logging", "woocommerce-correios" ),
                        //     "default"     => "no",
                        //     /* translators: %s: method title */
                        //     "description" => sprintf( __( "Log %s events, such as WebServices requests.", "woocommerce-correios" ), $this->method_title ) . $this->get_log_link(  ),
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
                        "-1" => __( "Any Shipping Class", "woocommerce-correios" ),
                        "0"  => __( "No Shipping Class", "woocommerce-correios" ),
                     );

                    if ( !empty( $shipping_classes ) ) {
                        $options += wp_list_pluck( $shipping_classes, "name", "term_id" );
                    }

                    return $options;
                }

                /**
                 * calculate_shipping function.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping( $package )
                {
                    triibo_fs_log( "calculate_shipping", $package );

                    $rate = array(
                        "label"    => $this->title,
                        "cost"     => "10.99",
                        "calc_tax" => "per_item"
                     );

                    // Register the rate
                    $this->add_rate( $rate );
                }
            }
        }
    }

    add_filter( "woocommerce_shipping_methods", "add_your_shipping_method" );
    function add_your_shipping_method( $methods )
    {
        $methods["your_shipping_method"] = "WC_Your_Shipping_Method";
        return $methods;
    }

    /**
     * Registra logs
     */
    function triibo_fs_log( $function, $data )
    {
        $nl  = PHP_EOL . "==========================" . PHP_EOL;
        $nl .= "Return $function"                     . PHP_EOL;
        $nl .= date("Y-m-d H:i:s")                    . PHP_EOL;

        file_put_contents(
            plugin_dir_path( __FILE__ ) . "log-" . date("Ymd") . ".log",
            $nl . json_encode( $data, TRUE ),
            FILE_APPEND
        );
    }

}
