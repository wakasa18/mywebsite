<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
helper(['url', 'form']);
session()->set(['role' => 'admin', 'branch_id' => null, 'full_name' => 'Maria Santos']);
$product = ['id'=>1,'product_id'=>1,'product_name'=>'Ascorbic Acid + Zinc 500mg / 10mg Film-coated Tablet','sku'=>'VITAMIN-C-ZINC-LONG-SKU-001','category_id'=>1,'category_name'=>'Vitamins','price'=>20,'cost_price'=>10,'stock'=>3,'quantity'=>2,'subtotal'=>40,'expiration_date'=>'2028-01-01','status'=>'active','branch_id'=>1,'branch_name'=>'Santa Maria Main Branch','unit'=>'bottle','reorder_level'=>20,'forecast_has_demand'=>true,'reorder_forecast'=>30,'days_left'=>2,'suggested_qty'=>30,'urgency'=>'critical'];
$products=[];
for ($i=0;$i<24;$i++) $products[]=array_replace($product,['sku'=>$product['sku'].'-'.$i,'stock'=>$i<4?0:3,'forecast_has_demand'=>$i%2===0]);
$notificationData=['low_stock_products'=>$products,'low_stock_total'=>24,'scope_label'=>'All branches','low_stock_url'=>'/admin/products'];
$lowStock=view('layouts/low_stock_modal',compact('notificationData'),['saveData'=>false]);
$held=['label'=>'Customer order — vitamins and maintenance medicines','cart'=>[$product],'held_at'=>'2026-09-22 10:30:00'];
$pos=view('cashier/sales/index',['products'=>[$product],'cart'=>[],'discounts'=>[],'heldSales'=>array_fill(0,8,$held),'branches'=>[],'categories'=>[],'branchId'=>'','categoryId'=>''],['saveData'=>false]);
$reports=view('admin/reports/index',['topProducts'=>[],'recentSales'=>[],'reportHistory'=>[],'forecastHistory'=>[],'futureForecast'=>[],'reportDateFrom'=>'2026-09-01','reportDateTo'=>'2026-09-22','forecastDateFrom'=>'2026-08-01','forecastDateTo'=>'2026-09-22','branches'=>[],'branchId'=>'','reorderForecast'=>array_fill(0,12,$product),'forecastingDataLog'=>[['id'=>123,'product_name'=>$product['product_name'],'sku'=>$product['sku'],'forecast_month'=>'2026-10-01','predicted_quantity'=>30,'predicted_revenue'=>3000,'generated_at'=>'2026-09-22 10:30:00']]],['saveData'=>false]);
foreach (['held'=>$pos,'forecast'=>$reports] as $name=>$html) {
    // Replace any DB-derived notification modal with deterministic synthetic records.
    $start=strpos($html,'<div class="inventory-modal" id="lowStockModal"');
    if ($start!==false) {
        $end=strpos($html,'<script>',$start);
        $html=substr($html,0,$start).substr($html,$end);
    }
    $html=str_replace('<main class="content" id="pageContent" tabindex="-1">','<main class="content" id="pageContent" tabindex="-1"><button id="reviewLowStockTrigger" data-open-low-stock>Low-stock products</button>',$html);
    $html=str_replace('id="notificationMenu" role="menu" aria-label="Inventory notifications">','id="notificationMenu" role="menu" aria-label="Inventory notifications"><button id="reviewNotificationStock" role="menuitem" data-open-low-stock>Low stock</button>',$html);
    $html=preg_replace('~(<script>\s*\(function\(\)\{)~',$lowStock.'$1',$html,1);
    $html=preg_replace('~https?://[^"\s]+/assets/~','/assets/',$html);
    file_put_contents(__DIR__.'/modals-'.$name.'.html',$html);
}
echo "Rendered real POS and reports views with synthetic modal records.\n";
