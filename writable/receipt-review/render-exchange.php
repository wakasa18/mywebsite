<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
helper(['url', 'form']);
session()->set(['role'=>'admin','branch_id'=>null,'full_name'=>'Preview Staff']);
$db = new \CodeIgniter\Database\SQLite3\Connection(['database'=>':memory:', 'DBDriver'=>'SQLite3','DBPrefix'=>'','DBDebug'=>true]);
$db->initialize();
(new ReflectionProperty(\CodeIgniter\Database\Config::class, 'instances'))->setValue(null, ['tests'=>$db]);
$db->query('CREATE TABLE sales (id INTEGER PRIMARY KEY, invoice_no TEXT, branch_id INTEGER)');
$db->query('CREATE TABLE refund_items (sale_id INTEGER, refund_event_id TEXT, refund_subtotal NUMERIC)');
$db->table('sales')->insert(['id'=>1,'invoice_no'=>'INV-20261001-ORIGINAL','branch_id'=>1]);
$db->table('refund_items')->insert(['sale_id'=>1,'refund_event_id'=>str_repeat('a',32),'refund_subtotal'=>225]);
$sale = ['id'=>1,'invoice_no'=>'INV-20261001-ORIGINAL','sale_date'=>'2026-10-01 10:30:00', 'branch_id'=>1,'branch_name'=>'Main Branch','branch_address'=>'Santa Maria, Bulacan','branch_contact'=>'09123456789','full_name'=>'Preview Staff','payment_method'=>'cash','reference_no'=>'','total_amount'=>250,'discount_amount'=>25,'final_total'=>225,'amount_paid'=>225,'change_amount'=>0,'status'=>'completed','notes'=>''];
$items = [['id'=>1,'product_name_snapshot'=>'Carbocisteine 500mg Capsule','quantity'=>2,'price'=>125,'subtotal'=>250,'discount_applied'=>25,'remaining_qty'=>2,'remaining_cents'=>22500]];
$products = [];
foreach (['Ascorbic Acid + Zinc 500mg / 10mg Film-coated Tablet','Paracetamol 500mg Tablet','Vitamin B Complex Tablet','Calcium Supplement Capsule','Disposable Face Mask 50s','Ambroxol 30mg Tablet'] as $i=>$name) $products[]=['product_id'=>$i+2,'product_name'=>$name,'sku'=>'SKU-00'.($i+2),'stock'=>50,'price'=>140,'unit'=>'box'];
$quote = ['sale'=>$sale,'returns'=>[1=>['item'=>$items[0],'quantity'=>2,'cents'=>22500,'condition'=>'resellable','restock'=>2]],'replacements'=>[2=>['name'=>$products[0]['product_name'],'quantity'=>2,'subtotal'=>28000,'discount'=>0]],'credit'=>22500,'total'=>28000,'difference'=>5500,'discount'=>0,'reason'=>'Customer requested replacement','reference'=>'','method'=>'cash'];
$token = str_repeat('b',48);
$fixtures = [
 'form'=>['cashier/sales/exchange',['sale'=>$sale,'items'=>$items,'products'=>$products,'discounts'=>[],'token'=>$token,'input'=>[]]],
 'review'=>['cashier/sales/exchange',['sale'=>$sale,'quote'=>$quote,'token'=>$token]],
 'even'=>['cashier/sales/exchange',['sale'=>$sale,'quote'=>array_replace($quote,['total'=>22500,'difference'=>0]),'token'=>$token]],
 'payout'=>['cashier/sales/exchange',['sale'=>$sale,'quote'=>array_replace($quote,['total'=>14000,'difference'=>-8500]),'token'=>$token]],
 'receipt'=>['cashier/sales/receipt',['sale'=>array_replace($sale,['id'=>2,'invoice_no'=>'EXC-1-'.str_repeat('a',32),'total_amount'=>280,'discount_amount'=>0,'final_total'=>280,'amount_paid'=>280]),'items'=>[['id'=>2,'product_name_snapshot'=>$products[0]['product_name'],'quantity'=>2,'price'=>140,'subtotal'=>280,'discount_applied'=>0]]]],
];
foreach ($fixtures as $name=>[$view,$data]) {
    $html = view($view,$data,['saveData'=>false]);
    file_put_contents(__DIR__.'/exchange-'.$name.'.html',preg_replace('~https?://[^"\s]+/assets/~','/assets/',$html));
}
echo "Rendered five exchange fixtures with an in-memory database.\n";
