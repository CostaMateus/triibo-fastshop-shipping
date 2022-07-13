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
    private $_local_env     = true;

    private $_api_url_hml   = "http://php-hml.triibo.com.br";           // HOMOLOG
    private $_api_key_hml   = "fastshopappkey-triibo-AQPPTC";           // HOMOLOG
    private $_api_token_hml = "7472216240776562417070486d4c6170693139"; // HOMOLOG

    private $_api_url       = "http://php.triibo.com.br";               // PROD
    private $_api_key       = "fastshopappkey-triibo-AQPPTC";           // PROD
    private $_api_token     = "50083473200638420676844774754275516748"; // PROD

	protected $package      = null;

    private $header         = null;
    private $body           = null;

	/**
	 * Initialize webservice.
	 *
	 * @param array $package Shipping package.
	 */
	public function __construct( $package = array() )
    {
		$this->package = $package;
        $this->get_headers();
        $this->process_package();
	}

    /**
     * Headers request
     *
     * @return array
     */
    private function get_headers()
    {
        $key   = ($this->_local_env) ? $this->_api_key_hml   : $this->_api_key;
        $token = ($this->_local_env) ? $this->_api_token_hml : $this->_api_token;

        $this->header = [
            "Accept: application/json",
            "Content-Type: application/json",
            "api-key: $key",
            "api-token: $token",
        ];
    }

	/**
	 * Process the package
	 *
	 * @return bool
	 */
	private function process_package()
    {
        $postalCode = $this->package["destination"]["postcode"];
        $items      = [];

        foreach ($this->package["contents"] as $content)
        {
            $product = wc_get_product( $content["product_id"] );

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
        $url = ($this->_local_env) ? $this->_api_url_hml : $this->_api_url;

        $ch  = curl_init();

		curl_setopt_array( $ch, [
			CURLOPT_URL            => "$url/api/marketplace/wc/freightcalculation",
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_ENCODING       => "",
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
			// CURLOPT_SSL_VERIFYHOST => FALSE,
			// CURLOPT_SSL_VERIFYPEER => FALSE,
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
        $url = ($this->_local_env) ? $this->_api_url_hml : $this->_api_url;

        $sku = $this->body["items"][0]["sku"];

        $ch  = curl_init();

		curl_setopt_array( $ch, [
			CURLOPT_URL            => "$url/api/marketplace/wc/updatesku/$sku",
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_ENCODING       => "",
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => true,
			// CURLOPT_SSL_VERIFYHOST => FALSE,
			// CURLOPT_SSL_VERIFYPEER => FALSE,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => "POST",
			CURLOPT_HTTPHEADER     => $this->header,
        ]);

        curl_exec( $ch );
        curl_close( $ch );
    }
}
