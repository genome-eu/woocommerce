<?php
/**
 * Plugin Name: Genome
 * Description: Genome Payment Gateway for Woocommerce
 * Author: dinarys LLC
 * Version: 1.0
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
add_action( 'plugins_loaded', 'wc_genome_gateway_init', 11 );

function wc_genome_gateway_init() {
	class WC_Genome_Gateway extends WC_Payment_Gateway {
		public $public_key;
		public $secret_key;
		public $instructions;

		public function __construct() {
			$this->id                 = 'genome_gateway';
			$this->icon               = apply_filters( 'woocommerce_offline_icon', '' );
			$this->has_fields         = false;
			$this->method_title       = __( 'Genome', 'wc-genome-offline' );
			$this->method_description = __( 'Allows using Genome for payments.', 'wc-genome-offline' );

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
				'success_url'   => $baseUrl,
				'decline_url'   => $baseUrl,
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
			if ( isset( $_POST['transactionId'], $_POST['productList'] ) && ! empty( $_POST['productList'] ) ) {
				$order_id = (int) trim( strip_tags( $_POST['productList'][0]['productId'] ) );

				$order = new WC_Order( $order_id );

				if ( ( $_POST['status'] === 'success' ) && $this->genome_redirect_form_validate() ) {
					$order->payment_complete();
					$order->add_order_note( 'Payment completed (transaction id: ' . $_POST['transactionId'] . ')',
						0,
						true );

				}

				echo 'OK';

			}

			exit();
		}
	}
}
