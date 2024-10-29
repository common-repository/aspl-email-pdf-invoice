<?php 

/**
 * Plugin Name: ASPL Email PDF Invoice
 * Plugin URI: https://acespritech.com/
 * Description: This plugin automatically adds a PDF invoice to the order confirmation emails and sent out to your customers.
 * Version: 1.1.0
 * Author: Acespritech Solutions Pvt Ltd
 * Author URI: https://woocommerce.com
 * Text Domain: aspl
 * Domain Path: /i18n/languages/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 */

if ( ! defined( 'ABSPATH' ) ){
    exit;
}

/*Create a dir in upload folder*/
function aspl_pdf_invoice_activate() {
 
    $upload = wp_upload_dir();
    $upload_dir = $upload['basedir'];
    $upload_dir = $upload_dir . '/aspl_pdf';
    if (! is_dir($upload_dir)) {
       mkdir( $upload_dir, 0700 );
    }
}
register_activation_hook( __FILE__, 'aspl_pdf_invoice_activate' );

/*ADD script and style*/
add_action('admin_enqueue_scripts', 'aspl_pdf_invoice_admin_style');
function aspl_pdf_invoice_admin_style(){    
    wp_enqueue_style('aspl_pips_admin_style', plugins_url('css/aspl_pips_custom_css.css', __FILE__));
}

/*Add admin menu page*/
add_action('admin_menu', 'aspl_pips_menu');
function aspl_pips_menu(){	
    $hook = add_menu_page('Aspl Invoice', 'Aspl Invoice', 'manage_options', 'aspl_pips_pdf_page', 'aspl_pdf_page_fun' , 'dashicons-thumbs-up',  61);
}

/*Admin Page CallBack Function*/
function aspl_pdf_page_fun(){
	include 'include/pdf_data.php';
}

/*Add metabox in woocommerce order edit page */
add_action( 'add_meta_boxes', 'aspl_pdf_invoice_add_meta_boxes' );
if ( ! function_exists( 'aspl_pdf_invoice_add_meta_boxes' ) ){

    function aspl_pdf_invoice_add_meta_boxes(){
        add_meta_box( 'aspl_other_pdf_fields', __('View Invoice PDF','woocommerce'), 'aspl_pdf_invoice_add_fields_for_packaging_pdf', 'shop_order', 'side', 'core' );
    }

}

/*Metabox Callback Function*/
if ( ! function_exists( 'aspl_pdf_invoice_add_fields_for_packaging_pdf' ) ){

	function aspl_pdf_invoice_add_fields_for_packaging_pdf(){

        global $post;
		$order_id = $post->ID;
        ?>
        <ul class="aspl_pips_pdf_meta_main">
        	<li>
				<a href="?page=aspl_pips_pdf_page&order_id=<?php echo esc_attr($order_id); ?>" class='button aspl_pdf_a_button'>PDF for Invoice</a><br>
        	</li>
	    </ul>
        <?php
    }

}

/* Create a Page on user side */
add_action( 'init', 'aspl_pips_create_pdf_page' );
function aspl_pips_create_pdf_page(){

    $pages = get_pages(); 
    $contact_page= array(   'slug' => 'aspl_pips_invoice',  'title' =>'InvoicePDF');

    $check_page_exist = get_page_by_title('InvoicePDF', 'OBJECT', 'page');
       
    if(empty($check_page_exist)){

        $page_id = wp_insert_post(array(
            'post_title' => $contact_page['title'],
            'post_type' =>'page',   
            'post_name' => $contact_page['slug'],
            'post_status' => 'publish',
            'post_excerpt' => ' ',  
          ));
        
    }

}

add_filter('page_template', 'aspl_invoice_pdf_template');
function aspl_invoice_pdf_template($template) {
    
    global $post;
    $post_slug = $post->post_name;
    if( is_page('aspl_pips_invoice') ){
        $template = dirname( __FILE__ )  . '/include/aspl-pips-invoice-pdf-template.php';
    }

    return $template;
}

/*Email attachments hook*/
add_filter( 'woocommerce_email_attachments', 'aspl_attach_invoive_pdf_to_email', 10, 3);
function aspl_attach_invoive_pdf_to_email( $attachments, $id, $order) {
   
    aspl_pdf_maker_fun($order);
    $upload = wp_upload_dir();
    $upload_dir = $upload['basedir'];
    $upload_dir = $upload_dir . '/aspl_pdf/invoice_'.$order->get_id().'.pdf';

    $attachments[] = $upload_dir;
    return $attachments;

}

