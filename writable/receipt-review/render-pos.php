<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
helper(['url','form']);
session()->set(['role'=>'cashier','branch_id'=>null,'full_name'=>'Maria Santos']);
$names=['Ascorbic Acid + Zinc 500mg / 10mg Film-coated Tablet','Paracetamol 500mg Tablet','Cetirizine 10mg Tablet','Isopropyl Alcohol 70% 500mL','Oral Rehydration Salts Sachet','Amoxicillin 500mg Capsule','Multivitamins + Iron Syrup 120mL','Hydrocortisone Cream 1%'];
$products=[];
foreach($names as $i=>$name) $products[]=['id'=>$i+1,'product_id'=>$i+1,'product_name'=>$name,'sku'=>'PHX-00'.($i+1),'category_id'=>$i%3+1,'category_name'=>['Vitamins','Medicines','First aid'][$i%3],'price'=>25.5+$i*10,'cost_price'=>10,'stock'=>$i===7?0:20,'quantity'=>2,'subtotal'=>2*(25.5+$i*10),'expiration_date'=>$i===6?'2020-01-01':'2028-01-01','status'=>'active','branch_id'=>1,'unit'=>'box'];
$discount=['id'=>1,'discount_name'=>'Store discount','discount_type'=>'percentage','discount_value'=>10,'applies_to'=>'all','minimum_purchase'=>0,'max_discount_amount'=>null,'category_id'=>null,'product_id'=>null];
foreach(['full','empty'] as $state){
 $html=view('cashier/sales/index',['products'=>$products,'cart'=>$state==='full'?array_slice($products,0,3):[],'discounts'=>[$discount],'heldSales'=>[['label'=>'Customer order','cart'=>[$products[0]],'held_at'=>'2026-09-22 10:30:00']],'branches'=>[],'categories'=>[],'branchId'=>'','categoryId'=>''],['saveData'=>false]);
 file_put_contents(__DIR__.'/pos-'.$state.'.html',preg_replace('~https?://[^"\s]+/assets/~','/assets/',$html));
}
echo "Rendered POS filled/empty cart fixtures.\n";
$html = view('cashier/sales/branch_availability', [
 'keyword'=>'Paracetamol','total'=>2,'page'=>1,'availability'=>[
 ['product_name'=>'Paracetamol 500mg Tablet','sku'=>'PARA-500','unit'=>'box','stock'=>25,'branch_name'=>'Santa Maria Main Branch','address'=>'42 Long Pharmacy Road, Santa Maria, Bulacan','contact_number'=>'09123456789'],
 ['product_name'=>'Paracetamol 500mg Capsule','sku'=>'PARA-C500','unit'=>'bottle','stock'=>8,'branch_name'=>'Patag Branch','address'=>'Patag, Santa Maria, Bulacan','contact_number'=>'09123456780'],
 ]], ['saveData'=>false]);
file_put_contents(__DIR__.'/branch-availability.html', preg_replace('~https?://[^"\s]+/assets/~','/assets/',$html));
