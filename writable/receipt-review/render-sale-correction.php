<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
helper(['url','form']);
session()->set(['role'=>'admin','branch_id'=>null,'full_name'=>'Preview Administrator']);
$db = new \CodeIgniter\Database\SQLite3\Connection(['database'=>':memory:','DBDriver'=>'SQLite3','DBPrefix'=>'','DBDebug'=>true]);
$db->initialize();
(new ReflectionProperty(\CodeIgniter\Database\Config::class,'instances'))->setValue(null,['tests'=>$db]);
$db->query('CREATE TABLE discounts (id INTEGER PRIMARY KEY, discount_type TEXT, discount_value NUMERIC, applies_to TEXT, minimum_purchase NUMERIC, max_discount_amount NUMERIC, product_id INTEGER, category_id INTEGER)');
$db->table('discounts')->insert(['id'=>1,'discount_type'=>'percentage','discount_value'=>20,'applies_to'=>'all','minimum_purchase'=>200]);
$db->table('discounts')->insert(['id'=>2,'discount_type'=>'fixed','discount_value'=>44,'applies_to'=>'all','minimum_purchase'=>200]);
$sale = ['id'=>1,'invoice_no'=>'INV-CORRECTION-PREVIEW','user_id'=>1,'branch_id'=>1,'discount_id'=>1,'full_name'=>'Main Cashier','branch_name'=>'Main Branch','sale_date'=>'2026-10-04 09:59:00','total_amount'=>220,'discount_amount'=>44,'final_total'=>176,'amount_paid'=>200,'change_amount'=>24,'payment_method'=>'cash','reference_no'=>'','status'=>'completed','notes'=>''];
$items = [['id'=>1,'product_id'=>1,'product_name_snapshot'=>'Zinc 20mg Tablet','quantity'=>1,'price'=>100,'cost_price_at_sale'=>40,'subtotal'=>100,'discount_applied'=>20,'profit'=>40],['id'=>2,'product_id'=>2,'product_name_snapshot'=>'Tempra Syrup','quantity'=>1,'price'=>120,'cost_price_at_sale'=>50,'subtotal'=>120,'discount_applied'=>24,'profit'=>46]];
$fixtures = ['percentage'=>[$sale,$items], 'fixed'=>[array_replace($sale,['discount_id'=>2]),$items], 'missing'=>[array_replace($sale,['discount_id'=>99]),$items], 'plain'=>[array_replace($sale,['discount_id'=>null,'discount_amount'=>0,'final_total'=>220,'amount_paid'=>244]),array_map(static fn($item)=>array_replace($item,['discount_applied'=>0,'profit'=>$item['subtotal']-$item['cost_price_at_sale']]),$items)]];
foreach ($fixtures as $name=>[$record,$lines]) {
    $context = (new \App\Libraries\SaleCorrectionPricing($db))->context($record,$lines);
    $html = view('admin/sale_correction/edit',['sale'=>$record,'items'=>$lines,'discountContext'=>$context,'formInput'=>[],'correctionHistory'=>[],'validation'=>service('validation')],['saveData'=>false]);
    file_put_contents(__DIR__.'/sale-correction-'.$name.'.html',preg_replace('~https?://[^"\s]+/assets/~','/assets/',$html));
}
echo "Rendered four sale correction fixtures with an in-memory database.\n";
