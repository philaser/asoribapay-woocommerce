<?php
/*
Plugin Name:AsoribaPay payment gateway for Woocommerce
Plugin URI: http://www.asoriba.com
Description: AsoribaPay gateway for woocommerce
Version: 0.2
WC requires at least: 3.0
WC tested up to: 3.2
Author: Asoriba Inc
Author URI: http://www.asoriba.com
*/
add_action('plugins_loaded', 'woocommerce_asoribapay_init', 0);
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
      $this -> image = $this -> settings['image'];
      

      $this -> msg['message'] = "";
      $this -> msg['class'] = "";

      add_action('init', array(&$this, 'init_payment_url'));
      if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
      add_action('woocommerce_receipt_payu', array(&$this, 'receipt_page'));
      add_action('woocommerce_api_asoribapay', array(&$this, 'verify_payment'));
      add_action('woocommerce_api_asoribapaytest', array(&$this, 'verify_momo_payment'));
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
            ),
            'image' => array(
                'title'       => 'Payment page image',
                'type'        => 'file',
                'default'	  => 'This image will be displayed on the payment page'
            )
        );
    }

       public function admin_options(){
        echo '<h3>'.__('AsoribaPay Payment Gateway', 'asoriba').'</h3>';
        echo '<p>'.__('AsoribaPay is an innovative way of paying for your stuff').'</p>';
        echo '<table class="form-table">';
        // Generate the HTML For the settings form.
        $this -> generate_settings_html();
        echo '</table>';

    }

    /**
     *  There are no payment fields for payu, but we want to show the description if set.
     **/
    function payment_fields(){
        if($this -> description) echo wpautop(wptexturize($this -> description));
    }
    /**
     * Receipt Page
     **/
    function receipt_page($order){
        echo '<p>'.__('Thank you for your order, please click the button below to pay with PayU.', 'asoriba').'</p>';
        echo $this -> generate_payu_form($order);
    }
    /**
     * Generate payu button link
     **/
    public function init_payment_url($order_id){

       


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

            

		 
			  $response = wp_remote_post('https://paymentsandbox.asoriba.com/payment/v1.0/initialize', array(
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
		 
            if ( $_GET['status'] == 'successful') {
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
    
              
            } elseif($_GET['status'] == 'pending'){
                //redirect to storefront
                $order->update_status('processing', 'ID: ' . $transaction_id ." " );
                $woocommerce->cart->empty_cart();
                wp_redirect($this->get_re);
                wc_add_notice(  'You have cancelled your order. Your cart has been emptied', 'notice' );
                return;

            }elseif($_GET['status'] == 'Cancel'){
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
                
                $error = "The following fields require attention ";
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


    function verify_momo_payment(){

        $order_id = $_POST['order_id'];
        $order = wc_get_order($order_id);

        $order->update_status('failed', 'momo worked ! ' );

        return;

    }

    /**
     * Check for valid payu server callback
     **/
    function check_payu_response(){
        global $woocommerce;
        if(isset($_REQUEST['txnid']) && isset($_REQUEST['mihpayid'])){
            $order_id_time = $_REQUEST['txnid'];
            $order_id = explode('_', $_REQUEST['txnid']);
            $order_id = (int)$order_id[0];
            if($order_id != ''){
                try{
                    $order = new WC_Order( $order_id );
                    $merchant_id = $_REQUEST['key'];
                    $amount = $_REQUEST['Amount'];
                    $hash = $_REQUEST['hash'];

                    $status = $_REQUEST['status'];
                    $productinfo = "Order $order_id";
                    echo $hash;
                    echo "{$this->salt}|$status|||||||||||{$order->billing_email}|{$order->billing_first_name}|$productinfo|{$order->order_total}|$order_id_time|{$this->merchant_id}";
                    $checkhash = hash('sha512', "{$this->salt}|$status|||||||||||{$order->billing_email}|{$order->billing_first_name}|$productinfo|{$order->order_total}|$order_id_time|{$this->merchant_id}");
                    $transauthorised = false;
                    if($order -> status !=='completed'){
                        if($hash == $checkhash)
                        {

                          $status = strtolower($status);

                            if($status=="success"){
                                $transauthorised = true;
                                $this -> msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
                                $this -> msg['class'] = 'woocommerce_message';
                                if($order -> status == 'processing'){

                                }else{
                                    $order -> payment_complete();
                                    $order -> add_order_note('PayU payment successful<br/>Unnique Id from PayU: '.$_REQUEST['mihpayid']);
                                    $order -> add_order_note($this->msg['message']);
                                    $woocommerce -> cart -> empty_cart();
                                }
                            }else if($status=="pending"){
                                $this -> msg['message'] = "Thank you for shopping with us. Right now your payment staus is pending, We will keep you posted regarding the status of your order through e-mail";
                                $this -> msg['class'] = 'woocommerce_message woocommerce_message_info';
                                $order -> add_order_note('PayU payment status is pending<br/>Unnique Id from PayU: '.$_REQUEST['mihpayid']);
                                $order -> add_order_note($this->msg['message']);
                                $order -> update_status('on-hold');
                                $woocommerce -> cart -> empty_cart();
                            }
                            else{
                                $this -> msg['class'] = 'woocommerce_error';
                                $this -> msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
                                $order -> add_order_note('Transaction Declined: '.$_REQUEST['Error']);
                                //Here you need to put in the routines for a failed
                                //transaction such as sending an email to customer
                                //setting database status etc etc
                            }
                        }else{
                            $this -> msg['class'] = 'error';
                            $this -> msg['message'] = "Security Error. Illegal access detected";

                            //Here you need to simply ignore this and dont need
                            //to perform any operation in this condition
                        }
                        if($transauthorised==false){
                            $order -> update_status('failed');
                            $order -> add_order_note('Failed');
                            $order -> add_order_note($this->msg['message']);
                        }
                        add_action('the_content', array(&$this, 'showMessage'));
                    }}catch(Exception $e){
                        // $errorOccurred = true;
                        $msg = "Error";
                    }

            }



        }

    }

    function showMessage($content){
            return '<div class="box '.$this -> msg['class'].'-box">'.$this -> msg['message'].'</div>'.$content;
        }
     // get all pages
    function get_pages($title = false, $indent = true) {
        $wp_pages = get_pages('sort_column=menu_order');
        $page_list = array();
        if ($title) $page_list[] = $title;
        foreach ($wp_pages as $page) {
            $prefix = '';
            // show indented child pages?
            if ($indent) {
                $has_parent = $page->post_parent;
                while($has_parent) {
                    $prefix .=  ' - ';
                    $next_page = get_page($has_parent);
                    $has_parent = $next_page->post_parent;
                }
            }
            // add to page list array array
            $page_list[$page->ID] = $prefix . $page->post_title;
        }
        return $page_list;
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