/*Pdf Make function */
function aspl_pdf_maker_fun($order){

    ob_end_clean(); 
    ob_start();

    $u_name_f = $order->get_billing_first_name();
    $u_name_l = $order->get_billing_last_name();
    $c_name = $order->get_billing_company();
    $u_city = $order->get_billing_city();
    $u_state = $order->get_billing_state();
    $u_pincode = $order->get_billing_postcode();
    $u_country = $order->get_billing_country();
    $u_email = $order->get_billing_email();
    $u_phone = $order->get_billing_phone();
    $blog_title = get_bloginfo( 'name' );
    $u_bill_1 = $order->get_billing_address_1();
    $u_bill_2 = $order->get_billing_address_2();
    $time = strtotime($order->get_date_created());
    $o_date = date('Y-m-d',$time);
    $total = $order->get_total();
    $file_path_url = plugin_dir_url(__FILE__).'include/invoice.pdf';

    $upload = wp_upload_dir();
    $upload_dir = $upload['basedir'];
    $upload_dir = $upload_dir . '/aspl_pdf/invoice_'.$order->get_id().'.pdf';
    
    require_once('include/fpdf/fpdf.php');

    $pdf = new FPDF();

    $pdf->AddPage();
    $pdf->SetTitle('Invoice PDF');
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(190,10, $blog_title ,0,1,'l',0);
    $pdf->Cell(0,0, '' ,'B',1,'L',0);
    $pdf->Cell(0,2, '' ,'B',1,'L',0);
    $pdf->Cell(190,20, 'Invoice' ,0,1,'C',0);

    $pdf->SetFont('Arial','B',8);
    $pdf->Cell(95,5, '' ,0,0,'L',0);
    $pdf->Cell(95,5, 'Order Id : '.$order->get_id() ,0,1,'L',0);
    $pdf->Cell(95,5, '' ,0,0,'L',0);
    $pdf->Cell(95,5, 'Date : '.$o_date ,0,1,'L',0);
    $pdf->Cell(95,5, '' ,0,0,'L',0);
    $pdf->Cell(95,5, 'Payment Method : '.$order->get_payment_method_title() ,0,1,'L',0);

    $pdf->Cell(200,5, '' ,0,1,'L',0);

    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(95,15, 'Billing Address' ,0,0,'L',0);
    $pdf->Cell(95,15, 'Customer Contact' ,0,1,'L',0);

    $pdf->SetFont('Arial','',10);
    $pdf->Cell(95,5, $u_city ,0,0,'L',0);
    $pdf->Cell(15,5, 'Name' ,0,0,'L',0);
    $pdf->Cell(3,5, ':' ,0,0,'L',0);
    $pdf->Cell(77,5, $u_name_f.' '.$u_name_l ,0,1,'L',0);

    $pdf->Cell(95,5, $u_bill_1 ,0,0,'L',0);
    $pdf->Cell(15,5, 'E-mail' ,0,0,'L',0);
    $pdf->Cell(3,5, ':' ,0,0,'L',0);
    $pdf->Cell(77,5, $u_email ,0,1,'L',0);

    $pdf->Cell(95,5, $u_bill_2 ,0,0,'L',0);
    $pdf->Cell(15,5, 'Phone' ,0,0,'L',0);
    $pdf->Cell(3,5, ':' ,0,0,'L',0);
    $pdf->Cell(77,5, $u_phone ,0,1,'L',0);

    $pdf->Cell(95,5, $u_pincode ,0,0,'L',0);
    $pdf->Cell(15,5, 'Compny' ,0,0,'L',0);
    $pdf->Cell(3,5, ':' ,0,0,'L',0);
    $pdf->Cell(77,5, $c_name ,0,1,'L',0);

    $pdf->Cell(95,5, $u_state ,0,1,'L',0);
    $pdf->Cell(95,5, $u_country ,0,1,'L',0);

    $pdf->Cell(200,5, '' ,0,1,'L',0);
    $pdf->Cell(0,2, '' ,'B',1,'L',0);
    $pdf->Cell(200,5, '' ,0,1,'L',0);

    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(130,10, 'Product' ,1,0,'C',0);
    $pdf->Cell(30,10, ' Quantity ' ,1,0,'C',0);
    $pdf->Cell(30,10, ' Price ' ,1,1,'C',0);

    $items = $order->get_items();
    foreach ($items as $key => $value) {

        $product_id = $value['product_id'];
        $product_name = $value['name'];
        $product_qty = $value['quantity'];
        $product_sub_total = $value['subtotal'];

        $product = wc_get_product( $product_id );
        $product_price = $product->get_price()*$product_qty;
        $product_sku = $product->get_sku();

        $pdf->SetFont('Arial','',10);
        $pdf->Cell(190,2, '' ,0,1,'L',0);
        $pdf->Cell(130,10, $product_name ,0,0,'L',0);
        $pdf->Cell(30,10, $product_qty ,0,0,'R',0);
        $pdf->Cell(30,10, number_format($product_price,2) ,0,1,'R',0);
        $pdf->Cell(130,5, 'SKU : '.$product_sku ,0,1,'L',0);
        $pdf->Cell(190,5, '' ,0,1,'L',0);
        $pdf->Cell(190,0, '' ,1,1,'L',0);

    }
    $pdf->Cell(130,10, '' ,0,0,'L',0);
    $pdf->Cell(30,10, 'Total' ,0,0,'L',0);
    $pdf->Cell(30,10, $total ,0,1,'L',0);
    $pdf->Output('F', $upload_dir, true);
}

/* Add order columns */
add_filter( 'woocommerce_account_orders_columns', 'aspl_pdf_invoice_add_custom_account_orders_column', 10, 1 );
function aspl_pdf_invoice_add_custom_account_orders_column( $columns ) {

    $ordered_columns = array();

    // Inserting a new column in a specific location
    $ordered_columns['order-number'] = $columns['order-number'];
    $ordered_columns['order-date'] = $columns['order-date'];
    $ordered_columns['order-status'] = $columns['order-status'];
    $ordered_columns['order-total'] = $columns['order-total'];
    $ordered_columns['order-actions'] = $columns['order-actions'];
    $ordered_columns['order-pdf'] =  __( 'Invoice PDF', 'woocommerce' ); // <== New column

    return $ordered_columns;

}

/* Adds data to the custom "Invoice PFD" column in "My Account > Orders". */
function aspl_pdf_invoice_order_column_pdf_view_btn( $order ) {
    $order_id = $order->get_ID();
    $nonce = wp_create_nonce( 'aspl_pips_email_nonce' );
    ?>
        <a href="<?php echo esc_url(get_home_url().'/aspl_pips_invoice?order_id='.$order_id.'&_wpnonce='.$nonce); ?>" class="button button-primary">View Invoice</a>
    <?php

}
add_action( 'woocommerce_my_account_my_orders_column_order-pdf', 'aspl_pdf_invoice_order_column_pdf_view_btn' );
