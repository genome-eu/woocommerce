<?php
/**
 * Plugin Name: Genome
 * Description: Genome Payment Gateway for Woocommerce
 * Author: dinarys LLC
 * Version: 1.1
 * Text Domain: wc-gemome-gateway
 * Domain Path: /i18n/languages/
 *
 * @package   WC-Genome-Gateway
 * @author    dinarys LLC
 * @category  Admin
 *
 */

use Genome\Lib\Genome\Scriney;
use Genome\Lib\Util\SignatureHelper;
use Genome\Lib\Util\StringHelper;

defined( 'ABSPATH' ) or exit;

define('WCGatewayGenomePluginUrl', plugin_dir_url(__FILE__));

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	return;
}

spl_autoload_register( 'genome_autoloader' );

/**
 * @param string $class_name
 */
function genome_autoloader( $class_name ) {
	if ( false !== strpos( $class_name, 'Genome' ) ) {
		$classes_dir = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'Lib' . DIRECTORY_SEPARATOR;
		$class_file  = str_replace( 'Genome\Lib', DIRECTORY_SEPARATOR, $class_name ) . '.php';
		$class_file  = str_replace( array( '\\', '_' ), DIRECTORY_SEPARATOR, $class_file );
		require_once $classes_dir . $class_file;
	}
}

/**
 * Add the gateway to WC Available Gateways
 *
 * @param array $gateways all available WC gateways
 * @return array all WC gateways + offline gateway
 */
function wc_genome_add_to_gateways( $gateways ) {
	$gateways[] = 'WC_Genome_Gateway';

	return $gateways;
}

add_filter( 'woocommerce_payment_gateways', 'wc_genome_add_to_gateways' );

