<?php

if ( ! defined( 'ABSPATH' ) ){
    exit;
}

ob_end_clean(); 
ob_start();
if (isset($_GET['order_id'])) {

$order_id = sanitize_text_field($_GET['order_id']);
$order = new WC_Order( $order_id );

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

require('fpdf/fpdf.php'); 
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
$pdf->Cell(95,5, 'Order Id : '.$order_id ,0,1,'L',0);
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
// $pdf->Write(6,iconv('UTF-8', 'ASCII//TRANSLIT', get_woocommerce_currency_symbol()));
$pdf->Cell(130,10, '' ,0,0,'L',0);
$pdf->Cell(30,10, 'Total' ,0,0,'L',0);
$pdf->Cell(30,10, $total ,0,1,'L',0);
$pdf->Output('','invoice.pdf');

}else{
	wp_redirect( admin_url( '/edit.php?post_type=shop_order' ) );
}