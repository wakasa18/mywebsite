<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
helper(['url','form']);
session()->set(['role'=>'cashier','branch_id'=>null,'full_name'=>'Review Staff']);
$product = ['id'=>1,'product_id'=>1,'product_name'=>'Test medicine','sku'=>'TEST','category_id'=>1,'category_name'=>'Review category','price'=>1.01,'cost_price'=>0.25,'stock'=>50,'quantity'=>1,'subtotal'=>1.01,'expiration_date'=>'2028-01-01','status'=>'active','branch_id'=>1,'unit'=>'Tablet'];
$discount = ['id'=>1,'discount_name'=>'Half off','discount_type'=>'percentage','discount_value'=>50,'applies_to'=>'all','minimum_purchase'=>0,'max_discount_amount'=>null,'category_id'=>null,'product_id'=>null];
$html = view('cashier/sales/index',['products'=>[$product],'cart'=>[$product],'discounts'=>[$discount,array_replace($discount,['id'=>2,'discount_name'=>'Minimum purchase','discount_value'=>10,'minimum_purchase'=>59.70])],'heldSales'=>[],'branches'=>[],'categories'=>[],'branchId'=>'','categoryId'=>''],['saveData'=>false]);
file_put_contents(__DIR__.'/discount-pos.html',preg_replace('~https?://[^"\s]+/assets/~','/assets/',$html));
$item = ['id'=>1,'product_id'=>1,'product_name_snapshot'=>'Test medicine','price'=>10,'cost_price_at_sale'=>4,'quantity'=>5,'subtotal'=>50,'discount_applied'=>20];
$sale = ['id'=>1,'invoice_no'=>'TEST-1','full_name'=>'Review Staff','branch_name'=>'Review Branch','sale_date'=>'2026-09-20 10:00:00','status'=>'completed','payment_method'=>'cash','reference_no'=>null,'amount_paid'=>100,'total_amount'=>50,'discount_amount'=>20,'final_total'=>30,'notes'=>''];
foreach (['fixed'=>20,'capped'=>10,'legacy'=>20,'plain'=>0] as $name=>$discountAmount) {
    $sale['discount_amount'] = $discountAmount;
    $sale['final_total'] = 50 - $discountAmount;
    $item['discount_applied'] = $discountAmount;
    $html=view('admin/sale_correction/edit',['sale'=>$sale,'items'=>[$item],'correctionHistory'=>[],'validation'=>service('validation')],['saveData'=>false]);
    file_put_contents(__DIR__.'/discount-'.$name.'.html',preg_replace('~https?://[^"\s]+/assets/~','/assets/',$html));
}
echo "Rendered discount checkout and correction fixtures.\n";