/**
 * Adds plugin page links
 *
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_genome_gateway_plugin_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=genome_gateway' ) . '">' . __( 'Configure', 'wc-genome-offline' ) . '</a>'
	);

	return array_merge( $plugin_links, $links );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_genome_gateway_plugin_links' );

add_action( 'plugins_loaded', 'wc_genome_gateway_init', 11 );

function wc_genome_gateway_init() {
	class OrderInfo {
		private $orderId;
		private $reference;
		private $orderStatus;
		private $orderResponseMessage;

		/**
		 * @param int $orderId
		 * @param string $reference
		 */
		public function __construct( $orderId, $reference, $orderStatus, $orderResponseMessage ) {
			if ( ! is_int( $orderId ) ) {
				throw new InvalidArgumentException( 'OrderId must be integer' );
			}
			if ( ! is_string( $reference ) ) {
				throw new InvalidArgumentException( 'Reference must be string' );
			}
			if ( ! is_string( $orderStatus ) ) {
				throw new InvalidArgumentException( 'orderStatus must be string' );
			}
			if ( ! is_string( $orderResponseMessage ) ) {
				throw new InvalidArgumentException( 'orderMessage must be string' );
			}

			$this->orderId   = $orderId;
			$this->reference = $reference;
			$this->orderStatus = $orderStatus;
			$this->orderResponseMessage = $orderResponseMessage;
		}

		/**
		 * @return int
		 */
		public function getOrderId() {
			return $this->orderId;
		}

		/**
		 * @return string
		 */
		public function getReference() {
			return $this->reference;
		}
		
		/**
		 * @return string
		 */
		public function getOrderStatus() {
			return $this->orderStatus;
		}
		
		/**
		 * @return string
		 */
		public function getOrderResponseMessage() {
			return $this->orderResponseMessage;
		}
	}

	/**
	 * Genome Payment Gateway
	 *
	 * Provides an Genome Payment Gateway;
	 * We load it later to ensure WC is loaded first since we're extending it.
	 *
	 * @class WC_Genome_Gateway
	 * @extends WC_Genome_Gateway
	 * @version 1.0.0
	 * @package WooCommerce/Classes/Payment
	 * @author dinarys LLC
	 */
	class WC_Genome_Gateway extends WC_Payment_Gateway {
		public $public_key;
		public $secret_key;
		public $instructions;
		/**
		 * Genome image location
		 */
		const GENOME_LOGO = 'assets/images/genome_mastercard_visa.svg';

		public function __construct() {
			$this->id                 = 'genome_gateway';
			$this->icon               = apply_filters( 'woocommerce_genome_icon', WCGatewayGenomePluginUrl.$this::GENOME_LOGO );
			$this->has_fields         = false;
			$this->method_title       = __( 'Genome', 'wc-genome-offline' );
			$this->method_description = __( 'Allows using Genome for payments.', 'wc-genome-offline' ) . '<br>' .
			                            __( 'You need to setup the following callback url in your application:', 'wc-genome-offline' ) . ' ' . get_home_url() . '?wc-api=wc_' . $this->id;

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
			$this->public_key   = $this->get_option( 'public_key' );
			$this->secret_key   = $this->get_option( 'secret_key' );

			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( &$this, 'genome_payment_completed' ) );
			add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'pay_for_order' ) );
		}

		/**
		 * Initializes Gateway Settings Form Fields
		 */
		public function init_form_fields() {
			$this->form_fields = apply_filters( 'wc_offline_form_fields',
				array(
					'enabled' => array(
						'title'   => __( 'Enable/Disable', 'wc-genome-offline' ),
						'type'    => 'checkbox',
						'label'   => __( 'Enable Genome Payments', 'wc-genome-offline' ),
						'default' => 'yes'
					),

					'title' => array(
						'title'       => __( 'Title', 'wc-genome-offline' ),
						'type'        => 'text',
						'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-genome-offline' ),
						'default'     => __( 'Genome Payments', 'wc-genome-offline' ),
						'desc_tip'    => true,
					),

					'description' => array(
						'title'       => __( 'Description', 'wc-genome-offline' ),
						'type'        => 'textarea',
						'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-genome-offline' ),
						'default'     => __( '', 'wc-genome-offline' ),
						'desc_tip'    => true,
					),

					'instructions' => array(
						'title'       => __( 'Instructions', 'wc-genome-offline' ),
						'type'        => 'textarea',
						'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-genome-offline' ),
						'default'     => '',
						'desc_tip'    => true,
					),

					'public_key' => array(
						'title'       => __( 'Public key', 'wc-genome-offline' ),
						'type'        => 'text',
						'description' => __( 'Public key provided for your account by Genome gateway', 'wc-genome-offline' ),
						'default'     => '',
						'desc_tip'    => true,
					),

					'secret_key' => array(
						'title'       => __( 'Secret key', 'wc-genome-offline' ),
						'type'        => 'text',
						'description' => __( 'Secret key provided for your account by Genome gateway', 'wc-genome-offline' ),
						'default'     => '',
						'desc_tip'    => true,
					),
				) );
		}

		/**
		 * Processes the payment and returns the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {
			$order = new WC_Order( $order_id );

			return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url( true )
			);
		}

		/**
		 * @param int $order_id
		 * @return array
		 */
		public function prepare_form( $order_id ) {
			$order = new WC_Order( $order_id );

			$baseUrl = $this->get_return_url( $order );

			if ( strpos( $baseUrl, '?' ) !== false ) {
				$baseUrl .= '&';
			} else {
				$baseUrl .= '?';
			}
			
			$locale = get_locale();
			
			/*locale flow*/
			if($locale == 'ru_RU'){
				$lang = "ru-RU";
			}elseif($locale == 'de_DE'){
				$lang = "de-DE";
			}elseif($locale == 'fr_FR'){
				$lang = "fr-FR";
			}elseif($locale == 'pt_PT'){
				$lang = "pt-PT";
			}elseif($locale == 'it_IT'){
				$lang = "it-IT";
			}elseif($locale == 'es_ES'){
				$lang = "es-ES";
			}elseif($locale == 'tr_TR'){
				$lang = "tr-TR";
			}elseif($locale == 'sv_SE'){
				$lang = "sv-SE";
			}elseif($locale == 'nn_NO'){
				$lang = "no-NO";
			}elseif($locale == 'da_DK'){
				$lang = "da-DA";
			}elseif($locale == 'fi'){
				$lang = "fl-FL";
			}elseif($locale == 'nl_NL'){
				$lang = "nl-NL";
			}elseif($locale == 'ga'){
				$lang = "en-GA";
			}elseif($locale == 'pl_PL'){
				$lang = "pl-PL";
			}elseif($locale == 'lt_LT'){
				$lang = "lt-LT";
			}else{
				$lang = "en-US";
			}

			$productData[] = array(
				'productType' => 'fixedProduct',
				'productId'   => $order_id,
				'productName' => "Order id #{$order_id}",
				'currency'    => get_woocommerce_currency(),
				'amount'      => $order->get_total()
			);

			$params = array(
				'customProduct' => json_encode( $productData ),
				'email'         => $order->get_billing_email(),
				'key'           => $this->public_key,
				'uniqueuserid'  => $order->get_billing_email(),
				'firstname'     => $order->get_billing_first_name(),
				'lastname'      => $order->get_billing_last_name(),
				'city'          => $order->get_billing_city(),
				'phone'         => $order->get_billing_phone(),
				'address'       => $order->get_billing_address_1(),
				'zip'           => $order->get_billing_postcode(),
				'success_url'   => $baseUrl,
				'decline_url'   => $baseUrl,
				'locale'		=> $lang,
			);

			$helper    = new SignatureHelper();
			$signature = $helper->generate( $params, $this->secret_key, true );

			$params['signature'] = $signature;

			return $params;
		}

		/**
		 * @return bool
		 */
		public function genome_redirect_form_validate() {
			$scriney = new Scriney( $this->public_key, $this->secret_key );

			return $scriney->validateCallback( $_POST );
		}

		/**
		 * @param string $body
		 * @param string $signature
		 * @return bool
		 */
		public function validate_callback2( $body, $signature ) {
			$scriney = new Scriney( $this->public_key, $this->secret_key );

			return $scriney->validateCallback2( $body, $signature );
		}

		/**
		 * @param int $order_id
		 * @return bool
		 */
		public function pay_for_order( $order_id ) {
			$order = new WC_Order( $order_id );

			echo '<p>' . __( 'Redirecting to payment provider.', 'txtdomain' ) . '</p>';

			$order->add_order_note( __( 'Order placed and user redirected.', 'txtdomain' ) );
			$order->update_status( 'on-hold', __( 'Awaiting payment.', 'txtdomain' ) );

			WC()->cart->empty_cart();

			wc_enqueue_js( 'jQuery( "#submit-form" ).click();' );

			$formData = $this->prepare_form( $order_id );

			$stringHelper = new StringHelper();
			foreach ( $formData as $key => $value ) {
				$formData .= '<input type="hidden" name="' . $stringHelper->encodeHtmlAttribute( $key ) . '" value="' . $stringHelper->encodeHtmlAttribute( $value ) . '">';
			}

			echo '<form action="' . 'https://hpp-service.genome.eu/hpp' . '" method="post" target="_top">' . $formData .
			     '<div class="btn-submit-payment" style="display: none;">' .
			     '<button type="submit" id="submit-form"></button>' .
			     '</div>' .
			     '</form>';

			return true;
		}

		public function genome_payment_completed() {
			$order_info = $this->validateStatusAndGetOrderInfo();
			$order      = new WC_Order( $order_info->getOrderId() );
			if ($order_info->getOrderStatus() === 'success') {
				$order->payment_complete();
				WC()->cart->empty_cart();
				$order->add_order_note( 'Payment completed (transaction id: ' . $order_info->getReference() . ')', 0, true );
				echo 'OK';
				exit();
			}
			else {
				$order->update_status('failed');
				$order->add_order_note( 'The payment was failed due to ' .$order_info->getOrderResponseMessage(). ' (transaction id: ' . $order_info->getReference() . ')', 0, true );
				echo 'OK';
				exit();
			}
		}

		/**
		 * @return OrderInfo
		 */
		private function validateStatusAndGetOrderInfo() {
			$input = file_get_contents( 'php://input' );
			if ( $input !== '' ) {
				$signature = null;
				foreach ( getallheaders() as $name => $value ) {
					if ( $name === 'X-Signature' ) {
						$signature = $value;
						break;
					}
				}
				if ( $signature === null ) {
					throw new RuntimeException( 'No signature in request' );
				}
				$decoded = json_decode( $input, true );
				if ( ! is_array( $decoded ) ) {
					throw new RuntimeException( 'Unable to decode callback' );
				}
				$order_id = (int) trim( strip_tags( $decoded['productList'][0]['productId'] ) );
				if ( $decoded['status'] === 'success' || $decoded['status'] === 'decline' || $decoded['status'] === 'error' && $this->validate_callback2( $input, $signature ) ) {
					return new OrderInfo(  $order_id, $decoded['reference'], $decoded['status'], $decoded['message'] );
				}
			}
			if ( isset( $_POST['transactionId'], $_POST['productList'] ) && ! empty( $_POST['productList'] ) ) {
				$order_id = (int) trim( strip_tags( $_POST['productList'][0]['productId'] ) );
				if ( ( $_POST['status'] === 'success' ) && $this->genome_redirect_form_validate() ) {
					return new OrderInfo( $order_id, $_POST['transactionId'], $_POST['status'] );
				}
			}
			throw new RuntimeException( 'Unsupported callback' );
		}
	}
}

