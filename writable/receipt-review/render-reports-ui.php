<?php
require dirname(__DIR__,2).'/system/Test/bootstrap.php';
helper(['url','form']);
session()->set(['role'=>'admin','branch_id'=>null,'full_name'=>'Maria Santos']);
$product=['product_name'=>'Ascorbic Acid + Zinc 500mg / 10mg Film-coated Tablet','product_name_snapshot'=>'Ascorbic Acid + Zinc 500mg / 10mg Film-coated Tablet','sku'=>'VIT-C-ZINC-001','branch_name'=>'Santa Maria Main Branch','stock'=>3,'reorder_level'=>20,'days_left'=>2,'suggested_qty'=>45,'urgency'=>'critical','total_qty_sold'=>125,'total_sales'=>6250];
$period=['report_date'=>'2026-09-01','total_transactions'=>48,'gross_sales'=>124500,'net_sales'=>119000,'total_discount'=>5500,'cogs'=>80000,'gross_profit'=>39000,'forecast'=>115000];
$data=['branchName'=>'All branches','branches'=>[['id'=>1,'branch_name'=>'Santa Maria Main Branch']],'branchId'=>'','reportType'=>'daily','forecastType'=>'daily','reportDateFrom'=>'2026-09-01','reportDateTo'=>'2026-09-23','forecastDateFrom'=>'2026-08-01','forecastDateTo'=>'2026-09-23','salesSummary'=>$period,'profitSummary'=>$period,'grossProfitMargin'=>32.77,'averageTransactionValue'=>2479.17,'nextForecastValue'=>8500,'reportHistory'=>[$period,array_replace($period,['report_date'=>'2026-09-02','net_sales'=>88000])],'forecastHistory'=>[$period],'futureForecast'=>[['forecast_date'=>'2026-09-24','forecast_value'=>8500]],'topProducts'=>[$product,array_replace($product,['product_name_snapshot'=>'Paracetamol 500mg Tablet','total_qty_sold'=>98])],'recentSales'=>[['invoice_no'=>'INV-20260923-105600-123ABC','status'=>'completed','remaining_total'=>455.75,'final_total'=>455.75,'sale_date'=>'2026-09-23 10:56:00']],'reorderForecast'=>[$product],'cashMovements'=>['rows'=>[['date'=>'2026-09-23','method'=>'cash','receipts'=>2000,'refunds'=>150,'net'=>1850]],'totals'=>['receipts'=>2000,'refunds'=>150,'net'=>1850,'cash_net'=>1850,'unknown_refunds'=>50],'returns'=>[['return_condition'=>'resellable','quantity'=>2]]],'exportHistory'=>[['created_at'=>'2026-09-23 10:45:00','full_name'=>'Maria Santos','branch_name'=>'Santa Maria Main Branch','export_type'=>'pdf','date_from'=>'2026-09-01','date_to'=>'2026-09-23','gross_sales'=>124500,'gross_profit'=>39000]]];
foreach(['full','empty','mixed'] as $state){
 $fixture=$data;
 $fixture['forecastingDataLog']=[];
 if($state==='full')$fixture['forecastingDataLog']=[['id'=>1,'product_name'=>'Vitamin C + Zinc','sku'=>'VIT-C','forecast_month'=>'2026-09-01','predicted_quantity'=>125,'predicted_revenue'=>1250,'method_used'=>'Manual Holt b=1 2026-08-01..2026-09-23 daily a=0.3 t=0.2','generated_at'=>'2026-09-27 12:00:00']];
 if($state==='full')for($day=24;$day<=30;$day++)$fixture['forecastingDataLog'][]=['id'=>$day,'product_name'=>null,'sku'=>null,'forecast_month'=>'2026-09-'.$day,'predicted_quantity'=>null,'predicted_revenue'=>8500,'method_used'=>'Sales Holt b=1 2026-08-01..2026-09-23 daily a=0.3 t=0.2','generated_at'=>'2026-09-27 12:00:00'];
 if($state==='mixed')$fixture['topProducts']=[];
 if($state==='empty')foreach(['salesSummary','profitSummary','reportHistory','forecastHistory','futureForecast','topProducts','recentSales','reorderForecast','exportHistory'] as $key)$fixture[$key]=[];
 $html=view('admin/reports/index',$fixture,['saveData'=>false]);
 $html=preg_replace('~https?://[^"\s]+/assets/~','/assets/',$html);
 $html=str_replace('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js','/chart.umd.min.js',$html);
 file_put_contents(__DIR__.'/reports-ui-'.$state.'.html',$html);
}
echo "Rendered populated and empty report fixtures.\n";
$data['reorderForecast'] = [];
$data['forecastingDataLog'] = [];
for ($i = 1; $i <= 24; $i++) {
    $urgency = ['critical', 'warning', 'watch', 'unknown'][($i - 1) % 4];
    $data['reorderForecast'][] = array_replace($product, ['product_name' => $product['product_name'] . ' ' . $i, 'sku' => 'RESTOCK-' . $i, 'urgency' => $urgency, 'days_left' => $urgency === 'unknown' ? null : $i, 'suggested_qty' => $urgency === 'unknown' ? 0 : 45]);
    $data['forecastingDataLog'][] = ['id' => $i, 'product_name' => $product['product_name'] . ' ' . $i, 'sku' => 'RESTOCK-' . $i, 'forecast_month' => '2026-09-01', 'predicted_quantity' => 125, 'predicted_revenue' => 1250, 'method_used' => $i % 2 ? 'Manual Holt b=1 2026-08-01..2026-09-23 daily a=0.3 t=0.2' : 'Holt Linear (branch-aware, a=0.3, b=0.2)', 'generated_at' => '2026-09-27 12:00:00'];
}
$html = view('admin/reports/index', $data, ['saveData' => false]);
$html = preg_replace('~https?://[^"\s]+/assets/~', '/assets/', $html);
$html = str_replace('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', '/chart.umd.min.js', $html);
file_put_contents(__DIR__ . '/reports-ui-restock.html', $html);
