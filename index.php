<?php
/*
Plugin Name: Evertec btrans para woocommerce 
Description: Plugin para añadir pasarela de pago evertec btras a woocommerce. 
Version: 3.5.2
Author: Jesualex
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!defined('ABSPATH')) {
	exit;
}

add_action('plugins_loaded', 'woocommerce_btrans_evtc_init', 0);

function woocommerce_btrans_evtc_init() {

	if (!class_exists('WC_Payment_Gateway')) {
		return;
	}

	/**
	 * Gateway class
	 */
	class WC_Btrans_Evtc extends WC_Payment_Gateway {
		public function __construct() {
			
			// Go wild in here
			$this->id = 'evertec';
			$this->method_title = __('Evertec', 'btrans');
			$this->icon = plugins_url('images/logo.png', __FILE__);
			$this->has_fields = false;

			$this->init_form_fields();
			$this->init_settings();
			$this->title = $this->settings['title'];

			$this->CurrencyCode = $this->settings['CurrencyCode'];
			$this->AcquiringInstitutionCode = $this->settings['AcquiringInstitutionCode'];
			$this->description = $this->settings['description'];
			$this->MerchantType = $this->settings['MerchantType'];
			$this->MerchantNumber = $this->settings['MerchantNumber'];
			$this->MerchantTerminal = $this->settings['MerchantTerminal'];
			$this->PageLanguaje = $this->settings['PageLanguaje'];
			$this->MerchantName = $this->settings['MerchantName'];

			$this->liveurl = 'https://cert.btrans.evertecinc.com/postwebbtrans/amexpostlog.php';
			$this->notify_url = home_url('/wc-api/WC_Btrans_Evtc');

			$this->msg['message'] = "";
			$this->msg['class'] = "";
			//add_action('init', array(&$this, 'check_evertec_response'));
			//update for woocommerce >2.0
			add_action( 'woocommerce_api_wc_btrans_evtc', array( $this, 'check_evertec_response',));
			if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
					$this,
					'process_admin_options',
				));
			} else {
				add_action('woocommerce_update_options_payment_gateways', array(&$this,
					'process_admin_options',
				));
			}
			add_action('woocommerce_receipt_evertec', array(
				$this,
				'receipt_page',
			));
			add_action('woocommerce_thankyou_evertec', array(
				$this,
				'thankyou_page',
			));
		}

		function init_form_fields() {

			$this->form_fields = array(
				'enabled' => array(
					'title' => __('Enable/Disable', 'btrans'),
					'type' => 'checkbox',
					'label' => __('activar Evertec btrans .', 'btrans'),
					'default' => 'no',
				),
				'test' => array(
					'title' => __('Enable/Disable', 'btrans'),
					'type' => 'checkbox',
					'label' => __('usar valores de prueba', 'btrans'),
					'default' => 'no',
				),
				'title' => array(
					'title' => __('Title:', 'btrans'),
					'type' => 'text',
					'description' => __('Este control se muestra al usuario mientras cancela la orden.', 'btrans'),
					'default' => __('Evertec btrans', 'btrans'),
				),
				'description' => array(
					'title' => __('Description:', 'btrans'),
					'type' => 'textarea',
					'description' => __('Este control se muestra al usuario mientras cancela la orden.', 'btrans'),
					'default' => __('Pague seguro con su tarjeta de credito o debito usando Evertec btrans Secure Servers.', 'btrans'),
				),
				'CurrencyCode' => array(
					'title' => __('Currency Code', 'btrans'),
					'type' => 'text',
					'description' => __('Código de moneda peso 214 dólares 840."'),
					'default' => __('214', 'btrans'),
				),
				'AcquiringInstitutionCode' => array(
					'title' => __('Acquiring Institution Code', 'btrans'),
					'type' => 'text',
					'description' => __('Será asignado por Evertec ', 'btrans'),
					'default' => __('349', 'btrans'),
				),
				'MerchantType' => array(
					'title' => __('Merchant Type', 'btrans'),
					'type' => 'text',
					'description' => __('Valor asignado por el banco adquirente. Campo Numérico. Código de 
											Categoría de Comercio (MCC) ', 'btrans'),
					'default' => __('5440', 'btrans'),
				),
				'MerchantNumber' => array(
					'title' => __('Merchant Number', 'btrans'),
					'type' => 'text',
					'description' => __('Valor asignado por el banco adquirente. Campo numérico.
											Completar con espacios a la derecha. ', 'btrans'),
					'default' => __('349999999      ', 'btrans'),
				),
				'MerchantTerminal' => array(
					'title' => __('Merchant Terminal', 'btrans'),
					'type' => 'text',
					'description' => __('Valor asignado por Evertec. Campo numérico. ', 'btrans'),
					'default' => __('0000767600001', 'btrans'),
				),
				'PageLanguaje' => array(
					'title' => __('Page Languaje', 'btrans'),
					'type' => 'text',
					'description' => __('ESP o ENG. Identifica el lenguaje en que hay que mostrar la pagina de
											autorización . ', 'btrans'),
					'default' => __('ENG', 'btrans'),
				),
				'MerchantName' => array(
					'title' => __('Merchant Name', 'btrans'),
					'type' => 'text',
					'description' => __('Alfanumérico de 40 posiciones. ', 'btrans'),
					'default' => __('COMERCIO PARA REALIZAR PRUEBAS        DO', 'btrans'),
				),
			);
		}
		/**
		 * Admin Panel Options
		 * - Options for bits like 'title' and availability on a country-by-country basis
		 *
		 */
		public function admin_options() {
			echo '<h3>' . __('Evertec btrans pasarela de pago', 'btrans') . '</h3>';
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
		}
		/**
		 *  There are no payment fields for Evertec btrans, but we want to show the description if set.
		 *
		 */
		function payment_fields() {
			if ($this->description) {
				echo wpautop(wptexturize($this->description));
			}

		}
		/**
		 * Receipt Page
		 *
		 */
		function receipt_page($order) {

			echo '<p>' . __('Gracias por por preferirnos.', 'btrans') . '</p>';
			echo $this->generate_evertec_form($order);
		}
		/**
		 * Process the payment and return the result
		 *
		 */
		function process_payment($order_id) {
			$order = new WC_Order($order_id);
			return array(
				'result' => 'success',
				'redirect' => $order->get_checkout_payment_url(true),
			);
		}
		/**
		 * Check for valid Evertec btrans server callback
		 *
		 */
		function check_evertec_response() {
			global $woocommerce;

			$this->msg['class'] = 'error';
			$this->msg['message'] = "Transaccion declinada, revise sus datos. Gracias por comprar con nosotros.";
			if ($_POST) {
				
				$nuevo_CreditCardNumber = $_POST["CreditCardNumber"]; 
				$nuevo_ResponseCode = $_POST["ResponseCode"];
				$nuevo_AuthorizationCode = $_POST["AuthorizationCode"]; 
				$nuevo_RetrivalReferenceNumber = $_POST["RetrivalReferenceNumber"]; 
				$nuevo_OrdenId = $_POST["OrdenID"]; 
				$nuevo_TransactionId = $_POST["TransactionID"]; 
				
				if ($nuevo_OrdenId != '') {
					try {
						$order = new WC_Order($nuevo_OrdenId);
						$order->add_order_note('Respuesta bancaria<br/>CreditCard Number: ' . $nuevo_CreditCardNumber.'
										<br/>ResponseCode: ' . $nuevo_ResponseCode.'<br/> autorizacion code nro:'. $nuevo_AuthorizationCode
										.'<br/> RetrivalReferenceNumber:'. $nuevo_RetrivalReferenceNumber.
										'<br/> OrdenId:'. $nuevo_OrdenId.'<br/> TransactionId:'. $nuevo_TransactionId);
						$order_status = $nuevo_ResponseCode;
						$transauthorised = false;
						if ($order->status !== 'completed') {
							if ($order_status == "00") {
								$transauthorised = true;
								$this->msg['message'] = "Gracias por comprar con nosotros. su transaccion ha sido completada exitosamente. Referencia nro: ". $nuevo_RetrivalReferenceNumber." codigo de autorizacion: ".$nuevo_AuthorizationCode;
								$this->msg['class'] = 'success';
								$woocommerce->cart->empty_cart();
								if ($order->status != 'processing') {
									$order->payment_complete();
									$order->add_order_note('Transaccion completada correctamente');
								}
							} else if ($order_status === "09") {
								$this->msg['message'] = "Gracias por su compra, le informaremos sobre el estatus de su transaccion a su email.";
								$this->msg['class'] = 'success';
							} else if ($order_status === "06") {
								$this->msg['class'] = 'error';
								$this->msg['message'] = "Transaccion declinada, revise sus datos. Gracias por comprar con nostros.";
							} else {
								$this->msg['class'] = 'error';
								$this->msg['message'] = "Transaccion declinada, revise sus datos. Gracias por comprar con nostros.";
							}

							if ($transauthorised == false) {
								$order->update_status('failed');
								$order->add_order_note('Failed');
							}
						}
					} catch (Exception $e) {

						$this->msg['class'] = 'error';
						$this->msg['message'] = "Transaccion declinada, revise sus datos. Gracias por comprar con nostros. error = {$e}";
					}
				}
			}
			if (function_exists('wc_add_notice')) {
				wc_add_notice($this->msg['message'], $this->msg['class']);
			} else {
				if ($this->msg['class'] == 'success') {
					$woocommerce->add_message($this->msg['message']);
				} else {
					$woocommerce->add_error($this->msg['message']);
				}
				$woocommerce->set_messages();
			}
			$redirect_url = get_permalink(woocommerce_get_page_id('myaccount'));
			wp_redirect($redirect_url);
			exit;
		}
		/*
		//Removed For WooCommerce 2.0
		function showMessage($content){
		return '<div class="box '.$this -> msg['class'].'-box">'.$this -> msg['message'].'</div>'.$content;
		}*/
		/**
		 * Generate Evertec btrans button link
		 *
		 */
		public function generate_evertec_form($order_id) {
			global $woocommerce;
			$order = new WC_Order($order_id);
			
			if(strlen($this->MerchantName)>40) $MerchantName = substr($this->MerchantName, 0, 40);
			else $MerchantName = str_pad ($this->MerchantName , 40 ," " );
			
			if(strlen($order_id)>6) $oid = substr($order_id, strlen($order_id)-6);
			else $oid = str_pad ( $order_id , 6 , "0", STR_PAD_LEFT  );
			wc_enqueue_js('
    $.blockUI({
        message: "' . esc_js(__('Gracias por comprar con nosotros. justo ahora vamos a redirigirle a Evertec inc para que realize su pago.', 'woocommerce')) . '",
        baseZ: 99999,
        overlayCSS:
        {
            background: "#fff",
            opacity: 0.6
        },
        css: {
            padding:        "20px",
            zindex:         "9999999",
            textAlign:      "center",
            color:          "#555",
            border:         "3px solid #aaa",
            backgroundColor:"#fff",
            cursor:         "wait",
            lineHeight:     "24px",
        }
    });
jQuery("#submit_evertec_payment_form").click();
');
	if($this->settings['test']=='yes'){
		$form = '<form action="' . esc_url($this->liveurl) . '" method="post" id="evertec_payment_form" target="_top">
' . '<input name="TransactionType" value="0200" type="hidden">
 <input name="CurrencyCode" value="214" type="hidden">
 <input name="AcquiringInstitutionCode" value="349" type="hidden">
 <input name="MerchantType" value="5440" type="hidden">
 <input name="MerchantNumber" value="349999999      " type="hidden">
 <input name="MerchantTerminal" value="000076760001" type="hidden">
 <input name="ReturnUrl" value="'.$this->notify_url.'" type="hidden">
 <input name="CancelUrl" value="'.$this->notify_url.'" type="hidden">
 <input name="PageLanguaje" value="ENG" type="hidden">
 <input name="OrdenId" value="'.$order_id.'" type="hidden">
 <input name="TransactionId" value="'.$oid.'" type="hidden">
 <input name="Amount" value="'.str_pad ( ($order->order_total*100) , 12 ,"0" , STR_PAD_LEFT  ).'" type="hidden">
 <input name="Tax" value="'.str_pad ( ($order->order_tax*100) , 12 ,"0" , STR_PAD_LEFT  ).'" type="hidden">
 <input name="MerchantName" value="COMERCIO PARA REALIZAR PRUEBAS        DO" type="hidden">
 <input name="KeyEncriptionKey" value="'.md5('5440349999999      000076760001'.
												$oid.str_pad ( ($order->order_total*100) , 12 , "0", STR_PAD_LEFT  )
												.str_pad ( ($order->order_tax*100) , 12 , "0", STR_PAD_LEFT  )).'" type="hidden">
 <input name="Ipclient" value="10.199.999.999" type="hidden">
 ' . '
<!-- Button Fallback -->
<div class="payment_buttons">
    <input type="submit" class="button alt" id="submit_evertec_payment_form" value="' . __('Pay via Evertec btrans', 'woocommerce') . '" /> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Cancel order &amp; restore cart', 'woocommerce') . '</a>
</div>
<script type="text/javascript">
    jQuery(".payment_buttons").hide();
</script>
</form>';
	}else{
			$form = '<form action="' . esc_url($this->liveurl) . '" method="post" id="evertec_payment_form" target="_top">
' . '<input name="TransactionType" value="0200" type="hidden">
 <input name="CurrencyCode" value="'.$this->CurrencyCode.'" type="hidden">
 <input name="AcquiringInstitutionCode" value="'.$this->AcquiringInstitutionCode.'" type="hidden">
 <input name="MerchantType" value="'.$this->MerchantType.'" type="hidden">
 <input name="MerchantNumber" value="'.$this->MerchantNumber.'" type="hidden">
 <input name="MerchantTerminal" value="'.$this->MerchantTerminal.'" type="hidden">
 <input name="ReturnUrl" value="'.$this->notify_url.'" type="hidden">
 <input name="CancelUrl" value="'.$this->notify_url.'" type="hidden">
 <input name="PageLanguaje" value="'.$this->PageLanguaje.'" type="hidden">
 <input name="OrdenId" value="'.$order_id.'" type="hidden">
 <input name="TransactionId" value="'.$oid.'" type="hidden">
 <input name="Amount" value="'.str_pad ( ($order->order_total*100) , 12 ,"0" , STR_PAD_LEFT  ).'" type="hidden">
 <input name="Tax" value="'.str_pad ( ($order->order_tax*100) , 12 ,"0" , STR_PAD_LEFT  ).'" type="hidden">
 <input name="MerchantName" value="'.$MerchantName.'" type="hidden">
 <input name="KeyEncriptionKey" value="'.md5($this->MerchantType.$this->MerchantNumber.$this->MerchantTerminal.
												$oid.str_pad ( ($order->order_total*100) , 12 , "0", STR_PAD_LEFT  )
												.str_pad ( ($order->order_tax*100) , 12 , "0", STR_PAD_LEFT  )).'" type="hidden">
 <input name="Ipclient" value="'.$order->customer_ip_address.'" type="hidden">
 ' . '
<!-- Button Fallback -->
<div class="payment_buttons">
    <input type="submit" class="button alt" id="submit_evertec_payment_form" value="' . __('Pay via Evertec btrans', 'woocommerce') . '" /> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Cancel order &amp; restore cart', 'woocommerce') . '</a>
</div>
<script type="text/javascript">
    jQuery(".payment_buttons").hide();
</script>
</form>';
	}
	
			return $form;
		}
		// get all pages
		function get_pages($title = false, $indent = true) {
			$wp_pages = get_pages('sort_column=menu_order');
			$page_list = array();
			if ($title) {
				$page_list[] = $title;
			}

			foreach ($wp_pages as $page) {
				$prefix = '';
				// show indented child pages?
				if ($indent) {
					$has_parent = $page->post_parent;
					while ($has_parent) {
						$prefix .= ' - ';
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
	 *
	 */
	function woocommerce_add_btrans_evtc_gateway($methods) {
		$methods[] = 'WC_Btrans_Evtc';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'woocommerce_add_btrans_evtc_gateway');
}


function btransRemoveCharacters($str) {
	return trim(preg_replace('/ +/', ' ', preg_replace('/[^a-zA-Z0-9,\s]/', '', strip_tags($str))));
}

function btrans_debug($what) {
	echo '<pre>';
	print_r($what);
	echo '</pre>';
}
?>
