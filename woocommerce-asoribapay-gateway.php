<?php
/*
* Plugin Name:AsoribaPay payment gateway for Woocommerce
* Plugin URI: http://woocommerce.com/products/asoribapay-gateway/
* Description: AsoribaPay gateway for woocommerce
* Version: 0.4
* Author: Woocommerce
* Author URI: http://woocommerce.com/
* Developer: Asoriba Inc
* Developer URI: https://asoriba.com/
* Text Domain: asoribapay-payment-gateway-for-woocommerce
*   
* Woo: 12345:342928dfsfhsf8429842374wdf4234sfd
* WC requires at least: 2.2
* WC tested up to: 3.4
*
* Copyright: © 2009-2018 WooCommerce.
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/


add_action('plugins_loaded', 'woocommerce_asoribapay_init', 0);
// hook and function for setting image file
add_action( 'wp_ajax_myprefix_get_image', 'myprefix_get_image');

function myprefix_get_image() {

      if(isset($_GET['id']) ){
          $image = wp_get_attachment_image( filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT ), 'thumbnail', true, array( 'id' => 'myprefix-preview-image' ) );
          update_option('myprefix_image_id', filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT ));
          $data = array(
              'image'    => $image,
          );
          wp_send_json_success( $data );
      } else {
          wp_send_json_error();
      }
      wp_die();
  } 


function woocommerce_asoribapay_init(){
  if(!class_exists('WC_Payment_Gateway')) return;

  class WC_AsoribaPay extends WC_Payment_Gateway{
    public function __construct(){
      $this -> id = 'asoribapay';
      $this -> method_title = 'AsoribaPay';
      $this -> has_fields = false;

      $this -> init_form_fields();
      $this -> init_settings();

      $this -> title = $this -> settings['title'];
      $this -> description = $this -> settings['description'];
      $this -> api_key = $this -> settings['api_key'];
      $this -> image = get_option( 'myprefix_image_url' );
      

      $this -> msg['message'] = "";
      $this -> msg['class'] = "";
      
      // For the Media Selector
      
      add_action('init', array(&$this, 'init_payment_url'));
      add_action( 'admin_enqueue_scripts', array(&$this, 'load_wp_media_files'));

        
      if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
      add_action('woocommerce_api_asoribapay', array(&$this, 'verify_payment'));
   }
   

    function init_form_fields(){

        $this->form_fields = array(
            'enabled' => array(
                'title'       =>  __( 'Enable/Disable', 'woocommerce' ),
                'label'   => __( 'Enable AsoribaPay Gateway', 'woocommerce' ),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'AsoribaPay',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default'     => 'Pay with your credit card via our super-cool payment gateway.',
            ),
            'api_key' => array(
                'title'       => 'API Key',
                'type'        => 'text',
                'default'	  => 'your API key goes here'
            )
        );
    }


    public function load_wp_media_files() {

          // Enqueue WordPress media scripts
          wp_enqueue_media();
          // Enqueue custom script that will interact with wp.media
          wp_enqueue_script( 'myprefix_script', plugins_url( '/js/myscript.js' , __FILE__ ), array('jquery'), '0.6' );
      }

      


       public function admin_options(){
        $image_id = get_option( 'myprefix_image_id' );

        if( intval( $image_id ) > 0 ) {
            // Change with the image size you want to use
            $image = wp_get_attachment_image( $image_id, 'thumbnail', true, array( 'id' => 'myprefix-preview-image' ) );
            $image_url =  wp_get_attachment_image_src($image_id, 'thumbnail', true);
          update_option('myprefix_image_url', $image_url[0]);
        } else {
            // Some default image
            $image = '<img id="myprefix-preview-image" width="50px" height="50px" src="https://asoribawebsite.com/wp-content/uploads/2017/10/app-logo.png" />';
            $image_url = "https://asoribawebsite.com/wp-content/uploads/2017/10/app-logo.png";
            update_option('myprefix_image_url', $image_url);
        }
        
        echo '<h3>'.__('AsoribaPay Payment Gateway', 'asoriba').'</h3>';
        echo '<p>'.__('AsoribaPay is an innovative way of paying for your stuff').'</p>';
        echo '<table class="form-table">';
        // Generate the HTML For the settings form.
        $this -> generate_settings_html();
        //HTML for the image selector
        ?><tr>
        
        <td align="left">
        <Label> <strong>Payment page image</strong> </Label>
        </td>
        <td align="right">
        <?php echo $image; ?>
        </td>
        <td align="left">
        <input type="hidden" name="myprefix_image_id" id="myprefix_image_id" value="<?php echo esc_attr( $image_id ); ?>" class="regular-text" />
        <input type='button' class="button-primary" value="<?php esc_attr_e( 'Select and save image', 'mytextdomain' ); ?>" id="myprefix_media_manager"/>
        </td>

        </tr>
        <?php

        echo '</table>';

    }

    /**
     *  There are no payment fields for AsoribaPay, but we want to show the description if set.
     **/
    function payment_fields(){
        if($this -> description) echo wpautop(wptexturize($this -> description));
    }
    /**
     * Process the payment and return the result
     **/
    function process_payment($order_id){
        global $woocommerce;
        
            

			// we need it to get any order detailes
			$order = wc_get_order( $order_id );
            $order_data = $order->get_data();
            


            
            $callback = home_url( '/' ) . 'wc-api/asoribapay';

			/*
			  * Array with parameters for API interaction
			 */
			$args = array(
                'pub_key' =>  $this -> api_key ,
				'amount' => $order->get_total(),
				'tokenize' => true,
				'metadata' => array(
					'order_id' => $order_data['id'],
					'product_name' => 'Payment By ' . $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'] ,
					'product_description' => ''
				),
				'callback' => $callback ,
				'order_image_url' => $this -> image ,
				'sharable' => '',
				'first_name' => $order_data['billing']['first_name'],
				'last_name' => $order_data['billing']['last_name'],
				'email' => $order_data['billing']['email'],
				'phone_number' => $order_data['billing']['phone']
			);

            

		 
			  $response = wp_remote_post('https://payment.asoriba.com/payment/v1.0/initialize', array(
				'method' => 'POST',
				'headers' => array('Content-Type' => 'application/json',
									'Accept' => 'application/json',
									'x-widget' => 'true'),
					'sslverify' => false,
					'body' => json_encode($args)
			 		)
			  );

			 if( !is_wp_error( $response ) ) {
		 
				 $body = json_decode( $response['body'], true );

				 

				 if ( $body['status'] == 'success' ) {
                    // Redirect to the payment provider
					return array(
						'result' => 'success',
						'redirect' => $body['url']
					);
		 
				 } else {
					wc_add_notice(  'Please try again. error is ' . $body['error'] , 'error' );
					return;
				}
		 
			} else {
				wc_add_notice(  'Connection error.', 'error' );
				return;
			}
    }

    function verify_payment(){

        global $woocommerce;
        $checkout_url = $woocommerce->cart->get_checkout_url();
        $store_url = get_permalink( woocommerce_get_page_id( 'shop' ) );


        

        if( !is_wp_error( $_GET ) ) {
            $order = wc_get_order( $_GET['metadata']['order_id'] );

            $transaction_id = $_GET['transaction_uuid'];
		 
            if ( $_GET['status_code'] == '100') {
                // complete payment and redirect to thank you page
               
    
                $order->payment_complete();
                $order->reduce_order_stock();
    
                $order->update_status('completed', 'ID: ' . $transaction_id ." " );
                // some notes to customer (replace true with false to make it private)
                $order->add_order_note( 'The AsoribaPay transaction ID is ' . $transaction_id, true );
             
                        // Empty cart
                $woocommerce->cart->empty_cart();
                
                wp_redirect( $this->get_return_url( $order ) );
                exit;
    
              
            } elseif($_GET['status_code'] == '0000'){
                //redirect to storefront
                $order->update_status('processing', 'ID: ' . $transaction_id ." " );
                $woocommerce->cart->empty_cart();
                wp_redirect($this->get_re);
                wc_add_notice(  'You have cancelled your order. Your cart has been emptied', 'notice' );
                return;

            }elseif($_GET['status_code'] == '632'){
                //redirect to storefront
                $order->update_status('cancelled', 'ID: ' . $transaction_id ." " );
                $woocommerce->cart->empty_cart();
                wp_redirect($store_url);
                wc_add_notice(  'You have cancelled your order. Your cart has been emptied', 'notice' );
                return;

            } else {
                // generate error string
                $str = $_GET['error_fields'];
                $cleaned = str_replace('_', ' ', $str);
                $separated = explode(", ",$cleaned);
                
                $error = "The following fields require attention: ";
                foreach($separated as $value){
                    if (!empty($value)){
                        
                    $error = $error . $value . " ";
                    }
                }
                
                
               $order->update_status('failed', 'ID: ' . $transaction_id ." " );
               wp_redirect($checkout_url);
               wc_add_notice(  'Please try again. ' . $error , 'error' );
               return;
           }
    
       } else {
        wp_redirect($checkout_url);
           wc_add_notice(  'Connection error.', 'error' );
           return;
       }
            
    }


}

   /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_asoribapay_gateway($methods) {
        $methods[] = 'WC_AsoribaPay';
        return $methods;
    }


    add_filter('woocommerce_payment_gateways', 'woocommerce_add_asoribapay_gateway' );
}
