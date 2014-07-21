<?php
/*
Plugin Name: WooCommerce Pesapal Standard Gateway
Plugin URI:  https://jonathan-kosgei.github.io/woocommerce-pesapal-standard-gateway/
Description: Extends WooCommerce with a Pesapal gateway.
Version: 1.0
Author: Jonathan Kosgei
Author URI: https://github.com/jonathan-kosgei
 
	Copyright: © 2009-2011 Jonathan Kosgei.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
 
add_action('plugins_loaded', 'woocommerce_pesapal_standard_init', 0);
 
function woocommerce_pesapal_standard_init() {
 
	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
 
	require_once'includes/OAuth.php';
	/**
 	 * Gateway class
 	 */
	class WC_Pesapal_Standard extends WC_Payment_Gateway {
	
	       public function __construct(){
          $this->id           = 'pesapal_standard';
          $this->method_title = __('Pesapal', 'woocommerce');
          $this->has_fields   = false;
          $this->testmode     = ($this->get_option('testmode') === 'yes') ? true : false;
          $this->debug	      = $this->get_option( 'debug' );
          $this->log = new WC_Logger();      
          $this->notify_url   = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Pesapal_Standard_Gateway', home_url( '/' ) ) );

          if($this->testmode){
            $api                    = 'http://demo.pesapal.com/';
            $this->consumer_key     = $this->get_option('testconsumerkey');
            $this->consumer_secret  = $this->get_option('testsecretkey');
          }
          else{
            $api                    = 'https://www.pesapal.com/';
            $this->consumer_key     = $this->get_option('consumerkey');
            $this->consumer_secret  = $this->get_option('secretkey');
          }
          
          $this->consumer                         = new OAuthConsumer($this->consumer_key, $this->consumer_secret);
          $this->signature_method                 = new OAuthSignatureMethod_HMAC_SHA1();
          $this->token = $this->params            = NULL;
          
          // Gateway payment URLs
          $this->post_order                       = $api.'api/PostPesapalDirectOrderV4';
          $this->query_status 		  = $api.'API/QueryPaymentStatus';
	  $this->query_status_by_order_id  = $api.'API/QueryPaymentStatusByMerchantRef';
	  $this->querypaymentdetails 		  = $api.'API/querypaymentdetails';
 
          // IPN Request URL
          $this->notify_url   = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Pesapal_Gateway', home_url( '/' ) ) );
 
          $this->init_form_fields();
          $this->init_settings();
          
          // Settings
          $this->title = $this->get_option('title');
          $this->description = $this->get_option('description');
          $this->testmode = $this->get_option( 'testmode' );
          $this->debug = $this->get_option( 'debug' );
          $this->consumerkey = $this->get_option('consumerkey');
          $this->secretkey = $this->get_option('secretkey');
          $this->testsecretkey = $this->get_option('testsecretkey');
          $this->testconsumerkey = $this->get_option('testconsumerkey');
 
          
          // Actions
          add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
          add_action('woocommerce_receipt_pesapal', array( $this, 'receipt_page'));
          add_action( 'woocommerce_api_wc_pesapal_gateway', array( $this, 'check_ipn_response' ) );
          add_action('woocommerce_thankyou_pesapal', array( $this, 'pesapal_return_handler'));
 
}//close constructor
 
       function init_form_fields() {
          $this->form_fields = array(
            'enabled' => array(
              'title' => __( 'Enable/Disable', 'woothemes' ),
              'type' => 'checkbox',
              'label' => __( 'Enable the Pesapal Gateway', 'woothemes' ),
              'default' => 'no'
            ),
            'title' => array(
              'title' => __( 'Title', 'woothemes' ),
              'type' => 'text',
              'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ),
              'default' => __( 'Pesapal Payment', 'woothemes' )
            ),
            'description' => array(
              'title' => __( 'Description', 'woocommerce' ),
              'type' => 'textarea',
              'description' => __( 'This is the description which the user sees during checkout.', 'woocommerce' ),
              'default' => __("Pay via Pesapal, using either mobile money, visa/mastercard, bank transfer or your pesapal e-wallet.", 'woocommerce')
            ),,
            'ipnurl' => array(
              'title' => __( 'Pesapal IPN URL', 'woothemes' ),
              'type' => 'text',
              'description' => __( 'Copy and Paste this URL in the Pesapal control panel.', 'woothemes' ),
              'default' => $this->notify_url
            ),
            'consumerkey' => array(
              'title' => __( 'Pesapal Consumer Key', 'woothemes' ),
              'type' => 'text',
              'description' => __( 'Pesapal consumer key.', 'woothemes' ),
              'default' => ''
            ),
            'secretkey' => array(
              'title' => __( 'Pesapal Secret Key', 'woothemes' ),
              'type' => 'text',
              'description' => __( 'Pesapal secret key.', 'woothemes' ),
              'default' => ''
            ),
            'testmode' => array(
              'title' => __( 'Use Demo Gateway', 'woothemes' ),
              'type' => 'checkbox',
              'label' => __( 'Use Demo Gateway', 'woothemes' ),
              'description' => __( 'Test the Pesapal Gateway.', 'woothemes' ),
              'default' => 'no'
            ),
            'testconsumerkey' => array(
              'title' => __( 'Pesapal Demo Consumer Key', 'woothemes' ),
              'type' => 'text',
              'description' => __( 'Your test Pesapal consumer key, get one at demo.pesapal.com .', 'woothemes' ),
              'default' => ''
            ),
              'testsecretkey' => array(
              'title' => __( 'Pesapal Demo Secret Key', 'woothemes' ),
              'type' => 'text',
              'description' => __( 'Your test Pesapal secret key, get one at demo.pesapal.com .', 'woothemes' ),
              'default' => ''
            ),
            'debug' => array(
			'title' => __( 'Debug Log', 'woocommerce' ),
			'type' => 'checkbox',
			'label' => __( 'Enable logging', 'woocommerce' ),
			'default' => 'no',
			'description' => sprintf( __( 'Enable logging : <code>woocommerce/logs/pesapal-%s.txt</code>', 'woocommerce' ), sanitize_file_name( wp_hash( 'pesapal' ) ) ),
		    ),
          );
        }//close init_form_fields
 
       public function admin_options() { ?>
        
          <h3><?php _e('Pesapal', 'woocommerce'); ?></h3>
          <p><?php _e('Pesapal works with mobile payment options as well as visa/mastercard and bank transfer.', 'woocommerce');?>
          <?php _e('<strong>Developer: </strong>Jonathan<br />', 'woocommerce'); ?>
          </p>
          <table class="form-table">
          <?php
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
          ?>
          </table>
          <script type="text/javascript">
          jQuery(function(){
            var testMode = jQuery("#woocommerce_pesapal_testmode");
            var consumer = jQuery("#woocommerce_pesapal_testconsumerkey");
            var secret = jQuery("#woocommerce_pesapal_testsecretkey");
            
            if (testMode.is(":not(:checked)")){
              consumer.parents("tr").css("display","none");
              secrect.parents("tr").css("display","none");
            }
            
 
            // Add onclick handler
            testMode.click(function(){            
              // If checked
              if (testMode.is(":checked")) {
                //show the hidden div
                consumer.parents("tr").show("fast");
                secrect.parents("tr").show("fast");
              } else {
                //otherwise, hide it
                consumer.parents("tr").hide("fast");
                secrect.parents("tr").hide("fast");
              }
            });
 
          });
          </script>
 
          <?php
        } // close admin_options
 
 
	function generate_pesapal_iframe($order_id){
 
		$url = $this->create_url($order_id);
 
		return '<iframe src="'.$url.'" width="100%" height="700px"  scrolling="auto" frameBorder="0">
			    <p>Unable to load payment page.</p>
			  </iframe>';
	}
 
 
public function create_url($order_id){
          $order            = &new WC_Order( $order_id );
          $order_xml        = $this->pesapal_xml($order_id);
          $callback_url     = $this->get_return_url( $order );
          
          
          $url = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, "GET", $this->post_order, $this->params);
          $url->set_parameter("oauth_callback", $callback_url);
          $url->set_parameter("pesapal_request_data", $order_xml);
          $url->sign_request($this->signature_method, $this->consumer, $this->token);
          
 
          return $url;
        }
 
 
