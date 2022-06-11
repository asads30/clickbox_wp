<?php

/**
 * Plugin Name: CLICKBox WC
 * Description: Плагин для интеграции Woocommerce с CLICKBox
 * Version: 1.0.0
 * Author: CLICKBox
 * Author URI: http://clickbox.uz/
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: clickbox
 * Domain Path: /i18n/languages/
**/

if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'WC_CLICKBOX_PLUGIN_URL', plugin_dir_url(__FILE__) );

function log_me($message) {
    if ( WP_DEBUG === true ) {
        if ( is_array($message) || is_object($message) ) {
            error_log( print_r($message, true) );
        } else {
            error_log( $message );
        }
    }
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    function clickbox_shipping_method(){
        if (!class_exists('Clickbox_Shipping_Method')) {
            class Clickbox_Shipping_Method extends WC_Shipping_Method
            {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct($instance_id = 0)
                {
                    $this->id = 'clickbox';
                    $this->instance_id = absint($instance_id);
                    $this->method_title = 'CLICKBox';
                    $this->method_description = 'CLICKBox - доставка в почтоматы';
                    $this->supports = array(
                        'shipping-zones',
                        'settings'
                    );
                    load_plugin_textdomain( 'clickbox', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/languages/');
                    $this->availability = 'including';
                    $this->init();
                    $this->enabled = 'yes';
                    $this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : 'CLICKBox';
                }
                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                function init(){
                    $this->init_form_fields();
                    $this->init_settings();
                    add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
                }

                /**
                 * Define settings field for this shipping
                 * @return void
                 */
                function init_form_fields(){
                    $this->form_fields = array(
                        'title' => array(
                            'title' => 'Название',
                            'type' => 'text',
                            'description' => 'Название способа доставки через почтоматы. По умолчанию: CLICKBox',
                            'default' => 'CLICKBox',
                        ),
                        'merchant_id' => array(
                            'title' => 'Merchant id',
                            'type' => 'text',
                            'description' => 'Merchant id',
                            'default' => '',
                        ),
                        'merchant_secret' => array(
                            'title' => 'Merchant secret',
                            'type' => 'text',
                            'description' => 'Merchant secret',
                            'default' => '',
                        )
                    );
                }
                /**
                 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping($package = array()){
                    $rate = array(
                        'id' => $this->id,
                        'label' => $this->title,
                        'cost' => 15000
                    );
                    $this->add_rate($rate);
                }
            }
        }
    }
    add_action('woocommerce_shipping_init', 'clickbox_shipping_method');

    function add_clickbox_shipping_method($methods){
        $methods['clickbox'] = 'Clickbox_Shipping_Method';
        return $methods;
    }
    add_filter('woocommerce_shipping_methods', 'add_clickbox_shipping_method');

    function clickbox_scripts_and_styles() {
        remove_action( 'wp_head', array( $GLOBALS['woocommerce'], 'generator' ) );
        if (is_checkout() ) {
            wp_enqueue_script('yandexmap_js', 'https://api-maps.yandex.ru/2.1/?lang=ru_RU&amp;apikey=63c06bef-0885-4954-8338-87733b6be0e6', [], 0.1, true);
            wp_enqueue_script('tingle_js', WC_CLICKBOX_PLUGIN_URL . 'assets/js/tingle.min.js', [], 0.1, true);
            wp_enqueue_script('clickbox_js', WC_CLICKBOX_PLUGIN_URL . 'assets/js/app.js', [], 0.1, true);
            wp_enqueue_style('tingle_css', WC_CLICKBOX_PLUGIN_URL . 'assets/css/tingle.min.css', [], 0.1);
            wp_enqueue_style('clickbox_css', WC_CLICKBOX_PLUGIN_URL . 'assets/css/style.css', [], 0.1);
        }
    }
    add_action( 'wp_enqueue_scripts', 'clickbox_scripts_and_styles' );

    add_action( 'woocommerce_review_order_before_payment', function() {        
        echo '<div id="selectClickbox" style="display: none"><h5 id="clickbox-edit">' . esc_html__( 'Выберите пункт выдачи заказов', 'clickbox' ) . '</h5>' . '<button class="selectClickbox" type="button" id="clickbox-btn">' . esc_html__( 'Выбрать', 'clickbox' ) . '</button></div>';
    });

    function clickbox_script() { ?>
        <script type="text/javascript">
            jQuery(function($) {
                var selector = '#selectClickbox';
                var shpm = $('input[name="shipping_method[0]"]:checked').val();
                if ( shpm == 'clickbox' ) {
                    $( selector ).show();
                } else {
                    $( selector ).hide();               
                }
                $( 'form.checkout' ).on( 'change', 'input[name^="shipping_method"]', function() {
                    var c_s_m = $( this ).val();                    
                    if ( c_s_m.indexOf( 'clickbox' ) >= 0 ) {
                        $( selector ).show();
                    } else {
                        $( selector ).hide();               
                    }
                });
                $( 'form.checkout' ).on( 'change', 'select[name="billing_state"]', function() {
                    var shpm = $('input[name="shipping_method[0]"]:checked').val();
                    if ( shpm == 'clickbox' ) {
                        $( selector ).show();
                    } else {
                        $( selector ).hide();               
                    }
                });
                $( 'form.checkout' ).on( 'change', 'select[name="billing_country"]', function() {
                    var shpm = $('input[name="shipping_method[0]"]:checked').val();
                    if ( shpm == 'clickbox' ) {
                        $( selector ).show();
                    } else {
                        $( selector ).hide();               
                    }
                });
            });
        </script>
        <?php
    }
    add_action( 'woocommerce_review_order_before_payment', 'clickbox_script', 10, 0 );

    function clickbox_checkout_add( $checkout) {
        woocommerce_form_field( 'clickbox_celltype', array(
            'type'          => 'hidden',
            'class'         => array('clickbox_celltype'),
            ), $checkout->get_value( 'clickbox_celltype' ));
        woocommerce_form_field( 'clickbox_dimensionz', array(
            'type'          => 'hidden',
            'class'         => array('clickbox_dimensionz'),
            ), $checkout->get_value( 'clickbox_dimensionz' ));
    }
    add_action( 'woocommerce_after_order_notes', 'clickbox_checkout_add', 10, 1 );

    function shipping_apartment_update_order_meta( $order_id ) {
        if ( ! empty( $_POST['clickbox_celltype'] ) ) {
            update_post_meta( $order_id, 'clickbox_cell_type', sanitize_text_field( $_POST['clickbox_celltype'] ) );
        }
        if ( ! empty( $_POST['clickbox_dimensionz'] ) ) {
            update_post_meta( $order_id, 'clickbox_dimension_z', sanitize_text_field( $_POST['clickbox_dimensionz'] ) );
        }
    }
    add_action( 'woocommerce_checkout_update_order_meta', 'shipping_apartment_update_order_meta', 10, 1 );

    add_action( 'woocommerce_after_shipping_rate', 'clickbox_custom_fields', 20, 2 );
    function clickbox_custom_fields( $method, $index ) {
        if( ! is_checkout()) return;

        $clickbox_method_shipping = 'clickbox';

        if( $method->id != $clickbox_method_shipping ) return;

        $chosen_method_id = WC()->session->chosen_shipping_methods[ $index ];

        if($chosen_method_id == $clickbox_method_shipping ):

            echo '<div class="clickbox-fields">';

            woocommerce_form_field( 'clickbox_pochtomatid' , array(
                'type'          => 'hidden',
                'class'         => array(),
                'required'      => true,
            ), WC()->checkout->get_value( 'clickbox_pochtomatid' ));

            echo '</div>';
        endif;
    }

    add_action('woocommerce_checkout_process', 'clickbox_checkout_process');
    function clickbox_checkout_process() {
        if( isset( $_POST['clickbox_pochtomatid'] ) && empty( $_POST['clickbox_pochtomatid'] ) )
            wc_add_notice( esc_html__( 'Пожалуйста выберите почтомат', 'clickbox' ), "error" );
    }

    add_action( 'woocommerce_checkout_update_order_meta', 'carrier_update_order_meta', 30, 1 );
    function carrier_update_order_meta( $order_id ) {
        if( isset( $_POST['clickbox_pochtomatid'] ))
            update_post_meta( $order_id, 'clickbox_pochtomat_id', sanitize_text_field( $_POST['clickbox_pochtomatid'] ) );
    }

    function clickbox_register_status( $order_statuses ){
        $order_statuses['wc-clickbox-send'] = array(                                 
            'label' => _x( 'Отправить в CLICKBox', 'Order status', 'woocommerce' ),
            'public' => false,                                 
            'exclude_from_search' => false,                                 
            'show_in_admin_all_list' => true,                                 
            'show_in_admin_status_list' => true,                                 
            'label_count' => _n_noop( 'Отправить в CLICKBox <span class="count">(%s)</span>', 'Отправить в CLICKBox <span class="count">(%s)</span>', 'woocommerce' ),                              
        );      
        return $order_statuses;
    }
    add_filter( 'woocommerce_register_shop_order_post_statuses', 'clickbox_register_status', 10, 1 );

    function clickbox_show_status( $order_statuses ) {      
        $order_statuses['wc-clickbox-send'] = _x( 'Отправить в CLICKBox', 'Order status', 'woocommerce' );       
        return $order_statuses;
    }
    add_filter( 'wc_order_statuses', 'clickbox_show_status', 10, 1 );

    function clickbox_getshow_status( $bulk_actions ) {
        $bulk_actions['mark_clickbox-send'] = 'Изменить статус на Отправить в CLICKBox';
        return $bulk_actions;
    }
    add_filter( 'bulk_actions-edit-shop_order', 'clickbox_getshow_status', 10, 1 );

    function clickbox_get_status($order){
        $clickbox_method_shipping = get_option( 'woocommerce_clickbox_settings' );
        $merchant_id = $clickbox_method_shipping['merchant_id'];
        $merchant_secret = $clickbox_method_shipping['merchant_secret'];
        $authToken = $merchant_id . ':' . $merchant_secret;
        $authTokenEncode = 'Authorization: Basic ' . base64_encode($authToken);
        $context = stream_context_create(array(
                'http' => array(
                    'header'  => "$authTokenEncode"
                ),
                "ssl"=>array(
                    "verify_peer"=>false,
                    "verify_peer_name"=>false,
                )
            )
        );
        $status_click_box = '';
        $status_click_box_size = '';
        $data = '';
		$address_clickbox = '';
        if (!$order->get_meta('clickbox_orderid')) {
            $status_click_box = "Заказа нет в кликбокс";
        } else {
            try {
                $url = file_get_contents("http://dev.clickbox.uz/api/merchant/bookings/" . $order->get_meta('clickbox_orderid'), true, $context);
                $data = json_decode($url);
                $status_array = [
                    'waiting_payment' => 'Ожидание оплаты',
                    'waiting_package' => 'Ожидание посылки',
                    'queue' => 'В очереди',
                    'waiting_driver' => 'Ожидние водителя',
                    'delivered' => 'Посылка в почтомате',
                    'delivering' => 'Посылка в пути',
                    'done' => 'Выполнено',
                    'canceled' => 'Отменено',
                    'expired' => 'Время истекло',
                    'failed' => 'Срыв заказа'
                ];
				$address_clickbox = $data->data->routes[1]->address;
                foreach ($status_array as $key => $stat) {
                    if ($key == $data->data->status) {
                        $status_click_box = $stat;
                    }
                }
            } catch (Exception $e) {
                $status_click_box = 'Заказ в CLICK BOX не найден';
            }
            echo '<strong>ID заказа CLICKBOX: </strong>' . $order->get_meta('clickbox_orderid');
            echo ' - <a href="https://dev.clickbox.uz/admin/orders/' . $order->get_meta('clickbox_orderid') . '" target="_blank">Посмотреть </a>' . '<br />';
            echo '<strong>Статус заказа в CLICKBOX: </strong>' . $status_click_box . '<br />';            
            echo '<strong>Адрес почтомата: </strong>' . $address_clickbox;
        }
    }
    add_action( 'woocommerce_admin_order_data_after_billing_address', 'clickbox_get_status', 10, 1 );

    function clickbox_order_sendpay( $order_id, $order ) {
        try {
            if ($order->get_meta('clickbox_orderid')) {
                $url = "https://dev.clickbox.uz/api/merchant/bookings/" . $order->get_meta('clickbox_orderid') . "/pay";
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_PUT, true);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $clickbox_method_shipping = get_option( 'woocommerce_clickbox_settings' );
                $merchant_id = $clickbox_method_shipping['merchant_id'];
                $merchant_secret = $clickbox_method_shipping['merchant_secret'];
                $authToken = $merchant_id . ':' . $merchant_secret;
                $authTokenEncode = 'Authorization: Basic ' . base64_encode($authToken);
                $headers = array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    $authTokenEncode
                );
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                $resp = curl_exec($curl);
                log_me($resp);
                curl_close($curl);
            }
        } catch (\Exception $exception) {
        }
           
     }
    add_action( 'woocommerce_order_status_clickbox-send', 'clickbox_order_sendpay', 20, 2 );  
	
	function clickbox_order_sendcancel( $order_id, $order ) {
        try {
            if ($order->get_meta('clickbox_orderid')) {
                $url = "https://dev.clickbox.uz/api/merchant/bookings/" . $order->get_meta('clickbox_orderid') . "/cancel";
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_PUT, true);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $clickbox_method_shipping = get_option( 'woocommerce_clickbox_settings' );
                $merchant_id = $clickbox_method_shipping['merchant_id'];
                $merchant_secret = $clickbox_method_shipping['merchant_secret'];
                $authToken = $merchant_id . ':' . $merchant_secret;
                $authTokenEncode = 'Authorization: Basic ' . base64_encode($authToken);
                $headers = array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    $authTokenEncode
                );
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                $resp = curl_exec($curl);
                log_me($resp);
                curl_close($curl);
            }
        } catch (\Exception $exception) {
            
        }  
    }
    add_action( 'woocommerce_order_status_cancelled', 'clickbox_order_sendcancel', 20, 2 ); 

    function order_send_clickbox( $order_id, $order ){
        $ordermeta = new WC_Order( $order_id );
        $orderwc = wc_get_order($order_id);
        $order_data = $ordermeta->get_data();
        $get_phone = $order_data['billing']['phone'];
        $user_phone = preg_replace("/[^,.0-9]/", '', $get_phone);
        $pochtomatid = $ordermeta->get_meta('clickbox_pochtomat_id');
        $celltypeid = $orderwc->get_meta('clickbox_cell_type');
        $dimensionz = $orderwc->get_meta('clickbox_dimension_z');
        $billing_first_name = $ordermeta->get_billing_first_name();
        $product_name = 'ID заказа в мерчанте: ' . $order_id;
        $clickbox_method_shipping = get_option( 'woocommerce_clickbox_settings' );
        $merchant_id = $clickbox_method_shipping['merchant_id'];
        $merchant_secret = $clickbox_method_shipping['merchant_secret'];
        $authToken = $merchant_id . ':' . $merchant_secret;
        $authTokenEncode = 'Authorization: Basic ' . base64_encode($authToken);
        if( $ordermeta->has_shipping_method('clickbox') ) {
            try {
                $url = "https://dev.clickbox.uz/api/merchant/booking/delivery-to-cell";
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $headers = array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    $authTokenEncode
                );
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                $dataSend = array(
                    "phone" => $user_phone,
                    "postomat_to_id" => $pochtomatid,
                    "rate_id" => 2,
                    "address" => "Саларская наб., 35А",
                    "lng" => "69.313307",
                    "lat" => "41.326666",
                    "distance" => 20000,
                    "sender_name" => $merchant_id,
                    "cells" => array(
                        array(
                            "name" => $product_name,
                            "cell_type_id" => $celltypeid, 
                            "shipment_type_id" => "3",
                            "dimension_x" => "45",
                            "dimension_y" => "30",
                            "dimension_z" => $dimensionz,
                            "weight" => "25",
                        )
                    ),
                    "sender_floor" => 0,
                    "receiver_name" => $billing_first_name,
                    "receiver_phone" => $user_phone,
                    "receiver_floor" => 0,
                    "loaders" => 0,
                    "payment_type" => 'click',
                    "payment_method" => 'prepay',
                    "parcel_cost_enabled" => 0,
                    "parcel_cost" => 0,
                    "company_id" => 2
                );
                $data_string = json_encode($dataSend);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                $resp = curl_exec($curl);
                $clickboxRes = json_decode($resp);
                update_post_meta($order_id, 'clickbox_orderid', esc_attr($clickboxRes->data->id));
                curl_close($curl);
            } catch (\Exception $exception) {
            }
        }
    }
    add_action( 'woocommerce_checkout_order_processed', 'order_send_clickbox', 20, 2 );
}