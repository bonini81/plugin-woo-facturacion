<?php
/*
Plugin Name: Integración Contifico y Woocommerce 
Description: Integrar Woocommerce con Contifico
Plugin URI:   https://github.com/bonini81/plugin-woo-facturacion
Author:      Msc. Andrés Domínguez Bonini 
Version:     1.0
License:     GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.txt
*/


/* after an order has been processed, we will use the  'woocommerce_thankyou' hook, to add our function, to send the data */
//add_action('woocommerce_thankyou', 'wdm_send_order_to_ext'); 

//Changed the hook to order status completed
add_action('woocommerce_order_status_completed', 'wdm_send_order_to_ext'); 
function wdm_send_order_to_ext( $order_id ){
    // get order object and order details

    $order = wc_get_order( $order_id ); 
    //$order = new WC_Order( $order_id ); 
    $email = $order->billing_email;
    $phone = $order->billing_phone;
    $shipping_type = $order->get_shipping_method();
    $shipping_cost = $order->get_total_shipping();
    $fecha = date("d/m/Y");

    // set the address fields
    $user_id = $order->user_id;
    $address_fields = array( 
        'country',
        'title',
        'first_name',
        'last_name',
        'company',
        'address_1',
        'address_2',
        'address_3',
        'address_4',
        'city',
        'state',
        'wooccm13',
        'postcode');

    $address = array();
    if(is_array($address_fields)){
        foreach($address_fields as $field){
            $address['billing_'.$field] = get_user_meta( $user_id, 'billing_'.$field, true );
            $address['shipping_'.$field] = get_user_meta( $user_id, 'shipping_'.$field, true );
        }
    }
    
    // get coupon information (if applicable)
    $cps = array();
    $cps = $order->get_items( 'coupon' );
    
    $coupon = array();
    foreach($cps as $cp){
            // get coupon titles (and additional details if accepted by the API)
            $coupon[] = $cp['name'];
    }
    
    // get product details
    $order_items = $order->get_items();
    
    /*$item_sku = [];
    $item_name = [];
    $item_qty = [];
    $item_price = [];
    $iva = 12;
    $porcentaje_descuento = 0.00;
    $base_cero = 0.00;
    $base_gravable = $item_price;
    $base_no_gravable = 0.00;*/


    $items = [];


    foreach ( $order_items as $item ) {
        $product_wc = $item->get_product();
        $sku = $product_wc->get_sku();
        $item_qty = (float) $item['quantity'];
        $item_price = (float) $item['total'];
      

          $iva = 12;
    $porcentaje_descuento = 0.00;
    $base_cero = 0.00;
    $base_gravable = $item_price;
    $base_no_gravable = 0.00;
      
    $items[] = [

                'producto_id' =>          $sku,
				'cantidad'             => $item_qty,
				'precio'               => $item_price,
				'porcentaje_iva'       => $iva,
				'porcentaje_descuento' => $porcentaje_descuento,
				'base_cero'            => $base_cero,
				'base_gravable'        => $base_gravable,
				'base_no_gravable'     => $base_no_gravable

    ];
    
    }
    
    /* for online payments, send across the transaction ID/key. If the payment is handled offline, you could send across the order key instead */
    $transaction_key = get_post_meta( $order_id, '_transaction_id', true );
    $transaction_key = empty($transaction_key) ? $_GET['key'] : $transaction_key;   
    
    // set the username and password
    $api_username = 'FrguR1kDpFHaXHLQwplZ2CwTX3p8p9XHVTnukL98V5U'; // for now api key by bonini81
    $api_password = '02914770-4a13-45f0-bfe3-c2e4666cdbcf'; //  for now token by bonini81

    // to test out the API, set $api_mode as ‘sandbox’
   /* $api_mode = 'sandbox';
    if($api_mode == 'sandbox'){
        // sandbox URL example
        $endpoint = "https://api.contifico.com/sistema/api/v1/registro/documento/"; 
    }*/
   /* else{
        // production URL example
        $endpoint = "https://api.contifico.com/sistema/api/v1/registro/documento/"; 
    }*/

        // setup the data which has to be sent

        $endpoint = "https://api.contifico.com/sistema/api/v1/registro/documento/"; 

//variables from Contifico:
 
 

        $data = array(

            'pos' => '02914770-4a13-45f0-bfe3-c2e4666cdbcf',
              'fecha_emision' =>  $fecha,
              'tipo_documento' => 'FAC',
              'documento' => '001-001-423456730',
              'estado' => 'P',
              'electronico' => true,
              'autorizacion'=> '',
              'caja_id' => null,
          
              'cliente' => array(
          'ruc' => $address['billing_wooccm13'] . '001',
          'cedula' => $address['billing_wooccm13'],
          'razon_social' => $address['billing_first_name'],
          'telefonos' => $phone,
          'direccion' => $address['billing_address_1'],
          'tipo' => 'N',
          'email' => $email,
          'es_extranjero' => false
                  
              ),
          
              'vendedor' => array(
                  
                  'ruc' => '1792785537001',
          'cedula' => '1708457229',
          'razon_social' => 'SERVICIOS TRUE NORTH TRUENORTH S.A',
          'telefonos' => '0969078192',
          'direccion' => 'QUITO / Parroquia Tababela S/N vía a Y SN Y Aeropuerto Internacional Maris',
          'tipo' => 'J',
          'email' => 'doc.electronicostruenorth@gmail.com',
          'es_extranjero' => false
                  
              ),
          
              'descripcion' => 'FACTURA 0040',
              'subtotal_0' => 0.00,
              'subtotal_12' => 25.00,
              'iva' => 3,
              'servicio' => 0.00,
              'total' =>  28.00,
              'adicional1' => '',
              'adicional2' => '',
          
          
          'detalles' =>  $items,
          
              'cobros' => array(
                   array(
          'forma_cobro' => 'TC',
          'monto' =>  28.00,
          'numero_cheque' => '',
          'tipo_ping' => 'D'
                      ),    
                      ),
          
          );
  


$data_string = json_encode($data);    
                                                                       
                                                                                                                                                                                          



/*$headers = array( 
    'Authorization: 02914770-4a13-45f0-bfe3-c2e4666cdbcf',
    'Content-Type: application/json'
);*/

            // send API request via cURL
        $ch = curl_init();

        /* set the complete URL, to process the order on the external system. Let’s consider http://example.com/buyitem.php is the URL, which invokes the API */
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( 
            'Authorization: FrguR1kDpFHaXHLQwplZ2CwTX3p8p9XHVTnukL98V5U',
            'Content-Type: application/json'                                                                         
        )); 
        
    
        $response = curl_exec($ch);
        print_r($response);

        curl_close ($ch);
        
        // the handle response    
        if (strpos($response,'ERROR') !== false) {
                print_r($response);
        } else {
                // success
        }


 }






 // Example link
// https://wisdmlabs.com/blog/woocommerce-order-management-system-integration/