public function pesapal_xml($order_id) {
          
          $order                      = new WC_Order( $order_id );
          $pesapal_args['total']      = $order->get_total();
          $pesapal_args['reference']  = $order_id;
          $pesapal_args['first_name'] = $order->billing_first_name;
          $pesapal_args['last_name']  = $order->billing_last_name;
          $pesapal_args['email']      = $order->billing_email;
          $pesapal_args['phone']      = $order->billing_phone;
          
          $i = 0;
          foreach($order->get_items() as $item){
            $product = $order->get_product_from_item($item);
            
            $cart[$i] = array(
              'id' => ($product->get_sku() ? $product->get_sku() : $product->id),
              'particulars' => $cart_row['name'],
              'quantity' => $item['qty'],
              'unitcost' => $product->regular_price,
              'subtotal' => $order->get_item_total($item, true)
            );
            $i++;
          }
          
          $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
            <PesapalDirectOrderInfo xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\"
            Amount=\"" . $pesapal_args['total'] . "\"
            Description=\"Order from " . bloginfo('name') . ".\"
            Type=\"MERCHANT\"
            Reference=\"" . $pesapal_args['reference'] . "\"
            FirstName=\"" . $pesapal_args['first_name'] . "\"
            LastName=\"" . $pesapal_args['last_name'] . "\"
            Email=\"" . $pesapal_args['email'] . "\"
            PhoneNumber=\"" . $pesapal_args['phone'] . "\"
            Currency=\"" . get_woocommerce_currency() . "\"
            xmlns=\"http://www.pesapal.com\" />
            <lineitems>";
          foreach($cart as $item){
            $xml .= "<lineitem
                  uniqueid=\"".$item['id']."\"
                  particulars=\"".$item['particulars']."\"
                  quantity=\"".$item['quantity']."\"
                  unitcost=\"".$item['unitcost']."\"
                  subtotal=\"".$item['subtotal']."\"></lineitem>";
          }
          $xml .= "</lineitems></pesapaldirectorderinfo>";
          
          return htmlentities($xml);
        }
 
 
	public function receipt_page($order_id){
 
		echo $this->generate_pesapal_iframe($order_id);
 
	}
 
 
	public function pesapal_return_handler(){
 
		$params = stripslashes_deep($_GET);
 
		$reference =  $pesapal_tracking_id = NULL;
 
		if(isset($params['pesapal_merchant_reference']))
		$reference = $params['pesapal_merchant_reference'];
 
		if(isset($params['pesapal_transaction_tracking_id']))
		$pesapal_tracking_id = $params['pesapal_transaction_tracking_id'];
 
	}
 
	public function check_ipn_response(){
 
		global $woocommerce;
 
		$params = stripslashes_deep($_GET);
 
		$reference =  $pesapal_tracking_id = NULL;
 
		if(isset($params['pesapal_notification_type']))
		$reference = $params['pesapal_notification_type'];
 
		if(isset($params['pesapal_merchant_reference']))
		$reference = $params['pesapal_merchant_reference'];
 
		if(isset($params['pesapal_transaction_tracking_id']))
		$pesapal_tracking_id = $params['pesapal_transaction_tracking_id'];
 
		$order = new WC_Order( $reference );
 
		$request_status = OAuthRequest::from_consumer_and_token(
		$this->consumer, 
		$this->token, 
		"GET", 
		$this->query_status, 
		$this->params
		);
			    
		$request_status->set_parameter("pesapal_merchant_reference", $reference);
		$request_status->set_parameter("pesapal_transaction_tracking_id",$pesapal_tracking_id);
		$request_status->sign_request($this->signature_method, $this->consumer, $this->token);
 
		$args = array (
		'sslverify' 	=> false,
		'timeout' 	=> 60,
		'httpversion'   => '1.1',
		'compress'      => false,
		'decompress'    => false,
		'user-agent'	=> 'WooCommerce/' . WC()->version
				);
 
 
		$response = wp_remote_get( $request_status, $args );
 
		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
 
		$this->log->add( 'pesapal', $response );
 
		$header_size  = curl_getinfo($response['response']['body'], CURLINFO_HEADER_SIZE);
				$raw_header   = substr($response['response']['body'], 0, $header_size - 4);
				$headerArray  = explode("\r\n\r\n", $raw_header);
				$header       = $headerArray[count($headerArray) - 1];
		
				//transaction status
				$elements = preg_split("/=/",substr($response['response']['body'], $header_size));
				$pesapal_status = $elements[1];
 
		switch ($pesapal_status){
		case "COMPLETED":
		$order->payment_complete();
		$order->reduce_order_stock();
		$woocommerce->cart->empty_cart();
		break;
		case "FAILED":
		case "INVALID":
		$order->update_status( 'failed', sprintf( __( 'Payment %s via IPN.', 'woocommerce' ), strtolower( $pesapal_status ) ) );
		break;
		case "PENDING":
		$order->update_status( 'on-hold', sprintf( __( 'Payment pending: %s', 'woocommerce' ), 'Waiting for PesaPal confirmation' ) );
		break;
		default:
		//No action
		break;
		}
 
	}
 
}//close check_ipn_response
 
 
	public function process_payment($order_id){
 
		$order = new WC_Order( $order_id );
 
		return array(
		'result' 	=> 'success',
		'redirect'	=> $order->get_checkout_payment_url( true )
		);
 
	}
	
 
 
 
 
 
 
	}
	
	/**
 	* Add the Gateway to WooCommerce
 	**/
	function woocommerce_add_pesapal_standard_gateway($methods) {
		$methods[] = 'WC_Pesapal_Standard';
		return $methods;
	}
	
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_pesapal_standard_gateway' );
} 
