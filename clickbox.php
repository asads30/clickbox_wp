<?php

/**
 * Plugin Name: CLICKBox
 * Description: Плагин для интеграции с CLICKBox
 * Version: 1.0.0
 * Author: CLICKBox - Asadbek Ibragimov
 * Author URI: http://clickbox.uz/
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: clickbox
 * Domain Path: /i18n/languages/
**/

if ( ! defined( 'WPINC' ) ) {
    die;
}

// Путь к плагину
define( 'WC_CLICKBOX_PLUGIN_URL', plugin_dir_url(__FILE__) );

// Проверка что Woocommerce активирован
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    function clickbox_shipping_method(){
        if (!class_exists('CLickbox_Shipping_Method')) {
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
                    $this->countries = array(
                        'UZ',
                    );

                    $this->init();

                    $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
                    $this->title = 'CLICKBox';
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
                    $this->init_form_fields();
                    $this->init_settings();

                    // Save settings in admin if you have any defined
                    add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
                }

                /**
                 * Define settings field for this shipping
                 * @return void
                 */
                function init_form_fields()
                {

                    $this->form_fields = array(

                        'enabled' => array(
                            'title' => __('Enable', 'clickbox'),
                            'type' => 'checkbox',
                            'description' => __('Enable this shipping.', 'clickbox'),
                            'default' => 'yes'
                        ),

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
                        'label' => 'CLICKBox',
                        'cost' => 15000
                    );
                    $this->add_rate($rate);
                }
            }
        }
    }
    add_action('woocommerce_shipping_init', 'clickbox_shipping_method');

    function add_clickbox_shipping_method($methods)
    {
        $methods['clickbox'] = 'Clickbox_Shipping_Method';
        return $methods;
    }
    add_filter('woocommerce_shipping_methods', 'add_clickbox_shipping_method');

    // Подключение стилей и скриптов
    function clickbox_scripts_and_styles() {
        remove_action( 'wp_head', array( $GLOBALS['woocommerce'], 'generator' ) );
        if (is_checkout() ) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('inputmask_js', WC_CLICKBOX_PLUGIN_URL . 'assets/js/jquery.inputmask.bundle.js', [], 0.1, true);
            wp_enqueue_script('googlemaps_js', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyAFTWLzPcF1hRcc4X5q0fqG_w-FAgCZlrk&libraries=geometry', [], 0.1, true);
            wp_enqueue_script('yandexmap_js', 'https://api-maps.yandex.ru/2.1/?lang=ru_RU&amp;apikey=63c06bef-0885-4954-8338-87733b6be0e6', [], 0.2, true);            wp_enqueue_script('clickbox_js', WC_CLICKBOX_PLUGIN_URL . 'assets/js/app.js?ver3', [], 0.2, true);
            wp_enqueue_style('clickbox_css', WC_CLICKBOX_PLUGIN_URL . 'assets/css/style.css?ver2', [], 0.2);
        }
    }
    add_action( 'wp_enqueue_scripts', 'clickbox_scripts_and_styles' );

    // Валидация по габаритам
    function clickbox_validate_order( $posted )   {
        $packages = WC()->shipping->get_packages();
        foreach ( $packages as $i => $package ) {
            $CLICKBox_Shipping_Method = new CLICKBox_Shipping_Method();
            $length = 0;
            $width = 0;
            $height = 0;
            $itemBox = null;
            global $post;
            $args = array( 'posts_per_page' => -1 , 'post_type' => 'boxs');
            $myposts = get_posts( $args );
            foreach($package['contents'] as $item_id => $values) { 
                $_product = $values['data'];
                if($_product->get_length()){
                    $allLength = $_product->get_length();
                }
                if($_product->get_height()){
                    $allHeight = $_product->get_height();
                }
                if($_product->get_width()){
                    $width = $width + $_product->get_width() * $values['quantity'];
                }
                if($_product->get_length()){
                    $length = max($allLength, $length);
                }
                if($_product->get_length()){
                    $height = max($allHeight, $height);
                }
            }
            $availableBox = null;
            foreach ( $myposts as $key => $post ) : 
                setup_postdata( $post );
                if($length <= get_field('length') && $width <= get_field('width') && $height <= get_field('height')){
                    $availableBox = get_field('pochtomat');
                }
            endforeach;
            if($availableBox >= 1) {
                echo '<script>document.getElementById("clickbox-box").value = ' .  $availableBox . ';</script>';
            } else {
                echo '<script>document.getElementById("clickbox-box").value = 0;</script>';
                $message = sprintf( pll__( 'clickboxLimit2' ), $CLICKBox_Shipping_Method->title );
                $messageType = "error";
                if( ! wc_has_notice( $message, $messageType ) ) {
                    wc_add_notice( $message, $messageType );
                }
            }
        }
    }
    add_action( 'woocommerce_review_order_before_cart_contents', 'clickbox_validate_order' , 10, 1 );

    // Вывод выбора почтоматов
    add_action( 'woocommerce_review_order_before_payment', function() {        
        echo '<div class="selectBox" id="selectClickbox" style="display: none"><h5 id="clickbox-edit">' . esc_html__( 'Выберите пункт выдачи заказов', 'clickbox' ) . '</h5>' . '<button class="selectClickbox" type="button" id="clickbox-btn">' . esc_html__( 'Выбрать', 'clickbox' ) . '</button></div>';
    });

    function clickbox_script() { ?>
        <script type="text/javascript">
            jQuery(function($) {
                var selector = '#selectClickbox';
                var selector2 = '#selectBringo';
                var selector3 = '#billing_address_1_field';   
                var selector4 = '#billing_city_field';
                var selector5 = '#dopadress';
                $( 'form.checkout' ).on( 'change', 'input[name^="shipping_method"]', function() {
                    var c_s_m = $( this ).val();                    
                    if ( c_s_m.indexOf( 'bringo' ) >= 0 ) {
                        $( selector ).hide();   
                        $( selector2 ).show();
                        $('#billing_city').val('Весь город');
                    } else if ( c_s_m.indexOf( 'local_pickup' ) >= 0 ) {
                        $( selector ).hide();   
                        $( selector2 ).hide();
                        $('#billing_city').val('Весь город');
                        $('#billing_address_1').val('Саларская набережная');
                    } else {
                        $('#billing_city').val('Весь город');
                        $( selector ).show();
                        $( selector2 ).hide();               
                    }
                });
                $( 'form.checkout' ).on( 'change', 'select[name="billing_state"]', function() {
                    var c_s_m = $( this ).val();           
                    $('body').trigger('update_checkout');         
                    if ( c_s_m.indexOf( '01' ) >= 0 ) {
                        $( selector ).show(); 
                        $( selector2 ).hide();
                        $( selector3 ).hide();
                        $( selector4 ).hide();
                        $( selector5 ).hide();
                        $('#billing_city').val('Весь город');
                    } else {
                        $( selector ).hide();  
                        $( selector2 ).hide();    
                        $( selector3 ).show();
                        $( selector4 ).show();    
                        $( selector5 ).show();
                        $('#billing_city').val('');
                    }
                });
                var billingState = $('select[name="billing_state"]').val();
                if(billingState == '01'){
                    setTimeout(() => {
                        $('#billing_city').val('Весь город');
                        $('#billing_city_field').hide();
                        $('#billing_address_1_field').hide();
                        $( '#dopadress' ).hide();
                    }, 500);
                } else {
                    setTimeout(() => {
                        $('#billing_city').val('');
                    }, 500);
                }
            });
        </script>
        <?php
    }
    add_action( 'woocommerce_review_order_before_payment', 'clickbox_script', 10, 0 );

    // Добавление полей в оформление заказа
    function clickbox_checkout_add( $checkout) {

        woocommerce_form_field( 'clickbox_celltype', array(
            'type'          => 'hidden',
            'class'         => array('clickbox_celltype'),
            ), $checkout->get_value( 'clickbox_celltype' ));

        woocommerce_form_field( 'clickbox_dimensionz', array(
            'type'          => 'hidden',
            'class'         => array('clickbox_dimensionz'),
            ), $checkout->get_value( 'clickbox_dimensionz' ));

        woocommerce_form_field( 'clickbox-box', array(
            'type'          => 'hidden',
            'class'         => array('clickbox-box'),
            ), $checkout->get_value( 'clickbox-box' ));
        
    }
    add_action( 'woocommerce_after_order_notes', 'clickbox_checkout_add', 10, 1 );

    function shipping_apartment_update_order_meta( $order_id ) {
        if ( ! empty( $_POST['clickbox_celltype'] ) ) {
            update_post_meta( $order_id, 'clickbox_cell_type', sanitize_text_field( $_POST['clickbox_celltype'] ) );
        }
        if ( ! empty( $_POST['clickbox_dimensionz'] ) ) {
            update_post_meta( $order_id, 'clickbox_dimension_z', sanitize_text_field( $_POST['clickbox_dimensionz'] ) );
        }
        if ( ! empty( $_POST['clickbox-box'] ) ) {
            update_post_meta( $order_id, 'clickbox_box_size', sanitize_text_field( $_POST['clickbox-box'] ) );
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

    /// Регистрация нового статуса
    function clickbox_register_status( $order_statuses ){
        $order_statuses['wc-clickbox-send'] = array(                                 
            'label' => _x( 'Готов к отправке', 'Order status', 'woocommerce' ),
            'public' => false,                                 
            'exclude_from_search' => false,                                 
            'show_in_admin_all_list' => true,                                 
            'show_in_admin_status_list' => true,                                 
            'label_count' => _n_noop( 'Готов к отправке <span class="count">(%s)</span>', 'Готов к отправке <span class="count">(%s)</span>', 'woocommerce' ),                              
        );      
        return $order_statuses;
    }
    add_filter( 'woocommerce_register_shop_order_post_statuses', 'clickbox_register_status', 10, 1 );

    // Показ нового статуса
    function clickbox_show_status( $order_statuses ) {      
        $order_statuses['wc-clickbox-send'] = _x( 'Готов к отправке', 'Order status', 'woocommerce' );       
        return $order_statuses;
    }
    add_filter( 'wc_order_statuses', 'clickbox_show_status', 10, 1 );

    add_filter('xa_exclude_validation','xa_exclude_addr_validation');
    function xa_exclude_addr_validation() {
        if(isset($_POST['shipping_method']) && (explode(':',$_POST['shipping_method'][0])[0]) == 'local_pickup') { //change local_pickup to desired WooCommerce shipping method
            update_option('exclude_addr_val',true);
    }
        else if(isset($_POST['shipping_method'])) {
            update_option('exclude_addr_val',false);
            delete_option('exclude_addr_val');
    }
        $exclude_validation = get_option('exclude_addr_val');
        if($exclude_validation) {
            return true;
        }
    }

    // Изменение нового статуса
    function clickbox_getshow_status( $bulk_actions ) {
        $bulk_actions['mark_clickbox-send'] = 'Изменить статус на Готов к отправке';
        return $bulk_actions;
    }
    add_filter( 'bulk_actions-edit-shop_order', 'clickbox_getshow_status', 10, 1 );

    // Вывод статуса с CLICKBox
    function clickbox_get_status($order){
        $context = stream_context_create(array(
                'http' => array(
                    'header'  => "Authorization: Basic MTo1ZGU0ZmI3MjcyMGFl"
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
                $url = file_get_contents("http://app.clickbox.uz/api/merchant/bookings/" . $order->get_meta('clickbox_orderid'), true, $context);
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
            echo ' - <a href="https://app.clickbox.uz/admin/orders/' . $order->get_meta('clickbox_orderid') . '" target="_blank">Посмотреть </a>' . '<br />';
            echo '<strong>Статус заказа в CLICKBOX: </strong>' . $status_click_box . '<br />';
            if (!$order->get_meta('clickbox_box_size')) {
                $status_click_box_size = "Коробка не передана";
            } else {
                if($order->get_meta('clickbox_box_size') == 1){
                    $status_click_box_size = "S";
                } else if($order->get_meta('clickbox_box_size') == 2){
                    $status_click_box_size = "M";
                } else if($order->get_meta('clickbox_box_size') == 3){
                    $status_click_box_size = "L";
                } else if($order->get_meta('clickbox_box_size') == 4){
                    $status_click_box_size = "XL";
                }
            }
            echo '<strong>Подходящяя упаковка: </strong>' . $status_click_box_size . '<br />';
            echo '<strong>Адрес почтомата: </strong>' . $address_clickbox;
        }
    }
    add_action( 'woocommerce_admin_order_data_after_billing_address', 'clickbox_get_status', 10, 1 );

    // Отправка в API после изменения статуса
    function clickbox_order_sendpay( $order_id, $order ) {
        try {
            if ($order->get_meta('clickbox_orderid')) {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://app.clickbox.uz/api/merchant/bookings/'.$order->get_meta('clickbox_orderid').'/pay',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER => array(
                        'Accept: application/json',
                        'Content-Type: application/json',
                        'Authorization: Basic MTo1ZGU0ZmI3MjcyMGFl'
                    ),
                ));
                $response = curl_exec($curl);
                curl_close($curl);
            }
        } catch (\Exception $exception) {
        }
           
     }
    add_action( 'woocommerce_order_status_clickbox-send', 'clickbox_order_sendpay', 20, 2 );  
	
	function clickbox_order_sendcancel( $order_id, $order ) {
        try {
            if ($order->get_meta('clickbox_orderid')) {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://app.clickbox.uz/api/merchant/bookings/'.$order->get_meta('clickbox_orderid').'/cancel',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER => array(
                        'Accept: application/json',
                        'Content-Type: application/json',
                        'Authorization: Basic MTo1ZGU0ZmI3MjcyMGFl'
                    ),
                ));
                $response = curl_exec($curl);
                curl_close($curl);
            }
        } catch (\Exception $exception) {
            
        }  
    }
    add_action( 'woocommerce_order_status_cancelled', 'clickbox_order_sendcancel', 20, 2 ); 

    function register_box_post_types(){
        register_post_type( 'boxs', [
            'labels' => [
                'name'               => 'Коробки', // основное название для типа записи
                'singular_name'      => 'Коробка', // название для одной записи этого типа
                'add_new'            => 'Добавить коробку', // для добавления новой записи
                'add_new_item'       => 'Добавление коробки', // заголовка у вновь создаваемой записи в админ-панели.
                'edit_item'          => 'Редактирование коробки', // для редактирования типа записи
                'new_item'           => 'Новая коробка', // текст новой записи
                'view_item'          => 'Смотреть коробку', // для просмотра записи этого типа.
                'search_items'       => 'Искать коробку', // для поиска по этим типам записи
                'not_found'          => 'Не найдено', // если в результате поиска ничего не было найдено
                'not_found_in_trash' => 'Не найдено в корзине', // если не было найдено в корзине
                'menu_name'          => 'Коробки', // название меню
            ],
            'description'         => '',
            'public'              => true,
        ] );
    }
    add_action( 'init', 'register_box_post_types', 10, 0 );

    add_action( 'woocommerce_checkout_order_processed', 'order_send_clickbox', 20, 2 );
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
        $product_name = 'ID заказа в Market: ' . $order_id;
        if( $ordermeta->has_shipping_method('clickbox') ) {
            try {
                $url = "https://app.clickbox.uz/api/merchant/booking/delivery-to-cell";
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $headers = array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Basic MTo1ZGU0ZmI3MjcyMGFl'
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
                    "sender_name" => "CLICK Market",
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
}