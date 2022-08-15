<?php
/**
 * Triibo Webservice
 *
 * @package Triibo_Fastshop_Shipping/Classes/Webservice
 * @since   1.0.0
 * @version 1.0.0
 */

if ( ! defined( "ABSPATH" ) ) exit;

/**
 * Triibo Webservice integration class.
 */
class WC_Triibo_Webservice
{
    const _api_url_hml   = "http://php-hml.triibo.com.br";           // HOMOLOG
    const _api_key_hml   = "fastshopappkey-triibo-AQPPTC";           // HOMOLOG
    const _api_token_hml = "7472216240776562417070486d4c6170693139"; // HOMOLOG

    const _api_url       = "http://php.triibo.com.br";               // PROD
    const _api_key       = "fastshopappkey-triibo-AQPPTC";           // PROD
    const _api_token     = "50083473200638420676844774754275516748"; // PROD

    private $url         = null;

	protected $package   = null;

    private $header      = null;
    private $body        = null;

	/**
	 * Initialize webservice.
	 *
	 * @param array $package Shipping package.
	 */
	public function __construct( $package = array() )
    {
        $_local_env    = ( strpos( home_url(), "dev-" ) !== FALSE ) ? true : false;

        $key           = $_local_env ? self::_api_key_hml   : self::_api_key;
        $token         = $_local_env ? self::_api_token_hml : self::_api_token;
        $this->url     = $_local_env ? self::_api_url_hml   : self::_api_url;

        $this->package = $package;

        $this->header  = [
            "Accept: application/json",
            "Content-Type: application/json",
            "api-key: $key",
            "api-token: $token",
        ];

        $this->process_package();
	}

	/**
	 * Process the package
	 *
	 * @return bool
	 */
	private function process_package()
    {
        $postalCode = $this->package[ "destination" ][ "postcode" ];
        $items      = [];

        foreach ( $this->package[ "contents" ] as $content )
        {
            $product = wc_get_product( $content[ "product_id" ] );

            $items[] = [
                "sku"      => $product->get_sku(),
                "quantity" => $content["quantity"]
            ];
        }

        $this->body = [
            "postalCode" => $postalCode,
            "items"      => $items
        ];
    }

	/**
	 * Get shipping prices.
	 *
	 * @return array
	 */
	public function get_shipping()
    {
        $url = $this->url . "/api/marketplace/wc/freightcalculation";

        $ch  = curl_init();

		curl_setopt_array( $ch, [
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_ENCODING       => "",
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => "POST",
			CURLOPT_HTTPHEADER     => $this->header,
            CURLOPT_POSTFIELDS     => json_encode( $this->body )
        ]);

        $response = curl_exec( $ch );
        curl_close( $ch );

        return json_decode( $response, TRUE );
    }

    public function update_stock()
    {
        $ch  = curl_init();

        foreach ( $this->body[ "items" ] as $item )
        {
            $sku = $item[ "sku" ];

            $url = $this->url . "/api/marketplace/wc/updatesku/$sku";

            curl_setopt_array( $ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_ENCODING       => "",
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => "POST",
                CURLOPT_HTTPHEADER     => $this->header,
            ]);

            curl_exec( $ch );
        }

        curl_close( $ch );
    }
}
